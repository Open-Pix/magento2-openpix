<?php
namespace OpenPix\Pix\Model\Total\Quote;
/**
 * Class Custom
 * @package OpenPix\Pix\Model\Total\Quote
 */
class Custom extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    const LOG_NAME = 'pix_collect';
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    /**
     * Custom constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \OpenPix\Pix\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \OpenPix\Pix\Helper\Data $helper
    ) {
        $this->_priceCurrency = $priceCurrency;
        $this->_helperData = $helper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this|bool
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $this->_helperData->log('Pix::collect - collecting', self::LOG_NAME);

        parent::collect($quote, $shippingAssignment, $total);
        $baseDiscount = 5;
        $discount = $this->_priceCurrency->convert($baseDiscount);

        $this->_helperData->log(
            'Pix::collect - collecting discount ' . $discount,
            self::LOG_NAME
        );

        $this->_helperData->log(
            'Pix::collect - collecting discount ' . $discount,
            self::LOG_NAME
        );

        $total->addTotalAmount('customdiscount', -$discount);
        $total->addBaseTotalAmount('customdiscount', -$baseDiscount);
        $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseDiscount);
        $quote->setCustomDiscount(-$discount);

        $this->_helperData->log(
            'Pix::collect - collecting total ' . json_encode($quote->getData()),
            self::LOG_NAME
        );
        return $this;
    }
}
