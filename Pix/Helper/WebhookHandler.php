<?php

namespace OpenPix\Pix\Helper;


use Magento\Framework\Controller\Result\JsonFactory;

class WebhookHandler
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid
     */
    protected $chargePaid;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    private $resultJsonFactory;

    const LOG_NAME = 'webhook_handler';

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Psr\Log\LoggerInterface $logger,
        \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid $chargePaid,
        \OpenPix\Pix\Helper\Data $helper,
        JsonFactory $resultJsonFactory
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
        $this->chargePaid = $chargePaid;
        $this->_helperData = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function getRemoteIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    public function isValidTestWebhookPayload($jsonBody)
    {
        if (isset($jsonBody["evento"])) {
            return true;
        }

        return false;
    }

    public function isValidWebhookPayload($jsonBody)
    {
        if (!isset($jsonBody["charge"]) || !isset($jsonBody["charge"]["correlationID"])) {
            return false;
        }

        if (!isset($jsonBody["pix"]) || !isset($jsonBody["pix"]["endToEndId"])) {
            return false;
        }

        return true;
    }

    public function isPixDetachedPayload($jsonBody): bool
    {
        if (!isset($jsonBody["pix"])) {
            return false;
        }

        if (isset($jsonBody["charge"]) && isset($jsonBody["charge"]["correlationID"])) {
            return false;
        }

        return true;
    }

    /**
     * Handle incoming webhook.
     *
     * @param string $body
     *
     * @return bool
     */
    public function handle($body)
    {
        try {
            $jsonBody = json_decode($body, true);

            if ($this->isValidTestWebhookPayload($jsonBody)) {
                $this->_helperData->log('OpenPix WebApi::ProcessWebhook Test Call', self::LOG_NAME);

                return ["error" => null, "success" => "Webhook Test Call: " . $jsonBody["evento"] ];
            }

            if ($this->isPixDetachedPayload($jsonBody)) {
                $this->_helperData->log('OpenPix WebApi::ProcessWebhook Pix Detached', self::LOG_NAME);

                return ["error" => null, "success" => "Pix Detached with endToEndId: " . $jsonBody["pix"]["endToEndId"] ];
            }

            if (!$this->isValidWebhookPayload($jsonBody)) {
                $this->_helperData->log('OpenPix WebApi::ProcessWebhook Invalid Payload', self::LOG_NAME, $jsonBody);

                return ["error" => "Invalid Payload", "success" => null ];
            }
        } catch (\Exception $e) {
            $this->logger->info(__(sprintf('Fail when interpreting webhook JSON: %s', $e->getMessage())));
            return false;
        }

        $charge = $jsonBody["charge"];
        $pix = $jsonBody["pix"];

        return $this->chargePaid->chargePaid($charge, $pix);
    }
}
