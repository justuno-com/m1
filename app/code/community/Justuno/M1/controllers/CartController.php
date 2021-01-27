<?php
use Exception as E;
use Justuno_M1_Lib as L;
use Justuno_M1_Response as R;
use Mage_Catalog_Model_Product as P;
use Mage_Checkout_Model_Cart as Cart;
use Mage_Checkout_Model_Session as Sess;
# 2020-01-20
final class Justuno_M1_CartController extends Mage_Core_Controller_Front_Action {
	/**
	 * 2020-01-20
	 * 1) "Implement the «add a configurable product to the cart» endpoint": https://github.com/justuno-com/m1/issues/38
	 * @see Mage_Checkout_CartController::addAction():
	 * https://github.com/OpenMage/magento-mirror/blob/1.9.4.3/app/code/core/Mage/Checkout/controllers/CartController.php#L203-L280
	 */
	function addAction() {R::p(function() {
		/**
		 * 2020-01-21
		 * @see Mage_Checkout_CartController::_initProduct()
		 * https://github.com/OpenMage/magento-mirror/blob/1.9.4.3/app/code/core/Mage/Checkout/controllers/CartController.php#L103-L120
		 */
		$p = self::product('product'); /** @var P $p */
		$params = ['product' => $p->getId(), 'qty' => L::reqI('qty', 1)];
		if ($p->isConfigurable()) {
			$ch = self::product('variant'); /** @var P $ch */
			$sa = []; /** @var array(int => int) $sa */
			/**
			 * 2020-01-27
			 * 1) In Magento 2, the \Magento\Catalog\Model\Product::getTypeInstance() method does not have arguments:
			 * https://github.com/magento/magento2/blob/2.0.0/app/code/Magento/Catalog/Model/Product.php#L628-L640
			 * It always returns a singleton:
			 * 1.1) \Magento\Catalog\Model\Product\Type::factory():
			 * https://github.com/magento/magento2/blob/2.0.0/app/code/Magento/Catalog/Model/Product/Type.php#L114-L135
			 * 1.2) \Magento\Catalog\Model\Product\Type\Pool::get()
			 * https://github.com/magento/magento2/blob/2.0.0/app/code/Magento/Catalog/Model/Product/Type/Pool.php#L31-L49
			 * 2) In Magento 1, the method has an optional $singleton argument with the default `false` value:
			 * @uses \Mage_Catalog_Model_Product::getTypeInstance()
			 * https://github.com/OpenMage/magento-mirror/blob/1.9.4.5/app/code/core/Mage/Catalog/Model/Product.php#L252-L275
			 */
			foreach ($p->getTypeInstance(true)->getConfigurableAttributesAsArray($p) as $a) {
				/** @var array(string => mixed) $a */
				$sa[(int)$a['attribute_id']] = $ch[$a['attribute_code']];
			}
			$params['super_attribute'] = $sa;
		}
		/**
		 * 2020-01-21
		 * @see Mage_Checkout_CartController::addAction()
		 * https://github.com/OpenMage/magento-mirror/blob/1.9.4.3/app/code/core/Mage/Checkout/controllers/CartController.php#L236-L250
		 */
		$cart = Mage::getSingleton('checkout/cart'); /** @var Cart $cart */
		$cart->addProduct($p, $params);
		$cart->save();
		$sess = Mage::getSingleton('checkout/session'); /** @var Sess $sess */
		$sess->setCartWasUpdated(true);
		Mage::dispatchEvent('checkout_cart_add_product_complete', [
			'product' => $p, 'request' => $this->getRequest(), 'response' => $this->getResponse()
		]);
	});}

	/**
	 * 2020-01-21
	 * @used-by addAction()
	 * @param string $k
	 * @return P
	 * @throws E
	 */
	private static function product($k) {
		$r = new P; /** @var P $r */
		$r['store_id'] = Mage::app()->getStore()->getId();
		$r->load(L::assert(L::reqI($k))); /** @var int $pid */
		L::assert($r->getId());
		return $r;
	}
}