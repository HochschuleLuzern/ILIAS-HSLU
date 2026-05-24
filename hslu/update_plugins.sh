#!/bin/bash

set -euo pipefail

flavor=${1:-prod}

echo "Getting plugins for flavor: $flavor"

default_branch=hslu-ilias10
git_org=https://github.com/HochschuleLuzern
curdir="$PWD"

# Sanity check: are we in an ILIAS root directory?
if [[ ! -f ilias_version.php ]]; then
    echo 'Error: This script must be run from the root directory of an ILIAS installation.'
    exit 1
fi

clone_repo() {
    target_flavor="$1"
    if [[ $target_flavor != $flavor && $flavor != all ]]; then
        return
    fi

    destination="$2"
    folder="public/Customizing/global/plugins/$destination"
    repo_name="${3:-$(basename "$destination")}"
    repo="$git_org/$repo_name.git"
    branch="${4:-$default_branch}"
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

clone_repo prod Services/EventHandling/EventHook/HSLUObjectDefaults
clone_repo prod Services/UIComponent/UserInterfaceHook/HSLUUIDefaults
clone_repo prod Services/Cron/CronHook/NotifyOnCronFailure
clone_repo prod Services/Cron/CronHook/EventoImport
clone_repo prod Services/Cron/CronHook/ResetLoginAttempts
clone_repo prod Services/COPage/PageComponent/EmbedMiro
clone_repo prod Services/Repository/RepositoryObject/CourseWizard
clone_repo prod Modules/Test/Export/ExportWithEventoID
clone_repo prod Services/Repository/RepositoryObject/EtherpadLite        ILIAS-Etherpad-Lite-Plugin
clone_repo prod Services/Repository/RepositoryObject/LiveVoting
clone_repo prod Services/Repository/RepositoryObject/InteractiveVideo
clone_repo prod Modules/TestQuestionPool/Questions/assStackQuestion
clone_repo prod Services/Cron/CronHook/SrLifeCycleManager

clone_repo exam Services/Cron/CronHook/EventoImportLite
clone_repo exam Modules/Test/Export/ExportWithEventoID
