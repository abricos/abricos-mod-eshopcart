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
			WHERE deldate=0
		";
		return $db->query_read($sql);
	}
	
	public static function PaymentAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_payment
			(title, descript, def, dateline) VALUES (
				'".bkstr($d->tl)."',
				'".bkstr($d->dsc)."',
				".bkint($d->def).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function PaymentUpdate(Ab_Database $db, $paymentid, $d){
		$sql = "
			UPDATE ".$db->prefix."eshp_payment
			SET
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."',
				def=".bkint($d->def)."
			WHERE paymentid=".bkint($paymentid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
		
	public static function PaymentDefaultSet(Ab_Database $db, $payemntid){
		$sql = "
			UPDATE ".$db->prefix."eshp_payment
			SET def=0
			WHERE paymentid<>".bkint($paymentid)."
		";
		$db->query_write($sql);
		
		$sql = "
			UPDATE ".$db->prefix."eshp_payment
			SET def=1
			WHERE paymentid=".bkint($paymentid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function PaymentRemove(Ab_Database $db, $paymentid){
		$sql = "
			UPDATE ".$db->prefix."eshp_payment
			SET deldate=".TIMENOW."
			WHERE paymentid=".bkint($paymentid)."
			LIMIT 1
		";
		$db->query_write($sql);
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