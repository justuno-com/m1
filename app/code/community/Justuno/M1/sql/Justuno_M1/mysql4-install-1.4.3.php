<?php
use Justuno_M1_DB as DB;
/** @var Mage_Core_Model_Resource_Setup $this */
$t_catalog_product_entity = DB::t('catalog_product_entity');
$t_catalog_product_super_link = DB::t('catalog_product_super_link');
$t_cataloginventory_stock_status = DB::t('cataloginventory_stock_status');
foreach (['insert', 'update'] as $e) {/** @var string $e */DB::conn()->query("
	CREATE TRIGGER justuno__cataloginventory_stock_status__$e AFTER $e ON $t_cataloginventory_stock_status
	FOR EACH ROW
	UPDATE $t_catalog_product_entity
	SET updated_at = CURRENT_TIMESTAMP()
	WHERE
		entity_id = NEW.product_id
		OR entity_id IN (SELECT parent_id FROM $t_catalog_product_super_link WHERE product_id = NEW.product_id)
	;
");}