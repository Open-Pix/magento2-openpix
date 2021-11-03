<?php

namespace OpenPix\Pix\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Config\Model\ResourceModel\Config;
use OpenPix\Pix\Helper\Data;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\UrlInterface;
// use MutableScopeConfig


class Button extends Action {
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    protected $curl;
    protected $_helperData;
    protected $config;
    protected $urlInterface;

    /**
     * @var Data
     */

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        Data $helper,
        MutableScopeConfigInterface $config,
        UrlInterface $urlInterface
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->_helperData = $helper;
        $this->config = $config;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute() {
        $result = $this->resultJsonFactory->create();
        $appID = $this->_helperData->getAppID();
        $apiUrl = $this->_helperData->getOpenPixApiUrl();
        $webhookUrl = $this->urlInterface->getBaseUrl() ."openpix/index/webhook";
        // $authorization = $this->_helperData::uuid_v4(); // to use this, i should update the webhook_authorization first
        $authorization = $this->_helperData->getWebhookAuthorization();
        $payload = [
            'webhook' => [
                'name' => 'WooCommerce-Webhook',
                'url' => $webhookUrl,
                'authorization' => $authorization, // should be uuid
                'isActive' => true,
            ],
        ];
        $this->curl->setOptions(
            [
                CURLOPT_URL => $apiUrl . '/api/openpix/v1/webhook',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: ' . $appID,
                ],
                CURLOPT_VERBOSE => true
            ]
        );

        $this->curl->post($apiUrl."/api/openpix/v1/webhook", \json_encode($payload));

        $result->setData(['body'=>\json_decode($this->curl->getBody())]);
        return $result;
    }
}

?>
