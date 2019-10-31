<?php
use Mage_Sales_Model_Order_Item as OI;
// 2019-10-31
final class Justuno_Jumagext_OI {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_Orders::p()
	 * @param OI $i
	 * @param bool $withTax [optional]
	 * @param bool $withDiscount [optional]
	 * @return float
	 */
	static function price(OI $i, $withTax = false, $withDiscount = false) {/** @var float $r */
		$r = floatval($withTax ? $i->getPriceInclTax() : $i->getPrice()) ?:
			($i->getParentItem() ? self::price($i->getParentItem(), $withTax) : .0)
		;
		/**
		 * 2017-09-30
		 * We should use @uses df_oqi_top(), because the `discount_amount` and `base_discount_amount` fields
		 * are not filled for the configurable children.
		 */
		return !$withDiscount ? $r : ($r - self::top($i)->getDiscountAmount() / self::qty($i));
	}

	/**
	 * 2019-10-31
	 * @used-by price()
	 * @param OI $i
	 * @return int
	 */
	private static function qty(OI $i) {return intval($i->getQtyOrdered());}

	/**
	 * 2019-10-31
	 * @used-by price()
	 * @param OI $i
	 * @return OI
	 */
	private static function top(OI $i) {return $i->getParentItem() ?: $i;}
}