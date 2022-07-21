<?php

namespace OpenPix\Pix\Helper\Payment\Override;

use Magento\Framework\App\Area;
use Magento\Payment\Model\InfoInterface;

class Data extends \Magento\Payment\Helper\Data
{
    /**
     * Render payment information block
     *
     * @param InfoInterface $info
     * @param int $storeId
     * @return string
     * @throws Exception
     */
    public function getInfoBlockHtml(InfoInterface $info, $storeId)
    {
        $this->_appEmulation->startEnvironmentEmulation(
            $storeId,
            Area::AREA_FRONTEND,
            true
        );

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = $this->getInfoBlock($info);
            $paymentBlock->setArea(Area::AREA_FRONTEND)->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlock->setIsSendingEmail(1);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            $this->_appEmulation->stopEnvironmentEmulation();
            throw $exception;
        }

        $this->_appEmulation->stopEnvironmentEmulation();

        return $paymentBlockHtml;
    }
}
