#!/usr/bin/env bash
# Helpers shared by install.sh and varnish.sh, which run inside the php container.
set -euo pipefail

LOG=/tmp/magento-test.log
touch "$LOG"
cd /magento

step() {
  printf '  - %s ... ' "$1"
  STEP_STARTED_AT=$(date +%s)
}

passed() {
  echo "ok ($(($(date +%s) - STEP_STARTED_AT))s)"
}

failed() {
  echo "FAILED"
  printf '%s\n' "$@" | sed 's/^/      /'
  exit 1
}

run() {
  local output=/tmp/magento-test-command.log
  "$@" >"$output" 2>&1 || failed "\$ $*" "$(sed 's/\x1b\[[0-9;]*[A-Za-z]//g' "$output" | tail -n 40)"
  cat "$output" >>"$LOG"
}

query() {
  php -r '$db = new PDO("mysql:host=db;dbname=magento", "root", "magento"); foreach ($db->query($argv[1]) as $row) { echo $row[0], "\n"; }' "$1"
}
