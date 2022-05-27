<?php

namespace OpenPix\Pix\Api;

interface OpenPixManagementInterface
{
    public const OPENPIX_LOG_NAME = 'pix_collect';
    public const OPENPIX_DISCOUNT_CODE = 'openpix_discount';
    public const OPENPIX_CUSTOMER_BALANCE_SESSION = 'customer_openpix_data_session';
    public const PHONE_LENGTH = 11;
    public const PHONE_PREFIX_NUMBER = '55';

    public const GET_BALANCE_API = 'api/openpix/v1/giftback/balance';
    public const CREATE_CHARGE_API = 'api/openpix/v1/charge';

    /**
     * Get balance of customer from OpenPix API by customer Tax ID
     *
     * @param string $customerTaxId
     *
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerBalanceByTaxId($customerTaxId);

    /**
     * Calculate and convert the discount amount from customer balance and base grand total
     *
     * @param float $balance
     * @param \Magento\Quote\Model\Quote $quote
     * @param float $totalDiscountAmount
     *
     * @return float
     */
    public function calculateAndConvertBalance(
        $balance,
        $quote,
        $totalDiscountAmount
    );

    /**
     * Create a charge after placing order successfully
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function createCharge($quote, $order);

    /**
     * Build getBalance API URL
     *
     * @param string $customerTaxId
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBalanceApiURL($customerTaxId);

    /**
     * Get Create a charge API
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCreateChargeApiURL();

    /**
     * Save data from charge api to order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     *
     * @return void
     */
    public function saveDataToOrder($order, $data);

    /**
     * Clear customer balance data in the session
     *
     * @return void
     */
    public function clearDataInCache();
}
