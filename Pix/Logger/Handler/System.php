<?php

namespace OpenPix\Pix\Logger\Handler;

use Monolog\Logger;

/**
 * OpenPix logger handler
 */
class System extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/openpix.log';
}
