<?php

namespace OpenPix\Pix\Helper;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class WebhookHandler
{
    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        RemoteAddress $remoteAddress,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
    }

    public function getRemoteIp()
    {
        return $this->remoteAddress->getRemoteAddress();
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

            if (!$jsonBody || !isset($jsonBody['event'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Webhook event not found!'));
            }

            $type = $jsonBody['event']['type'];
            $data = $jsonBody['event']['data'];
        } catch (\Exception $e) {
            $this->logger->info(__(sprintf('Fail when interpreting webhook JSON: %s', $e->getMessage())));
            return false;
        }

        switch ($type) {
            case 'test':
                $this->logger->info(__('Webhook test event.'));
                break;
//            case 'bill_paid':
//                return $this->chargePaid->chargePaid($data);
            default:
                $this->logger->warning(__(sprintf('Webhook event ignored by plugin: "%s".', $type)));
                break;
        }
    }
}
