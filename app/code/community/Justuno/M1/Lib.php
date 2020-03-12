<?php
use Exception as E;
// 2020-01-15
final class Justuno_M1_Lib {
	/**
	 * 2020-01-21
	 * @used-by Justuno_M1_CartController::product()
	 * @param mixed $cond
	 * @param null $m
	 * @return mixed
	 * @throws E
	 */
	static function assert($cond, $m = null) {return $cond ?: self::error($m);}

	/**
	 * 2020-03-13
	 * @used-by \Justuno_M1_Catalog_Variants::variant()
	 * @param boolean $v
	 * @return string
	 */
	static function bts($v) {return $v ? 'true' : 'false';}

	/**
	 * 2020-01-16 It formats $v as a value which can be used in the `var name = <?= df_ejs($v); ?>;` expression.
	 * @used-by js()
	 * @used-by app/design/frontend/base/default/template/justuno/m1.phtml
	 * @param mixed $v
	 * @return string
	 */
	static function ejs($v) {return !is_string($v) ? self::json_encode($v) : implode(
		str_replace("'", '\u0027', trim(json_encode($v), '"')), ["'", "'"]
	);}
	
	/**
	 * 2020-01-15
	 * @used-by app/design/frontend/base/default/template/justuno/m1.phtml
	 * @param string $k
	 * @param mixed $v
	 * @param bool $tag [optional]
	 * @return string
	 */
	static function js($k, $v, $tag = true) {
		$r = sprintf("window.$k = %s;", self::ejs($v)); /** @var string $r */
		return !$tag ? $r : "<script>$r</script>";
	}

	/**
	 * 2020-01-15
	 * @used-by ejs()
	 * @used-by Justuno_M1_Response::res()
	 * @param mixed $v
	 * @return string
	 */
	static function json_encode($v) {return json_encode($v,
		JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
	);}

	/**
	 * 2020-01-21
	 * @used-by reqI()
	 * @used-by Justuno_M1_Filter::byDate()
	 * @used-by Justuno_M1_Filter::p()
	 * @param string $k
	 * @param mixed|null $d [optional]
	 * @return string
	 */
	static function req($k, $d = null) {return Mage::app()->getRequest()->getParam($k, $d);}

	/**
	 * 2020-01-21
	 * @used-by Justuno_M1_CartController::addAction()
	 * @used-by Justuno_M1_CartController::product()
	 * @used-by Justuno_M1_Filter::p()
	 * @param string $k
	 * @param mixed|null $d [optional]
	 * @return string
	 */
	static function reqI($k, $d = null) {return (int)self::req($k, $d);}

	/**
	 * 2020-01-21
	 * @used-by assert()
	 * @param string|string[]|mixed|E|null ...$m
	 * @throws E
	 */
	private static function error(...$m) {throw $m instanceof E ? $m : new E(
		is_null($m) ? null : (is_array($m) ? implode("\n\n", $m) : sprintf(...$m))
	);}
}