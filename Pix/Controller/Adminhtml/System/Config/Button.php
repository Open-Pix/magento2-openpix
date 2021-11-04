<?php

namespace OpenPix\Pix\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use OpenPix\Pix\Helper\Data;


class Button extends Action {
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    protected $_helperData;
    protected $_curl;
    protected $urlInterface;
    /**
     * @var Data
     */

    /**
     * @param Context $context,
     * @param JsonFactory $resultJsonFactory ,
     * @param Data $_helperData ,
     *
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        UrlInterface $urlInterface,
        Data $helper

    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_helperData = $helper;
        $this->_curl = $curl;
        $this->urlInterface = $urlInterface;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute() {
        $result = $this->resultJsonFactory->create();
        $appID = $this->_helperData->getAppID();
        $apiUrl = $this->_helperData->getOpenPixApiUrl();
        $webhookUrl = $this->urlInterface->getBaseUrl() ."openpix/index/webhook";
        $newAuthorization = $this->_helperData::uuid_v4();
        $oldAuthorization = $this->_helperData->getWebhookAuthorization();

        $this->_helperData->setConfig('webhook_authorization', $newAuthorization);

        $payload = [
            'webhook' => [
                'name' => 'WooCommerce-Webhook',
                'url' => $webhookUrl,
                'authorization' => $newAuthorization, // should be uuid
                'isActive' => true,
            ],
        ];
        $this->_curl->setOptions(
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

        $this->_curl->post($apiUrl."/api/openpix/v1/webhook", \json_encode($payload));

        $response = json_decode($this->_curl->getBody(),true);
        if($response['webhook']['authorization']) {
            $this->_helperData->setConfig('webhook_authorization', $response['webhook']['authorization']);
        }
        $result = $this->resultJsonFactory->create();
        $result->setData(['body'=>$response]);
        return $result;
    }
}

?>
