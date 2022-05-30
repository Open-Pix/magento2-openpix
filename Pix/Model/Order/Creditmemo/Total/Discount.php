<?php

namespace OpenPix\Pix\Model\Order\Creditmemo\Total;

class Discount extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setOpenpixDiscount(0);
        $creditmemo->setBaseOpenpixDiscount(0);

        $items = $creditmemo->getItems();
        if (!count($items)) {
            return $this;
        }

        $order = $creditmemo->getOrder();
        $totalOpenpixDiscount = $order->getOpenpixDiscount() ?? 0;
        $baseTotalOpenpixDiscount = $order->getBaseOpenpixDiscount() ?? 0;

        $creditmemo->setOpenpixDiscount(-$totalOpenpixDiscount);
        $creditmemo->setBaseOpenpixDiscount(-$baseTotalOpenpixDiscount);

        $creditmemo->setGrandTotal(
            $creditmemo->getGrandTotal() - $totalOpenpixDiscount
        );
        $creditmemo->setBaseGrandTotal(
            $creditmemo->getBaseGrandTotal() - $baseTotalOpenpixDiscount
        );
        return $this;
    }
}
