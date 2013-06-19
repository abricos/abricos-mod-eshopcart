<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';

class EShopCartManager extends Ab_ModuleManager {
	
	/**
	 * 
	 * @var EShopCartModule
	 */
	public $module = null;
	
	/**
	 * @var EShopCartManager
	 */
	public static $instance = null;
	
	/**
	 * Конфиг
	 * @var EShopCartConfig
	 */
	public $config = null;
	
	public function __construct($module){
		parent::__construct($module);

		EShopCartManager::$instance = $this;

		$this->config = new EShopCartConfig(Abricos::$config['module']['eshopcart']);
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(EShopCartAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(EShopCartAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(EShopCartAction::VIEW);
	}

	public function AJAX($d){
		
		if (intval($d->productaddtocart) > 0){
			$this->CartProductAdd($d->productaddtocart);
		}

		switch($d->do){
			case "initdata": return $this->InitDataToAJAX();

			case "cartproductlist": return $this->CartProductListToAJAX();
			case "productaddtocart": return $this->CartProductAddToAJAX($d->productid);
			
			case "paymentlist": return $this->PaymentListToAJAX();
			case "paymentsave": return $this->PaymentSaveToAJAX($d->savedata);
			case "paymentlistorder": return $this->PaymentListSetOrder($d->paymentorders);
			case "paymentremove": return $this->PaymentRemove($d->paymentid);

			case "deliverylist": return $this->DeliveryListToAJAX();
			case "deliverysave": return $this->DeliverySaveToAJAX($d->savedata);
			case "deliverylistorder": return $this->DeliveryListSetOrder($d->deliveryorders);
			case "deliveryremove": return $this->DeliveryRemove($d->deliveryid);
			
			case "discountlist": return $this->DiscountListToAJAX();
			case "discountsave": return $this->DiscountSaveToAJAX($d->savedata);
			case "discountremove": return $this->DiscountRemove($d->discountid);

			case "configadmin": return $this->ConfigAdminToAJAX();
			case "configadminsave": return $this->ConfigAdminSave($d->savedata);
		}

		return null;
	}
	
	public function InitDataToAJAX(){
		if (!$this->IsViewRole()){ return null; }
		
		$ret = new stdClass();

		$obj = $this->CartProductListToAJAX();
		$ret->cartproducts = $obj->cartproducts;
		
		$obj = $this->PaymentListToAJAX();
		$ret->payments = $obj->payments;

		$obj = $this->DeliveryListToAJAX();
		$ret->deliverys = $obj->deliverys;
		
		$obj = $this->DiscountListToAJAX();
		$ret->discounts = $obj->discounts;
		
		$obj = $this->ConfigAdminToAJAX();
		if (!empty($obj)){
			$ret->configadmin = $obj->configadmin;
		}
		
		return $ret;
	}
	
	/**
	 * Список товаров в корзине текущего пользователя
	 * @return EShopCartProductList
	 */
	public function CartProductList(){
		if (!$this->IsViewRole()){ return null; }
		
		$rows = EShopCartQuery::CartProductList($this->db, $this->user);
		
		$list = new EShopCartProductList();
		$checkDouble = array(); $isDouble = array();
		
		while (($d = $this->db->fetch_array($rows))){
			$item = new EShopCartProduct($d);
			
			$productid = $item->productid;
			if (empty($checkDouble[$productid])){
				$checkDouble[$productid] = $item;
				$list->Add($item);
			}else{
				$bItem = $checkDouble[$productid];
				$bItem->quantity += $item->quantity;
				$isDouble[$productid] = $bItem;
			}
		}
		
		if (count($isDouble) > 0){
			foreach($isDouble as $productid => $item){
				EShopCartQuery::CartProductUpdateDouble($this->db, $this->user, $item);
			}
		}
		
		if ($list->Count() > 0){
			Abricos::GetModule('eshop')->GetManager();
			$catMan = EShopManager::$instance->cManager;
			$cfg = new CatalogElementListConfig();
			for ($i=0;$i<$list->Count();$i++){
				$item = $list->GetByIndex($i);
				array_push($cfg->elids, $item->productid);
			}
			$list->productList = $catMan->ProductList($cfg);
		}
				
		return $list;
	}
	
	public function CartProductListToAJAX(){
		$list = $this->CartProductList();
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->cartproducts = $list->ToAJAX();
		return $ret;
	}
	
	/**
	 * Добавить продукт в корзину
	 * @param integer $productid
	 */
	public function CartProductAdd($productid, $quantity = 1){
		if (empty($productid) || !$this->IsWriteRole()){ return null; }
		
		Abricos::GetModule('eshop')->GetManager();
		$catMan = EShopManager::$instance->cManager;
		
		$product = $catMan->Element($productid);
		if (empty($product)){ return null; }
		
		$price = $product->detail->optionsBase['price'];
		
		EShopCartQuery::CartProductAppend($this->db, $this->user, $productid, $quantity, $price);
	}
	
	public function CartProductAddToAJAX($productid){
		$this->CartProductAdd($productid);
		
		return $this->CartProductListToAJAX();
	}
	
	/**
	 * @return EShopCartPaymentList
	 */
	public function PaymentList(){
		if (!$this->IsViewRole()){ return null; }
		
		$list = new EShopCartPaymentList();
		$rows = EShopCartQuery::PaymentList($this->db);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new EShopCartPayment($d));
		}
		return $list;
	}
	
	public function PaymentListToAJAX(){
		$list = $this->PaymentList();
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->payments = $list->ToAJAX();
		return $ret;
	}
	
	/**
	 * @param object $sd
	 * @return EShopCartPayment
	 */
	public function PaymentSave($sd){
		if (!$this->IsAdminRole()){ return null; }

		$paymentid = intval($sd->id);
		
		$utm  = Abricos::TextParser(true);
		$utm->jevix->cfgSetAutoBrMode(true);
		
		$utmf  = Abricos::TextParser(true);
		
		$sd->tl = $utmf->Parser($sd->tl);
		$sd->dsc = $utm->Parser($sd->dsc);

		if ($paymentid == 0){
			$paymentid = EShopCartQuery::PaymentAppend($this->db, $sd);
		}else{
			EShopCartQuery::PaymentUpdate($this->db, $paymentid, $sd);
		}
		
		if (!empty($sd->def)){
			EShopCartQuery::PaymentDefaultSet($this->db, $paymentid);
		}
		
		return $paymentid;
	}
	
	public function PaymentSaveToAJAX($sd){
		$paymentid = $this->PaymentSave($sd);
		
		if (empty($paymentid)){ return null; }
		
		$ret = $this->PaymentListToAJAX();
		$ret->paymentid = $paymentid;
		return $ret;
	}
	
	public function PaymentListSetOrder($orders){
		if (!$this->IsAdminRole()){ return null; }
	
		EShopCartQuery::PaymentListSetOrder($this->db, $orders);
	
		return true;
	}
	
	public function PaymentRemove($paymentid){
		if (!$this->IsAdminRole()){ return null; }
		
		EShopCartQuery::PaymentRemove($this->db, $paymentid);
		
		return true;
	}
	
	/**
	 * @return EShopCartDeliveryList
	 */
	public function DeliveryList(){
		if (!$this->IsViewRole()){ return null; }
		
		$list = new EShopCartDeliveryList();
		$rows = EShopCartQuery::DeliveryList($this->db);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new EShopCartDelivery($d));
		}
		return $list;
	}
	
	public function DeliveryListToAJAX(){
		$list = $this->DeliveryList();
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->deliverys = $list->ToAJAX();
		return $ret;
	}
	
	/**
	 * @param object $sd
	 * @return EShopCartDelivery
	 */
	public function DeliverySave($sd){
		if (!$this->IsAdminRole()){ return null; }

		$deliveryid = intval($sd->id);
		
		$utm  = Abricos::TextParser(true);
		$utm->jevix->cfgSetAutoBrMode(true);
		
		$utmf  = Abricos::TextParser(true);
		
		$sd->tl = $utmf->Parser($sd->tl);
		$sd->dsc = $utm->Parser($sd->dsc);

		if ($deliveryid == 0){
			$deliveryid = EShopCartQuery::DeliveryAppend($this->db, $sd);
		}else{
			EShopCartQuery::DeliveryUpdate($this->db, $deliveryid, $sd);
		}
		
		if (!empty($sd->def)){
			EShopCartQuery::DeliveryDefaultSet($this->db, $deliveryid);
		}
		
		return $deliveryid;
	}
	
	public function DeliverySaveToAJAX($sd){
		$deliveryid = $this->DeliverySave($sd);
		
		if (empty($deliveryid)){ return null; }
		
		$ret = $this->DeliveryListToAJAX();
		$ret->deliveryid = $deliveryid;
		return $ret;
	}
	
	public function DeliveryListSetOrder($orders){
		if (!$this->IsAdminRole()){ return null; }
	
		EShopCartQuery::DeliveryListSetOrder($this->db, $orders);
	
		return true;
	}
	
	public function DeliveryRemove($deliveryid){
		if (!$this->IsAdminRole()){ return null; }
		
		EShopCartQuery::DeliveryRemove($this->db, $deliveryid);
		
		return true;
	}

	
	/**
	 * @return EShopCartDiscountList
	 */
	public function DiscountList(){
		if (!$this->IsViewRole()){ return null; }
	
		$list = new EShopCartDiscountList();
		$rows = EShopCartQuery::DiscountList($this->db);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new EShopCartDiscount($d));
		}
		return $list;
	}
	
	public function DiscountListToAJAX(){
		$list = $this->DiscountList();
		if (empty($list)){ return null; }
	
		$ret = new stdClass();
		$ret->discounts = $list->ToAJAX();
		return $ret;
	}
	
	/**
	 * @param object $sd
	 * @return EShopCartDiscount
	 */
	public function DiscountSave($sd){
		if (!$this->IsAdminRole()){ return null; }
	
		$discountid = intval($sd->id);
	
		$utm  = Abricos::TextParser(true);
		$utm->jevix->cfgSetAutoBrMode(true);
	
		$utmf  = Abricos::TextParser(true);
	
		$sd->tl = $utmf->Parser($sd->tl);
		$sd->dsc = $utm->Parser($sd->dsc);
	
		if ($discountid == 0){
			$discountid = EShopCartQuery::DiscountAppend($this->db, $sd);
		}else{
			EShopCartQuery::DiscountUpdate($this->db, $discountid, $sd);
		}
	
		if (!empty($sd->def)){
			EShopCartQuery::DiscountDefaultSet($this->db, $discountid);
		}
	
		return $discountid;
	}
	
	public function DiscountSaveToAJAX($sd){
		$discountid = $this->DiscountSave($sd);
	
		if (empty($discountid)){ return null; }
	
		$ret = $this->DiscountListToAJAX();
		$ret->discountid = $discountid;
		return $ret;
	}
	
	public function DiscountRemove($discountid){
		if (!$this->IsAdminRole()){ return null; }
	
		EShopCartQuery::DiscountRemove($this->db, $discountid);
	
		return true;
	}
	
	public function ConfigAdminToAJAX(){
		if (!$this->IsAdminRole()){ return null; }
		
		$ret = new stdClass();
		$ret->configadmin = new stdClass();
		$ret->configadmin->emls = $this->EMailAdmin();
		
		return $ret;
	}

	private function EMailAdmin(){
		return Brick::$builder->phrase->Get('eshop', 'adm_emails');
	}
	
	public function ConfigAdminSave($sd){
		if (!$this->IsAdminRole()){ return null; }

		Brick::$builder->phrase->Set('eshop', 'adm_emails', $sd->emls);
		Brick::$builder->phrase->Save();
		
		return true;
	}
	

	public function ArrayToObject($o){
		if (is_array($o)){
			$ret = new stdClass();
			foreach($o as $key => $value){
				$ret->$key = $value;
			}
			return $ret;
		}else if (!is_object($o)){
			return new stdClass();
		}
		return $o;
	}	

	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){
				$ids1[$row[$fnids1]] = $row[$fnids1];
			}
			if (is_array($ids2)){
				$ids2[$row[$fnids2]] = $row[$fnids2];
			}
			if (is_array($ids3)){
				$ids3[$row[$fnids3]] = $row[$fnids3];
			}
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
}

?>