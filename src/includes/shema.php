<?php
/**
 * Схема таблиц модуля
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	Abricos::GetModule('eshopcart')->permission->Install();
	
	// корзина покупателя
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_cart (
			`cartid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Локальный идентификатор пользователя',
			`session` varchar(32) NOT NULL DEFAULT '' COMMENT 'Сессия пользователя если userid=0',
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата добавления в корзину',
			`productid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор продукта',
			`quantity` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во единиц продукта',
			`price` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Цена за единицу',
			PRIMARY KEY  (`cartid`),
			KEY `userid` (`userid`),
			KEY `session` (`session`)
		)".$charset
	);

	// заказы
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_order (
			`orderid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя, товар заказал авторизованный пользователь',
			`deliveryid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор доставки',
			`paymentid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор способа оплаты',
			`firstname` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя',
			`lastname` varchar(50) NOT NULL DEFAULT '' COMMENT 'Фамилия',
			`secondname` varchar(50) NOT NULL DEFAULT '' COMMENT 'Отчество',
			`phone` varchar(250) NOT NULL DEFAULT '' COMMENT 'Телефоны, если несколько - разделены запятой',
			`adress` TEXT NOT NULL COMMENT 'Адрес доставки',
			`extinfo` TEXT NOT NULL COMMENT 'Дополнительная информация',
			`status` int(1) unsigned NOT NULL Default 0 COMMENT 'Статус заказа: 0-заказ создан, 1-принят к исполнению, 2-выполнен',
			`secretkey` varchar(32) NOT NULL DEFAULT '' COMMENT 'Идентификатор заказа, для определения статуса заказа',
			`ip` varchar(15) NOT NULL default '' COMMENT 'IP адрес заказчика',
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			PRIMARY KEY  (`orderid`)
		)".$charset
	);
	
	// товары в заказе
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_orderitem (
			`orderitemid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`orderid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор заказа',
			`productid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор продукта',
			`quantity` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во единиц продукта',
			`price` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Цена за единицу',
			PRIMARY KEY  (`orderitemid`),
			KEY `userid` (`orderid`)
		)".$charset
	);
	
	// способы доставки
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_delivery (
			`deliveryid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`parentdeliveryid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор родителя',
			`title` varchar(250) NOT NULL DEFAULT '' COMMENT '',
			`ord` int(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Сортировка',
			`disabled` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Если 1, то отключен',
			`price` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Цена доставки',
			`fromzero` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Если заказ выше или равен этой сумме, то доставка бесплатна',
			PRIMARY KEY  (`deliveryid`)
		)".$charset
	);
	
	// способ оплаты
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_payment (
			`paymentid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`title` varchar(250) NOT NULL DEFAULT '' COMMENT '',
			`ord` int(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Сортировка',
			`disabled` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Если 1, то отключен',
			`def` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'По умолчанию',
			`descript` TEXT NOT NULL COMMENT '',
			`js` TEXT NOT NULL COMMENT '',
			`php` TEXT NOT NULL COMMENT '',
			PRIMARY KEY  (`paymentid`)
		)".$charset
	);
	
	// система скидок
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eshp_discount (
			`discountid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`dtype` int(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Тип скидки: 0-разовая, 1-накопительная',
			`disabled` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Отключить',
			`title` varchar(250) NOT NULL DEFAULT '' COMMENT '',
			`descript` TEXT NOT NULL COMMENT '',
	
			`price` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'значение скидки',
			`ispercent` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-значение в процентах, 0-абсолютная сумма',
	
			`fromsum` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT '',
			`endsum` double(10,2) unsigned NOT NULL DEFAULT 0 COMMENT '',
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			PRIMARY KEY  (`discountid`)
		)".$charset
	);
	
}

if ($updateManager->isUpdate('0.1.0.1')){
	
	$db->query_write("
		ALTER TABLE ".$pfx."eshp_payment
			ADD `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			ADD `deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления'
	");

	$db->query_write("
		ALTER TABLE ".$pfx."eshp_delivery
			ADD `def` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'По умолчанию',
			ADD `descript` TEXT NOT NULL COMMENT '',
			ADD `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			ADD `deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления'
	");
	
	$db->query_write("
		ALTER TABLE ".$pfx."eshp_discount
			ADD `deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления'
	");
}
?>