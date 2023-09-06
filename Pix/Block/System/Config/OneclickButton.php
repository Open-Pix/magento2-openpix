<?php

namespace OpenPix\Pix\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class OneclickButton extends Field
{
    protected $_template = 'OpenPix_Pix::system/config/OneclickButton.phtml';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element
            ->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }
    
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    
    public function getAjaxUrl()
    {
        return $this->getUrl('openpix_pix/system_config/prepareOneclick');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'id' => 'openpix_oneclick_button',
                'label' => __('Configure now with one click'),
            ]);
        
        return $button->toHtml();
    }
}
