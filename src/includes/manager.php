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
     *
     * @var EShopCartConfig
     */
    public $config = null;

    public function __construct($module){
        parent::__construct($module);

        EShopCartManager::$instance = $this;

        $this->config = new EShopCartConfig(isset(Abricos::$config['module']['eshopcart']) ? Abricos::$config['module']['eshopcart'] : array());
    }

    public function IsAdminRole(){
        return $this->IsRoleEnable(EShopCartAction::ADMIN);
    }

    public function IsWriteRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(EShopCartAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(EShopCartAction::VIEW);
    }

    public function AJAX($d){

        if (isset($d->productaddtocart) && intval($d->productaddtocart) > 0){
            $this->CartProductAdd($d->productaddtocart);
        }

        switch ($d->do){
            case "initdata":
                return $this->InitDataToAJAX();

            case "cartproductlist":
                return $this->CartProductListToAJAX();
            case "productaddtocart":
                return $this->CartProductAddToAJAX($d->productid);
            case "productupdateincart":
                return $this->CartProductUpdateToAJAX($d->productid, $d->quantity);
            case "productremovefromcart":
                return $this->CartProductRemoveToAJAX($d->productid);

            case "ordering":
                return $this->OrderingToAJAX($d->savedata);

            case "order":
                return $this->OrderToAJAX($d->orderid);

            case "paymentlist":
                return $this->PaymentListToAJAX();
            case "paymentsave":
                return $this->PaymentSaveToAJAX($d->savedata);
            case "paymentlistorder":
                return $this->PaymentListSetOrder($d->paymentorders);
            case "paymentremove":
                return $this->PaymentRemove($d->paymentid);

            case "deliverylist":
                return $this->DeliveryListToAJAX();
            case "deliverysave":
                return $this->DeliverySaveToAJAX($d->savedata);
            case "deliverylistorder":
                return $this->DeliveryListSetOrder($d->deliveryorders);
            case "deliveryremove":
                return $this->DeliveryRemove($d->deliveryid);

            case "discountlist":
                return $this->DiscountListToAJAX();
            case "discountsave":
                return $this->DiscountSaveToAJAX($d->savedata);
            case "discountremove":
                return $this->DiscountRemove($d->discountid);

            case "configadmin":
                return $this->ConfigAdminToAJAX();
            case "configadminsave":
                return $this->ConfigAdminSave($d->savedata);
        }

        // TODO: на удаление/переделку
        switch ($d->do){

            case "orderbuild":
                return $this->old_OrderBuild($d);
            case "order-remove":
                return $this->old_OrderRemove($d->orderid);
            case "order-accept":
                return $this->old_OrderAccept($d->orderid);
            case "order-close":
                return $this->old_OrderClose($d->orderid);
        }

        return null;
    }

    public function InitDataToAJAX(){
        if (!$this->IsViewRole()){
            return null;
        }

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

        Abricos::GetModule('eshop')->GetManager();
        $catMan = EShopManager::$instance->cManager;
        $obj = $catMan->CurrencyDefaultToAJAX();
        $ret->currency = $obj->currency;

        return $ret;
    }

    private function CartProductListFillElements(EShopCartProductList $list){
        if ($list->Count() == 0){
            return;
        }

        Abricos::GetModule('eshop')->GetManager();
        $catMan = EShopManager::$instance->cManager;
        $cfg = new CatalogElementListConfig();
        for ($i = 0; $i < $list->Count(); $i++){
            $item = $list->GetByIndex($i);
            array_push($cfg->elids, $item->productid);
        }
        $list->productList = $catMan->ProductList($cfg);
    }

    private $_cacheCartProductList = null;

    /**
     * Список товаров в корзине текущего пользователя
     *
     * @return EShopCartProductList
     */
    public function CartProductList(){
        if (!$this->IsViewRole()){
            return null;
        }

        if (!empty($this->_cacheCartProductList)){
            return $this->_cacheCartProductList;
        }

        $rows = EShopCartQuery::CartProductList($this->db, $this->user);

        $list = new EShopCartProductList();
        $checkDouble = array();
        $isDouble = array();

        $tQuantity = 0;
        $tSum = 0;

        while (($d = $this->db->fetch_array($rows))){
            $item = new EShopCartProduct($d);

            $productid = $item->productid;
            if (empty($checkDouble[$productid])){
                $checkDouble[$productid] = $item;
                $list->Add($item);
            } else {
                $bItem = $checkDouble[$productid];
                $bItem->quantity += $item->quantity;
                $isDouble[$productid] = $bItem;
            }
            $tQuantity += $item->quantity;
            $tSum += $item->quantity * $item->price;
        }
        $list->quantity = $tQuantity;
        $list->sum = $tSum;

        if (count($isDouble) > 0){
            foreach ($isDouble as $productid => $item){
                EShopCartQuery::CartProductUpdateDouble($this->db, $this->user, $item);
            }
        }

        $this->CartProductListFillElements($list);
        $this->_cacheCartProductList = $list;
        return $list;
    }

    public function CartProductListToAJAX(){
        $list = $this->CartProductList();
        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->cartproducts = $list->ToAJAX();
        return $ret;
    }

    /**
     * Добавить продукт в корзину
     *
     * @param integer $productid
     */
    public function CartProductAdd($productid, $quantity = 1){
        if (empty($productid) || !$this->IsWriteRole()){
            return null;
        }

        Abricos::GetModule('eshop')->GetManager();
        $catMan = EShopManager::$instance->cManager;

        $product = $catMan->Element($productid);
        if (empty($product)){
            return null;
        }
        $elType = $catMan->ElementTypeList()->Get(0);
        $option = $elType->options->GetByName('price');
        if (empty($option)){
            return null;
        }

        $optionBase = $product->detail->optionsBase;

        $price = doubleval(isset($optionBase['price']) ? $optionBase['price'] : 0);
        $currencyDefault = $catMan->CurrencyDefault();

        if ($price > 0 && $option->currencyid > 0 && $currencyDefault->id != $option->currencyid){
            $currency = $catMan->CurrencyList()->Get($option->currencyid);
            if (!empty($currency) && $currency->rateVal > 0){
                $price = $price / $currency->rateVal;
            }
        }

        EShopCartQuery::CartProductAppend($this->db, $this->user, $productid, $quantity, $price);
    }

    public function CartProductAddToAJAX($productid){
        $this->CartProductAdd($productid);

        return $this->CartProductListToAJAX();
    }

    public function CartProductUpdate($productid, $quantity = 1){
        if (empty($productid) || !$this->IsWriteRole()){
            return null;
        }

        Abricos::GetModule('eshop')->GetManager();
        $catMan = EShopManager::$instance->cManager;

        $product = $catMan->Element($productid);
        if (empty($product)){
            return null;
        }
        // $optionBase = $product->detail->optionsBase;

        // $price = isset($optionBase['price']) ? $optionBase['price'] : "";

        EShopCartQuery::CartProductUpdate($this->db, Abricos::$user, $productid, $quantity);
    }

    public function CartProductUpdateToAJAX($productid, $quantity){
        $this->CartProductUpdate($productid, $quantity);

        return $this->CartProductListToAJAX();
    }

    public function CartProductRemove($productid){
        if (!$this->IsWriteRole()){
            return null;
        }

        EShopCartQuery::CartProductRemove($this->db, $this->user, $productid);
    }

    public function CartProductRemoveToAJAX($productid){
        $this->CartProductRemove($productid);

        return $this->CartProductListToAJAX();
    }

    public function Ordering($sd){
        if (!$this->IsWriteRole()){
            return null;
        }

        $plist = $this->CartProductList();
        if ($plist->Count() == 0){
            return null;
        }

        $paymentid = intval($sd->paymentid);
        $deliveryid = intval($sd->deliveryid);

        $utmf = Abricos::TextParser(true);

        $ci = $sd->customer;
        $ci->fnm = $utmf->Parser($ci->fnm);
        $ci->lnm = $utmf->Parser($ci->lnm);
        $ci->ph = $utmf->Parser($ci->ph);
        $ci->adr = $utmf->Parser($ci->adr);
        $ci->dsc = $utmf->Parser($ci->dsc);

        $ip = $_SERVER['REMOTE_ADDR'];

        $orderid = EShopCartQuery::OrderApppend($this->db, $this->userid, $paymentid, $deliveryid, $ci, $ip);

        $totalQty = 0;
        $totalSum = 0;

        for ($i = 0; $i < $plist->Count(); $i++){
            $p = $plist->GetByIndex($i);
            EShopCartQuery::OrderItemAppend($this->db, $orderid, $p->productid, $p->quantity, $p->price);

            $totalQty += $p->quantity;
            $totalSum += $p->quantity * $p->price;
        }

        EShopCartQuery::CartClear($this->db, $this->user);

        // отправка уведомление о новом заказе админу/-ам

        $brick = Brick::$builder->LoadBrickS('eshopcart', 'templates', null, null);
        $v = &$brick->param->var;


        $repd = array(
            "orderid" => $orderid,
            "qty" => $totalQty,
            "sm" => number_format($totalSum, 2, ',', ' '),
            "fnm" => $ci->fnm,
            "lnm" => $ci->lnm,
            "ph" => $ci->ph,
            "adr" => $ci->adr,
            "dsc" => $ci->dsc,
            "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
        );

        $semails = $this->EMailAdmin();
        $aemails = explode(",", $semails);
        foreach ($aemails as $email){
            $repd['email'] = $email = trim($email);

            $subject = Brick::ReplaceVarByData($v['newordersubj'], $repd);
            $body = Brick::ReplaceVarByData($v['neworderbody'], $repd);

            Abricos::Notify()->SendMail($email, $subject, $body);
        }
    }

    public function OrderingToAJAX($sd){
        $this->Ordering($sd);

        return $this->CartProductListToAJAX();
    }

    public function OrderItemList($orderid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $rows = EShopCartQuery::OrderItemList($this->db, $orderid);

        $list = new EShopCartProductList();

        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new EShopCartProduct($d));
        }

        $this->CartProductListFillElements($list);

        return $list;
    }

    /**
     * Заказ
     *
     * @param integer $orderid
     */
    public function Order($orderid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $row = EShopCartQuery::Order($this->db, $orderid);
        if (empty($row)){
            return null;
        }

        $item = new EShopCartOrder($row);
        $item->cartProductList = $this->OrderItemList($orderid);

        return $item;
    }

    public function OrderToAJAX($orderid){
        $item = $this->Order($orderid);
        if (empty($item)){
            return null;
        }

        $ret = new stdClass();
        $ret->order = $item->ToAJAX();

        return $ret;
    }

    /**
     * @return EShopCartPaymentList
     */
    public function PaymentList(){
        if (!$this->IsViewRole()){
            return null;
        }

        $list = new EShopCartPaymentList();
        $rows = EShopCartQuery::PaymentList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new EShopCartPayment($d));
        }
        return $list;
    }

    public function PaymentListToAJAX(){
        $list = $this->PaymentList();
        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->payments = $list->ToAJAX();
        return $ret;
    }

    /**
     * @param object $sd
     * @return EShopCartPayment
     */
    public function PaymentSave($sd){
        if (!$this->IsAdminRole()){
            return null;
        }
        $sd = array_to_object($sd);

        $sd->id = isset($sd->id) ? intval($sd->id) : 0;
        $sd->tl = isset($sd->tl) ? $sd->tl : "";
        $sd->dsc = isset($sd->dsc) ? $sd->dsc : "";
        $sd->def = isset($sd->def) ? $sd->def : 0;

        $paymentid = $sd->id;

        $utm = Abricos::TextParser(true);
        $utm->jevix->cfgSetAutoBrMode(true);

        $utmf = Abricos::TextParser(true);

        $sd->tl = $utmf->Parser($sd->tl);
        $sd->dsc = $utm->Parser($sd->dsc);

        if ($paymentid == 0){
            $paymentid = EShopCartQuery::PaymentAppend($this->db, $sd);
        } else {
            EShopCartQuery::PaymentUpdate($this->db, $paymentid, $sd);
        }

        if (!empty($sd->def)){
            EShopCartQuery::PaymentDefaultSet($this->db, $paymentid);
        }

        return $paymentid;
    }

    public function PaymentSaveToAJAX($sd){
        $paymentid = $this->PaymentSave($sd);

        if (empty($paymentid)){
            return null;
        }

        $ret = $this->PaymentListToAJAX();
        $ret->paymentid = $paymentid;
        return $ret;
    }

    public function PaymentListSetOrder($orders){
        if (!$this->IsAdminRole()){
            return null;
        }

        EShopCartQuery::PaymentListSetOrder($this->db, $orders);

        return true;
    }

    public function PaymentRemove($paymentid){
        if (!$this->IsAdminRole()){
            return null;
        }

        EShopCartQuery::PaymentRemove($this->db, $paymentid);

        return true;
    }

    /**
     * @return EShopCartDeliveryList
     */
    public function DeliveryList(){
        if (!$this->IsViewRole()){
            return null;
        }

        $list = new EShopCartDeliveryList();
        $rows = EShopCartQuery::DeliveryList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new EShopCartDelivery($d));
        }
        return $list;
    }

    public function DeliveryListToAJAX(){
        $list = $this->DeliveryList();
        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->deliverys = $list->ToAJAX();
        return $ret;
    }

    /**
     * @param object $sd
     * @return EShopCartDelivery
     */
    public function DeliverySave($sd){
        if (!$this->IsAdminRole()){
            return null;
        }

        $deliveryid = intval($sd->id);

        $utm = Abricos::TextParser(true);
        $utm->jevix->cfgSetAutoBrMode(true);

        $utmf = Abricos::TextParser(true);

        $sd->tl = $utmf->Parser($sd->tl);
        $sd->dsc = $utm->Parser($sd->dsc);

        if ($deliveryid == 0){
            $deliveryid = EShopCartQuery::DeliveryAppend($this->db, $sd);
        } else {
            EShopCartQuery::DeliveryUpdate($this->db, $deliveryid, $sd);
        }

        if (!empty($sd->def)){
            EShopCartQuery::DeliveryDefaultSet($this->db, $deliveryid);
        }

        return $deliveryid;
    }

    public function DeliverySaveToAJAX($sd){
        $deliveryid = $this->DeliverySave($sd);

        if (empty($deliveryid)){
            return null;
        }

        $ret = $this->DeliveryListToAJAX();
        $ret->deliveryid = $deliveryid;
        return $ret;
    }

    public function DeliveryListSetOrder($orders){
        if (!$this->IsAdminRole()){
            return null;
        }

        EShopCartQuery::DeliveryListSetOrder($this->db, $orders);

        return true;
    }

    public function DeliveryRemove($deliveryid){
        if (!$this->IsAdminRole()){
            return null;
        }

        EShopCartQuery::DeliveryRemove($this->db, $deliveryid);

        return true;
    }


    /**
     * @return EShopCartDiscountList
     */
    public function DiscountList(){
        if (!$this->IsViewRole()){
            return null;
        }

        $list = new EShopCartDiscountList();
        $rows = EShopCartQuery::DiscountList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new EShopCartDiscount($d));
        }
        return $list;
    }

    public function DiscountListToAJAX(){
        $list = $this->DiscountList();
        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->discounts = $list->ToAJAX();
        return $ret;
    }

    /**
     * @param object $sd
     * @return EShopCartDiscount
     */
    public function DiscountSave($sd){
        if (!$this->IsAdminRole()){
            return null;
        }

        $discountid = intval($sd->id);

        $utm = Abricos::TextParser(true);
        $utm->jevix->cfgSetAutoBrMode(true);

        $utmf = Abricos::TextParser(true);

        $sd->tl = $utmf->Parser($sd->tl);
        $sd->dsc = $utm->Parser($sd->dsc);

        if ($discountid == 0){
            $discountid = EShopCartQuery::DiscountAppend($this->db, $sd);
        } else {
            EShopCartQuery::DiscountUpdate($this->db, $discountid, $sd);
        }

        if (!empty($sd->def)){
            EShopCartQuery::DiscountDefaultSet($this->db, $discountid);
        }

        return $discountid;
    }

    public function DiscountSaveToAJAX($sd){
        $discountid = $this->DiscountSave($sd);

        if (empty($discountid)){
            return null;
        }

        $ret = $this->DiscountListToAJAX();
        $ret->discountid = $discountid;
        return $ret;
    }

    public function DiscountRemove($discountid){
        if (!$this->IsAdminRole()){
            return null;
        }

        EShopCartQuery::DiscountRemove($this->db, $discountid);

        return true;
    }

    public function ConfigAdminToAJAX(){
        if (!$this->IsAdminRole()){
            return null;
        }

        $ret = new stdClass();
        $ret->configadmin = new stdClass();
        $ret->configadmin->emls = $this->EMailAdmin();

        return $ret;
    }

    private function EMailAdmin(){
        $ph = $this->module->GetPhrases()->Get('adm_emails');
        return $ph->value;
    }

    public function ConfigAdminSave($sd){
        if (!$this->IsAdminRole()){
            return null;
        }

        $utmf = Abricos::TextParser(true);
        $sd->emls = isset($sd->emls) ? $utmf->Parser($sd->emls) : "";

        $phrases = $this->module->GetPhrases();
        $phrases->Set('adm_emails', $sd->emls);
        Abricos::$phrases->Save();

        return true;
    }


    public function ArrayToObject($o){
        if (is_array($o)){
            $ret = new stdClass();
            foreach ($o as $key => $value){
                $ret->$key = $value;
            }
            return $ret;
        } else if (!is_object($o)){
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * TODO: Старая версия методов - на удаление
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Статус заказа - Новый
     *
     * @var integer
     */
    const ORDER_STATUS_NEW = 0;

    /**
     * Статус заказа - Принятый на исполнение
     *
     * @var integer
     */
    const ORDER_STATUS_EXEC = 1;

    /**
     * Статус заказа - Закрытый
     *
     * @var integer
     */
    const ORDER_STATUS_ARHIVE = 2;


    public function DSProcess($name, $rows){
        $p = $rows->p;
        $db = $this->db;

        switch ($name){
            case 'cart':
                foreach ($rows->r as $r){
                    if ($r->f == 'd'){
                        $this->old_CartRemove($r->d->id);
                    }
                    if ($r->f == 'u'){
                        $this->old_CartAppend($r->d);
                    }
                }
                return;
        }
    }

    public function DSGetData($name, $rows){
        $p = $rows->p;

        switch ($name){
            case 'cart':
                return $this->old_Cart($p->orderid);

            case 'order':
                return $this->old_Order($p->orderid);
            case 'orderitem':
                return $this->old_OrderItemList($p->orderid);

            case 'orders-new':
                return $this->old_Orders('new', $p->page, $p->limit);
            case 'orders-exec':
                return $this->old_Orders('exec', $p->page, $p->limit);
            case 'orders-arhive':
                return $this->old_Orders('arhive', $p->page, $p->limit);
            case 'orders-recycle':
                return $this->old_Orders('recycle', $p->page, $p->limit);
            case 'orderscnt-new':
                return $this->old_OrdersCount('new');
            case 'orderscnt-exec':
                return $this->old_OrdersCount('exec');
            case 'orderscnt-arhive':
                return $this->old_OrdersCount('arhive');
            case 'orderscnt-recycle':
                return $this->old_OrdersCount('recycle');
        }

        return null;
    }


    /**
     * Получить данные для работы кирпичей по сборке списка продуктов
     */

    private $_productListData = null;

    public function old_GetProductListData(){
        if (!is_null($this->_productListData)){
            return $this->_productListData;
        }

        $smMenu = Abricos::GetModule('sitemap')->GetManager()->GetMenu();
        $catItemMenu = $smMenu->menuLine[count($smMenu->menuLine) - 1];

        // если на конце uri есть запись /pageN/, где N - число, значит запрос страницы
        $listPage = 1;

        $adress = Abricos::$adress;

        $tag = $adress->dir[$adress->level - 1];
        if (substr($tag, 0, 4) == 'page'){
            $listPage = intval(substr($tag, 4, strlen($tag) - 4));
        }

        $this->_productListData = array(
            "listPage" => $listPage,
            "catids" => $this->module->GetFullSubCatalogId($catItemMenu)
        );
        return $this->_productListData;
    }


    /**
     * Сформировать заказ клиента
     *
     */
    public function old_OrderBuild($data){
        $userid = $this->userid;
        $db = $this->db;
        if ($this->user->id == 0 && $data->auth->type == 'reg'){
            // пользователь решил заодно и зарегистрироваться
            $login = $data->auth->login;
            $email = $data->auth->email;
            $pass = $data->auth->pass;
            $err = $this->user->GetManager()->Register($login, $pass, $email, true);
            if ($err == 0){
                $user = UserQuery::UserByName($db, $login);
                $userid = $user['userid'];
            }
        }

        $od = new stdClass();
        $od->deliveryid = $data->deli->deliveryid;
        $od->paymentid = $data->pay->paymentid;

        $deli = $data->deli;
        $od->userid = $userid;
        $od->firstname = $deli->firstname;
        $od->lastname = $deli->lastname;
        $od->phone = $deli->phone;
        $od->adress = $deli->adress;
        $od->extinfo = $deli->extinfo;
        $od->ip = $_SERVER['REMOTE_ADDR'];

        $orderid = EShopQuery::OrderAppend($db, $od);

        EShopQuery::CartUserSessionFixed($db, $userid, $this->userSession);

        $rows = $this->CartByUserId($userid);
        while (($row = $db->fetch_array($rows))){
            EShopQuery::OrderItemAppend($db, $orderid, $row['id'], $row['qty'], $row['pc']);
        }
        EShopQuery::CartClear($db, $userid, $this->userSession);

        $order = $this->db->fetch_array(EShopQuery::old_Order($this->db, $orderid));

        // отправить уведомление на емайл админам
        $config = $this->Config(false);
        $emails = $config['adm_emails'];
        $arr = explode(',', $emails);
        $subject = $config['adm_notify_subj'];
        $body = Brick::ReplaceVarByData($config['adm_notify'], array(
            'orderid' => $orderid,
            'summ' => $order['sm'],
            'qty' => $order['qty'],

            'fnm' => $order['fnm'],
            'lnm' => $order['lnm'],
            'phone' => $order['ph'],
            'adress' => $order['adress'],
            'extinfo' => $order['extinfo']
        ));
        $body = nl2br($body);

        foreach ($arr as $email){
            $email = trim($email);
            if (empty($email)){
                continue;
            }

            Abricos::Notify()->SendMail($email, $subject, $body);
        }
    }

    /**
     * Принять заказ на исполнение
     */
    public function old_OrderAccept($orderid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $order = $this->old_Order($orderid);
        if (empty($order)){
            return;
        }
        EShopQuery::old_OrderAccept($this->db, $orderid);
    }

    /**
     * Исполнить заказ (закрыть)
     */
    public function old_OrderClose($orderid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $order = $this->old_Order($orderid);
        if (empty($order)){
            return;
        }
        EShopQuery::old_OrderClose($this->db, $orderid);
    }

    /**
     * Удалить заказ в корзину
     *
     * @param integer $orderid идентификатор заказа
     */
    public function old_OrderRemove($orderid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $order = $this->old_Order($orderid);
        if (empty($order)){
            return;
        }
        EShopQuery::old_OrderRemove($this->db, $orderid);
        return $orderid;
    }

    /**
     * Получить информацию для полей заказа товара
     *
     */
    public function old_OrderLastInfo(){
        return array(
            "fam" => "Ivanov",
            "im" => "Ivan",
            "otch" => "Ivanovich"
        );
    }

    public function old_CartUpdate($product){
        $pcart = $this->old_CartItem($product->id);
        if (empty($pcart)){ // Hacker???
            return;
        }
        $newQty = bkint($product->qty);
        EShopQuery::old_CartRemove($this->db, $product->id);
        if ($newQty < 1){
            return;
        }
        return $this->old_CartAppend($product->id, $newQty);
    }

    /**
     * Положить товар в корзину текущего пользователя
     *
     * @return вернуть информацию по корзине
     */
    public function old_CartAppend($productid, $quantity){
        $quantity = bkint($quantity);
        if ($quantity < 1){
            return;
        }
        $db = $this->db;

        $product = $this->module->GetCatalogManager()->Element($productid, true);
        if (empty($product)){
            // попытка добавить несуществующий продукт???
            return null;
        }

        $cartid = EShopQuery::old_CartAppend($this->db, $this->userid, $this->userSession, $productid, $quantity, $product['fld_price']);

        return $this->old_CartInfo();
    }

    public function old_CartItem($productid){
        return $this->db->fetch_array(EShopQuery::old_Cart($this->db, $this->userid, $this->userSession, $productid));
    }

    public function old_CartRemove($productid){
        $pcart = $this->old_CartItem($productid);
        if (empty($pcart)){ // Hacker???
            return;
        }
        EShopQuery::old_CartRemove($this->db, $productid);
    }

    /**
     * Получить информацию по корзине текущего пользователя
     */
    public function old_CartInfo(){
        $info = EShopQuery::old_CartInfo($this->db, $this->userid, $this->userSession);

        return array(
            'qty' => intval($info['qty']),
            'sum' => doubleval($info['sm'])
        );
    }

    public function old_Cart($orderid){
        $orderid = intval($orderid);
        if ($orderid > 0){
            return $this->old_OrderItemList($orderid);
        }
        return EShopQuery::old_Cart($this->db, $this->userid, $this->userSession);
    }

    public function CartByUserId($userid){
        return EShopQuery::old_Cart($this->db, $userid, $this->userSession);
    }

    public function old_OrderTypeToStatus($type){
        switch ($type){
            case 'new':
                return EShopCartManager::ORDER_STATUS_NEW;
            case 'exec':
                return EShopCartManager::ORDER_STATUS_EXEC;
            case 'arhive':
                return EShopCartManager::ORDER_STATUS_ARHIVE;
            case 'recycle':
                return -1;
        }
        return 999;
    }

    /**
     * Получить список заказов
     *
     */
    public function old_Orders($type, $page, $limit){
        if (!$this->IsAdminRole()){
            return null;
        }
        $status = $this->old_OrderTypeToStatus($type);
        return EShopQuery::old_Orders($this->db, $status, $page, $limit);
    }

    public function old_OrdersCount($type){
        if (!$this->IsAdminRole()){
            return null;
        }
        $status = $this->old_OrderTypeToStatus($type);
        return EShopQuery::old_OrdersCount($this->db, $status);
    }

    /**
     * Получить информацию о заказе
     */
    public function old_Order($orderid){
        if ($this->IsAdminRole()){
            return EShopQuery::old_Order($this->db, $orderid);
        } else if ($this->userid > 0){
            return EShopQuery::old_Order($this->db, $orderid, $this->userid);
        }
        return null;
    }

    /**
     * Получить список продукции конкретного заказа
     */
    public function old_OrderItemList($orderid){
        if ($this->IsAdminRole()){
            return EShopQuery::old_OrderItemList($this->db, $orderid);
        } else if ($this->userid > 0){
            return EShopQuery::old_OrderItemList($this->db, $orderid, $this->userid);
        }
        return null;
    }

}

?>