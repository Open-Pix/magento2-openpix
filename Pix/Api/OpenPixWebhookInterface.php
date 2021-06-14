<?php

namespace OpenPix\Pix\Api;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param \OpenPix\Pix\Api\Data\OpenPixChargeInterface $charge The charge.
     * @param \OpenPix\Pix\Api\Data\PixTransaction\PixTransactionInterface $pix The pix transaction.
     * @return string
     */
    public function processWebhook($charge, $pix);

    /**
     * GET version of OpenPix magento2 webapi
     * @return string
     */
    public function getVersion();
}
