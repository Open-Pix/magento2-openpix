<?php

namespace OpenPix\Pix\Api;

interface OpenPixManagementInterface
{
    public const OPENPIX_LOG_NAME = 'pix_collect';
    public const OPENPIX_DISCOUNT_CODE = 'openpix_discount';
    public const OPENPIX_CUSTOMER_BALANCE_SESSION = 'customer_openpix_data_session';

    public const GET_BALANCE_API = 'api/openpix/v1/giftback/balance';

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
     * Build getBalance API URL
     *
     * @param string $customerTaxId
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBalanceApiURL($customerTaxId);

    /**
     * Clear customer balance data in the session
     *
     * @return void
     */
    public function clearDataInCache();
}
