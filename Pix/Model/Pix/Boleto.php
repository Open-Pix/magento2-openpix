<?php

namespace OpenPix\Pix\Model\Pix;

use Magento\Sales\Model\Order;
use Ramsey\Uuid\Uuid;

/**
 * Class Payment Boleto
 *
 * @see       https://www.openpix.com.br Official Website
 * @author    OpenPix (and others) <hi@openpix.com.br>
 * @copyright https://www.openpix.com.br
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   OpenPix\Pix\Model
 */
class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const CODE = 'openpix_boleto';

    protected $_code = self::CODE;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;
    protected $_storeManager;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    /**
     *
     */
    const LOG_NAME = 'boleto_checkout';

    /**
     * @var \Magento\Framework\Message\ManagerInterface;
     */
    protected $messageManager;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \OpenPix\Pix\Api\OpenPixManagementInterface
     */
    private $openPixManagement;

    private $_curl;

    protected $_infoBlockType = \OpenPix\Pix\Block\Info\Boleto::class;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \OpenPix\Pix\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \OpenPix\Pix\Api\OpenPixManagementInterface $openPixManagement,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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
        $this->_storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->quoteFactory = $quoteFactory;
        $this->openPixManagement = $openPixManagement;
        $this->_curl = $curl;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        if (!$this->_helperData->getBoletoEnabled()) {
            return false;
        }
        return true;
    }

    public function refund(
        \Magento\Payment\Model\InfoInterface $payment,
        $value
    ) {
        $appID = $this->_helperData->getAppID();

        if (empty($appID)) {
            $this->_helperData->log(
                'OpenPix: AppID not configured',
                self::LOG_NAME
            );

            return;
        }

        $valueInCents = round(floatval($value), 2) * 100;
        $chargeId = $payment
            ->getCreditmemo()
            ->getInvoice()
            ->getTransactionId();

        $baseUrl = $this->_helperData->getOpenPixApiUrl();
        $url = "$baseUrl/api/v1/charge/$chargeId/refund";

        $this->_curl->setOptions([
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $appID,
                'user-agent: Plugin-Magento2/' .
                $this->_helperData->getPluginVersion(),
            ],
        ]);

        $body = [
            'correlationID' => Uuid::uuid4(),
            'value' => $valueInCents,
        ];

        $this->_curl->post($url, json_encode($body));

        $status = $this->_curl->getStatus();
        $response = $this->_curl->getBody();

        $this->_helperData->log(
            "Refund Boleto: Status $status, Response: $response"
        );

        return $this;
    }

    private function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = '+' . $phone;

        $length = strlen($phone);

        if ($length === 12) {
            return $phone . '9';
        }

        if ($length === 11) {
            $prefix = substr($phone, 0, 3);
            $rest = substr($phone, 3);

            return "$prefix" . '9' . "$rest";
        }

        return $phone;
    }

    private function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function get_amount_openpix($value)
    {
        $value = round(floatval($value), 2) * 100;
        return (int) $value;
    }

    public function getCustomerData($order)
    {
        $billingAddress = $order->getBillingAddress();
        $taxId = $this->_helperData->getTaxId($order);

        if (!$taxId) {
            $this->_helperData->log(
                'Boleto::Error - Customer taxId not found',
                self::LOG_NAME
            );
            return;
        }

        $name =
            $billingAddress->getFirstName() .
            ' ' .
            $billingAddress->getLastName();
        $email =
            $order->getCustomerEmail() ?? ($billingAddress->getEmail() ?? '');
        $phone = $billingAddress->getTelephone() ?? '';

        $street = $billingAddress->getStreet();
        $streetName = isset($street[0]) ? $street[0] : '';
        $streetNumber = isset($street[1]) ? $street[1] : '';
        $neighborhood = isset($street[2]) ? $street[2] : '';
        $complement = isset($street[3]) ? $street[3] : '';

        $address = [
            'zipcode' => preg_replace(
                '/[^0-9]/',
                '',
                $billingAddress->getPostcode() ?? ''
            ),
            'street' => $streetName,
            'number' => $streetNumber,
            'neighborhood' => $neighborhood,
            'city' => $billingAddress->getCity() ?? '',
            'state' => $billingAddress->getRegionCode() ?? '',
            'complement' => $complement,
            'country' => $billingAddress->getCountryId() ?? 'BR',
        ];

        $isValidAddress = $this->isValidAddress($address);

        $customer = [
            'name' => $name,
            'taxID' => $taxId,
            'email' => $email,
            'phone' => $this->formatPhone($phone),
        ];

        if ($isValidAddress) {
            $customer['address'] = $address;
        }

        return $customer;
    }

    private function isValidAddress($address)
    {
        return !empty($address['zipcode']) &&
            !empty($address['street']) &&
            !empty($address['number']) &&
            !empty($address['neighborhood']) &&
            !empty($address['city']) &&
            !empty($address['state']) &&
            !empty($address['country']);
    }

    public function getPayload($order, $correlationID)
    {
        $grandTotal = $order->getGrandTotal();
        $storeName = $this->getStoreName();
        $customer = $this->getCustomerData($order);

        $orderId = $order->getIncrementId();
        $quoteId = $order->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);

        $value = $this->get_amount_openpix($grandTotal);

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
                'value' => $value,
                'comment' => $comment_trimmed,
                'additionalInfo' => $additionalInfo,
                'type' => 'BOLETO',
            ];
        }

        return [
            'correlationID' => $correlationID,
            'value' => $value,
            'comment' => $comment_trimmed,
            'customer' => $customer,
            'additionalInfo' => $additionalInfo,
            'type' => 'BOLETO',
        ];
    }

    public function order(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    ) {
        try {
            $this->_helperData->log(
                'Boleto::initialize - Start create charge at OpenPix',
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
                    __('Error creating Boleto')
                );
                $this->messageManager->addErrorMessage($response['errors']);
                $this->_helperData->log(
                    'Boleto::ResponseError - Error while creating OpenPix Charge',
                    self::LOG_NAME,
                    $arrayLog
                );

                throw new \Exception($response['errors'], 1);
            }

            $this->_helperData->debugJson(
                'Boleto::ResponseSuccess - Response Payload ',
                self::LOG_NAME,
                $response
            );

            $charge = $response['charge'];
            $paymentLinkUrl = $charge['paymentLinkUrl'];
            $qrCodeImage = $charge['qrCodeImage'];
            $brCode = $response['brCode'];

            // Store boleto-specific data
            $paymentMethods = isset($charge['paymentMethods'])
                ? $charge['paymentMethods']
                : [];
            $boletoData = isset($paymentMethods['boleto'])
                ? $paymentMethods['boleto']
                : [];

            $order->setOpenpixCorrelationid($correlationID);
            $order->setOpenpixPaymentlinkurl($paymentLinkUrl);
            $order->setOpenpixQrcodeimage($qrCodeImage);
            $order->setOpenpixBrcode($brCode);

            // Store boleto-specific information
            if (isset($boletoData['boletoBarcode'])) {
                $order->setData(
                    'openpix_boleto_barcode',
                    $boletoData['boletoBarcode']
                );
            }
            if (isset($boletoData['boletoDigitable'])) {
                $order->setData(
                    'openpix_boleto_digitable',
                    $boletoData['boletoDigitable']
                );
            }
            if (isset($boletoData['barcodeImage'])) {
                $order->setData(
                    'openpix_boleto_image',
                    $boletoData['barcodeImage']
                );
            }

            $orderId = $order->getIncrementId();

            $message = __(
                'New Order placed, Boleto generated and saved on OpenPix Platform'
            );
            $status = $this->_helperData->getOrderStatus();

            $order
                ->setStatus($status)
                ->setState(Order::STATE_NEW)
                ->addStatusHistoryComment($message->getText());

            $payment->setSkipOrderProcessing(true);
            $this->openPixManagement->clearDataInCache();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error creating Boleto'));
            $this->_helperData->log(
                'Boleto::Error - Error while creating charge',
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
                CURLOPT_URL => $apiUrl . '/api/v1/charge',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $app_ID,
                    'Content-Type: application/json',
                    'user-agent: Plugin-Magento2/' .
                    $this->_helperData->getPluginVersion(),
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            $responseData = json_decode($response, true);

            return $responseData;
        } catch (\Exception $e) {
            $this->_helperData->log(
                'Boleto::Error while creating charge',
                self::LOG_NAME,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
