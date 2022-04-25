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
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */

    //         const OPENPIX_ENV = 'development';
    const OPENPIX_ENV = 'staging';
    //     const OPENPIX_ENV = 'production';

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
     * @param WriterInterface $configWriter
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
        encryptor $encryptor,
        WriterInterface $writerConfig,
        TypeListInterface $cacheTypeList
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
        $this->_writerConfig = $writerConfig;
        $this->cacheTypeList = $cacheTypeList;
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
        if (self::OPENPIX_ENV === 'development') {
            return 'http://localhost:5001';
        }

        if (self::OPENPIX_ENV === 'staging') {
            return 'https://api.openpix.dev';
        }

        // production
        return 'https://api.openpix.com.br';
    }

    public static function getOpenPixPluginUrlScript(): string
    {
        if (self::OPENPIX_ENV === 'development') {
            return 'http://localhost:4444/openpix.js';
        }

        if (self::OPENPIX_ENV === 'staging') {
            return 'https://plugin.openpix.dev/v1/openpix-dev.js';
        }

        // production
        return 'https://plugin.openpix.com.br/v1/openpix.js';
    }

    public function getOpenPixEnabled()
    {
        return $this->getConfig('payment/openpix_pix/active');
    }

    public function getConfig($path, $clearCache = false)
    {
        if ($clearCache) {
            $this->clearCache();
        }
        $storeScope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }
    public function getScopeConfig()
    {
        return ScopeInterface::SCOPE_STORE;
    }
    public function setConfig($variable, $value, $clearCache = false)
    {
        if ($clearCache) {
            $this->clearCache();
        }
        $path = 'payment/openpix_pix/' . $variable;
        return $this->_writerConfig->save($path, $value);
    }
    public function clearCache()
    {
        $this->cacheTypeList->cleanType(
            \Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER
        );
    }
    public function getAppID($clearCache = false)
    {
        return $this->getConfig('payment/openpix_pix/app_ID', $clearCache);
    }

    public function getWebhookAuthorization($clearCache = false)
    {
        return $this->getConfig(
            'payment/openpix_pix/webhook_authorization',
            $clearCache
        );
    }

    public function getOrderStatus($clearCache = false)
    {
        $status = $this->getConfig(
            'payment/openpix_pix/order_status',
            $clearCache
        );
        return $status ?? \Magento\Sales\Model\Order::STATE_PROCESSING;
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

    public function validateCPF($cpf)
    {
        if (empty($cpf)) {
            return false;
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calculates the check digits to verify that the
        // CPF is valid
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * ($t + 1 - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    public function validateCNPJ($cnpj)
    {
        if (empty($cnpj)) {
            return false;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);

        if ($cnpj[12] != (($n %= 11) < 2 ? 0 : 11 - $n)) {
            return false;
        }

        for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);

        if ($cnpj[13] != (($n %= 11) < 2 ? 0 : 11 - $n)) {
            return false;
        }

        return true;
    }
}
