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
        $appID = $this->getAppId();
        $apiUrl = $this->_helperData->getOpenPixApiUrl();
        $webhookUrl = $this->urlInterface->getBaseUrl() ."openpix/index/webhook";
        $newAuthorization = $this->_helperData::uuid_v4();
        $oldAuthorization = $this->_helperData->getWebhookAuthorization(true);
        if(empty($appID)) {
            $result->setData([
                'success' => false,
                'message' => 'OpenPix: You need to add appID before configuring webhook.'
            ]);
            return $result;
        }

        $this->_helperData->setConfig('webhook_authorization', $newAuthorization,true);

        $responseGetWebhooks = $this->getWebhooksFromApi($apiUrl.'/api/openpix/v1/webhook',$appID, $webhookUrl);

        $hasActiveWebhook = false;
        if(isset($responseGetWebhooks['webhooks'])) {
            foreach($responseGetWebhooks['webhooks'] as $webhook){
                if($webhook['isActive']) {
                    $hasActiveWebhook =true;
                    break;
                }
            }
        }

        if($hasActiveWebhook){
            $hasActiveWebhookPayload = $this->returnHasActiveWebhookPayload($webhook);
            $result->setData($hasActiveWebhookPayload);
            return $result;
        }

        $responseCreateWebhook = $this->createNewWebhok($apiUrl.'/api/openpix/v1/webhook',$appID, $webhookUrl, $newAuthorization);

        if(isset($responseCreateWebhook['error']) || isset($responseCreateWebhook['errors'])) {
            // roolback of oldSettings
            $result->setData($this->handleError($responseCreateWebhook,['oldAuth' => $oldAuthorization, 'newAuth' => $newAuthorization, "actualAuth"=> $this->_helperData->getWebhookAuthorization(true)]));
            $this->_helperData->setConfig('webhook_authorization', $oldAuthorization, true);

            return $result;
        }
        if(isset($responseCreateWebhook['webhook'])) {
            $responseCreateWebhook = $this->returnCreateWebhookPayload($responseCreateWebhook);
            $result->setData($responseCreateWebhook);
            return $result;
        }
         $result->setData([
            'success' => false,
            'message' => 'OpenPix: Something went wrong.'
        ]);
        $this->_helperData->log("OpenPix: User doesn't have connection with api ",self::LOG_NAME,['app_ID'=>$appID, 'webhookUrl'=>$webhookUrl, 'apiUrl'=>$apiUrl]);
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
    public function handleError($responseBody, $additionalLogs = []) {
        $errorFromApi =
            $responseBody['error'] ?? $responseBody['errors'][0]['message'];

        $message = 'OpenPix: Error while creating one-click webhook.';
        $errorResponse = $responseBody['error'] ?? $responseBody['errors'][0]['message'];
        $this->_helperData->log($message,self::LOG_NAME,[$errorResponse,$additionalLogs]);
        return [
            'message' => "OpenPix: Error while creating one-click webhook. \n $errorFromApi",
            'success' => false,
        ];
    }
    public function returnHasActiveWebhookPayload($webhook) {
        if(isset($webhook['authorization'])) {
            $this->_helperData->setConfig('webhook_authorization', $webhook['authorization'],true);
        }
        if(isset($webhook['hmacSecretKey'])) {
            $this->_helperData->setConfig('hmac_authorization', $webhook['hmacSecretKey']);
        }
        $this->_helperData->setConfig('webhook_status', 'Configured');
        $result = [
            'message' => 'OpenPix: Webhook already configured.',
            'body' => [
                'webhook_authorization' =>
                    $webhook['authorization'],
                'hmac_authorization' =>
                    $webhook['hmacSecretKey'],
                'webhook_status' => 'Configured',
            ],
            'success' => true,
        ];
        return $result;
    }
    public function returnCreateWebhookPayload($responseCreateWebhook) {
        $formatedBodyWebhook = [
            'webhook_authorization' =>
                $responseCreateWebhook['webhook']['authorization'],
            'hmac_authorization' =>
                $responseCreateWebhook['webhook']['hmacSecretKey'],
            'webhook_status' => 'Configured',
        ];
        if($responseCreateWebhook['webhook']['authorization']) {
            $this->_helperData->setConfig('webhook_authorization', $responseCreateWebhook['webhook']['authorization']);
        }
        $result = [
            'body' => $formatedBodyWebhook,
            'success' => true,
            'message' => 'OpenPix: Webhook configured.'
        ];
        return $result;
    }
    public function getAppId() {
        $appID = trim($this->getRequest()->getParam('app_ID')) ?? '';
        $this->getRequest()->getParam('app_ID');
        if (
            empty($appID) &&
            !empty($this->_helperData->getAppID())
        ) {
            $appID = trim($this->getRequest()->getParam('app_ID'));
            $this->_helperData->setConfig('app_ID', $appID);
        }
        if(!$appID) {
            $this->_helperData->log('OpenPix: Cannot get app_ID of user',self::LOG_NAME);
            return false;
        }
        return $appID;
    }
}

