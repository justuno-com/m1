<?php
use Mage_Adminhtml_Block_System_Config_Form_Field as _P;
use Mage_Adminhtml_Block_Widget_Button as B;
use Varien_Data_Form_Element_Abstract as E;
# 2019-11-26
final class Justuno_M1_Block_GenerateToken extends _P {
	/**
	 * 2019-11-26
	 * @override
	 * @see Mage_Core_Block_Template::_construct()
	 * @used-by Varien_Object::__construct()
	 */
    protected function _construct() {parent::_construct(); $this->setTemplate('justuno/button.phtml');}

    /**
	 * 2019-11-26
	 * @override
	 * @see _P::_getElementHtml()
	 * @used-by Mage_Adminhtml_Block_System_Config_Form_Field::render()
     * @param E $e
     * @return string
     */
    protected function _getElementHtml(E $e) {return $this->_toHtml();}
    
    /**
	 * 2019-11-26
     * @used-by app/design/adminhtml/default/default/template/justuno/button.phtml
     * @return string
     */
	protected function getButtonHtml() {return $this->getLayout()->createBlock(B::class, '', [
		'id' => 'justuno_button',
		'label' => $this->helper('adminhtml')->__('Generate New Token'),
		'onclick' => 'javascript:generateToken(); return false;'
	])->toHtml();}
}