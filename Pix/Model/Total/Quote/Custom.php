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

    public function calculateDiscount(
        $giftbackBalance,
        $baseGrandTotal,
        $grandTotal
    ) {
        if ($giftbackBalance === 0) {
            return 0;
        }

        // the $giftbackBalance is in int
        // must be converted to double with two decimals cases
        $giftbackBalanceRounded = round(abs($giftbackBalance / 100), 2);

        // if the $giftbackBalanceRounded is bigger the total value of quote must apply the enough to rest R$ 0.01 cents
        if ($giftbackBalanceRounded > $baseGrandTotal) {
            return round($baseGrandTotal - 0.01, 2);
        }

        // if the $giftbackBalanceRounded is less than quote total apply it fully
        $discount = $baseGrandTotal - $giftbackBalanceRounded;

        // return the discount calculated
        return round($discount, 2);
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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get(
            'Magento\Customer\Model\Session'
        );

        parent::collect($quote, $shippingAssignment, $total);

        // check if customer is logged
        if (!$customerSession->isLoggedIn()) {
            $this->_helperData->log(
                'Pix::collect - customer not logged',
                self::LOG_NAME
            );
            // customer login action
            return null;
        }

        // check is customer has taxvat (taxID)
        $customerTaxVat = $quote->getCustomerTaxvat();

        $this->_helperData->log(
            'Pix::collect - customer taxvat ' . json_encode($customerTaxVat),
            self::LOG_NAME
        );

        if (!$customerTaxVat) {
            $this->_helperData->log(
                'Pix::collect - customer does not have taxID',
                self::LOG_NAME
            );
            return null;
        }

        $curl = curl_init();

        $app_ID = $this->_helperData->getAppID();

        if (!$app_ID) {
            $this->messageManager->addErrorMessage(__('Missing AppID'));
            //            throw new \Exception('Missing AppID', 1);
        }

        $apiUrl = $this->_helperData->getOpenPixApiUrl();

        $this->_helperData->log('API URL ', self::LOG_NAME, $apiUrl);

        // getting customer giftback balance by taxID
        curl_setopt_array($curl, [
            CURLOPT_URL =>
                $apiUrl . '/api/openpix/v1/giftback/balance/' . $customerTaxVat,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $app_ID,
            ],
            CURLOPT_VERBOSE => true,
        ]);

        $response = curl_exec($curl);

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl) || $response === false) {
            $this->messageManager->addErrorMessage(__('Error creating Pix'));
            $this->_helperData->log(
                'Curl Error creating pix',
                self::LOG_NAME,
                json_encode(
                    curl_getinfo($curl),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            );

            curl_close($curl);

            //            throw new \Exception(
            //                'Erro ao criar Pix, tente novamente por favor',
            //                1
            //            );
        }

        curl_close($curl);

        if ($statusCode === 401) {
            $this->messageManager->addErrorMessage(__('Invalid AppID'));
            $this->_helperData->log('Invalid appID', self::LOG_NAME);

            //            throw new \Exception('AppID Inválido', 1);
        }

        $responseBody = json_decode($response, true);

        if ($statusCode !== 200) {
            $this->messageManager->addErrorMessage(
                __('Status code different from 200 ' . $statusCode)
            );
            $this->messageManager->addErrorMessage($responseBody);
            $this->_helperData->log(
                'Status code different from 200 ' . $statusCode,
                self::LOG_NAME,
                json_encode(
                    [$responseBody],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            );

            //            throw new \Exception(
            //                'Erro ao criar Pix, tente novamente por favor',
            //                1
            //            );
        }

        $this->_helperData->log(
            'Pix::collect - response giftback ' . json_encode($responseBody),
            self::LOG_NAME
        );

        $giftbackBalance = $responseBody['balance'];

        $this->_helperData->log(
            'Pix::collect - giftback balance ' . $giftbackBalance,
            self::LOG_NAME
        );

        if ($giftbackBalance <= 0) {
            $this->_helperData->log(
                'Pix::collect - customer does not have balance. Balance: ' .
                    $giftbackBalance,
                self::LOG_NAME
            );
            return null;
        }

        // calculate the discount
        // the calc is -> quote total - giftback
        // must pass the total of quote to the function
        $baseDiscount = $this->calculateDiscount($giftbackBalance);

        // applying the discount calculated
        $discount = $this->_priceCurrency->convert($baseDiscount);

        $total->addTotalAmount('customdiscount', -$discount);
        $total->addBaseTotalAmount('customdiscount', -$baseDiscount);
        $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseDiscount);
        $quote->setCustomDiscount(-$discount);

        return $this;
    }
}
