<?php

namespace OpenPix\Pix\Block\Info;

class Pix extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'OpenPix_Pix::info/pix.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Open_Pix::info/pix.phtml');
        return $this->toHtml();
    }
}
