<?php
use Mage_Catalog_Model_Product as P;
use Mage_CatalogInventory_Model_Stock_Item as SI;
// 2019-10-30
final class Justuno_Jumagext_Catalog_Variants {
	/**
	 * 2019-10-30
	 * @used-by \Justuno_Jumagext_ResponseController::catalogAction
	 * @param P $p
	 * @return array(array(string => mixed))
	 */
	static function p(P $p) { /** @var array(array(string => mixed)) $r */
		if ('configurable' !== $p->getTypeId()) {
			$r = self::variant($p);
		}
		else {
			$ct = $p->getTypeInstance(); /** @var Mage_Catalog_Model_Product_Type_Configurable $ct */
			$opts = array_column($ct->getConfigurableAttributesAsArray($p), 'attribute_code', 'id');
			$r = array_values(array_map(function(P $c) use($opts, $p) {return
				self::variant($c, $p, $opts)
			;}, $ct->getUsedProducts(null, $p)));
		}
		return $r;
	}

	/**
	 * 2019-10-30
	 * @param P $p
	 * @param P|null $parent [optional]
	 * @param array(int => string) $opts [optional]
	 * @return array(string => mixed)
	 */
	private static function variant(P $p, P $parent = null, $opts = []) {
		// 2019-08-28 Otherwise $p does not contain the product's price
		$p = $p->load($p->getId()); /** @var P $p */
		$si = new SI; /** @var SI $si */
		$si->loadByProduct($p);
		$r = [
			'ID' => $p->getId()
			,'InventoryQuantity' => (int)$si->getQty()
			/**
			 * 2019-10-30
			 * 1) «MSRP, Price, SalePrice, Variants.MSRP, and Variants.SalePrice all need to be Floats,
			 * or if that is not possible then Ints»: https://github.com/justuno-com/m1/issues/10
			 * 2) «MSRP was null for some variants but the MSRP wasn't null for the parent»:
			 * https://github.com/justuno-com/m1/issues/7
			 * 3) «If their isn't an MSRP for some reason just use the salesprice»:
			 * https://github.com/justuno-com/m1/issues/6
			 */
			,'MSRP' => (float)($p['msrp'] ?: ($parent ? $parent['msrp'] : $p->getPrice()))
			// 2019-10-30
			// «MSRP, Price, SalePrice, Variants.MSRP, and Variants.SalePrice all need to be Floats,
			// or if that is not possible then Ints»: https://github.com/justuno-com/m1/issues/10
			,'SalePrice' => (float)$p->getPrice()
			,'SKU' => $p->getSku()
			,'Title' => $p->getName()
		];
		/**
		 * 2019-10-30
		 * «within the ProductResponse and the Variants OptionType is being sent back as OptionType90, 91, etc...
		 * We need these sent back starting at OptionType1, OptionType2»:
		 * https://github.com/justuno-com/m1/issues/14
		 */
		foreach (array_values($opts) as $id => $code) {
			$id++;
			$r["Option$id"] = $p->getAttributeText($code);
		}
		return $r;
	}
}