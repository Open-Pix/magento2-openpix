<?php

namespace OpenPix\Pix\Controller\Index;

use OpenPix\Pix\Helper\Data;
use OpenPix\Pix\Helper\WebhookHandler;
use Magento\Framework\Controller\Result\JsonFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    private $webhookHandler;
    private $resultJsonFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        WebhookHandler $webhookHandler,
        Data $helperData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        JsonFactory $resultJsonFactory
    ) {
        $this->logger = $logger;
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
        $this->logger->debug(__(sprintf('Start webhook')));
        if (!$this->validateRequest()) {
            $ip = $this->webhookHandler->getRemoteIp();

            $this->logger->error(__(sprintf('Invalid webhook attempt from IP %s', $ip)));

            $resultJson->setHttpResponseCode(400);

            return $resultJson->setData([
                'error' => 'Invalid Webhook Authorization',
            ]);
        }

        $body = file_get_contents('php://input');
        $this->logger->info(__(sprintf("Webhook New Event!\n%s", $body)));

       $result = $this->webhookHandler->handle($body);

       if(isset($result["error"])) {
           $resultJson->setHttpResponseCode(400);
           return $resultJson->setData([ "error" => $result["error"]]);
       }

        $resultJson->setHttpResponseCode(200);
        return $resultJson->setData([ "success" => $result["success"]]);
    }

    /**
     * Validate the webhook for security reasons.
     *
     * @return bool
     */
    private function validateRequest()
    {
        $authorization = $this->getAuthorization();

        $systemWebhookAuthorization = $this->helperData->getWebhookAuthorization();

        return $systemWebhookAuthorization === $authorization;
    }



    public function getAuthorization()
    {

        if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (array_key_exists('Authorization', $_SERVER)) {
            return $_SERVER['Authorization'];
        }

        if (array_key_exists('HTTP_X_OPENPIX_AUTHORIZATION', $_SERVER)) {
            return $_SERVER['HTTP_X_OPENPIX_AUTHORIZATION'];
        }

        if (array_key_exists('authorization', $_GET)) {
            return $_GET['authorization'];
        }

        return '';
    }
}
