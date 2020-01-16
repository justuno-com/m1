<?php
// 2020-01-15
final class Justuno_M1_Lib {
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
	 * @param mixed $v
	 * @return string
	 */
	private static function json_encode($v) {return json_encode($v,
		JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
	);}
}