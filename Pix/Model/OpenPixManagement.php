<?php

namespace OpenPix\Pix\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Constructor
     *
     * @param \OpenPix\Pix\Helper\Data                          $helperData
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\HTTP\Client\Curl               $curlClient
     * @param \Magento\Checkout\Model\Session                   $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Magento\Sales\Api\OrderRepositoryInterface       $orderRepository
     */
    public function __construct(
        \OpenPix\Pix\Helper\Data $helperData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\HTTP\Client\Curl $curlClient,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->helperData = $helperData;
        $this->priceCurrency = $priceCurrency;
        $this->curlClient = $curlClient;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
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

    /**
     * @inheritDoc
     */
    public function createCharge($quote, $order)
    {
        try {
            if (!$this->helperData->getOpenPixEnabled()) {
                return false;
            }

            if (empty($quote) || empty($order)) {
                throw new LocalizedException(__('Quote or Order is empty!'));
            }

            $customerOpenpixData = $this->loadDataFromCache();
            if (empty($customerOpenpixData)) {
                return false;
            }

            $headers = $this->prepareHeaders();
            $this->getCurlClient()->setHeaders($headers);
            $createChargeApiURL = $this->getCreateChargeApiURL();
            $payload = $this->preparePayloadForChargeAPI($order, $quote);

            $this->getCurlClient()->post(
                $createChargeApiURL,
                json_encode($payload)
            );

            $responseBody = $this->getCurlClient()->getBody();
            $this->helperData->log(
                'Pix::collect - response charge API: ' . $responseBody,
                self::OPENPIX_LOG_NAME
            );

            $responseStatus = $this->getCurlClient()->getStatus();
            if ($responseStatus === 401) {
                throw new LocalizedException(__('Invalid AppID'));
            }

            if ($responseStatus !== 200) {
                $this->helperData->log(
                    'Status code different from 200 ' . $responseStatus,
                    self::OPENPIX_LOG_NAME,
                    json_encode(
                        [$responseBody],
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    )
                );

                throw new LocalizedException(
                    __('Error creating Pix, please try again')
                );
            }

            $this->helperData->log(
                'API Charge Response ',
                self::OPENPIX_LOG_NAME,
                $responseBody
            );

            $response = json_decode($responseBody, true);
            $this->saveDataToOrder($order, $response);
            $this->clearDataInCache();
        } catch (\Exception $exception) {
            $this->helperData->log(
                'API CHARGE ERROR: ' . $exception->getMessage(),
                self::OPENPIX_LOG_NAME
            );
            return false;
        }

        return true;
    }

    /**
     * Get Create a charge API
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCreateChargeApiURL()
    {
        $apiUrl = $this->helperData->getOpenPixApiUrl();
        if (empty($apiUrl)) {
            throw new LocalizedException(__('The API URL is empty'));
        }

        $chargeApiURL = $apiUrl . '/' . self::CREATE_CHARGE_API;
        $this->helperData->log(
            'API CHARGE URL: ',
            self::OPENPIX_LOG_NAME,
            $apiUrl
        );

        return $chargeApiURL;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function preparePayloadForChargeAPI($order, $quote)
    {
        $orderId = $order->getIncrementId();
        $additionalInfo = [
            [
                'key' => __('Pedido'),
                'value' => $orderId,
            ],
        ];

        try {
            $storeName = $this->storeManager->getStore()->getName();
        } catch (\Exception $exception) {
            $storeName = '';
        }

        $comment = substr("$storeName", 0, 100) . '#' . $orderId;
        $comment = substr($comment, 0, 140);

        $correlationID = $this->helperData->uuid_v4();

        if ($order->getCustomerIsGuest()) {
            return [
                'correlationID' => $correlationID,
                'value' => $this->formatValueToOpenPix(
                    $order->getBaseGrandTotal()
                ),
                'comment' => $comment,
                'additionalInfo' => $additionalInfo,
            ];
        }

        $customer = $this->getCustomerData($order);
        return [
            'correlationID' => $correlationID,
            'value' => $this->formatValueToOpenPix($order->getBaseGrandTotal()),
            'comment' => $comment,
            'giftbackValueToApply' => $this->formatValueToOpenPix(
                $quote->getOpenpixDiscount()
            ),
            'additionalInfo' => $additionalInfo,
            'customer' => $customer,
        ];
    }

    /**
     * Format value before sending to OpenPix
     *
     * @param float $amount
     *
     * @return int
     */
    private function formatValueToOpenPix($amount)
    {
        return $this->helperData->absint($amount);
    }

    /**
     * Get customer data
     *
     * @param $order
     *
     * @return array|null
     */
    public function getCustomerData($order)
    {
        $isCustomerGuest = $order->getCustomerIsGuest();
        if ($isCustomerGuest) {
            return [];
        }

        $taxID = $order->getCustomerTaxvat();
        $taxIDSafe = $this->getTaxID($taxID);
        $billing = $order->getBillingAddress();
        $email = $order->getCustomerEmail();
        $firstname = $order->getCustomerFirstname();
        $lastname = $order->getCustomerLastname();
        $phone = $billing->getTelephone();

        if (!$taxIDSafe && !$email && !$phone) {
            return null;
        }

        if (!$taxIDSafe) {
            return [
                'name' => $firstname . ' ' . $lastname,
                'email' => $email,
                'phone' => $this->formatPhone($phone),
            ];
        }

        return [
            'name' => $firstname . ' ' . $lastname,
            'taxID' => $taxIDSafe,
            'email' => $email,
            'phone' => $this->formatPhone($phone),
        ];
    }

    /**
     * Get Tax ID
     *
     * @param string $taxID
     *
     * @return null | string
     */
    public function getTaxID($taxID)
    {
        $isValidCPF = $this->helperData->validateCPF($taxID);
        $isValidCNPJ = $this->helperData->validateCNPJ($taxID);

        if ($isValidCPF || $isValidCNPJ) {
            return $taxID;
        }

        return null;
    }

    /**
     * Format phone
     *
     * @param string $phone
     *
     * @return array|string|string[]|null
     */
    public function formatPhone($phone)
    {
        if (strlen($phone) > self::PHONE_LENGTH) {
            return preg_replace('/^0|\D+/', '', $phone);
        }

        return self::PHONE_PREFIX_NUMBER . preg_replace('/^0|\D+/', '', $phone);
    }

    /**
     * Save data from charge api to order
     *
     * @param array $data
     *
     * @return void
     */
    public function saveDataToOrder($order, $data)
    {
        try {
            if (empty($order) || empty($data)) {
                throw new LocalizedException(
                    __('Something was wrong with this action!')
                );
            }

            $charge = $data['charge'];

            $paymentLinkUrl = $charge['paymentLinkUrl'];
            $qrCodeImage = $charge['qrCodeImage'];
            $brCode = $data['brCode'];

            $order->setOpenpixCorrelationid($charge['correlationID']);
            $order->setOpenpixPaymentlinkurl($paymentLinkUrl);
            $order->setOpenpixQrcodeimage($qrCodeImage);
            $order->setOpenpixBrcode($brCode);

            $message = __(
                'New Order placed, QrCode Pix generated and saved on OpenPix Platform'
            );
            $status = $this->helperData->getOrderStatus();

            $order
                ->setStatus($status)
                ->setState(Order::STATE_NEW)
                ->addStatusHistoryComment($message->getText());

            $order->getPayment()->setSkipOrderProcessing(true);
            $this->orderRepository->save($order);
        } catch (\Exception $exception) {
            $this->helperData->log(
                'Pix::Error - Error while creating charge',
                self::OPENPIX_LOG_NAME,
                $exception->getMessage()
            );
        }
    }
}
