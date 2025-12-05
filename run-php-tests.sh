#!/usr/bin/env bash
set -euo pipefail

# run-php-tests.sh
# Convenience script to install dev phpunit (if missing) and run the PHPUnit suite
# Usage: ./run-php-tests.sh [<phpunit-args>]

COMPOSER_CMD="${COMPOSER:-composer}"
PHPUNIT_BIN="vendor/bin/phpunit"
PHPUNIT_CONFIG="phpunit.xml.dist"

echo "== OpenPix: running PHP unit tests =="

if [ ! -f "$PHPUNIT_BIN" ]; then
  if command -v "$COMPOSER_CMD" >/dev/null 2>&1; then
    echo "phpunit not found under $PHPUNIT_BIN — installing phpunit via composer (dev requirement)..."
    # prefer adding as --dev to project; allow the command to continue even if package already exists
    "$COMPOSER_CMD" require --dev phpunit/phpunit:^9 --no-interaction || true
  else
    echo "ERROR: Composer not found. Install Composer or set COMPOSER env to point to it."
    exit 1
  fi
fi

if [ ! -x "$PHPUNIT_BIN" ] && [ -f "$PHPUNIT_BIN" ]; then
  # make vendor phpunit executable
  chmod +x "$PHPUNIT_BIN" || true
fi

if [ ! -f "$PHPUNIT_BIN" ]; then
  echo "ERROR: phpunit binary not found at $PHPUNIT_BIN after attempting install."
  exit 1
fi

echo "Running: $PHPUNIT_BIN -c $PHPUNIT_CONFIG $*"
exec "$PHPUNIT_BIN" -c "$PHPUNIT_CONFIG" "$@"
