<?php
final class Justuno_Jumagext_ResponseController extends Mage_Core_Controller_Front_Action {
	/**
	 * 2019-10-27
	 * /jumagext/response/catalog?pageSize=2&currentPage=1&sortProducts=entity_id&updatedSince=2014-01-01
	 * https://www.upwork.com/messages/rooms/room_e6b2d182b68bdb5e9bf343521534b1b6/story_2e22707221dd053eab677398848c8ea3
	 */
	function catalogAction() {Justuno_Jumagext_Catalog::p();}

	/** 2019-10-27 */
	function catalogFirstAction() {Justuno_Jumagext_CatalogFirst::p();}

	/** 2019-10-27 */
	function ordersAction() {Justuno_Jumagext_Orders::p();}
}