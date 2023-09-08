<?php

namespace OpenPix\Pix\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use OpenPix\Pix\Helper\Data;
use Magento\Framework\Controller\Result\Json;

class PrepareOneclick extends Action
{
    protected $_resultJsonFactory;
    protected $_helperData;
    protected $_curl;
    protected $_urlInterface;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        UrlInterface $urlInterface,
        Data $helper
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_helperData = $helper;
        $this->_curl = $curl;
        $this->_urlInterface = $urlInterface;
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->_resultJsonFactory->create();

        // Remove current App ID
        $this->_helperData->setAppID("", true);

        $webhookUrl = $this->_urlInterface->getBaseUrl() . "openpix/index/webhook";
        $platformUrl = $this->_helperData->getOpenPixPlatformUrl();
        $newPlatformIntegrationUrl = $platformUrl . "/home/applications/magento2/add/oneclick?website=" . $webhookUrl;

        $result->setData([
            "redirectUrl" => $newPlatformIntegrationUrl,
            "webhookUrl" => $webhookUrl,
        ]);

        return $result;
    }
}
