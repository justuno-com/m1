<?php 
class Justuno_Jumagext_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('justuno/system/config/button.phtml'); 
    }
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
    
    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData( array(
            'id'        => 'justuno_button',
            'label'     => $this->helper('adminhtml')->__('Generate New Token'),
            'onclick'   => 'javascript:generateToken(); return false;'
        ) );
        return $button->toHtml();
    }
}
?>