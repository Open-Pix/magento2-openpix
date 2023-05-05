<?php

namespace OpenPix\Pix\Helper\WebHookHandlers;

use Magento\Sales\Model\Order\Invoice;
use OpenPix\Pix\Helper\Data;

class ConfigureHandler
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $_helperData;

    const LOG_NAME = 'magento2-configure';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Data $_helperData
    ) {
        $this->logger = $logger;
        $this->_helperData = $_helperData;
    }

    /**
     * Handle 'magento2-configure' event.
     *
     * @param string $appID
     *
     * @return array
     */
    public function configure($appID)
    {
        $this->_helperData->log('OpenPix::configure Start', self::LOG_NAME);

        $alreadyHasAppID = !empty($this->_helperData->getAppID());

        if ($alreadyHasAppID) {
            $this->_helperData->log(
                'OpenPix::configure App ID already configured',
                self::LOG_NAME
            );

            return [
                'error' => 'App ID already configured',
                'success' => null,
            ];
        }

        $this->_helperData->setAppID($appID, true);

        $this->_helperData->log('OpenPix::configure Success', self::LOG_NAME);

        return [
            'error' => null,
            'success' => 'success',
        ];
    }
}
