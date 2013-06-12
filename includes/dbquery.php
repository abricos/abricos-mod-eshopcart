<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class EShopCartQuery {
	
	public static function PaymentList(Ab_Database $db){
		$sql = "
			SELECT
				paymentid as id,
				title as tl,
				ord,
				disabled as dis,
				def
			FROM ".$db->prefix."eshp_payment
		";
		return $db->query_read($sql);
	}
	
	public static function DeliveryList(Ab_Database $db){
		$sql = "
			SELECT
				deliveryid as id,
				parentdeliveryid as pid,
				title as tl,
				ord as ord,
				price as pc,
				fromzero as zr
			FROM ".$db->prefix."eshp_delivery
		";
		return $db->query_read($sql);
	}
		
}

?>