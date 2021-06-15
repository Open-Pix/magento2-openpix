<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixChargeInterface {
    /**
     * Return the status of this charge.
     *
     * @return string Charge Status.
     */
    public function getStatus(): string;

    /**
     * Set the status of this charge.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): OpenPixChargeInterface;

    /**
     * Return the correlationId.
     *
     * @return string CorrelationId.
     */
    public function getCorrelationId(): string;

    /**
     * Set the correlationId
     *
     * @param string $correlationId
     * @return $this
     */
    public function setCorrelationId(string $correlationId): OpenPixChargeInterface;
}
