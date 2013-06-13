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
			ORDER BY ord DESC
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
		
	public static function PaymentDefaultSet(Ab_Database $db, $paymentid){
		$sql = "
			UPDATE ".$db->prefix."eshp_payment
			SET def=0
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
	
	public static function PaymentListSetOrder(Ab_Database $db, $orders){
		if (count($orders) == 0){ return; }

		for ($i=0; $i<count($orders); $i++){
			$di = $orders[$i];
			$sql = "
				UPDATE ".$db->prefix."eshp_payment
				SET ord=".bkint($di->o)."
				WHERE paymentid=".bkint($di->id)."
				LIMIT 1
			";
			$db->query_write($sql);
		}
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
			WHERE deldate=0
		";
		return $db->query_read($sql);
	}
	
	public static function DeliveryAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_delivery
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
	
	public static function DeliveryUpdate(Ab_Database $db, $deliveryid, $d){
		$sql = "
			UPDATE ".$db->prefix."eshp_delivery
			SET
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."',
				def=".bkint($d->def)."
			WHERE deliveryid=".bkint($deliveryid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function DeliveryDefaultSet(Ab_Database $db, $deliveryid){
		$sql = "
			UPDATE ".$db->prefix."eshp_delivery
			SET def=0
		";
		$db->query_write($sql);
	
		$sql = "
			UPDATE ".$db->prefix."eshp_delivery
			SET def=1
			WHERE deliveryid=".bkint($deliveryid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function DeliveryListSetOrder(Ab_Database $db, $orders){
		if (count($orders) == 0){ return; }
	
		for ($i=0; $i<count($orders); $i++){
			$di = $orders[$i];
			$sql = "
				UPDATE ".$db->prefix."eshp_delivery
				SET ord=".bkint($di->o)."
				WHERE deliveryid=".bkint($di->id)."
				LIMIT 1
			";
			$db->query_write($sql);
		}
	}
	
	public static function DeliveryRemove(Ab_Database $db, $deliveryid){
		$sql = "
			UPDATE ".$db->prefix."eshp_delivery
			SET deldate=".TIMENOW."
			WHERE deliveryid=".bkint($deliveryid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}

	/* * * * * * * * * * Discount * * * * * * * *  */
	
	public static function DiscountList(Ab_Database $db){
		$sql = "
			SELECT
				discountid as id,
				dtype as tp,
				title as tl,
				descript as dsc,
				price as pc,
				ispercent as ptp,
				fromsum as fsm,
				endsum as esm,
				disabled as dis
			FROM ".$db->prefix."eshp_discount
			WHERE deldate=0
		";
		return $db->query_read($sql);
	}
	
	public static function DiscountAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_discount
			(dtype, title, descript, price, ispercent, fromsum, endsum, disabled, dateline) VALUES (
				".bkint($d->tp).",
				'".bkstr($d->tl)."',
				'".bkstr($d->dsc)."',
				".doubleval($d->pc).",
				".bkint($d->ptp).",
				".doubleval($d->fsm).",
				".doubleval($d->esm).",
				".bkint($d->dis).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function DiscountUpdate(Ab_Database $db, $discountid, $d){
		$sql = "
			UPDATE ".$db->prefix."eshp_discount
			SET
				dtype=".bkint($d->tp).",
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."',
				price=".doubleval($d->pc).",
				ispercent=".bkint($d->ptp).",
				fromsum=".doubleval($d->fsm).",
				endsum=".doubleval($d->esm).",
				disabled=".bkint($d->dis)."
			WHERE discountid=".bkint($discountid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function DiscountRemove(Ab_Database $db, $discountid){
		$sql = "
			UPDATE ".$db->prefix."eshp_discount
			SET deldate=".TIMENOW."
			WHERE discountid=".bkint($discountid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
		
}

?>