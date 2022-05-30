<?php

namespace OpenPix\Pix\Model\Quote\Address\Total;

use OpenPix\Pix\Api\OpenPixManagementInterface;

class Discount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var \OpenPix\Pix\Api\OpenPixManagementInterface
     */
    private $openPixManagement;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var \OpenPix\Pix\Helper\Data
     */
    private $helperData;

    /**
     * Constructor
     *
     * @param \OpenPix\Pix\Api\OpenPixManagementInterface $openPixManagement
     * @param \Magento\Customer\Model\SessionFactory      $customerSessionFactory
     * @param \OpenPix\Pix\Helper\Data                    $helperData
     */
    public function __construct(
        \OpenPix\Pix\Api\OpenPixManagementInterface $openPixManagement,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \OpenPix\Pix\Helper\Data $helperData
    ) {
        $this->setCode(OpenPixManagementInterface::OPENPIX_DISCOUNT_CODE);
        $this->openPixManagement = $openPixManagement;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->helperData = $helperData;
    }

    /**
     * @inheritdoc
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if (!$this->helperData->getOpenPixEnabled()) {
            return false;
        }

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        parent::collect($quote, $shippingAssignment, $total);

        $paymentMethod = $quote->getPayment()->getMethod();
        if (!$this->validatePaymentMethod($paymentMethod)) {
            $this->updateTotal($quote, $total, 0);
            return $this;
        }

        $discountAmount = $this->getDiscountFromCustomer(
            $quote,
            $total->getDiscountAmount()
        );
        if ($discountAmount <= 0) {
            $this->updateTotal($quote, $total, 0);
            return $this;
        }

        $this->updateTotal($quote, $total, $discountAmount);

        return $this;
    }

    /**
     * Update total
     *
     * @param $quote
     * @param $total
     * @param $amount
     *
     * @return void
     */
    protected function updateTotal($quote, $total, $amount)
    {
        $quote->setOpenpixDiscount($amount)->setBaseOpenpixDiscount($amount);

        $total->setOpenpixDiscount($amount)->setBaseOpenpixDiscount($amount);

        $total->addTotalAmount($this->getCode(), -$amount);
        $total->addBaseTotalAmount($this->getCode(), -$amount);
    }

    /**
     * @inheritdoc
     */
    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $result = null;
        $amount = $total->getOpenpixDiscount();
        if ($amount != 0) {
            $result = [
                'code' => $this->getCode(),
                'title' => __('Giftback Discount'),
                'value' => -$amount,
            ];
        }
        return $result;
    }

    /**
     * Validate payment method is openpix or not
     *
     * @param string $paymentMethod
     *
     * @return bool
     */
    protected function validatePaymentMethod($paymentMethod)
    {
        if (empty($paymentMethod)) {
            return false;
        }

        return strcasecmp($paymentMethod, \OpenPix\Pix\Model\Pix\Pix::CODE) ==
            0;
    }

    /**
     * Get discount from customer balance of OpendPix API
     *
     * @param $quote
     * @param float $totalDiscountAmount
     *
     * @return float
     */
    protected function getDiscountFromCustomer($quote, $totalDiscountAmount)
    {
        $customerSession = $this->customerSessionFactory->create();
        if (!$customerSession->isLoggedIn()) {
            $this->helperData->log(
                'Pix::collect - customer not logged',
                OpenPixManagementInterface::OPENPIX_LOG_NAME
            );
            return 0;
        }

        $customerTaxVat = $quote->getCustomerTaxvat();
        if (!$customerTaxVat) {
            $this->helperData->log(
                'Pix::collect - customer does not have taxID',
                OpenPixManagementInterface::OPENPIX_LOG_NAME
            );
            return 0;
        }

        $this->helperData->log(
            'Pix::collect - customer taxvat ' . $customerTaxVat,
            OpenPixManagementInterface::OPENPIX_LOG_NAME
        );

        try {
            $customerGiftBackBalance = $this->openPixManagement->getCustomerBalanceByTaxId(
                $customerTaxVat
            );
        } catch (\Exception $exception) {
            $customerGiftBackBalance = 0;
        }

        if ($customerGiftBackBalance <= 0) {
            $this->helperData->log(
                'Pix::collect - customer does not have balance. Balance: ' .
                    $customerGiftBackBalance,
                OpenPixManagementInterface::OPENPIX_LOG_NAME
            );
            return 0;
        }

        $this->helperData->log(
            'Pix::collect - giftback balance ' . $customerGiftBackBalance,
            OpenPixManagementInterface::OPENPIX_LOG_NAME
        );

        return $this->openPixManagement->calculateAndConvertBalance(
            $customerGiftBackBalance,
            $quote,
            $totalDiscountAmount
        );
    }
}
