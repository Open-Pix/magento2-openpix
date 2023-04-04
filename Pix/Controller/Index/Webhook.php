<?php

namespace OpenPix\Pix\Controller\Index;

use OpenPix\Pix\Helper\Data;
use OpenPix\Pix\Helper\WebhookHandler;
use Magento\Framework\Controller\Result\JsonFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    protected $logger;
    protected $_pageFactory;
    protected $helperData;
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

            $this->logger->error(
                __(sprintf('Invalid webhook attempt from IP %s', $ip))
            );

            $resultJson->setHttpResponseCode(400);

            return $resultJson->setData([
                'error' => 'Invalid Webhook Authorization',
            ]);
        }

        $body = file_get_contents('php://input');
        $this->logger->info(__(sprintf("Webhook New Event!\n%s", $body)));

        $result = $this->webhookHandler->handle($body);

        if (isset($result['error'])) {
            $resultJson->setHttpResponseCode(400);
            return $resultJson->setData(['error' => $result['error']]);
        }

        $resultJson->setHttpResponseCode(200);
        return $resultJson->setData(['success' => $result['success']]);
    }

    /**
     * Validate the webhook for security reasons.
     *
     * @return bool
     */
    private function validateRequest()
    {
        $systemWebhookAuthorization = $this->helperData->getWebhookAuthorization();

        $webhookAuthHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $webhookAuthOpenPixHeader =
            $_SERVER['HTTP_X_OPENPIX_AUTHORIZATION'] ?? '';
        $webhookAuthQueryString =
            $this->getRequest()->getParam('authorization') ?? '';

        $isAuthHeaderValid = $webhookAuthHeader === $systemWebhookAuthorization;
        $isAuthOpenPixHeaderValid =
            $webhookAuthOpenPixHeader === $systemWebhookAuthorization;
        $isAuthQueryStringValid =
            $webhookAuthQueryString === $systemWebhookAuthorization;

        return $isAuthHeaderValid ||
            $isAuthOpenPixHeaderValid ||
            $isAuthQueryStringValid;
    }
}
