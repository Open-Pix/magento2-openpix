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
        array $data = []
    )
    {
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
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
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
                $this->_helperData->format_decimal((float)$total * 100, 2)
            ); // In cents.
        } catch (\Exception $e) {
            $this->_helperData->log('Pix::get_amount_openpix - Error while converting from double to int (cents)', self::LOG_NAME, "total: " . $total);
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_helperData->log('Pix::initialize - Start create charge at OpenPix', self::LOG_NAME);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
            $grandTotal = $cart->getQuote()->getGrandTotal();

            $order = $payment->getOrder();

            $correlationID = $this->_helperData->uuid_v4();

            $payload = [
                'correlationID' => $correlationID,
                'value' => $this->get_amount_openpix($grandTotal),
                'comment' => "Magento2 Charge - " . $correlationID . " - " . $this->getStoreName(),
            ];

            $response = (array)$this->handleCreateCharge($payload);

            if (isset($response["errors"])) {
                throw new \Exception($response['errors'], 1);

                $arrayLog = [
                    'response' => $response,
                    'message' => array($response["errors"]),
                ];

                $this->_helperData->log('Pix::ResponseError - Error while creating OpenPix Charge', self::LOG_NAME, $response);
                throw new \Exception($response['errors'], 1);
            }

            $this->_helperData->log('Pix::ResponseSuccess - Response Payload', self::LOG_NAME, $response);

            $charge = $response['charge'];

            $paymentLinkUrl = $charge["paymentLinkUrl"];
            $qrCodeImage = $charge["qrCodeImage"];
            $brCode = $response["brCode"];

            $order->setOpenpixCorrelationid($correlationID);
            $order->setOpenpixPaymentlinkurl($paymentLinkUrl);
            $order->setOpenpixQrcodeimage($qrCodeImage);
            $order->setOpenpixBrcode($brCode);

            $this->_helperData->log('Pix::Success - going to checkout success', self::LOG_NAME, $response);

            $payment->setSkipOrderProcessing(true);
            return true;
        } catch (\Exception $e) {
            $this->_helperData->log('Pix::Error - Error while creating charge', self::LOG_NAME, $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    public function handleCreateCharge($data)
    {
        try {
            $curl = curl_init();

            $app_ID = $this->_helperData->getAppID();

            if (!$app_ID) {
                throw new \Exception("AppID undefined", 1);
            }

            $apiUrl = $this->_helperData->getOpenPixApiUrl();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiUrl . '/api/openpix/v1/charge',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Authorization: " . $app_ID,
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('cpfCnpjCustomer', $data['additional_data']['cpfCnpj'] ?? null);
        return $this;
    }
}
