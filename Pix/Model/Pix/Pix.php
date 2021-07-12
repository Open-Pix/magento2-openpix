<?php

namespace OpenPix\Pix\Model\Pix;

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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
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

    public function order(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    ) {
        try {
            $this->_helperData->log(
                'Pix::initialize - Start create charge at OpenPix',
                self::LOG_NAME
            );

            $order = $payment->getOrder();
            $grandTotal = $order->getGrandTotal();

            $correlationID = $this->_helperData->uuid_v4();

            $storeName = $this->getStoreName();

            $payload = [
                'correlationID' => $correlationID,
                'value' => $this->get_amount_openpix($grandTotal),
                'comment' => substr($storeName, 0, 140),
            ];

            $this->_helperData->log('Payload ', self::LOG_NAME, $payload);

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

            $this->_helperData->log(
                'Pix::ResponseSuccess - Response Payload',
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

            $this->_helperData->log(
                'Pix::Success - going to checkout success',
                self::LOG_NAME,
                $response
            );

            $message = __(
                'New Order placed, QrCode Pix generated and saved on OpenPix Platform'
            );

            $order
                ->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus(
                    $order
                        ->getConfig()
                        ->getStateDefaultStatus(
                            \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                        )
                )
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
            ]);

            $response = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if (curl_errno($curl) || $response === false) {
                $this->messageManager->addErrorMessage(
                    __('Error creating Pix')
                );

                curl_close($curl);
                throw new \Exception('Error creating Pix', 1);
            }

            curl_close($curl);

            if ($statusCode === 401) {
                $this->messageManager->addErrorMessage(__('Invalid AppID'));
                throw new \Exception('Invalid AppID', 1);
            }

            $responseBody = json_decode($response, true);

            if ($statusCode !== 200) {
                $this->messageManager->addErrorMessage(
                    __('Error creating Pix')
                );
                $this->messageManager->addErrorMessage($body);
                throw new \Exception('Error creating Pix', 1);
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
}
