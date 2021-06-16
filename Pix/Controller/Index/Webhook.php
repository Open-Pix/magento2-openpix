<?php

namespace OpenPix\Pix\Controller\Index;

use OpenPix\Pix\Helper\Data;
use OpenPix\Pix\Helper\WebhookHandler;

class Webhook extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    private $webhookHandler;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        WebhookHandler $webhookHandler,
        Data $helperData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->logger = $logger;
        $this->_pageFactory = $pageFactory;
        $this->webhookHandler = $webhookHandler;
        $this->helperData = $helperData;
        return parent::__construct($context);
    }

    /**
     * The route that webhooks will use.
     */
    public function execute()
    {
        $this->logger->debug(__(sprintf('Start webhook')));

        if (!$this->validateRequest()) {
            $ip = $this->webhookHandler->getRemoteIp();

            $this->logger->error(__(sprintf('Invalid webhook attempt from IP %s', $ip)));

            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Authorization',
            ];

            return json_encode($response);
        }

        $body = file_get_contents('php://input');
        $this->logger->info(__(sprintf("Webhook New Event!\n%s", $body)));

        $this->webhookHandler->handle($body);
    }

    /**
     * Validate the webhook for security reasons.
     *
     * @return bool
     */
    private function validateRequest()
    {
        $authorization = $this->getAuthorization();

        $systemWebhookAuthorization = $this->helperData->getWebhookAuthorizationGeneral();

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

        return '';
    }
}
