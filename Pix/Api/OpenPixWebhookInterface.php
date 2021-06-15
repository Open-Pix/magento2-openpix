<?php

namespace OpenPix\Pix\Api;

use OpenPix\Pix\Api\Data\OpenPixChargeInterface;
use OpenPix\Pix\Api\Data\PixTransactionInterface;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param OpenPixChargeInterface|null $charge The charge.
     * @param PixTransactionInterface|null $pix The pix transaction.
     * @param string|null $evento The evento string when is a test call from OpenPix creating a new webhook.
     * @return string
     */
    public function processWebhook(OpenPixChargeInterface $charge = null, PixTransactionInterface $pix = null, string $evento = null): string;
}
