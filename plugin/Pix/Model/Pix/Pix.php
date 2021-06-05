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
class Pix extends \Magento\Payment\Model\Method\AbstractMethod {
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
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        if (!$this->_helperData->getOpenPixEnabled()) {
            return false;
        }
        return true;
    }

    // @todo create a new charge
    // method preparing charge things
    // method creating charge

    // @openpix values
    // correlationID
    // identifier
    // value
    // comment

    public function get_openpix_amount($total)
    {
        return absint(
            wc_format_decimal((float) $total * 100, wc_get_price_decimals())
        ); // In cents.
    }

    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->_helperData->log('Pix::initialize - Start create charge at OpenPix');

        $info = $this->getInfoInstance();
        $order = $payment->getOrder();

        $payload = [
            'correlationID' => $order->getIncrementId(),
            'value' => $this->get_openpix_amount($order->getGrandAmount()),
            'comment' => $this->getStoreName(),
        ];


        $this->_helperData->log('Pix::Payload', $payload);

        $response = $this->handleCreateCharge($payload);

        $this->_helperData->log('Pix::Response', $response);

        $payment->setSkipOrderProcessing(true);
    }

    public function handleCreateCharge($data) {
        $curl = curl_init();

        $app_ID = $this->helperData->getAppID();

        $headers =[
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->app_ID,
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://localhost:5001/openpix/v1/charge',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ));
    }

}
