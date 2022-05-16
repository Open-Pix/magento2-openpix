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

        // @todo check if has user logged
        // @todo check if user logged has taxvat
        // @todo call openpix api to calculate the giftback discount passing: customer taxvat and total

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get(
            'Magento\Customer\Model\Session'
        );

        parent::collect($quote, $shippingAssignment, $total);

        if (!$customerSession->isLoggedIn()) {
            $this->_helperData->log(
                'Pix::collect - customer not logged',
                self::LOG_NAME
            );
            // customer login action
            return null;
        }

        $curl = curl_init();

        $app_ID = $this->_helperData->getAppID();

        if (!$app_ID) {
            $this->messageManager->addErrorMessage(__('Missing AppID'));
            throw new \Exception('Missing AppID', 1);
        }

        $apiUrl = $this->_helperData->getOpenPixApiUrl();

        $this->_helperData->log('API URL ', self::LOG_NAME, $apiUrl);

        $customerTaxVat = $quote->getCustomerTaxvat();

        if (!$customerTaxVat) {
            $this->_helperData->log(
                'Pix::collect - customer does not have taxID',
                self::LOG_NAME
            );
            return null;
        }

        $this->_helperData->log(
            'customer tax vai ' . json_encode($customerTaxVat),
            self::LOG_NAME,
            $apiUrl
        );

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

            throw new \Exception(
                'Erro ao criar Pix, tente novamente por favor',
                1
            );
        }

        curl_close($curl);

        if ($statusCode === 401) {
            $this->messageManager->addErrorMessage(__('Invalid AppID'));
            $this->_helperData->log('Invalid appID', self::LOG_NAME);

            throw new \Exception('AppID Inválido', 1);
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

            throw new \Exception(
                'Erro ao criar Pix, tente novamente por favor',
                1
            );
        }

        $this->_helperData->log('API response ', self::LOG_NAME, $responseBody);

        $this->_helperData->log(
            'Pix::collect - response giftback ' . $responseBody,
            self::LOG_NAME
        );

        $baseDiscount = 5;
        $discount = $this->_priceCurrency->convert($baseDiscount);

        $total->addTotalAmount('customdiscount', -$discount);
        $total->addBaseTotalAmount('customdiscount', -$baseDiscount);
        $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseDiscount);
        $quote->setCustomDiscount(-$discount);

        return $this;
    }
}
