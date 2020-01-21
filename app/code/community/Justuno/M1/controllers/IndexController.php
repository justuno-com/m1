<?php
final class Justuno_M1_IndexController extends Mage_Core_Controller_Front_Action {
	/**
	 * 2019-11-26
	 * @used-by app/design/frontend/base/default/template/justuno/m1.phtml
	 */
	function getcartAction(){
		$res = $this->getResponse(); /** @var Mage_Core_Controller_Response_Http $res */
		$res->setHeader('Content-type','application/json', true);
		$res->setBody(Mage::helper('core')->jsonEncode(Mage::getModel('checkout/cart')->getItems()));
	}
}