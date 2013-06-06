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

		switch($d->do){
			// case "initdata": return $this->InitDataToAJAX();
		}

		return null;
	}
	
	public function InitDataToAJAX(){
		if (!$this->IsViewRole()){ return null; }
		
		$ret = new stdClass();
		
		// $obj = $this->UserConfig();
		// $ret->userconfig = $obj->userconfig;
		
		return $ret;
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