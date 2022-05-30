<?php

namespace OpenPix\Pix\Block\Sales\Order;

use OpenPix\Pix\Api\OpenPixManagementInterface;

class Totals extends \Magento\Sales\Block\Adminhtml\Order\Totals
{
    /**
     * Get totals source object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Create the weee ("FPT") totals summary
     *
     * @return $this
     */
    public function initTotals()
    {
        $openPixDiscount = abs($this->getSource()->getOpenpixDiscount());
        $baseOpenPixDiscount = abs(
            $this->getSource()->getBaseOpenpixDiscount()
        );
        if (empty($openPixDiscount)) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject([
            'code' => OpenPixManagementInterface::OPENPIX_DISCOUNT_CODE,
            'label' => __('Giftback Discount'),
            'value' => -$openPixDiscount,
            'base_value' => -$baseOpenPixDiscount,
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
