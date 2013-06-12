<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class EShopCartModule extends Ab_Module {
	
	/**
	 * @var EShopCartModule
	 */
	public static $instance = null;
	
	private $_manager = null;
	
	public function EShopCartModule(){
		// версия модуля
		$this->version = "0.1";

		// имя модуля 
		$this->name = "eshopcart";

		$this->permission = new EShopCartPermission($this);
		
		EShopCartModule::$instance = $this;
	}
	
	/**
	 * @return EShopCartManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new EShopCartManager($this);
		}
		return $this->_manager;
	}
	
	public function GetContentName(){
		return "";
	}

}

class EShopCartAction {
	const VIEW	= 10;
	const WRITE	= 30;
	const ADMIN	= 50;
}

class EShopCartPermission extends Ab_UserPermission {

	public function EShopCartPermission(EShopCartModule $module){
		
		$defRoles = array(
			new Ab_UserRole(EShopCartAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(EShopCartAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(EShopCartAction::VIEW, Ab_UserGroup::ADMIN),

			new Ab_UserRole(EShopCartAction::WRITE, Ab_UserGroup::GUEST),
			new Ab_UserRole(EShopCartAction::WRITE, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(EShopCartAction::WRITE, Ab_UserGroup::ADMIN),
				
			new Ab_UserRole(EShopCartAction::ADMIN, Ab_UserGroup::ADMIN),
		);
		
		parent::__construct($module, $defRoles);
	}

	public function GetRoles(){
		return array(
			EShopCartAction::VIEW => $this->CheckAction(EShopCartAction::VIEW),
			EShopCartAction::WRITE => $this->CheckAction(EShopCartAction::WRITE),
			EShopCartAction::ADMIN => $this->CheckAction(EShopCartAction::ADMIN)
		);
	}
}
Abricos::ModuleRegister(new EShopCartModule());


?>