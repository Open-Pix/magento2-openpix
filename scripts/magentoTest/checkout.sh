#!/usr/bin/env bash
# Pages that list payment methods server side, where the EQP "MFTF Adobe Commerce Supplied" tests run:
# the storefront checkout payment methods API and admin Create New Order must answer 200.
source /scripts/lib.sh

BASE=http://varnish
JSON='Content-Type: application/json'
SKU=magento-test-checkout

lastException() {
  head -n 1 var/log/exception.log 2>/dev/null | cut -c1-400
}

adminToken() {
  curl -s -m 60 -X POST "$BASE/rest/V1/integration/admin/token" -H "$JSON" \
    -d '{"username":"admin","password":"Admin123Test"}' | tr -d '"'
}

createProduct() {
  curl -s -m 60 -o /dev/null -w '%{http_code}' -X POST "$BASE/rest/V1/products" -H "$JSON" -H "Authorization: Bearer $1" \
    -d "{\"product\":{\"sku\":\"$SKU\",\"name\":\"Magento Test Checkout\",\"attribute_set_id\":4,\"price\":10,\"status\":1,\"visibility\":4,\"type_id\":\"simple\",\"weight\":1,\"extension_attributes\":{\"website_ids\":[1],\"stock_item\":{\"qty\":100,\"is_in_stock\":true}}}}"
}

guestCartPaymentMethods() {
  local cart
  cart=$(curl -s -m 60 -X POST "$BASE/rest/V1/guest-carts" | tr -d '"')
  curl -s -m 60 -o /dev/null -X POST "$BASE/rest/V1/guest-carts/$cart/items" -H "$JSON" \
    -d "{\"cartItem\":{\"sku\":\"$SKU\",\"qty\":1,\"quote_id\":\"$cart\"}}"
  curl -s -m 120 -o /tmp/payment-methods.json -w '%{http_code}' "$BASE/rest/V1/guest-carts/$cart/payment-methods"
}

adminCreateOrderPage() {
  local jar=/tmp/admin-cookies admin formKey
  admin=$(bin/magento info:adminuri | sed -n 's|.*: *\(/[^ ]*\).*|\1|p' | sed 's|/$||')
  rm -f "$jar"
  formKey=$(curl -s -m 60 -c "$jar" -b "$jar" "$BASE$admin/" | sed -n 's/.*name="form_key" type="hidden" value="\([^"]*\)".*/\1/p' | head -1)
  curl -s -m 60 -o /dev/null -c "$jar" -b "$jar" --data-urlencode "form_key=$formKey" \
    --data-urlencode 'login[username]=admin' --data-urlencode 'login[password]=Admin123Test' "$BASE$admin/admin/index/index/"
  curl -s -m 60 -o /dev/null -c "$jar" -b "$jar" "$BASE$admin/sales/order_create/start/"
  curl -s -m 120 -o /tmp/order-create.html -w '%{http_code}' -c "$jar" -b "$jar" "$BASE$admin/sales/order_create/index/"
}

run bin/magento config:set admin/security/use_form_key 0
run bin/magento cache:flush
: >var/log/exception.log

step "storefront: payment methods of a guest cart (checkout API)"
token=$(adminToken)
[ "$(createProduct "$token")" = 200 ] || failed "could not create the test product via REST"
code=$(guestCartPaymentMethods)
[ "$code" = 200 ] || failed "GET /rest/V1/guest-carts/<cart>/payment-methods returned $code" "$(lastException)"
passed
echo "      methods: $(grep -o '"code":"[^"]*"' /tmp/payment-methods.json | cut -d'"' -f4 | tr '\n' ' ')"

step "admin: Create New Order page"
code=$(adminCreateOrderPage)
if [ "$code" != 200 ] || grep -q 'An error has happened' /tmp/order-create.html; then
  failed "sales/order_create/index returned $code with the Magento error page" "$(lastException)"
fi
passed
