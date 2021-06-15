<?php

namespace OpenPix\Pix\Model\Api;

use Magento\Framework\Model\AbstractModel;
use \OpenPix\Pix\Api\Data\PixTransactionInterface;

class PixTransaction extends AbstractModel implements PixTransactionInterface {
    public $endToEndId;

    /**
     * @return string
     */
    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    /**
     * @param string $endToEndId
     *
     * @return $this
     */
    public function setEndToEndId(string $endToEndId): PixTransactionInterface
    {
        $this->endToEndId = $endToEndId;
        return $this;
    }
}
