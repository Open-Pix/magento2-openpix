<?php

namespace OpenPix\Pix\Api;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param \OpenPix\Pix\Api\Data\OpenPixChargeInterface $charge The charge.
     * @return string
     */
    public function processWebhook($charge);

    /**
     * GET version of OpenPix magento2 webapi
     * @return string
     */
    public function getVersion();
}
