<?php

namespace OpenPix\Pix\Model\Api;

use OpenPix\Pix\Api\Data\OpenPixChargeInterface;

class OpenPixCharge implements OpenPixChargeInterface {
    public $status;
    public $correlationId;

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): OpenPixChargeInterface
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @param string $correlationId
     *
     * @return $this
     */
    public function setCorrelationId(string $correlationId): OpenPixChargeInterface
    {
        $this->correlationId = $correlationId;
        return $this;
    }
}
