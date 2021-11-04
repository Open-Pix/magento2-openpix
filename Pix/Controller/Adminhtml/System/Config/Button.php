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
    const LOG_NAME = 'webhook_configuration';

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

        if(empty($appID)) {
            $result->setData([
                'success' => false,
                'message' => 'OpenPix: You need to add appID before configuring webhook.'
            ]);
            return $result;
        }

        $this->_helperData->setConfig('webhook_authorization', $newAuthorization);

        $responseGetWebhooks = $this->getWebhooksFromApi($apiUrl.'/api/openpix/v1/webhook',$appID, $webhookUrl);
        $hasActiveWebhook = false;
        foreach($responseGetWebhooks['webhooks'] as $webhook){
            if($webhook['isActive']) {
                $hasActiveWebhook =true;
                break;
            }
        }
        if($hasActiveWebhook){
            if(isset($webhook['authorization'])) {
                $this->_helperData->setConfig('webhook_authorization', $webhook['authorization']);
            }
            if(isset($webhook['hmacSecretKey'])) {
                // $this->_helperData->setConfig('hmac_authorization', $webhook['hmacSecretKey']);
            }
            // $webhookStatus = __('Configured');
            $webhookStatus = 'Configured';
            // $this->_helperData->setConfig('webhook_status', $webhookStatus);
            $result->setData([
                'message' => 'OpenPix: Webhook already configured.',
                'body' => [
                    'webhook_authorization' =>
                        $webhook['authorization'],
                    'hmac_authorization' =>
                        $webhook['hmacSecretKey'],
                    'webhook_status' => $webhookStatus,
                ],
                'success' => true,
            ]);
            return $result;
        }
        $responseCreateWebhook = $this->createNewWebhok($apiUrl.'/api/openpix/v1/webhook',$appID, $webhookUrl, $newAuthorization);

        if(isset($responseCreateWebhook['error']) || isset($responseCreateWebhook['errors'])) {
            // roolback of oldSettings
            $this->_helperData->setConfig('webhook_authorization', $oldAuthorization);

            $result->setData($this->handleError($responseCreateWebhook));
            return $result;
        }
        $formatedBodyWebhook = [
            'webhook_authorization' =>
                $responseCreateWebhook['webhook']['authorization'],
            'hmac_authorization' =>
                $responseCreateWebhook['webhook']['hmacSecretKey'],
            'webhook_status' => 'Configured',
        ];
        $result->setData([
            'body' => $formatedBodyWebhook,
            'success' => true,
            'message' => 'OpenPix: Webhook configured.']
        );
        if($responseCreateWebhook['webhook']['authorization']) {
            $this->_helperData->setConfig('webhook_authorization', $responseCreateWebhook['webhook']['authorization']);
        }
        return $result;
    }
    public function getWebhooksFromApi($apiGetUrl, $appID, $siteUrl) {
        $this->_curl->setOptions(
            [
                CURLOPT_URL => "$apiGetUrl?url=$siteUrl" ,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: ' . $appID,
                ],
                CURLOPT_VERBOSE => true
            ]
        );
        $this->_curl->get($apiGetUrl);
        $response = json_decode($this->_curl->getBody(),true);
        return $response;
    }
    public function createNewWebhok($apiUrl, $appID, $webhookUrl,$newAuthorization) {
        $payload = [
            'webhook' => [
                'name' => 'Magento-2-Webhook',
                'url' => $webhookUrl,
                'authorization' => $newAuthorization, // should be uuid
                'isActive' => true,
            ],
        ];
        $this->_curl->setOptions(
            [
                CURLOPT_URL => $apiUrl,
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

        $this->_curl->post($apiUrl, \json_encode($payload));
        $this->_helperData->log('OpenPix: Try create new weebhook ',self::LOG_NAME,$this->_curl->getBody());

        $response = json_decode($this->_curl->getBody(),true);
        return $response;
    }
    public function handleError($responseBody) {
        $errorFromApi =
            $responseBody['error'] ?? $responseBody['errors'][0]['message'];

        $this->_helperData->log('OpenPix: Error while creating one-click webhook: '.$errorFromApi,self::LOG_NAME,$responseBody['error'] ?? $responseBody['errors']);

        return [
            'message' => "OpenPix: Error while creating one-click webhook. \n $errorFromApi",
            'success' => false,
        ];
    }
}

?>
