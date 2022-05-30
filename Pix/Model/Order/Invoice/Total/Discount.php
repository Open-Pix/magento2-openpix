<?php

namespace OpenPix\Pix\Model\Order\Invoice\Total;

class Discount extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoice->setOpenpixDiscount(0);
        $invoice->setBaseOpenpixDiscount(0);

        $items = $invoice->getItems();
        if (!count($items)) {
            return $this;
        }

        $order = $invoice->getOrder();
        $totalOpenpixDiscount = $order->getOpenpixDiscount() ?? 0;
        $baseTotalOpenpixDiscount = $order->getBaseOpenpixDiscount() ?? 0;

        $invoice->setOpenpixDiscount(-$totalOpenpixDiscount);
        $invoice->setBaseOpenpixDiscount(-$baseTotalOpenpixDiscount);

        $invoice->setGrandTotal(
            $invoice->getGrandTotal() - $totalOpenpixDiscount
        );
        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal() - $baseTotalOpenpixDiscount
        );
        return $this;
    }
}
