<?php

namespace OpenPix\Pix\Block\Info;

class Boleto extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'OpenPix_Pix::info/boleto.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('OpenPix_Pix::info/boleto.phtml');
        return $this->toHtml();
    }

    /**
     * Get boleto digitable line
     *
     * @return string
     */
    public function getBoletoDigitable()
    {
        $order = $this->getInfo()->getOrder();
        if ($order) {
            return $order->getData('openpix_boleto_digitable');
        }
        return '';
    }

    /**
     * Get boleto barcode
     *
     * @return string
     */
    public function getBoletoBarcode()
    {
        $order = $this->getInfo()->getOrder();
        if ($order) {
            return $order->getData('openpix_boleto_barcode');
        }
        return '';
    }

    /**
     * Get boleto barcode image URL
     *
     * @return string
     */
    public function getBoletoImageUrl()
    {
        $order = $this->getInfo()->getOrder();
        if ($order) {
            return $order->getData('openpix_boleto_image');
        }
        return '';
    }

    /**
     * Get payment link URL
     *
     * @return string
     */
    public function getPaymentLinkUrl()
    {
        $order = $this->getInfo()->getOrder();
        if ($order) {
            return $order->getData('openpix_paymentlinkurl');
        }
        return '';
    }
}
