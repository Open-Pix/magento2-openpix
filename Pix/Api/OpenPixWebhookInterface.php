<?php

namespace OpenPix\Pix\Api;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param \OpenPix\Pix\Api\Data\OpenPixChargeInterface|null $charge The charge.
     * @param \OpenPix\Pix\Api\Data\PixTransaction\PixTransactionInterface|null $pix The pix transaction.
     * @param string|null $evento The evento string when is a test call from OpenPix creating a new webhook.
     * @return string
     */
    public function processWebhook($charge = null, $pix = null, $evento = null);
}
