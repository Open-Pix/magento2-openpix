<?php

namespace OpenPix\Pix\Observer;

class QuoteSubmitBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Convert openpix discount amount from quote to order
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $order->setData(
            'openpix_discount',
            $quote->getData('openpix_discount')
        );
        $order->setData(
            'base_openpix_discount',
            $quote->getData('base_openpix_discount')
        );
    }
}
