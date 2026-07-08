#!/bin/bash
set -euo pipefail

STEP_BUCKETS=(10 30 100 300 1000 3000 10000 30000)
MAX_ATTEMPTS=3   # attempts per setup.php invocation (listing and --run)
RETRY_DELAY=$((2 + RANDOM % 5)).$((RANDOM % 10))  # seconds between retries; randomized per invocation to desynchronize concurrent runs
LOG_DIR="$HOME/ilias-migrations"
RUNS_CSV="$LOG_DIR/runs.csv"
MIGRATIONS_CSV="$LOG_DIR/migrations.csv"

usage() {
    echo "Usage: $0 [--no-pause] [--blacklist 'migration1,migration2,...'] ['migration3' 'migration4' ...]"
    echo ""
    echo "Options:"
    echo "  --pause       Pause at the end of the script (default, except in tmux)"
    echo "  --no-pause    Do not pause at the end of the script (default in tmux)"
    echo "  --blacklist   Comma-separated list of migrations to skip entirely"
    echo "  (positional)  If given, only run these migrations (ignoring all others)"
}

# --- argument parsing ---
BLACKLIST=""
ONLY=()
PAUSE_AT_END=yes
[[ -n "${TMUX:-}" ]] && PAUSE_AT_END=no

while [[ $# -gt 0 ]]; do
    case "$1" in
        --help)
            usage
            exit 0
            ;;
        --pause)
            PAUSE_AT_END=yes
            shift
            ;;
        --no-pause)
            PAUSE_AT_END=no
            shift
            ;;
        --blacklist)
            BLACKLIST="${2:?--blacklist requires an argument}"
            shift 2
            ;;
        -*)
            echo "Unknown option: $1" >&2
            usage
            exit 1
            ;;
        *)
            ONLY+=("$1")
            shift
            ;;
    esac
done

if [[ ! -f cli/setup.php ]]; then
    echo "Error: cli/setup.php not found. Run this script from the ILIAS root directory." >&2
    exit 1
fi

if [[ ! -f "$RUNS_CSV" ]]; then
    mkdir -p "$LOG_DIR"
    printf 'migration,steps,start,finish,duration\n' > "$RUNS_CSV"
    echo "Initialized $RUNS_CSV"
fi
if [[ ! -f "$MIGRATIONS_CSV" ]]; then
    printf 'migration,status,start,finish,duration\n' > "$MIGRATIONS_CSV"
    echo "Initialized $MIGRATIONS_CSV"
fi

# Parse blacklist into array
BLACKLIST_ARR=()
if [[ -n "$BLACKLIST" ]]; then
    IFS=',' read -ra BLACKLIST_ARR <<< "$BLACKLIST"
fi

# --- helpers ---

# Prints the migration listing on stdout. Retries on failure; returns
# non-zero if all attempts fail, so callers never mistake a crashed
# listing for "no pending migrations".
list_migrations() {
    local attempt out
    for (( attempt = 1; attempt <= MAX_ATTEMPTS; attempt++ )); do
        if out=$(php cli/setup.php migrate 2>&1); then
            printf '%s' "$out"
            return 0
        fi
        echo "[$(date -Is)] Listing migrations failed (attempt $attempt/$MAX_ATTEMPTS)" >&2
        if (( attempt < MAX_ATTEMPTS )); then
            sleep "$RETRY_DELAY"
        fi
    done
    printf '%s\n' "$out" >&2
    return 1
}

parse_pending_migrations() {
    local out
    out=$(list_migrations) || return 1
    printf '%s\n' "$out" \
        | grep '\[remaining steps:' \
        | sed 's/^[[:space:]]*//' \
        | cut -d: -f1 \
        || true
}

get_remaining_steps() {
    local migration="$1" out
    out=$(list_migrations) || return 1
    printf '%s\n' "$out" \
        | grep -F " ${migration}:" \
        | grep -oP '\[remaining steps: \K[0-9]+' \
        || true
}

is_blacklisted() {
    local migration="$1"
    local b
    for b in "${BLACKLIST_ARR[@]+"${BLACKLIST_ARR[@]}"}"; do
        [[ "$migration" == "$b" ]] && return 0
    done
    return 1
}

is_selected() {
    local migration="$1"
    [[ ${#ONLY[@]} -eq 0 ]] && return 0
    local o
    for o in "${ONLY[@]}"; do
        [[ "$migration" == "$o" ]] && return 0
    done
    return 1
}

# --- main loop ---
declare -A session_failed

while true; do
    if ! pending_output=$(parse_pending_migrations); then
        echo "[$(date -Is)] ERROR: cannot list migrations after $MAX_ATTEMPTS attempts, aborting." >&2
        exit 1
    fi
    mapfile -t all_pending < <(printf '%s' "$pending_output")

    pending=()
    for m in "${all_pending[@]+"${all_pending[@]}"}"; do
        is_blacklisted "$m" && continue
        [[ -v session_failed["$m"] ]] && continue
        is_selected "$m" || continue
        pending+=("$m")
    done

    if [[ ${#pending[@]} -eq 0 ]]; then
        if [[ ${#all_pending[@]} -gt 0 ]]; then
            echo "No actionable pending migrations (all blacklisted or failed this session)."
        else
            echo "All migrations done."
        fi
        break
    fi

    migration="${pending[0]}"
    bucket_idx=0
    fail_count=0
    mig_start=$(date -Is)
    mig_start_s=$(date +%s)

    echo ""
    echo "===== Migration: $migration ====="

    while true; do
        steps="${STEP_BUCKETS[$bucket_idx]}"
        run_start=$(date -Is)
        run_start_s=$(date +%s)

        if [[ -e "$LOG_DIR/stop" ]]; then
            echo "Stop requested, exiting."
            exit 0
        fi

        echo "[$(date -Is)] Running $steps steps..."

        exit_code=0
        php cli/setup.php migrate --run "$migration" --steps "$steps" --yes \
            || exit_code=$?

        run_end=$(date -Is)
        run_end_s=$(date +%s)
        run_duration=$(( run_end_s - run_start_s ))

        printf '%s,%s,%s,%s,%s\n' \
            "$migration" "$steps" "$run_start" "$run_end" "$run_duration" \
            >> "$RUNS_CSV"

        echo "[$(date -Is)] Run finished in ${run_duration}s"

        if [[ $exit_code -ne 0 ]]; then
            fail_count=$(( fail_count + 1 ))
            if [[ $fail_count -lt $MAX_ATTEMPTS ]]; then
                bucket_idx=0
                echo "[$(date -Is)] Run failed (exit $exit_code, attempt $fail_count/$MAX_ATTEMPTS), retrying in ${RETRY_DELAY}s with ${STEP_BUCKETS[$bucket_idx]} steps/chunk"
                sleep "$RETRY_DELAY"
                continue
            fi
            mig_duration=$(( run_end_s - mig_start_s ))
            printf '%s,failed,%s,%s,%s\n' \
                "$migration" "$mig_start" "$run_end" "$mig_duration" \
                >> "$MIGRATIONS_CSV"
            session_failed["$migration"]=1
            echo "[$(date -Is)] FAILED (exit $exit_code) -- skipping to next migration"
            break
        fi
        fail_count=0

        if ! remaining=$(get_remaining_steps "$migration"); then
            echo "[$(date -Is)] ERROR: cannot check remaining steps after $MAX_ATTEMPTS attempts, aborting." >&2
            exit 1
        fi
        if [[ -z "$remaining" ]] || [[ "$remaining" -eq 0 ]]; then
            mig_duration=$(( run_end_s - mig_start_s ))
            printf '%s,done,%s,%s,%s\n' \
                "$migration" "$mig_start" "$run_end" "$mig_duration" \
                >> "$MIGRATIONS_CSV"
            echo "[$(date -Is)] DONE $migration in ${mig_duration}s"
            break
        fi

        echo "[$(date -Is)] $remaining steps remaining"

        if [[ $run_duration -lt 120 ]] && [[ $bucket_idx -lt $(( ${#STEP_BUCKETS[@]} - 1 )) ]]; then
            bucket_idx=$(( bucket_idx + 1 ))
            echo "[$(date -Is)] Stepping up to ${STEP_BUCKETS[$bucket_idx]} steps/chunk"
        fi
    done
done

if [[ "$PAUSE_AT_END" == yes ]]; then
    unset TMOUT
    echo
    read -n 1 -s -r -p "Press any key to exit..."
    echo
fi
