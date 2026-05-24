#!/bin/bash

set -euo pipefail

flavor=${1:-prod}

./hslu/update_plugins.sh $flavor
npm clean-install --omit=dev --ignore-scripts --no-audit --no-fund
(cd components/ILIAS/Chatroom/chat && npm clean-install --omit=dev --ignore-scripts --no-audit --no-fund)
./hslu/skin/make.sh
./hslu/get_mathjax.sh
composer install --no-dev
