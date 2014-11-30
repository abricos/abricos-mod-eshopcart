<?php
/**
 * @package Abricos
 * @subpackage EShopCart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$man = EShopCartModule::$instance->GetManager();

$cpList = $man->CartProductList();

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "count" => $cpList->quantity,
    "summ" => number_format($cpList->sum, 2, ',', ' ')
));

?>