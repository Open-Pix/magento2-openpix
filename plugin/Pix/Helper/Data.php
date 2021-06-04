<?php

namespace OpenPix\Pix\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\Encryption\EncryptorInterface as encryptor;

class Data extends AbstractHelper {
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */

    /**
     * returning config value
     **/
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        encryptor $encryptor
    ) {
        $this->storeManager = $storeManager;
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
        return "https://api.openpix.com/transaction/create/";
    }

    public function getAppID() {
        return $this->getConfig('payment/openpix/app_ID');
    }
}
