<?php 
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class EShopCartOrder extends AbricosItem {
	
	public $userid;
	public $deliveryid;
	public $paymentid;
	public $ip;
	public $firstName;
	public $lastName;
	public $phone;
	public $address;
	public $descript;
	public $status;
	public $quantity;
	public $sum;
	
	/**
	 * @var EShopCartProductList
	 */
	public $cartProductList = null;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->userid		= $d['uid'];
		$this->deliveryid	= $d['delid'];
		$this->paymentid	= $d['payid'];
		$this->ip			= $d['ip'];
		$this->firstName	= $d['fnm'];
		$this->lastName		= $d['lnm'];
		$this->phone		= $d['ph'];
		$this->address		= $d['adr'];
		$this->descript		= $d['dsc'];
		$this->quantity		= $d['qt'];
		$this->sum			= $d['sm'];
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		
		$ret->uid	= $this->userid;
		$ret->payid = $this->paymentid;
		$ret->delid = $this->deliveryid;
		$ret->ip	= $this->ip;
		$ret->fnm	= $this->firstName;
		$ret->lnm	= $this->lastName;
		$ret->ph	= $this->phone;
		$ret->adr	= $this->address;
		$ret->dsc	= $this->descript;
		$ret->st	= $this->status;
		$ret->qt	= $this->quantity;
		$ret->sm	= $this->sum;
		
		if (!empty($this->cartProductList)){
			$ret->cartproducts = $this->cartProductList->ToAJAX();
		}
		
		return $ret;
	}
}


/**
 * Товар в корзине текущего пользоватля
 */
class EShopCartProduct extends AbricosItem {

	public $productid;
	
	/**
	 * Кол-во позиций
	 * @var integer
	 */
	public $quantity;
	
	/**
	 * Цена
	 * @var integer
	 */
	public $price;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->productid = intval($d['elid']);
		$this->price = doubleval($d['pc']);
		$this->quantity = intval($d['qt']);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->elid = $this->productid;
		$ret->pc = $this->price;
		$ret->qt = $this->quantity;
		
		return $ret;
	}
}

class EShopCartProductList extends AbricosList {
	
	/**
	 * Всего товаров в корзине
	 * @var integer
	 */
	public $quantity = 0;
	
	/**
	 * Сумма корзины
	 * @var integer
	 */
	public $sum = 0;

	/**
	 * @var EShopElementList
	 */
	public $productList;
	
	/**
	 * @return EShopCartProduct
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		if (!empty($this->productList)){
			$ret->elements = $this->productList->ToAJAX();
		}
		return $ret;
	}
}

class EShopCartDiscount extends AbricosItem {
	
	/**
	 * Тип скидки. 0 - разовая, 1 - накопительная
	 * @var integer
	 */
	public $type;
	
	public $title;
	public $descript;
	
	/**
	 * Значение скидки
	 * @var double
	 */
	public $price;

	/**
	 * Тип значения. 0 - в рублях, 1 - в процентах
	 * @var integer
	 */
	public $priceType;

	/**
	 * Применить скидку начиная с этой суммы
	 * @var double
	 */
	public $fromSum;
	
	/**
	 * Применить скидку до этой суммы
	 * @var double
	 */
	public $endSum;
	
	public $isDisabled;
	
	public function __construct($d){
		parent::__construct($d);

		$this->type = intval($d['tp']);
		$this->title = strval($d['tl']);
		$this->descript = strval($d['dsc']);
		$this->price = doubleval($d['pc']);
		$this->priceType = intval($d['ptp']);
		$this->fromSum = doubleval($d['fsm']);
		$this->endSum = doubleval($d['esm']);
		$this->price = doubleval($d['pc']);
		$this->isDisabled = intval($d['dis']) > 0;
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tp = $this->type;
		$ret->tl = $this->title;
		$ret->dsc = $this->descript;
		$ret->pc = $this->price;
		$ret->ptp = $this->priceType;
		$ret->fsm = $this->fromSum;
		$ret->esm = $this->endSum;
		$ret->dis = $this->isDisabled ? 1 : 0;
		return $ret;
	}
}

class EShopCartDiscountList extends AbricosList {}

class EShopCartPayment extends AbricosItem {
	
	public $title;
	public $order;
	public $default;
	
	public function __construct($d){
		parent::__construct($d);

		$this->title = strval($d['tl']);
		$this->order = intval($d['ord']);
		$this->default = intval($d['def']);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tl = $this->title;
		$ret->ord = $this->order;
		$ret->def = $this->default;
		return $ret;
	}
}

class EShopCartPaymentList extends AbricosList {}

class EShopCartDelivery extends AbricosItem {
	
	public $title;
	public $price;
	public $fromZero;
	public $order;
	public $default;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->title = strval($d['tl']);
		$this->price = doubleval($d['pc']);
		$this->fromZero = doubleval($d['zr']);
		$this->order = intval($d['ord']);
		$this->default = intval($d['def']);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tl = $this->title;
		$ret->pc = $this->price;
		$ret->zr = $this->fromZero;
		$ret->ord = $this->order;
		$ret->def = $this->default;
		return $ret;
	}
}

class EShopCartDeliveryList extends AbricosList {}

class EShopCartConfig {

	/**
	 * @var EShopCartConfig
	 */
	public static $instance;

	public function __construct($cfg){
		EShopCartConfig::$instance = $this;

		if (empty($cfg)){ $cfg = array(); }

		/*
		 if (isset($cfg['subscribeSendLimit'])){
		$this->subscribeSendLimit = intval($cfg['subscribeSendLimit']);
		}
		/**/
	}
}

?>