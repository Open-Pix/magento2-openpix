<?php

namespace OpenPix\Pix\Helper;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class OpenPixConfig extends AbstractHelper
{
    const OPENPIX_ENV = 'development';

    /**
     * Data constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context,
    ) {
        parent::__construct($context);
    }

    public function getOpenPixApiUrl()
    {
//        return 'http://host.docker.internal:5001';
        return 'http://localhost:5001';
    }

    public static function getOpenPixPluginUrlScript(): string
    {
        return 'http://localhost:4444/openpix.js';
    }
}
