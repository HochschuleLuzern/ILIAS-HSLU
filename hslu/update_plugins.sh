#!/bin/bash

set -euo pipefail

default_branch=ilias9
git_org=${HSLU_GIT_ORG:-https://github.com/HochschuleLuzern}
curdir="$PWD"

# Sanity check: are we in an ILIAS root directory?
if [[ ! -d Services ]]; then
    echo 'Error: This script must be run from the root directory of an ILIAS installation.'
    exit 1
fi

clone_repo() {
    destination="$1"
    folder="Customizing/global/plugins/$destination"
    repo_name="${2:-$(basename "$destination")}"
    repo="$git_org/$repo_name.git"
    branch="${3:-$default_branch}"
    echo "Getting $repo (branch: $branch) into $folder"

    if [[ ! -d "$folder/.git" ]]; then
        echo "Cloning..."
        mkdir -p "$folder"
        cd "$folder"
        git clone -b "$branch" "$repo" .
    else
        echo "Already exists, updating..."
        cd "$folder"
        git reset --hard
        git checkout "$branch"
        git pull
    fi

    cd "$curdir"
}

clone_repo Services/EventHandling/EventHook/HSLUObjectDefaults
clone_repo Services/UIComponent/UserInterfaceHook/HSLUUIDefaults
clone_repo Services/Cron/CronHook/NotifyOnCronFailure
clone_repo Services/Cron/CronHook/EventoImport
clone_repo Services/Cron/CronHook/ResetLoginAttempts
clone_repo Services/COPage/PageComponent/EmbedMiro
clone_repo Services/Repository/RepositoryObject/CourseWizard
clone_repo Modules/Test/Export/ExportWithEventoID
clone_repo Services/Repository/RepositoryObject/EtherpadLite        ILIAS-Etherpad-Lite-Plugin
clone_repo Services/Repository/RepositoryObject/LiveVoting
clone_repo Services/Repository/RepositoryObject/InteractiveVideo
clone_repo Modules/TestQuestionPool/Questions/assStackQuestion
clone_repo Services/Cron/CronHook/SrLifeCycleManager
