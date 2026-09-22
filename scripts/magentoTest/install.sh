#!/usr/bin/env bash
# Installation test of the Adobe Commerce Marketplace technical review:
# composer require, install with the extension enabled, compile, static content, production mode, reindex.
source /scripts/lib.sh

createProject() {
  [ -f composer.json ] && return 0
  step "download Magento $MAGENTO_VERSION from the Mage-OS mirror (first run only)"
  run composer create-project --no-interaction --no-install --repository-url=https://mirror.mage-os.org/ "magento/project-community-edition:$MAGENTO_VERSION" .
  run composer config --no-interaction allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
  run composer config --no-interaction allow-plugins.laminas/laminas-dependency-plugin true
  run composer config --no-interaction 'allow-plugins.magento/*' true
  run composer install --no-interaction --no-progress
  passed
}

removePreviousPackage() {
  [ -f .magento-test-package ] || return 0
  step "remove the package tested last time ($(cat .magento-test-package))"
  run composer remove --no-interaction --no-progress "$(cat .magento-test-package)"
  rm -f .magento-test-package
  passed
}

resetInstance() {
  rm -f app/etc/env.php app/etc/config.php
  rm -rf generated/code generated/metadata var/cache var/page_cache var/view_preprocessed var/di
  rm -rf pub/static/frontend pub/static/adminhtml pub/static/deployed_version.txt
}

requirePackage() {
  step "composer require $PKG_NAME:$PKG_VERSION from the zip"
  run composer config --no-interaction repositories.magento-test artifact /artifacts
  export COMPOSER_CACHE_DIR
  COMPOSER_CACHE_DIR=$(mktemp -d)
  run composer require --no-interaction --no-progress "$PKG_NAME:$PKG_VERSION"
  unset COMPOSER_CACHE_DIR
  echo "$PKG_NAME" >.magento-test-package
  diff -r "vendor/$PKG_NAME" /package >>"$LOG" 2>&1 || failed "vendor/$PKG_NAME is not the content of the zip"
  passed
}

lintPackage() {
  local errors
  step "php -l on PHP $(php -r 'echo PHP_VERSION;') (errors and deprecations)"
  errors=$(find "vendor/$PKG_NAME" -type f \( -name '*.php' -o -name '*.phtml' \) -print0 |
    xargs -0 -n1 php -d error_reporting=-1 -d display_errors=stderr -d log_errors=0 -l 2>&1 |
    grep -v '^No syntax errors detected' || true)
  [ -z "$errors" ] || failed "$errors"
  passed
}

installMagento() {
  step "setup:install with the extension (fresh database)"
  run bin/magento setup:install --no-interaction --cleanup-database \
    --base-url=http://varnish/ --db-host=db --db-name=magento --db-user=root --db-password=magento \
    --admin-firstname=Magento --admin-lastname=Test --admin-email=admin@example.com \
    --admin-user=admin --admin-password=Admin123Test \
    --language=en_US --timezone=America/Sao_Paulo --use-rewrites=1 \
    --search-engine=opensearch --opensearch-host=opensearch --opensearch-port=9200 \
    --disable-modules=Magento_TwoFactorAuth,Magento_AdminAdobeImsTwoFactorAuth
  bin/magento module:status "$MODULE_NAME" 2>>"$LOG" | grep -q 'Module is enabled' || failed "$MODULE_NAME is not enabled after setup:install"
  passed
}

productionMode() {
  step "setup:di:compile"
  run bin/magento setup:di:compile
  passed
  step "setup:static-content:deploy"
  run bin/magento setup:static-content:deploy -f --jobs=4 en_US
  passed
  step "deploy:mode:set production"
  run bin/magento deploy:mode:set production --skip-compilation --no-interaction
  passed
  step "indexer:reindex"
  run bin/magento indexer:reindex
  passed
}

createProject
removePreviousPackage
resetInstance
requirePackage
lintPackage
installMagento
productionMode
