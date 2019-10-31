<?php
// 2019-10-30
final class Justuno_Jumagext_Response {
	/**
	 * 2019-10-27
	 * @used-by Justuno_Jumagext_Catalog::p()
	 * @used-by Justuno_Jumagext_ResponseController::catalogFirstAction()
	 * @used-by Justuno_Jumagext_ResponseController::ordersAction()
	 */
	static function authorize() {
		if (!isset($_SERVER['DF_DEVELOPER'])) {
			$apitoken = Mage::getStoreConfig('justuno/justuno_settings/jutoken');
			$req_token = Mage::app()->getRequest()->getHeader('Authorization');
			if (empty($req_token)) {
				die('Token missing!');
			}
			if ($req_token !== $apitoken) {
				die('Token mismatched!');
			}
		}
	}

	/**
	 * 2019-10-30
	 * «if a property is null or an empty string do not send it back»:
	 * https://github.com/justuno-com/m1/issues/9
	 * @used-by filter()
	 * @used-by Justuno_Jumagext_Catalog::p()
	 * @param array(string => mixed) $a
	 * @return array(string => mixed)
	 */
	static function filter(array $a) {
		$r = []; /** @var array(string => mixed) $r */
		foreach ($a as $k => $v) { /** @var string $k */ /** @var mixed $v */
			if (!in_array($v, ['', null], true)) {
				$r[$k] = !is_array($v) ? $v : self::filter($v);
			}
		}
		return $r;
	}

	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_Catalog::p()
	 * @used-by Justuno_Jumagext_ResponseController::catalogFirstAction()
	 * @param string $path
	 * @return string
	 */
	static function url($path) {return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true) . $path;}
}