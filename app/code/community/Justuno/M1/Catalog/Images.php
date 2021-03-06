<?php
use Justuno_M1_Lib as L;
use Mage_Catalog_Model_Product as P;
use Varien_Object as O;
# 2019-10-30
final class Justuno_M1_Catalog_Images {
	/**
	 * 2019-10-30
	 * @used-by Justuno_M1_ResponseController::catalogAction()
	 * @param P $p
	 * @return array(array(string => mixed))
	 */
	static function p(P $p) { /** @var array(array(string => mixed)) $r */
		$r = [];
		$h = Mage::helper('catalog/image'); /** @var Mage_Catalog_Helper_Image $h */
		# 2019-20-31 «Faster way to load media images in a product collection»: https://magento.stackexchange.com/a/153570
		$p->getResource()->getAttribute('media_gallery')->getBackend()->afterLoad($p);
		$images = array_values($p->getMediaGalleryImages()->getItems()); /** @var O[] $images */
		# 2020-09-29
		# "Images with the «_hero_» string should have a priority in product feeds": https://github.com/justuno-com/m1/issues/47
		$f = function(O $i) {return (int)L::contains($i['file'], '_hero_');};
		usort($images, function(O $a, O $b) use($f) {return $f($b) - $f($a);});
		# 2019-10-30
		# «"ImageURL" should be "imageURL1" and we should have "imageURL2" and "ImageURL3"
		# if there are image available»: https://github.com/justuno-com/m1/issues/17
		foreach ($images as $idx => $i) {/** @var O $i */
			$idx++;
			# 2019-10-30
			# «the feed currently links to the large version of the first image only.
			# Could we change it to link to the small image?»: https://github.com/justuno-com/m1/issues/18
			$r["ImageURL$idx"] = (string)$h
				->init($p, 'image', $i['file'])
				->keepAspectRatio(true)
				->constrainOnly(true)
				->keepFrame(false)
				->resize(200, 200)
			;
		}
		return $r;
	}
}