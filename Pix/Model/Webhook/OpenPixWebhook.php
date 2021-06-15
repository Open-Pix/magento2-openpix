<?php

namespace OpenPix\Pix\Model\Webhook;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;

class OpenPixWebhook {
    const MODULE_NAME = 'OpenPix_Pix';

    protected $_moduleList;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;


    const LOG_NAME = 'pix_webpapi';

    public function __construct(
        ModuleListInterface $moduleList,
        \OpenPix\Pix\Helper\Data $helper
    )
    {
        $this->_moduleList = $moduleList;
        $this->_helperData = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        $this->_helperData->log('OpenPix WebApi::GetVersion Start', self::LOG_NAME);

        $moduleVersion = $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];

        $this->_helperData->log('OpenPix WebApi::GetVersion $moduleVersion', self::LOG_NAME, $moduleVersion);

        return 'OpenPix Pix Extension Version ' . $moduleVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhook($charge, $pix)
    {
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Start', self::LOG_NAME);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Charge', self::LOG_NAME, $charge);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Pix Transaction', self::LOG_NAME, $pix);

        // @todo validate authorization

        // @todo validate if is webhook payload test

        // @todo validate if is a valid webhook payload

        // @todo prepare fields -> correlationID, status, endToEndId

        // @todo get order_id by correlationID

        // @todo get order by id

        // @todo check if order has correlation id, if have return error order already paid

        return 'api process webhook';
    }
}
