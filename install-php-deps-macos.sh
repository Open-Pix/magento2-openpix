#!/usr/bin/env bash
set -euo pipefail

# install-php-deps-macos.sh
# Installs PHP, Composer, Node and common PHP extensions needed for this project on macOS using Homebrew.
# Run: ./install-php-deps-macos.sh [--php74]
# By default installs the latest PHP. Use --php74 to install PHP 7.4 as well (where available via Homebrew).

BREW=${BREW:-brew}
PHP74=false

for arg in "$@"; do
  case "$arg" in
    --php74) PHP74=true ;;
    -h|--help)
      echo "Usage: $0 [--php74]"
      echo "  --php74   Install php@7.4 alongside the latest php (if available in Homebrew)"
      exit 0
      ;;
  esac
done

echo "== OpenPix: macOS dependency installer =="

if ! command -v "$BREW" >/dev/null 2>&1; then
  echo "Homebrew not found."
  read -p "Install Homebrew now? (requires /bin/bash and internet) [y/N] " install_brew
  if [[ "$install_brew" =~ ^[Yy]$ ]]; then
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    echo "Homebrew installed. You may need to add Homebrew to your PATH."
  else
    echo "Please install Homebrew first: https://brew.sh/"
    exit 1
  fi
fi

echo "Updating Homebrew..."
"$BREW" update || true

# Install latest PHP
echo "Installing latest PHP via Homebrew..."
"$BREW" install php || true

if [ "$PHP74" = true ]; then
  echo "Attempting to install php@7.4 via Homebrew..."
  # tap shivammathur/php to get alternate PHP versions if necessary
  "$BREW" tap shivammathur/php || true
  "$BREW" install shivammathur/php/php@7.4 || "$BREW" install php@7.4 || true
fi

echo "Installing Composer..."
if ! command -v composer >/dev/null 2>&1; then
  "$BREW" install composer || true
fi

echo "Installing Node.js and yarn..."
if ! command -v node >/dev/null 2>&1; then
  "$BREW" install node || true
fi
if ! command -v yarn >/dev/null 2>&1; then
  # prefer corepack if available (Node >=16.10), otherwise brew
  if command -v corepack >/dev/null 2>&1; then
    corepack enable || true
    corepack prepare yarn@stable --activate || true
  else
    "$BREW" install yarn || true
  fi
fi

echo "Checking PHP extensions (openssl, curl, json, mbstring)..."
PHP_CMD=${PHP_CMD:-php}
if ! command -v "$PHP_CMD" >/dev/null 2>&1; then
  echo "Warning: php not found in PATH. You may need to restart your shell or add Homebrew PHP to PATH." 
else
  MISSING=()
  for ext in openssl curl json mbstring; do
    if ! "$PHP_CMD" -m 2>/dev/null | grep -qi "^${ext}$"; then
      MISSING+=("$ext")
    fi
  done

  if [ ${#MISSING[@]} -gt 0 ]; then
    echo "The following PHP extensions appear missing: ${MISSING[*]}"
    echo "Homebrew PHP normally ships with common extensions. If an extension is missing try installing it via pecl or ensure PHP was built against the right libraries."
    echo "Example: brew install openssl && brew reinstall php --with-openssl (Homebrew may manage openssl automatically)."
  else
    echo "All common extensions found: openssl, curl, json, mbstring"
  fi
fi

echo "Optional: install phpunit globally via composer (dev tool)"
read -p "Install phpunit (global composer require --dev phpunit/phpunit:^9)? [y/N] " install_phpunit
if [[ "$install_phpunit" =~ ^[Yy]$ ]]; then
  if command -v composer >/dev/null 2>&1; then
    composer global require phpunit/phpunit:^9 || true
    echo "Ensure ~/.composer/vendor/bin is in your PATH to use global composer binaries."
  else
    echo "Composer not found; cannot install phpunit globally."
  fi
fi

echo "Summary versions:"
command -v php >/dev/null 2>&1 && php -v || echo "php: not found"
command -v composer >/dev/null 2>&1 && composer --version || echo "composer: not found"
command -v node >/dev/null 2>&1 && node --version || echo "node: not found"
command -v yarn >/dev/null 2>&1 && yarn --version || echo "yarn: not found"

echo "Done. You may need to restart your terminal session for PATH changes to take effect."
