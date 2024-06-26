<?php

namespace OpenPix\Pix\Model\Api;

use Magento\Framework\Module\ModuleListInterface;

class OpenPixApi
{
    const MODULE_NAME = 'OpenPix_Pix';

    protected $_moduleList;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    const LOG_NAME = 'pix_api';

    public function __construct(
        ModuleListInterface $moduleList,
        \OpenPix\Pix\Helper\Data $helper
    ) {
        $this->_moduleList = $moduleList;
        $this->_helperData = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        $this->_helperData->log(
            'OpenPix WebApi::GetVersion Start',
            self::LOG_NAME
        );

        $moduleVersion = $this->_moduleList->getOne(self::MODULE_NAME)[
            'setup_version'
        ];

        $this->_helperData->log(
            'OpenPix WebApi::GetVersion $moduleVersion',
            self::LOG_NAME,
            $moduleVersion
        );

        return 'OpenPix Pix Extension Version ' . $moduleVersion;
    }
}
