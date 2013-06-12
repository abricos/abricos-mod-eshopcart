<?php 
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class EShopCartPayment extends AbricosItem {
	
	public $title;
	public $order;
	public $disabled;
	public $default;
	
	public function __construct($d){
		parent::__construct($d);
	
		$this->title = strval($d['tl']);
		$this->order = intval($d['ord']);
		$this->disabled = intval($d['dis']);
		$this->default = intval($d['def']);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tl = $this->title;
		$ret->ord = $this->order;
		$ret->dis = $this->disabled;
		$ret->def = $this->default;
		return $ret;
	}
}

class EShopCartPaymentList extends AbricosList {}

class EShopCartDelivery extends AbricosItem {
	
	public $title;
	public $order;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->title = strval($d['tl']);
		$this->order = intval($d['ord']);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tl = $this->title;
		$ret->ord = $this->order;
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