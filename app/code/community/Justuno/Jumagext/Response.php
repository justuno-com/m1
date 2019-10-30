<?php
// 2019-10-30
final class Justuno_Jumagext_Response {
	/**
	 * 2019-10-30
	 * «if a property is null or an empty string do not send it back»:
	 * https://github.com/justuno-com/m1/issues/9
	 * @used-by filter()
	 * @used-by Justuno_Jumagext_ResponseController::catalogAction()
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
}