<?php

namespace OpenPix\Pix\Helper;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface as encryptor;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use OpenPix\Pix\Logger\Logger;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */


//    const OPENPIX_ENV = 'development';
//    const OPENPIX_ENV = 'staging';
    const OPENPIX_ENV = 'production';

    // change this to work in development, staging or production
    /**
     * OpenPix Logging instance
     *
     * @var Logger
     */
    protected $_openpixLogger;

    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager ,
     * @param Context $context
     * @param Logger $logger
     * @param Session $checkoutSession ,
     * @param Customer $customer ,
     * @param ProductMetadataInterface $productMetadata ,
     * @param ModuleListInterface $moduleList ,
     * @param Curl $curl ,
     * @param SerializerInterface $serializer ,
     * @param encryptor $encryptor
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        Logger $logger,
        Session $checkoutSession,
        Customer $customer,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        Curl $curl,
        SerializerInterface $serializer,
        RemoteAddress $remoteAddress,
        encryptor $encryptor
    ) {
        $this->storeManager = $storeManager;
        $this->_openpixLogger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepo = $customer;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->_curl = $curl;
        $this->serializer = $serializer;
        $this->remoteAddress = $remoteAddress;
        $this->_encryptor = $encryptor;
        parent::__construct($context);
    }

    public static function uuid_v4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            random_int(0, 0xffff),
            random_int(0, 0xffff),

            // 16 bits for "time_mid"
            random_int(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * Log custom message using OpenPix logger instance
     *
     * @param        $message
     * @param string $name
     * @param null $array
     */
    public function log($message, $name = 'openpix', $array = null)
    {
        //if extra data is provided, it's encoded for better visualization
        if (!is_null($array)) {
            $message .= ' - ' . json_encode($array);
        }

        //set log
        $this->_openpixLogger->setName($name);
        $this->_openpixLogger->debug($message);
    }

    public function getOpenPixApiUrl()
    {
        return 'https://216ba36922e8.ngrok.io';

        if (self::OPENPIX_ENV === 'development') {
            return 'http://localhost:5001';
        }

        if (self::OPENPIX_ENV === 'staging') {
            return 'https://api.openpix.dev';
        }

        // production
        return 'https://api.openpix.com.br';
    }

    public function getOpenPixEnabled()
    {
        return $this->getConfig('payment/openpix_pix/active');
    }

    public function getConfig($path)
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    public function getAppID()
    {
        return $this->getConfig('payment/openpix_pix/app_ID');
    }

    public function getWebhookAuthorization()
    {
        return $this->getConfig('payment/openpix_pix/webhook_authorization');
    }

    /**
     * Convert a value to non-negative integer.
     *
     * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
     * @return int A non-negative integer.
     * @since 2.5.0
     *
     */
    public function absint($maybeint)
    {
        return abs((int) $maybeint);
    }

    /**
     * Format decimal numbers ready for DB storage.
     *
     * Sanitize, remove decimals, and optionally round + trim off zeros.
     *
     * This function does not remove thousands - this should be done before passing a value to the function.
     *
     * @param float|string $number Expects either a float or a string with a decimal separator only (no thousands).
     * @param mixed $dp number  Number of decimal points to use, blank to use woocommerce_price_num_decimals, or false to avoid all rounding.
     * @param bool $trim_zeros From end of string.
     * @return string
     */
    public function format_decimal($number, $dp = false, $trim_zeros = false)
    {
        $decimals = [',', '.', ''];

        // Remove locale from string.
        if (!is_float($number)) {
            $number = str_replace($decimals, '.', $number);

            // Convert multiple dots to just one.
            $number = preg_replace(
                '/\.(?![^.]+$)|[^0-9.-]/',
                '',
                wc_clean($number)
            );
        }

        if (false !== $dp) {
            $dp = intval('' === $dp ? 2 : $dp);
            $number = number_format(floatval($number), $dp, '.', '');
        } elseif (is_float($number)) {
            // DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
            $number = str_replace(
                $decimals,
                '.',
                sprintf('%.' . 2 . 'f', $number)
            );
            // We already had a float, so trailing zeros are not needed.
            $trim_zeros = true;
        }

        if ($trim_zeros && strstr($number, '.')) {
            $number = rtrim(rtrim($number, '0'), '.');
        }

        return $number;
    }
}
