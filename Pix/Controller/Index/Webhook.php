<?php

namespace OpenPix\Pix\Controller\Index;

use OpenPix\Pix\Helper\Data;
use OpenPix\Pix\Helper\WebhookHandler;
use Magento\Framework\Controller\Result\JsonFactory;
use OpenPix\Pix\Helper\OpenPixConfig;

class Webhook extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $helperData;
    private $webhookHandler;
    private $resultJsonFactory;

    public function __construct(
        WebhookHandler $webhookHandler,
        Data $helperData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        JsonFactory $resultJsonFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->webhookHandler = $webhookHandler;
        $this->helperData = $helperData;
        $this->resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }

    /**
     * The route that webhooks will use.
     */
    public function execute()
    {

        $resultJson = $this->resultJsonFactory->create();
        $body = file_get_contents('php://input');

        $this->helperData->log('Start webhook');

        if (!$this->validateRequest($body)) {
            $ip = $this->webhookHandler->getRemoteIp();

            $this->helperData->log("Invalid webhook attempt from IP $ip");

            $resultJson->setHttpResponseCode(400);

            return $resultJson->setData([
                'error' => 'Invalid Webhook Signature',
            ]);
        }

        $this->helperData->debugJson("Webhook New Event!", 'debug event', \json_decode($body, true));

        $result = $this->webhookHandler->handle($body);

        $statusCode = isset($result['error']) && \strlen($result['error']) > 0 ? 400 : 200;

        $resultJson->setHttpResponseCode($statusCode);

        return $resultJson->setData($result);
    }

    public function verifySignature(string $payload, string $signature)
    {
        $publicKey = OpenPixConfig::OPENPIX_PUBLIC_KEY_BASE64;

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            base64_decode($publicKey),
            'sha256WithRSAEncryption'
        );

        if(!$verify) {
            $this->helperData->log('Invalid signature');
            $this->helperData->log(
                __(sprintf(
                    "\nSignature: %s\nPayload: %s\nisValid: %s\npublicKey: %s",
                    $signature, $payload, $verify == 1 ? "true" : "false", $publicKey
                ))
            );
        }

        return $verify;
    }

    /**
     * Validate the webhook for security reasons.
     *
     * @return bool
     */
    private function validateRequest(string $payload)
    {
        $signatureHeader = $this->getRequest()->getHeader("x-webhook-signature");

        $isValid = $this->verifySignature($payload, $signatureHeader);

        return $isValid;
    }
}
