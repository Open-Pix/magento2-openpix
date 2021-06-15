<?php

namespace \OpenPix\Pix\Model\Api;

use Magento\Framework\Model\AbstractModel;
use \OpenPix\Pix\Api\Data\OpenPixChargeInterface;

class OpenPixCharge extends AbstractModel implements OpenPixChargeInterface {
    protected function _construct()
    {
        $this->_init(ResourceOrder::class);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return string
     */
    public function getCorrelationID()
    {
        return $this->getData(self::CORRELATION_ID);
    }

    /**
     * @param string $correlationID
     *
     * @return $this
     */
    public function setCorrelationID($correlationID)
    {
        return $this->setData(self::CORRELATION_ID, $correlationID);
    }
}
