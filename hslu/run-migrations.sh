#!/bin/bash
set -euo pipefail

STEP_BUCKETS=(10 30 100 300 1000 3000 10000 30000)
LOG_DIR="$HOME/ilias-migrations"
RUNS_CSV="$LOG_DIR/runs.csv"
MIGRATIONS_CSV="$LOG_DIR/migrations.csv"

usage() {
    echo "Usage: $0 [--no-pause] [--blacklist 'migration1,migration2,...'] ['migration3' 'migration4' ...]"
    echo ""
    echo "Options:"
    echo "  --no-pause    Do not pause at the end of the script"
    echo "  --blacklist   Comma-separated list of migrations to skip entirely"
    echo "  (positional)  If given, only run these migrations (ignoring all others)"
}

# --- argument parsing ---
BLACKLIST=""
ONLY=()
PAUSE_AT_END=true

while [[ $# -gt 0 ]]; do
    case "$1" in
        --help)
            usage
            exit 0
            ;;
        --no-pause)
            PAUSE_AT_END=false
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

list_migrations() {
    php cli/setup.php migrate 2>&1 || true
}

parse_pending_migrations() {
    list_migrations \
        | grep '\[remaining steps:' \
        | sed 's/^[[:space:]]*//' \
        | cut -d: -f1 \
        || true
}

get_remaining_steps() {
    local migration="$1"
    list_migrations \
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
    mapfile -t all_pending < <(parse_pending_migrations)

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
    mig_start=$(date -Is)
    mig_start_s=$(date +%s)

    echo ""
    echo "===== Migration: $migration ====="

    while true; do
        steps="${STEP_BUCKETS[$bucket_idx]}"
        run_start=$(date -Is)
        run_start_s=$(date +%s)

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
            mig_duration=$(( run_end_s - mig_start_s ))
            printf '%s,failed,%s,%s,%s\n' \
                "$migration" "$mig_start" "$run_end" "$mig_duration" \
                >> "$MIGRATIONS_CSV"
            session_failed["$migration"]=1
            echo "[$(date -Is)] FAILED (exit $exit_code) -- skipping to next migration"
            break
        fi

        remaining=$(get_remaining_steps "$migration")
        if [[ -z "$remaining" ]] || [[ "$remaining" -eq 0 ]]; then
            mig_duration=$(( run_end_s - mig_start_s ))
            printf '%s,done,%s,%s,%s\n' \
                "$migration" "$mig_start" "$run_end" "$mig_duration" \
                >> "$MIGRATIONS_CSV"
            echo "[$(date -Is)] DONE $migration in ${mig_duration}s"
            break
        fi

        echo "[$(date -Is)] $remaining steps remaining"

        if [[ $run_duration -lt 300 ]] && [[ $bucket_idx -lt $(( ${#STEP_BUCKETS[@]} - 1 )) ]]; then
            bucket_idx=$(( bucket_idx + 1 ))
            echo "[$(date -Is)] Stepping up to ${STEP_BUCKETS[$bucket_idx]} steps/chunk"
        fi
    done
done

if $PAUSE_AT_END; then
    echo
    read -n 1 -s -r -p "Press any key to exit..."
    echo
fi
