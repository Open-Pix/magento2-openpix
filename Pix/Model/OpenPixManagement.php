<?php

namespace OpenPix\Pix\Model;

use Magento\Framework\Exception\LocalizedException;

class OpenPixManagement implements \OpenPix\Pix\Api\OpenPixManagementInterface
{
    /**
     * @var \OpenPix\Pix\Helper\Data;
     */
    private $helperData;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curlClient;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Constructor
     *
     * @param \OpenPix\Pix\Helper\Data                          $helperData
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\HTTP\Client\Curl               $curlClient
     * @param \Magento\Checkout\Model\Session                   $checkoutSession
     */
    public function __construct(
        \OpenPix\Pix\Helper\Data $helperData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\HTTP\Client\Curl $curlClient,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helperData = $helperData;
        $this->priceCurrency = $priceCurrency;
        $this->curlClient = $curlClient;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    private function getCurlClient()
    {
        return $this->curlClient;
    }

    /**
     * Save customer balance data to session
     *
     * @param array $data
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function saveDataToCache($data)
    {
        return $this->checkoutSession->setData(
            self::OPENPIX_CUSTOMER_BALANCE_SESSION,
            $data
        );
    }

    /**
     * Get customer balance data from session
     *
     * @return null | array
     */
    public function loadDataFromCache()
    {
        return $this->checkoutSession->getData(
            self::OPENPIX_CUSTOMER_BALANCE_SESSION
        );
    }

    /**
     * @inheritDoc
     */
    public function clearDataInCache()
    {
        $this->checkoutSession->setData(
            self::OPENPIX_CUSTOMER_BALANCE_SESSION,
            null
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerBalanceByTaxId($customerTaxId)
    {
        try {
            if (!$this->helperData->getOpenPixEnabled()) {
                return false;
            }

            $customerOpenpixData = $this->loadDataFromCache();
            if (
                !empty($customerOpenpixData) &&
                isset($customerOpenpixData['balance'])
            ) {
                return $customerOpenpixData['balance'];
            }

            if (empty($customerTaxId)) {
                $this->helperData->log(
                    __('Pix::collect - customer does not have taxID'),
                    self::OPENPIX_LOG_NAME
                );

                throw new LocalizedException(__('Customer does not have TAX'));
            }

            $headers = $this->prepareHeaders();
            $this->getCurlClient()->setHeaders($headers);
            $this->getCurlClient()->setOptions([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING => '',
            ]);

            $apiUrl = $this->getBalanceApiURL($customerTaxId);
            $this->getCurlClient()->get($apiUrl);

            $responseBody = $this->getCurlClient()->getBody();
            $this->helperData->log(
                'Pix::collect - response giftback ' . $responseBody,
                self::OPENPIX_LOG_NAME
            );

            $response = json_decode($responseBody, true);
            $this->saveDataToCache($response);

            return $response['balance'] ?? 0;
        } catch (\Exception $exception) {
            $this->helperData->log(
                $exception->getMessage(),
                self::OPENPIX_LOG_NAME
            );

            return 0;
        }
    }

    /**
     * Build getBalance API URL
     *
     * @param string $customerTaxId
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBalanceApiURL($customerTaxId)
    {
        $apiUrl = $this->helperData->getOpenPixApiUrl();
        if (empty($apiUrl)) {
            throw new LocalizedException(__('The API URL is empty'));
        }

        $getBalanceApiURL =
            $apiUrl . '/' . self::GET_BALANCE_API . '/' . $customerTaxId;
        $this->helperData->log(
            'API URL ',
            self::OPENPIX_LOG_NAME,
            $getBalanceApiURL
        );

        return $getBalanceApiURL;
    }

    /**
     * Prepare the headers for the getBalance API
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareHeaders()
    {
        $app_ID = $this->helperData->getAppID();
        if (empty($app_ID)) {
            throw new LocalizedException(__('The APP ID is not configured!'));
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $app_ID,
        ];
    }

    /**
     * @inheritDoc
     */
    public function calculateAndConvertBalance(
        $balance,
        $quote,
        $totalDiscountAmount
    ) {
        if ($balance == 0 || empty($quote)) {
            return 0;
        }

        $address = $quote->getShippingAddress();
        $totalAmount =
            $address->getBaseSubtotal() +
            $address->getShippingAmount() +
            $totalDiscountAmount;

        $formattedBalance = round(abs($balance / 100), 2);

        if ($formattedBalance > $totalAmount) {
            return round($totalAmount - 0.01, 2);
        }

        return $this->priceCurrency->convert($formattedBalance);
    }
}
