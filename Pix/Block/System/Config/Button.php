<?php
namespace OpenPix\Pix\Block\System\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
class Button extends Field
{
    protected $_template = 'OpenPix_Pix::system/config/Button.phtml';
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    public function getAjaxUrl()
    {
        return  $this->getUrl('openpix_pix/system_config/button');
    }
    public function getButtonHtml()
    {
        $button = $this->getLayout()
                        ->createBlock('Magento\Backend\Block\Widget\Button')
                        ->setData(['id' => 'openpix_webhook_button', 'label' => __('Configure now with one click'),]);
        return $button->toHtml();
    }
}
