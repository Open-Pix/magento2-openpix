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
    const OPENPIX_ENV = 'development';

    public function __construct(
    ) {}

    public function getOpenPixApiUrl()
    {
        return 'http://host.docker.internal:5001';
//        return 'http://localhost:5001';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'http://localhost:4444/openpix.js';
    }
}
