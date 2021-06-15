<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixChargeInterface {
    /**
     * @var string
     */
    const STATUS = 'status';

    /**
     * @var string
     */
    const CORRELATION_ID = 'correlation_id';

    /**
     * Return the status of this charge.
     *
     * @return string Charge Status.
     */
    public function getStatus();

    /**
     * Set the status of this charge.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Return the correlationID.
     *
     * @return string CorrelationID.
     */
    public function getCorrelationID();

    /**
     * Set the correlationID
     *
     * @param string $correlationID
     * @return $this
     */
    public function setCorrelationID($correlationID);
}
