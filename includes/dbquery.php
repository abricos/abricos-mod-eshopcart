<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class EShopCartQuery {
	
	/**
	 * Перенос корзины гостя зарегестрированному пользователю 
	 *
	 * @param Ab_Database $db
	 * @param integer $userid идентификатор пользователя, если авторизован
	 * @param string $session сессия пользователя, если гость
	 */
	public static function CartUserSessionFixed(Ab_Database $db, User $user){
		if ($user->id == 0){ return; }
		$userid = $user->id;
		$session = $user->session->key;
		$sql = "
			UPDATE ".$db->prefix."eshp_cart
			SET userid=".bkint($userid).", session=''
			WHERE userid=0 AND session='".bkstr($session)."'
		";
		$db->query_write($sql);
	}
	
	public static function CartProductList(Ab_Database $db, User $user){
		EShopCartQuery::CartUserSessionFixed($db, $user);
		
		$userid = $user->id;
		$session = $user->session->key;
		
		$sql = "
			SELECT
				cartid as id,
				productid as elid,
				quantity as qt,
				price as pc
			FROM ".$db->prefix."eshp_cart
			WHERE ".($userid > 0 ? "userid=".bkint($userid) : "session='".bkstr($session)."'")."
		";
		return $db->query_read($sql);
	}
	
	public static function CartProductAppend(Ab_Database $db, User $user, $productid, $quantity, $price){
		$userid = $user->id;
		$session = $user->id > 0 ? "" : $user->session->key;
	
		$sql = "
			INSERT INTO ".$db->prefix."eshp_cart
			(userid, session, productid, quantity, price, dateline) VALUES (
				".bkint($userid).",
				'".bkstr($session)."',
				".bkint($productid).",
				".intval($quantity).",
				".doubleval($price).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function CartProductRemove(Ab_Database $db, User $user, $productid){
		$userid = $user->id;
		$session = $userid > 0 ? "" : $user->session->key;
		
		$sql = "
			DELETE FROM ".$db->prefix."eshp_cart
			WHERE productid=".bkint($productid)."
				AND userid=".bkint($userid)." AND session='".bkstr($session)."'
		";
		$db->query_write($sql);
	}
	
	public static function CartClear(Ab_Database $db, User $user){
		$userid = $user->id;
		$sessionid = $user->session->key;
		
		$sql = "
			DELETE FROM ".$db->prefix."eshp_cart
			WHERE ".($userid > 0 ? "userid=".bkint($userid) : "session='".bkstr($sessionid)."'")."
		";
		$db->query_write($sql);
	}
	
	public static function CartProductUpdateDouble(Ab_Database $db, User $user, EShopCartProduct $cartProduct){
		
		$userid = $user->id;
		$session = $user->id > 0 ? "" : $user->session->key;
				
		$sql = "
			DELETE FROM ".$db->prefix."eshp_cart
			WHERE productid=".bkint($cartProduct->productid)."
				AND userid=".bkint($userid)." AND session='".bkstr($session)."'
		";
		$db->query_write($sql);
		
		EShopCartQuery::CartProductAppend($db, $user, $cartProduct->productid, $cartProduct->quantity, $cartProduct->price);
	}
	
	public static function OrderApppend(Ab_Database $db, $userid, $paymentid, $deliveryid, $ci, $ip){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_order
			(userid, deliveryid, paymentid, firstname, lastname, phone, adress, extinfo, ip, dateline) VALUES (
				".bkint($userid).",
				".bkint($deliveryid).",
				".bkint($paymentid).",
				'".bkstr($ci->fnm)."',
				'".bkstr($ci->lnm)."',
				'".bkstr($ci->ph)."',
				'".bkstr($ci->adr)."',
				'".bkstr($ci->dsc)."',
				'".bkstr($ip)."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function Order(Ab_Database $db, $orderid, $userid = -1){
		$sql = "
			SELECT
				o.orderid as id,
				o.userid as uid,
				o.deliveryid as delid,
				o.paymentid as payid,
				o.ip as ip,
				o.firstname as fnm,
				o.lastname as lnm,
				o.phone as ph,
				o.adress as adress,
				o.extinfo as dsc,
				o.status as st,
				sum(i.quantity) as qty,
				sum(i.quantity*i.price) as sm,
				o.dateline as dl
			FROM ".$db->prefix."eshp_order o
			LEFT JOIN ".$db->prefix."eshp_orderitem i ON o.orderid=i.orderid
			WHERE o.orderid=".bkint($orderid)."
			GROUP BY i.orderid
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function OrderItemAppend(Ab_Database $db, $orderid, $productid, $quantity, $price){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_orderitem
			(orderid, productid, quantity, price) VALUES (
				".bkint($orderid).",
				".bkint($productid).",
				".bkint($quantity).",
				".doubleval($price)."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	/**
	 * Получить список товаров конкретного заказа
	 */
	public static function OrderItemList(Ab_Database $db, $orderid, $userid = -1){
		$sql = "
			SELECT
				oi.orderitemid as id,
				oi.productid as elid,
				oi.quantity as qt,
				oi.price as pc
			FROM ".$db->prefix."eshp_orderitem oi
			WHERE oi.orderid=".bkint($orderid)." ".($userid>0?" AND oi.userid=".intval($userid):"")."
			GROUP BY oi.productid
		";
		return $db->query_read($sql);
	}
		
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
			(title, price, fromzero, descript, def, dateline) VALUES (
				'".bkstr($d->tl)."',
				".bkint($d->pc).",
				".bkint($d->zr).",
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
				price=".bkint($d->pc).",
				fromzero=".bkint($d->zr).",
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


class EShopQuery {
	
	public static function OrderConfigList(Ab_Database $db){
		$sql = "
			SELECT 
				ordercfgid as id,
				ord,
				cfgtype as tp,
				title as tl,
				input as it,
				output as ot
			FROM ".$db->prefix."eshp_ordercfg
			ORDER BY ord
		";
		return $db->query_read($sql);
	}
	
	public static function OrderConfigAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_ordercfg
			(ord, cfgtype, title, input, output) VALUES
			(
				".bkint($d->ord).",
				".bkint($d->tp).",
				'".bkstr($d->tl)."',
				'".bkstr($d->it)."',
				'".bkstr($d->ot)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function OrderConfigUpdate(Ab_Database $db, $d){
		$sql = "
			UPDATE ".$db->prefix."eshp_ordercfg
			SET
				ord=".bkint($d->ord).",
				cfgtype=".bkint($d->tp).",
				title='".bkstr($d->tl)."',
				input='".bkstr($d->it)."',
				output='".bkstr($d->ot)."'
			WHERE ordercfgid=".bkint($d->id)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function OrderConfigRemove(Ab_Database $db, $id){
		$sql = "
			DELETE FROM ".$db->prefix."eshp_ordercfg
			WHERE ordercfgid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function old_Order(Ab_Database $db, $orderid, $userid = -1){
		$sql = "
			SELECT 
				o.orderid as id,
				o.userid as uid,
				o.deliveryid as delid,
				o.paymentid as payid,
				o.ip as ip,
				o.firstname as fnm,
				o.lastname as lnm,
				o.phone as ph,
				o.adress as adress,
				o.extinfo as extinfo,
				o.status as st,
				sum(i.quantity) as qty,
				sum(i.quantity*i.price) as sm,
				o.dateline as dl
			FROM ".$db->prefix."eshp_order o
			LEFT JOIN ".$db->prefix."eshp_orderitem i ON o.orderid=i.orderid
			WHERE o.orderid=".bkint($orderid)." ".($userid>0?" AND o.userid=".intval($userid):"")."
			GROUP BY i.orderid
		";
		return $db->query_read($sql);
	}
		
	/**
	 * Получить список товаров конкретного заказа
	 */
	public static function old_OrderItemList(Ab_Database $db, $orderid, $userid = -1){
		$sql = "
			SELECT
				a.productid as id,
				SUM(a.quantity) as qty,
				a.price as pc,
				p.catalogid as catid,
				p.eltypeid as eltid,
				p.title as tl,
				p.name as nm
			FROM ".$db->prefix."eshp_orderitem a 
			INNER JOIN ".CatalogQuery::$PFX."element p ON a.productid = p.elementid
			INNER JOIN ".$db->prefix."eshp_order o ON o.orderid = a.orderid
			WHERE a.orderid=".bkint($orderid)." ".($userid>0?" AND o.userid=".intval($userid):"")."
			GROUP BY a.productid
		";
		return $db->query_read($sql);
	}
	
	public static function old_Orders(Ab_Database $db, $status, $page, $limit){
		$from = (($page-1)*$limit);
		
		// если $status=-1, то выбрать удаленные
		$where = $status < 0 ? "o.deldate>0" : "deldate < 1 AND o.status=".bkint($status); 
		$sql = "
			SELECT 
				o.orderid as id,
				o.firstname as fnm,
				o.lastname as lnm,
				o.userid as uid,
				o.adress as adr,
				o.ip as ip,
				sum(i.quantity*i.price) as sm,
				o.dateline as dl
			FROM ".$db->prefix."eshp_order o
			LEFT JOIN ".$db->prefix."eshp_orderitem i ON o.orderid=i.orderid
			WHERE ".$where." 
			GROUP BY i.orderid
			ORDER BY o.dateline DESC
			LIMIT ".intval($from).", ".intval($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function old_OrdersCount(Ab_Database $db, $status){
		// если $status=-1, то выбрать удаленные
		$where = $status < 0 ? "o.deldate>0" : "deldate < 1 AND o.status=".bkint($status); 
		$sql = "
			SELECT count(*) as cnt
			FROM ".$db->prefix."eshp_order o
			WHERE ".$where." 
			LIMIT 1
		";
		return $db->query_read($sql);
	}
	
	public static function OrderAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_order 
			(userid, deliveryid, paymentid, firstname, lastname, secondname, phone, adress, extinfo, ip, dateline) VALUES (
				".bkint($d->userid).",
				".bkint($d->deliveryid).",
				".bkint($d->paymentid).",
				'".bkstr($d->firstname)."',
				'".bkstr($d->lastname)."',
				'".bkstr($d->secondname)."',
				'".bkstr($d->phone)."',
				'".bkstr($d->adress)."',
				'".bkstr($d->extinfo)."',
				'".bkstr($d->ip)."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	/**
	 * Принять заказ на исполнение
	 * @param Ab_Database $db
	 * @param integer $orderid
	 */
	public static function old_OrderAccept(Ab_Database $db, $orderid){
		$sql = "
			UPDATE ".$db->prefix."eshp_order 
			SET status=".EShopCartManager::ORDER_STATUS_EXEC."
			WHERE orderid=".bkstr($orderid)."
		";
		$db->query_write($sql);
	}
	
	/**
	 * Выполнить заказ (закрытие)
	 * @param Ab_Database $db
	 * @param integer $orderid
	 */
	public static function old_OrderClose(Ab_Database $db, $orderid){
		$sql = "
			UPDATE ".$db->prefix."eshp_order 
			SET status=".EShopCartManager::ORDER_STATUS_ARHIVE."
			WHERE orderid=".bkstr($orderid)."
		";
		$db->query_write($sql);
	}
	
	/**
	 * Удалить заказ в корзину
	 * 
	 * @param Ab_Database $db
	 * @param integer $orderid
	 */
	public static function old_OrderRemove(Ab_Database $db, $orderid){
		$sql = "
			UPDATE ".$db->prefix."eshp_order 
			SET deldate=".TIMENOW."
			WHERE orderid=".bkstr($orderid)."
		";
		$db->query_write($sql);
	}
	
	public static function OrderItemAppend(Ab_Database $db, $orderid, $productid, $quantity, $price){
		$sql = "
			INSERT INTO ".$db->prefix."eshp_orderitem 
			(orderid, productid, quantity, price) VALUES (
				".bkint($orderid).",
				".bkint($productid).",
				".bkint($quantity).",
				".doubleval($price)."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	/**
	 * Добавить продукт в корзину
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid идентификатор пользователя, если авторизован
	 * @param string $session сессия пользователя, если гость
	 * @param integer $productid идентификатор товара
	 * @param integer $quantity кол-во
	 * @param double $price цена
	 */
	public static function old_CartAppend(Ab_Database $db, $userid, $session, $productid, $quantity, $price){
		
		$session = $userid > 0 ? '' : $session;
		
		$sql = "
			INSERT INTO ".$db->prefix."eshp_cart 
			(userid, session, productid, quantity, price, dateline) VALUES (
				".bkint($userid).",
				'".bkstr($session)."',
				".bkint($productid).",
				".intval($quantity).",
				".doubleval($price).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	/**
	 * Если пользователь зарегистрирован, необходимо перенести товар в
	 * корзине набранный будучи гостем
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid идентификатор пользователя, если авторизован
	 * @param string $session сессия пользователя, если гость
	 */
	public static function CartUserSessionFixed(Ab_Database $db, $userid, $session){
		if ($userid < 1){ return; }
		$sql = "
			UPDATE ".$db->prefix."eshp_cart 
			SET userid=".bkint($userid).", session=''
			WHERE userid=0 AND session='".bkstr($session)."'
		";
		$db->query_write($sql);
	}
	
	/**
	 * Получить информацию по корзине
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid идентификатор пользователя
	 * @param string $session сессия пользователя
	 */
	public static function old_CartInfo(Ab_Database $db, $userid, $session){
		EShopQuery::CartUserSessionFixed($db, $userid, $session);
		$sql = "
			SELECT 
				SUM(quantity) as qty,
				SUM(quantity*price) as sm
			FROM ".$db->prefix."eshp_cart
			WHERE ".($userid > 0 ? "userid=".bkint($userid) : "session='".bkstr($session)."'")."
		";
		
		return $db->query_first($sql);
	}
	
	/**
	 * Получить полное содержание корзины
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid идентификатор пользователя
	 * @param string $session сессия пользователя
	 * @param integer $productid если указан, то возврат только этого продукта
	 */
	public static function old_Cart(Ab_Database $db, $userid, $session, $productid = 0){
		$productid = bkint($productid);
		$sql = "
			SELECT
				a.productid as id,
				SUM(a.quantity) as qty,
				a.price as pc,
				p.catalogid as catid,
				p.eltypeid as eltid,
				p.title as tl,
				p.name as nm
			FROM ".$db->prefix."eshp_cart a 
			INNER JOIN ".CatalogQuery::$PFX."element p ON a.productid = p.elementid
			WHERE ".($userid > 0 ? "a.userid=".bkint($userid) : "a.session='".bkstr($session)."'")."
			".($productid > 0 ? " AND a.productid=".$productid : "")."
			GROUP BY a.productid
		";
		return $db->query_read($sql);
	}
	
	public static function CartClear(Ab_Database $db, $userid, $sessionid){
		$userid = intval($userid);
		$sql = "
			DELETE FROM ".$db->prefix."eshp_cart 
			WHERE ".($userid > 0 ? "userid=".bkint($userid) : "session='".bkstr($sessionid)."'")."
		";
		$db->query_write($sql); 
	}
	
	public static function old_CartRemove(Ab_Database $db, $productid = 0){
		$sql = "
			DELETE FROM ".$db->prefix."eshp_cart
			WHERE productid=".bkint($productid)."
		";
		return $db->query_write($sql);
	}
}



?>