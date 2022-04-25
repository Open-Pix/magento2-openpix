<?php

namespace OpenPix\Pix\Model\Pix;

use Magento\Sales\Model\Order;

/**
 * Class Payment Pix
 *
 * @see       https://www.openpix.com.br Official Website
 * @author    OpenPix (and others) <hi@openpix.com.br>
 * @copyright https://www.openpix.com.br
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   OpenPix\Pix\Model
 */
class Pix extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const CODE = 'openpix_pix';

    protected $_code = self::CODE;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;
    protected $_storeManager;

    /**
     *
     */
    const LOG_NAME = 'pix_checkout';

    /**
     * @var \Magento\Framework\Message\ManagerInterface;
     */
    protected $messageManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \OpenPix\Pix\Helper\Data $helper,
        \OpenPix\Pix\Helper\Coupon $coupon,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Cart $cart,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_helperData = $helper;
        $this->_coupon = $coupon;
        $this->_storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->cart = $cart;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        if (!$this->_helperData->getOpenPixEnabled()) {
            return false;
        }
        return true;
    }

    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    function get_amount_openpix($total)
    {
        try {
            return $this->_helperData->absint(
                $this->_helperData->format_decimal((float) $total * 100, 2)
            ); // In cents.
        } catch (\Exception $e) {
            $this->_helperData->log(
                'Pix::get_amount_openpix - Error while converting from double to int (cents)',
                self::LOG_NAME,
                'total: ' . $total
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return $this;
    }

    public function getTaxID($taxID)
    {
        $isValidCPF = $this->_helperData->validateCPF($taxID);
        $isValidCNPJ = $this->_helperData->validateCNPJ($taxID);

        if ($isValidCPF || $isValidCNPJ) {
            return $taxID;
        }

        return null;
    }

    // get customer from billing
    public function getCustomerGuestData($order)
    {
        $billing = $order->getBillingAddress();

        $taxID = $billing->getVatId();
        $taxIDSafe = $this->getTaxID($taxID);

        $firstname = $billing->getFirstname();
        $lastname = $billing->getLastname();
        $email = $billing->getEmail();
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

    // get customer guest or from order if logged in
    public function getCustomerData($order)
    {
        $isCustomerGuest = $order->getCustomerIsGuest();

        if ($isCustomerGuest) {
            $customerGuest = $this->getCustomerGuestData($order);
            return $customerGuest;
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

    public function getPayload($order, $correlationID)
    {
        $grandTotal = $order->getGrandTotal();
        $storeName = $this->getStoreName();
        $customer = $this->getCustomerData($order);

        $orderId = $order->getIncrementId();

        $additionalInfo = [
            [
                'key' => __('Pedido'),
                'value' => $orderId,
            ],
        ];
        $comment = substr("$storeName", 0, 100) . '#' . $orderId;
        $comment_trimmed = substr($comment, 0, 140);
        if (!$customer) {
            return [
                'correlationID' => $correlationID,
                'value' => $this->get_amount_openpix($grandTotal),
                'comment' => $comment_trimmed,
                'additionalInfo' => $additionalInfo,
            ];
        }

        return [
            'correlationID' => $correlationID,
            'value' => $this->get_amount_openpix($grandTotal),
            'comment' => $comment_trimmed,
            'customer' => $customer,
            'additionalInfo' => $additionalInfo,
        ];
    }

    public function order(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount,
        \Magento\Quote\Api\Data\CartInterface $cart = null
    ) {
        try {
            $this->_helperData->log(
                'Pix::initialize - Start create charge at OpenPix',
                self::LOG_NAME
            );

            $order = $payment->getOrder();

            $correlationID = $this->_helperData->uuid_v4();

            $payload = $this->getPayload($order, $correlationID);

            $this->_helperData->debugJson('Payload ', self::LOG_NAME, $payload);

            $response = (array) $this->handleCreateCharge($payload);

            if (isset($response['errors'])) {
                $arrayLog = [
                    'response' => $response,
                    'message' => [$response['errors']],
                ];

                $this->messageManager->addErrorMessage(
                    __('Error creating Pix')
                );
                $this->messageManager->addErrorMessage($response['errors']);
                $this->_helperData->log(
                    'Pix::ResponseError - Error while creating OpenPix Charge',
                    self::LOG_NAME,
                    $arrayLog
                );
                throw new \Exception($response['errors'], 1);
            }

            $this->_helperData->debugJson(
                'Pix::ResponseSuccess - Response Payload ',
                self::LOG_NAME,
                $response
            );

            $charge = $response['charge'];

            $paymentLinkUrl = $charge['paymentLinkUrl'];
            $qrCodeImage = $charge['qrCodeImage'];
            $brCode = $response['brCode'];

            $order->setOpenpixCorrelationid($correlationID);
            $order->setOpenpixPaymentlinkurl($paymentLinkUrl);
            $order->setOpenpixQrcodeimage($qrCodeImage);
            $order->setOpenpixBrcode($brCode);

            $orderId = $order->getIncrementId();

            $couponCode = $this->_coupon->createRule(
                intval($charge['giftbackAppliedValue']),
                $orderId
            );

            $quote = $this->cart
                ->getQuote()
                ->setCouponCode($couponCode)
                ->collectTotals()
                ->save();

            $message = __(
                'New Order placed, QrCode Pix generated and saved on OpenPix Platform'
            );
            $status = $this->_helperData->getOrderStatus();

            $order
                ->setStatus($status)
                ->setState(Order::STATE_NEW)
                ->addStatusHistoryComment($message->getText());

            $payment->setSkipOrderProcessing(true);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error creating Pix'));
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_helperData->log(
                'Pix::Error - Error while creating charge',
                self::LOG_NAME,
                $e->getMessage()
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return $this;
    }

    public function handleCreateCharge($data)
    {
        try {
            $curl = curl_init();

            $app_ID = $this->_helperData->getAppID();

            if (!$app_ID) {
                $this->messageManager->addErrorMessage(__('Missing AppID'));
                throw new \Exception('Missing AppID', 1);
            }

            $apiUrl = $this->_helperData->getOpenPixApiUrl();

            $this->_helperData->log('API URL ', self::LOG_NAME, $apiUrl);

            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl . '/api/openpix/v1/charge',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
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
                $this->messageManager->addErrorMessage(
                    __('Error creating Pix')
                );
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
                $this->_helperData->log(
                    'Invalid appID',
                    self::LOG_NAME,
                    json_encode(
                        curl_getinfo($curl),
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    )
                );
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

            $this->_helperData->log(
                'API response ',
                self::LOG_NAME,
                $responseBody
            );

            return $responseBody;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error creating Pix'));
            $this->messageManager->addErrorMessage($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(
            'cpfCnpjCustomer',
            $data['additional_data']['cpfCnpj'] ?? null
        );
        return $this;
    }

    public function formatPhone($phone)
    {
        if (strlen($phone) > 11) {
            return preg_replace('/^0|\D+/', '', $phone);
        }

        return '55' . preg_replace('/^0|\D+/', '', $phone);
    }
}
