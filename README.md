## OpenPix for Magento2
OpenPix Magento2 Pix plugin.

Follow the docs on [OpenPix - Magento2 Docs](https://developers.woovi.com/docs/ecommerce/magento2/magento2-plugin)

![Product Page with Boleto Payment](./assets/demo1.png)

## Features

- Multiple payment methods: PIX, PIX Parcelado (Installments), and Boleto
- Fast and secure payment processing with OpenPix/Woovi
- Mobile-friendly checkout experience
- Automatic webhook notifications for payment updates
- Payment confirmation in real-time
- Support for multiple delivery addresses

## Available Payment Methods

The plugin provides three payment methods:

1. **PIX** (`openpix_pix`) - Instant payment via PIX QR Code
2. **PIX Parcelado** (`openpix_pix_parcelado`) - Installment payments via PIX
3. **Boleto Bancário** (`openpix_boleto`) - Bank slip payment with barcode

![Boleto Payment Confirmation](./assets/demo2.png)

## Requirements

Minimum requirements to run this module and developer tooling:

- PHP: >= 7.0 (the code uses scalar type hints, return types and null coalescing). Practical recommendation: use the PHP version required by your Magento installation. For Magento 2.4.x this is typically PHP 7.4; newer Magento releases may require PHP 8.x.
- PHP extensions: OpenSSL extension must be available (the webhook controller uses openssl_verify for signature validation). The module also assumes common PHP extensions available for Magento (json, curl, mbstring, etc.) — follow your Magento installation requirements.
- Composer: required to install PHP packages and register the module into a Magento application.
- Node.js and package manager: Node.js (recommended >= 14, preferably 16+) and Yarn or npm are needed for running the repository scripts and tests (Jest) and building front-end assets.

Running tests

This repository uses Jest for the JavaScript tests. To run tests locally:

1. Install dependencies:

```bash
# with npm
npm install

# or with yarn
yarn install
```

2. Run the test suite:

```bash
yarn test
# or
npm test
```

## Testing a zip before uploading to the Adobe Commerce Marketplace

`magento-test` runs locally the checks of the Marketplace technical review, so a wrong zip is caught before the upload:

```bash
pnpm magento-test                         # zip built from the current Pix/
pnpm magento-test path/to/release.zip     # a release zip
pnpm magento-test --quick                 # package and code checks only, no Docker (seconds)
MAGENTO_VERSION=2.4.9 pnpm magento-test   # another Magento release (default 2.4.8-p5)
pnpm magento-test --clean                 # remove the Docker containers, volumes and image
```

| Stage | Checks |
|---|---|
| Package | composer name (its vendor must be the Marketplace vendor), type, version in `composer.json`, `etc/module.xml` and `package.json`, autoload, module registration |
| Production config | `Helper/OpenPixConfig.php` and `view/frontend/requirejs-config.js` are the production ones, no `@woovi/do-not-merge` marker |
| Files | no `.DS_Store`, `__MACOSX` or other junk, same files and content as the committed `Pix/` |
| Code | `php -l` (errors and deprecations) and PHPCS with the EQP rules of `phpcs.xml` |
| Installation | Magento from the Mage-OS mirror in Docker: `composer require` of the zip, `setup:install`, `setup:di:compile`, `setup:static-content:deploy`, production mode, `indexer:reindex` |
| Varnish | the EQP scenario: with 10 products and 2 categories, home, 2 categories and 3 products must be `MISS` then `HIT` (`X-EQP-Cache` header), and again after updating 3 prices via REST |
| Checkout & admin | the storefront checkout payment methods API (guest cart) and admin Create New Order answer 200, and the unconfigured extension adds no payment method: the pages and conditions of the EQP MFTF tests |

It needs PHP 8.4, `composer install`, `jq` and Docker. The first Docker run downloads Magento and takes longer, the next ones reuse the volumes. The Commerce-supplied MFTF tests are not reproduced (Adobe does not require them to pass), but the Checkout & admin stage covers the pages they break on.

Notes

- There are environment helper scripts in `package.json` that copy environment-specific configuration into `Pix/Helper/OpenPixConfig.php` (see `config:local`, `config:staging`, `config:prod`).
- The webhook endpoint expects the `x-webhook-signature` header and uses the public key configured in `Pix/Helper/OpenPixConfig.php` for verification.
- If you plan to run the existing TypeScript test that uses `sed` (currently skipped), be aware macOS `sed -i` differs from GNU sed — adapt or install GNU sed (`brew install gnu-sed`) or adjust the test to be cross-platform.

If you want, I can add a short Installation section showing how to enable the module in a Magento project and ensure DB schema attributes are created.
