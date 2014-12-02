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
if (false) {// обманка для удобного обращения к переменным класа
    $product = new EShopElement();
}

$btl = $p["titleorder"];
if (isset($product->ext['sklad']) && $product->ext['sklad']>0){
    $btl = $p["titlebuy"];
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "productid" => $product->id,
    "btl" => $btl
));

?>