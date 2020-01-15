<?php
use Justuno_M1_Settings as S;
// 2019-10-30
final class Justuno_M1_Response {
	/**
	 * 2019-11-20
	 * @used-by Justuno_M1_Catalog::p()
	 * @used-by Justuno_M1_Orders::p()
	 * @param \Closure $f
	 */
	static function p(\Closure $f) {/** @var array(string => mixed) $r */
		try {self::authorize(); $r = $f();}
		catch (\Exception $e) {$r = ['message' => $e->getMessage()];}
		self::res($r);
	}

	/**
	 * 2019-10-31
	 * @used-by Justuno_M1_Catalog::p()
	 * @param string $path
	 * @return string
	 */
	static function url($path) {return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true) . $path;}

	/**
	 * 2019-10-27
	 * @used-by p()
	 */
	private static function authorize() {
		if (!isset($_SERVER['DF_DEVELOPER'])) {
			if (!($t = Mage::app()->getRequest()->getHeader('Authorization'))) {
				die('Token missing!');
			}
			if ($t !== S::token()) {
				die('Token mismatched!');
			}
		}
	}

	/**
	 * 2019-10-30
	 * «if a property is null or an empty string do not send it back»: https://github.com/justuno-com/m1/issues/9
	 * @used-by filter()
	 * @used-by res()
	 * @param array(string => mixed) $a
	 * @return array(string => mixed)
	 */
	private static function filter(array $a) {
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
	 * @used-by p()
	 * @param mixed[] $a
	 */
	private static function res(array $a) {
		$r = Mage::app()->getResponse(); /** @var Zend_Controller_Response_Http $r */
		$r->clearHeaders()->setHeader('Content-type','application/json', true);
		$r->setBody(json_encode(self::filter($a), JSON_PRETTY_PRINT));
	}
}