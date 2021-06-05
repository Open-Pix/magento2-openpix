<?php

namespace OpenPix\Pix\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use \Magento\Framework\Encryption\EncryptorInterface as encryptor;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Checkout\Model\Session;
use \Magento\Customer\Model\Customer;
use \Magento\Framework\App\ProductMetadataInterface;
use \Magento\Framework\Module\ModuleListInterface;
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Framework\Serialize\SerializerInterface;
use \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use OpenPix\Pix\Logger\Logger;

class Data extends AbstractHelper {
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */

    /**
     * OpenPix Logging instance
     *
     * @var Logger
     */
    protected $_openpixLogger;

    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager,
     * @param Context $context
     * @param Logger $logger
     * @param Session $checkoutSession,
     * @param Customer $customer,
     * @param ProductMetadataInterface $productMetadata,
     * @param ModuleListInterface $moduleList,
     * @param Curl $curl,
     * @param SerializerInterface $serializer,
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
        $this->logger = $logger;
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

    public function getConfig($path) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    public function getUrl() {
        return "https://api.openpix.com/openpix/v1/charge";
    }

    public function getAppID() {
        return $this->getConfig('payment/openpix/app_ID');
    }
}
