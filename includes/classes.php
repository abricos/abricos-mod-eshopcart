<?php 
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

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