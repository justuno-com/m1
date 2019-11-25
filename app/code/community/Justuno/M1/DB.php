<?php
use Mage_Core_Model_Resource as RC;
use Varien_Db_Adapter_Interface as IAdapter;
use Varien_Db_Adapter_Pdo_Mysql as Mysql;
use Varien_Db_Select as Select;
// 2019-11-07
final class Justuno_M1_DB {
	/**
	 * 2019-11-07
	 * @used-by Justuno_M1_Orders::stat()
	 * @used-by select()
	 * @return IAdapter|Mysql
	 */
	static function conn() {return self::res()->getConnection('read');}

	/**
	 * 2019-11-07
	 * @used-by Justuno_M1_Orders::stat()
	 * @return Select
	 */
	static function select() {return self::conn()->select();}

	/**
	 * 2019-11-07
	 * @used-by Justuno_M1_Orders::stat()
	 * @param string $s
	 * @return string
	 */
	static function t($s) {return self::res()->getTableName($s);}

	/**
	 * 2019-11-07
	 * @used-by conn()
	 * @used-by t()
	 * @return RC
	 */
	private static function res() {static $r; return $r ?: new RC;}
}


