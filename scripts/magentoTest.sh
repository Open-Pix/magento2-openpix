#!/usr/bin/env bash
# Tests a Magento 2 package zip the way the Adobe Commerce Marketplace technical review does, before uploading it.
# Usage: pnpm magento-test [package.zip] [--quick] [--clean]
#   without a zip, tests a zip built from the current Pix/ (same command as pack.sh)
#   --quick  package and code checks only, without Docker
#   --clean  remove the containers, volumes and image created by this script
# MAGENTO_VERSION picks the Magento release installed by the Docker stage (default 2.4.8-p5).
set -euo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
DOCKER_DIR="$ROOT/scripts/magentoTest"
MAGENTO_VERSION=${MAGENTO_VERSION:-2.4.8-p5}
ZIP=""
CURRENT_CODE=false
QUICK=false
FAILURES=0
DOCKER_STARTED=false
WORK=$(mktemp -d "${TMPDIR:-/tmp}/magento-test.XXXXXX")

ok() { echo "  ✓ $1"; }
fail() { echo "  ✗ $1"; FAILURES=$((FAILURES + 1)); }
warn() { echo "  ! $1"; }
indent() { awk -v max="${1:-40}" 'NR <= max { print "      " $0 }'; }
abort() { echo "✗ $1" >&2; exit 1; }
section() { printf '\n== %s\n' "$1"; }
compose() { docker compose -f "$DOCKER_DIR/compose.yml" "$@"; }

cleanup() {
  if [ "$DOCKER_STARTED" = true ]; then compose rm -fsv >/dev/null 2>&1 || true; fi
  rm -rf "$WORK"
}
trap cleanup EXIT

clean() {
  compose down -v --rmi local >/dev/null 2>&1 || true
  docker volume ls -q | grep '^magento-test-' | xargs docker volume rm >/dev/null 2>&1 || true
  docker images -q magento-test-php | xargs docker rmi -f >/dev/null 2>&1 || true
  echo "Removed the magento-test containers, volumes and image."
}

phpVersionFor() {
  case "$1" in
    2.4.7*) echo 8.3 ;;
    2.4.8*) echo 8.4 ;;
    2.4.9*) echo 8.5 ;;
    *) abort "unsupported MAGENTO_VERSION $1 (use 2.4.7-pX, 2.4.8-pX or 2.4.9)" ;;
  esac
}

mysqlImageFor() {
  case "$1" in
    2.4.7*) echo mysql:8.0 ;;
    *) echo mysql:8.4 ;;
  esac
}

parseArgs() {
  local arg
  for arg in "$@"; do
    case "$arg" in
      --quick) QUICK=true ;;
      --clean) clean; exit 0 ;;
      -h | --help) sed -n '2,7p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
      -*) abort "unknown option $arg" ;;
      *) ZIP=$arg ;;
    esac
  done
}

resolveZip() {
  if [ -z "$ZIP" ]; then
    CURRENT_CODE=true
    ZIP="$WORK/current.zip"
    (cd "$ROOT" && zip -qr "$ZIP" ./Pix/*)
    echo "Testing the current code (commit $(git -C "$ROOT" rev-parse --short HEAD))"
    return
  fi
  case "$ZIP" in /*) ;; *) ZIP="${INIT_CWD:-$PWD}/$ZIP" ;; esac
  [ -f "$ZIP" ] || abort "zip not found: $ZIP"
  echo "Testing $ZIP"
}

extractZip() {
  local top
  unzip -tq "$ZIP" >/dev/null 2>&1 || abort "not a valid zip: $ZIP"
  mkdir -p "$WORK/zip"
  unzip -q "$ZIP" -d "$WORK/zip"
  if [ -f "$WORK/zip/composer.json" ]; then PKG_DIR="$WORK/zip"; return; fi
  top=$(ls -A "$WORK/zip" | grep -v '^__MACOSX$' || true)
  if [ "$(printf '%s\n' "$top" | grep -c .)" = 1 ] && [ -f "$WORK/zip/$top/composer.json" ]; then
    PKG_DIR="$WORK/zip/$top"
    return
  fi
  abort "composer.json not found at the zip root nor inside a single top-level folder"
}

checkPackage() {
  local composer="$PKG_DIR/composer.json" expected release
  section "Package"
  NAME=$(jq -r '.name // ""' "$composer")
  VERSION=$(jq -r '.version // ""' "$composer")
  expected=$(jq -r .name "$ROOT/Pix/composer.json")
  release=$(jq -r .version "$ROOT/package.json")
  if [ "$NAME" = "$expected" ]; then ok "name $NAME (Marketplace vendor: ${NAME%%/*})"; else fail "name is '$NAME', this repo publishes '$expected'"; fi
  if [ "$(jq -r '.type // ""' "$composer")" = magento2-module ]; then ok "type magento2-module"; else fail "type must be magento2-module"; fi
  if [ "$VERSION" = "$release" ]; then ok "version $VERSION"; else fail "version is '$VERSION', package.json is $release"; fi
  if jq -e '(.autoload.files | index("registration.php")) and (.autoload["psr-4"] | length > 0)' "$composer" >/dev/null 2>&1; then
    ok "autoload psr-4 + registration.php"
  else
    fail "autoload needs psr-4 and files: [\"registration.php\"]"
  fi
  checkModule
}

checkModule() {
  local declared setupVersion
  MODULE_NAME=$(grep -oE "'[A-Za-z0-9]+_[A-Za-z0-9]+'" "$PKG_DIR/registration.php" 2>/dev/null | head -1 | tr -d "'" || true)
  declared=$(sed -n 's/.*<module name="\([^"]*\)".*/\1/p' "$PKG_DIR/etc/module.xml" 2>/dev/null | head -1 || true)
  setupVersion=$(sed -n 's/.*setup_version="\([^"]*\)".*/\1/p' "$PKG_DIR/etc/module.xml" 2>/dev/null | head -1 || true)
  if [ -n "$MODULE_NAME" ] && [ "$MODULE_NAME" = "$declared" ]; then ok "module $MODULE_NAME"; else fail "registration.php ('$MODULE_NAME') and etc/module.xml ('$declared') must register the same module"; fi
  if [ "$setupVersion" = "$VERSION" ]; then ok "etc/module.xml setup_version $setupVersion"; else fail "etc/module.xml setup_version is '$setupVersion', composer.json is '$VERSION'"; fi
}

checkProductionConfig() {
  section "Production config"
  if cmp -s "$PKG_DIR/Helper/OpenPixConfig.php" "$ROOT/config/OpenPixConfigProduction.php"; then
    ok "Helper/OpenPixConfig.php is config/OpenPixConfigProduction.php"
  else
    fail "Helper/OpenPixConfig.php is not the production config (run config:prod before zipping)"
  fi
  if cmp -s "$PKG_DIR/view/frontend/requirejs-config.js" "$ROOT/config/requirejs-config-prod.js"; then
    ok "view/frontend/requirejs-config.js is config/requirejs-config-prod.js"
  else
    fail "view/frontend/requirejs-config.js is not the production config (run config:prod before zipping)"
  fi
  if grep -rq '@woovi/do-not-merge' "$PKG_DIR"; then fail "has the @woovi/do-not-merge marker"; else ok "no @woovi/do-not-merge marker"; fi
}

checkFiles() {
  local junk expected actual changed
  section "Files"
  junk=$(cd "$WORK/zip" && find . \( -name .DS_Store -o -name __MACOSX -o -name .git -o -name node_modules -o -name '*.zip' \) -print | sed 's|^\./||')
  if [ -z "$junk" ]; then ok "no junk (.DS_Store, __MACOSX, .git, node_modules, zips)"; else fail "junk in the zip:"; printf '%s\n' "$junk" | indent 20; fi
  expected=$(git -C "$ROOT" ls-files Pix | sed 's|^Pix/||' | sort)
  actual=$(cd "$PKG_DIR" && find . -type f | sed 's|^\./||' | sort)
  if [ "$expected" = "$actual" ]; then
    ok "$(printf '%s\n' "$actual" | grep -c .) files, the same list as the committed Pix/"
  else
    fail "file list differs from the committed Pix/:"
    { diff <(printf '%s\n' "$expected") <(printf '%s\n' "$actual") || true; } | sed -n 's/^< /missing: /p; s/^> /extra:   /p' | indent 20
  fi
  changed=$(printf '%s\n' "$actual" | while IFS= read -r file; do
    if [ -f "$ROOT/Pix/$file" ] && ! cmp -s "$PKG_DIR/$file" "$ROOT/Pix/$file"; then echo "$file"; fi
  done)
  if [ -z "$changed" ]; then ok "content matches Pix/"; else fail "content differs from Pix/:"; printf '%s\n' "$changed" | indent 20; fi
  [ -z "$(git -C "$ROOT" status --porcelain -- Pix)" ] || warn "Pix/ has uncommitted changes: a release zip must come from committed code"
}

checkCode() {
  local php errors
  section "Code"
  if ! command -v php >/dev/null 2>&1; then fail "PHP not found (needed for php -l and PHPCS)"; return; fi
  php=$(php -r 'echo PHP_VERSION;')
  errors=$(find "$PKG_DIR" -type f \( -name '*.php' -o -name '*.phtml' \) -print0 |
    xargs -0 -n1 php -d error_reporting=-1 -d display_errors=stderr -d log_errors=0 -l 2>&1 |
    grep -v '^No syntax errors detected' || true)
  if [ -z "$errors" ]; then ok "php -l on PHP $php: no errors or deprecations"; else fail "php -l on PHP $php:"; printf '%s\n' "$errors" | indent 20; fi
  if [ ! -f "$ROOT/vendor/bin/phpcs" ]; then fail "PHPCS is not installed: run composer install"; return; fi
  if php "$ROOT/vendor/bin/phpcs" --standard="$ROOT/phpcs.xml" --no-colors --report=full -q "$PKG_DIR" >"$WORK/phpcs.txt" 2>&1; then
    ok "PHPCS with the EQP rules (Magento2 standard, severity 10)"
  else
    fail "PHPCS with the EQP rules:"
    indent 40 <"$WORK/phpcs.txt"
  fi
}

startContainers() {
  echo "  - starting MySQL, OpenSearch and PHP $PHP_VERSION (the first run builds the image and downloads Magento)"
  DOCKER_STARTED=true
  compose up -d --build --wait db opensearch php >"$WORK/compose.log" 2>&1 || {
    fail "could not start the containers:"
    tail -n 20 "$WORK/compose.log" | indent 20
    return 1
  }
}

runVarnishTest() {
  compose exec -T php bash /scripts/varnish.sh prepare || return 1
  compose exec -d -T -e PHP_CLI_SERVER_WORKERS=4 php php -S 0.0.0.0:8080 -t pub phpserver/router.php
  compose up -d --wait varnish >>"$WORK/compose.log" 2>&1 || { compose logs varnish 2>&1 | tail -n 20 | indent 20; return 1; }
  compose exec -T php bash /scripts/varnish.sh run
}

runInstallation() {
  export MAGENTO_VERSION PHP_VERSION MYSQL_IMAGE PKG_NAME PKG_VERSION MODULE_NAME ARTIFACTS_DIR PACKAGE_DIR
  PHP_VERSION=$(phpVersionFor "$MAGENTO_VERSION")
  MYSQL_IMAGE=$(mysqlImageFor "$MAGENTO_VERSION")
  PKG_NAME=$NAME PKG_VERSION=$VERSION ARTIFACTS_DIR="$WORK/artifacts" PACKAGE_DIR=$PKG_DIR
  section "Installation & Varnish (Magento $MAGENTO_VERSION, PHP $PHP_VERSION, $MYSQL_IMAGE, OpenSearch 3, Varnish 8)"
  if ! docker info >/dev/null 2>&1; then fail "Docker is not running (use --quick to skip this stage)"; return; fi
  mkdir -p "$ARTIFACTS_DIR"
  cp "$ZIP" "$ARTIFACTS_DIR/package.zip"
  startContainers || return 0
  if compose exec -T php bash /scripts/install.sh; then ok "installation test"; else fail "installation test"; return; fi
  if runVarnishTest; then ok "varnish test"; else fail "varnish test"; fi
}

summary() {
  section "Result"
  if [ "$FAILURES" -gt 0 ]; then echo "  ✗ $FAILURES check(s) failed: do not upload"; exit 1; fi
  if [ "$CURRENT_CODE" = true ]; then echo "  ✓ the current code passes, the zip built from it is safe to upload"; else echo "  ✓ ready to upload: $ZIP"; fi
  [ "$QUICK" = false ] || echo "  ! --quick skipped the installation and Varnish tests"
}

main() {
  parseArgs "$@"
  command -v jq >/dev/null 2>&1 || abort "jq is required"
  resolveZip
  extractZip
  checkPackage
  checkProductionConfig
  checkFiles
  checkCode
  [ "$QUICK" = true ] || runInstallation
  summary
}

main "$@"
