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
     * @var \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid
     */
    protected $chargePaid;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    /**
     * @var OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler
     */
    protected $configureHandler;

    private $resultJsonFactory;

    const LOG_NAME = 'webhook_handler';

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid $chargePaid,
        \OpenPix\Pix\Helper\Data $helper,
        JsonFactory $resultJsonFactory,
        \OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler $configureHandler
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->chargePaid = $chargePaid;
        $this->configureHandler = $configureHandler;
        $this->_helperData = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function getRemoteIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    public function isValidWebhookPayload($jsonBody)
    {
        if (
            !isset($jsonBody['charge']) ||
            !isset($jsonBody['charge']['correlationID'])
        ) {
            return false;
        }

        if (
            !isset($jsonBody['pix']) ||
            !isset($jsonBody['pix']['endToEndId'])
        ) {
            return false;
        }

        return true;
    }

    public function isPixDetachedPayload($jsonBody): bool
    {
        if (!isset($jsonBody['pix'])) {
            return false;
        }

        if (
            isset($jsonBody['charge']) &&
            isset($jsonBody['charge']['correlationID'])
        ) {
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

            $event = $jsonBody['evento'] ?? $jsonBody['event'];


            if ($event == 'teste_webhook') {
                $this->_helperData->log(
                    'OpenPix WebApi::ProcessWebhook Test Call',
                    self::LOG_NAME
                );

                return [
                    'error' => null,
                    'success' => 'Webhook Test Call: ' . $jsonBody['evento'],
                ];
            }

            if ($event == 'magento2-configure' && !empty($jsonBody['appID'])) {
                $this->_helperData->log(
                    'OpenPix WebApi::ProcessWebhook Configure',
                    self::LOG_NAME
                );

                $appID = $jsonBody['appID'];

                return $this->configureHandler->configure($appID);
            }

            if ($this->isPixDetachedPayload($jsonBody)) {
                $this->_helperData->log(
                    'OpenPix WebApi::ProcessWebhook Pix Detached',
                    self::LOG_NAME
                );

                return [
                    'error' => null,
                    'success' =>
                        'Pix Detached with endToEndId: ' .
                        $jsonBody['pix']['endToEndId'],
                ];
            }

            if ($this->isValidWebhookPayload($jsonBody)) {
                $charge = $jsonBody['charge'];
                $pix = $jsonBody['pix'];

                return $this->chargePaid->chargePaid($charge, $pix);
            }

            $this->_helperData->log(
                "OpenPix WebApi::ProcessWebhook Invalid Payload event: $event",
                self::LOG_NAME
            );

            return ['error' => 'Invalid Payload', 'success' => null];
        } catch (\Exception $e) {
            $this->_helperData->log(
                __(
                    sprintf(
                        'Fail when interpreting webhook JSON: %s',
                        $e->getMessage()
                    )
                )
            );
            return [
                'error' => 'Fail when interpreting webhook JSON',
                'success' => null,
            ];
        }
    }
}
