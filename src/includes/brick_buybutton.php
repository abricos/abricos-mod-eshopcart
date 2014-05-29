<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

$product = $p['product'];
if (false){// обманка для удобного обращения к переменным класа
	$product = new EShopElement();
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"productid" => $product->id,
	"btl" => $p[$product->ext['sklad']>0 ? "titlebuy" : "titleorder"]
));

?>