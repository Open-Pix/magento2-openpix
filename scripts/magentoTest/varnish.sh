#!/usr/bin/env bash
# Varnish test of the Adobe Commerce Marketplace technical review: with 10 products and 2 categories,
# home, 2 categories and 3 products must be MISS then HIT, and again after 3 price updates via REST.
source /scripts/lib.sh

eqpVcl() {
  awk '
    /\.probe = \{/ { skipping = 1; next }
    skipping { if ($0 ~ /^[[:space:]]*\}/) { skipping = 0 }; next }
    { print }
    /^sub vcl_deliver \{/ {
      print "    set resp.http.X-EQP-Cache = \"MISS\";"
      print "    if (obj.hits > 0) { set resp.http.X-EQP-Cache = \"HIT\"; }"
    }
  '
}

prepare() {
  step "generate fixtures (10 simple products, 2 categories)"
  run bin/magento setup:performance:generate-fixtures /scripts/fixtures.xml
  passed
  step "use Varnish as the full page cache"
  run bin/magento config:set system/full_page_cache/caching_application 2
  run bin/magento setup:config:set --no-interaction --http-cache-hosts=varnish:80
  run bin/magento varnish:vcl:generate --export-version=7 --backend-host=php --backend-port=8080 --access-list=php --output-file=/tmp/magento.vcl
  eqpVcl </tmp/magento.vcl >/varnish/default.vcl
  run bin/magento cache:flush
  passed
}

waitForVarnish() {
  local attempt code=""
  for attempt in $(seq 1 60); do
    code=$(curl -s -m 30 -o /dev/null -w '%{http_code}' http://varnish/health_check.php || true)
    [ "$code" = 200 ] && return 0
    sleep 2
  done
  failed "Varnish did not answer http://varnish/health_check.php (last status: $code)"
}

fetch() {
  curl -s -m 120 -o /dev/null -D - "http://varnish/$1" | tr -d '\r' |
    awk -F': ' 'NR == 1 { split($0, status, " ") } tolower($1) == "x-eqp-cache" { cache = $2 } END { print status[2], cache }'
}

expectCache() {
  local expected=$1 url result errors=""
  shift
  for url in "$@"; do
    result=$(fetch "$url")
    [ "$result" = "200 $expected" ] || errors="$errors/$url: expected 200 $expected, got '$result'"$'\n'
  done
  [ -z "$errors" ] || failed "$errors"
}

updatePrices() {
  local token sku code
  token=$(curl -s -m 60 -X POST http://varnish/rest/V1/integration/admin/token -H 'Content-Type: application/json' \
    -d '{"username":"admin","password":"Admin123Test"}' | tr -d '"')
  for sku in "$@"; do
    code=$(curl -s -m 60 -o /dev/null -w '%{http_code}' -X PUT "http://varnish/rest/V1/products/$sku" \
      -H "Authorization: Bearer $token" -H 'Content-Type: application/json' -d '{"product":{"price":123.45}}')
    [ "$code" = 200 ] || failed "PUT /rest/V1/products/$sku returned $code"
  done
}

runTest() {
  local categories products skus
  step "wait for Varnish"
  waitForVarnish
  passed
  categories=$(query "SELECT request_path FROM url_rewrite WHERE entity_type = 'category' AND store_id = 1 AND redirect_type = 0 ORDER BY entity_id LIMIT 2")
  products=$(query "SELECT request_path FROM url_rewrite WHERE entity_type = 'product' AND store_id = 1 AND redirect_type = 0 AND metadata IS NULL ORDER BY entity_id LIMIT 3")
  skus=$(query "SELECT e.sku FROM catalog_product_entity e JOIN url_rewrite u ON u.entity_id = e.entity_id AND u.entity_type = 'product' AND u.store_id = 1 AND u.redirect_type = 0 AND u.metadata IS NULL ORDER BY e.entity_id LIMIT 3")
  [ "$(printf '%s\n' "$categories" | grep -c .)" = 2 ] && [ "$(printf '%s\n' "$products" | grep -c .)" = 3 ] ||
    failed "the fixtures did not create 2 categories and 3 products with URLs"
  run bin/magento cache:flush
  step "home, 2 categories and 3 products: MISS"
  expectCache MISS "" $categories $products
  passed
  step "same pages again: HIT"
  expectCache HIT "" $categories $products
  passed
  step "update the price of the 3 products via REST"
  updatePrices $skus
  passed
  step "updated products and their categories: MISS"
  expectCache MISS $categories $products
  passed
  step "all pages again: HIT"
  expectCache HIT "" $categories $products
  passed
}

case "${1:-}" in
  prepare) prepare ;;
  run) runTest ;;
  *) failed "usage: varnish.sh prepare|run" ;;
esac
