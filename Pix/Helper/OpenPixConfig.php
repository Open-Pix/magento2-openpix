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

class OpenPixConfig
{
    const OPENPIX_ENV = 'production';

    public function __construct(
    ) {
    }

    public function getOpenPixApiUrl()
    {
        return 'https://api.openpix.com.br';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'https://plugin.openpix.com.br/v1/openpix.js';
    }
}
