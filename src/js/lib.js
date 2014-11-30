/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'sys', files: ['number.js']},
        {name: 'eshop', files: ['lib-manager.js']}
	]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		R = NS.roles;

	NS.NumberFormat = {
		decimalPlaces: 2,
		thousandsSeparator: ' ',
		suffix: ' '
	};
	NS.numberFormat = function(val, nf){
		nf = nf || NS.NumberFormat;
		return YAHOO.util.Number.format(val, nf);
	};

	var SysNS = Brick.mod.sys;

	var buildTemplate = this.buildTemplate;
	buildTemplate({},'');

	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;

	// элемент товара в корзине текущего пользователя
	var CartProduct = function(d){
		d = L.merge({
			'elid': 0,
			'qt': 0,
			'pc': 0
		}, d || {});
		CartProduct.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CartProduct, SysNS.Item, {
		init: function(d){
			this.product = null;
			CartProduct.superclass.init.call(this, d);
		},
		update: function(d){
			this.productid = d['elid']|0;
			this.quantity = d['qt']|0;
			this.price = d['pc']|0;
		},
		getSum: function(){
			if (!L.isValue(this.product)){ return 0; }
			return this.quantity*this.product.ext['price'];
		}
	});
	NS.CartProduct = CartProduct;

	var CartProductList = function(d){
		this.productList = null;

		CartProductList.superclass.constructor.call(this, d, CartProduct, {
			// 'order': '!order'
		});
	};
	YAHOO.extend(CartProductList, SysNS.ItemList, {
		getSum: function(){
			var sm = 0;
			this.foreach(function(item){
				sm += item.getSum();
			});
			return sm;
		},
		getQuantity: function(){
			var qt = 0;
			this.foreach(function(item){
				qt += item.quantity;
			});
			return qt;
		}
	});
	NS.CartProductList = CartProductList;

	var Payment = function(d){
		d = L.merge({
			'tl': '',
			'dsc': '',
			'def': 0,
			'ord': 0
		}, d || {});
		Payment.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Payment, SysNS.Item, {
		update: function(d){
			this.title = d['tl'];
			this.descript = d['dsc'];
			this.isDefault = (d['def']|0)>0;
			this.order = d['ord']|0;
		}
	});
	NS.Payment = Payment;

	var PaymentList = function(d){
		PaymentList.superclass.constructor.call(this, d, Payment, {
			'order': '!order'
		});
	};
	YAHOO.extend(PaymentList, SysNS.ItemList, {
		getDefaultId: function(){
			var defid = 0;
			this.foreach(function(payment){
				if (payment.isDefault){
					defid = payment.id;
					return true;
				}
			});
			return defid;
		}
	});
	NS.PaymentList = PaymentList;

	var Delivery = function(d){
		d = L.merge({
			'tl': '',
			'pc': 0,
			'zr': 0,
			'dsc': '',
			'def': 0,
			'ord': 0
		}, d || {});
		Delivery.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Delivery, SysNS.Item, {
		update: function(d){
			this.title = d['tl'];
			this.price = d['pc']|0;
			this.fromZero = d['zr']|0;
			this.descript = d['dsc'];
			this.isDefault = (d['def']|0)>0;
			this.order = d['ord']|0;
		}
	});
	NS.Delivery = Delivery;

	var DeliveryList = function(d){
		DeliveryList.superclass.constructor.call(this, d, Delivery, {
			'order': '!order'
		});
	};
	YAHOO.extend(DeliveryList, SysNS.ItemList, {
		getDefaultId: function(){
			var defid = 0;
			this.foreach(function(delivery){
				if (delivery.isDefault){
					defid = delivery.id;
					return true;
				}
			});
			return defid;
		}
	});
	NS.DeliveryList = DeliveryList;

	var Order = function(d){
		d = L.merge({
			'uid': 0,
			'payid': 0,
			'delid': 0,
			'ip': '',
			'fnm': '',
			'lnm': '',
			'ph': '',
			'adr': '',
			'dsc': '',
			'st': 0,
			'qt': 0,
			'sm': 0,
			'cartproducts': null
		}, d || {});
		Order.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Order, SysNS.Item, {
		update: function(d){
			this.userid = d['uid']|0;
			this.paymentid = d['payid']|0;
			this.deliveryid = d['delid']|0;
			this.ip = d['ip'];
			this.firstName = d['fnm'];
			this.lastName = d['lnm'];
			this.phone = d['ph'];
			this.address = d['adr'];
			this.descript = d['dsc'];
			this.status = d['st']|0;
			this.quantity = d['qt']|0;
			this.sum = d['sm']|0;

			this.cartProductList = null;
			if (L.isValue(d['cartproducts'])){
				this.cartProductList = new NS.CartProductList();
				NS.manager._updateCartProductList(d, this.cartProductList);
			}
		},
		getDelivery: function(){
			return NS.manager.deliveryList.get(this.deliveryid);
		},
		getPayment: function(){
			return NS.manager.paymentList.get(this.paymentid);
		}
	});
	NS.Order = Order;

	var ConfigAdmin = function(d){
		d = L.merge({
			'emls': ''
		}, d||{});
		this.init(d);
	};
	ConfigAdmin.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			this.emails = d['emls'];
		}
	};
	NS.ConfigAdmin = ConfigAdmin;

	var Discount = function(d){
		d = L.merge({
			'tp': 0,
			'tl': '',
			'dsc': '',
			'pc': 0,
			'ptp': 0,
			'fsm': 0,
			'esm': 0,
			'dis': 0
		}, d || {});
		Discount.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Discount, SysNS.Item, {
		update: function(d){
			this.type = d['tp'];
			this.title = d['tl'];
			this.descript = d['dsc'];
			this.price = d['pc'];
			this.priceType = d['ptp'];
			this.fromSum = d['fsm'];
			this.endSum = d['esm'];
			this.isDisabled = d['dis']>0;
		}
	});
	NS.Discount = Discount;

	var DiscountList = function(d){
		DiscountList.superclass.constructor.call(this, d, Discount, {
			'order': 'fromSum'
		});
	};
	YAHOO.extend(DiscountList, SysNS.ItemList, {});
	NS.DiscountList = DiscountList;


	var Manager = function (callback, productAddToCart){
		this.init(callback, productAddToCart);
	};
	Manager.prototype = {
		init: function(callback, productAddToCart){
			productAddToCart = productAddToCart|0;
			NS.manager = this;

			this.discountList = new DiscountList();
			this.paymentList = new PaymentList();
			this.deliveryList = new DeliveryList();
			this.cartProductList = new CartProductList();
			this.configAdmin = null;
			this.eshopManager = null;

			var __self = this;
			R.load(function(){
				Brick.mod.eshop.initManager(function(man){
					__self.eshopManager = man;

					var act = {'do': 'initdata'};
					if (productAddToCart > 0){
						act['productaddtocart'] = productAddToCart;
					}

					__self.ajax(act, function(d){
						__self._updateConfigAdmin(d);
						__self._updateDiscountList(d);
						__self._updatePaymentList(d);
						__self._updateDeliveryList(d);
						__self._updateCartProductList(d);
						NS.life(callback, __self);
					});
				});
			});
		},
		ajax: function(data, callback){
			data = data || {};

			Brick.ajax('{C#MODNAME}', {
				'data': data,
				'event': function(request){
					NS.life(callback, request.data);
				}
			});
		},

		_updateCartProductList: function(d, cartProductList){
			if (!L.isValue(d) || !L.isValue(d['cartproducts']) || !L.isValue(d['cartproducts']['list'])){
				return null;
			}
			var isCurrentUser = true;
			if (!cartProductList){
				cartProductList = this.cartProductList;
				isCurrentUser = true;
			}
			cartProductList.clear();
			cartProductList.update(d['cartproducts']['list']);
			var pList = cartProductList.productList = this.eshopManager._elementListUpdate(d['cartproducts']);

			cartProductList.foreach(function(item){
				item.product = pList.get(item.productid);
			});

			if (isCurrentUser){
				this.updateCartInfoElements();
			}
		},

		updateCartInfoElements: function(){
			var sm = 0, qt = 0;
			this.cartProductList.foreach(function(item){
				sm += item.getSum();
				qt += item.quantity;
			});

			// обновить данные на страницах
			var elsQt = Dom.getElementsByClassName('eshop-cart-count');
			for (var i=0; i<elsQt.length;i++){
				elsQt[i].innerHTML = qt;
			}
			var elsSm = Dom.getElementsByClassName('eshop-cart-summ');
			for (var i=0; i<elsSm.length;i++){
				elsSm[i].innerHTML = NS.numberFormat(sm);
			}
		},

		cartProductAdd: function(productid, callback){
			var __self = this;
			this.ajax({
				'do': 'productaddtocart',
				'productid': productid
			}, function(d){
				__self._updateCartProductList(d);
				NS.life(callback);
			});
		},

		cartProductRemove: function(productid, callback){
			var __self = this;
			this.ajax({
				'do': 'productremovefromcart',
				'productid': productid
			}, function(d){
				__self._updateCartProductList(d);
				NS.life(callback);
			});
		},

		cartProductListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'cartproductlist'
			}, function(d){
				__self._updateCartProductList(d);
				NS.life(callback, list);
			});
		},

		configAdminSave: function(sd, callback){
			var __self = this;
			this.ajax({
				'do': 'configadminsave',
				'savedata': sd
			}, function(d){
				__self._updateConfigAdmin(d);
				NS.life(callback);
			});
		},

		_updateConfigAdmin: function(d){
			if (!L.isValue(d) || !L.isValue(d['configadmin'])){ return null; }

			if (!L.isValue(this.configAdmin)){
				this.configAdmin = new NS.ConfigAdmin(d['configadmin']);
			}else{
				this.configAdmin.update(d['configadmin']);
			}
		},

		_updatePaymentList: function(d){
			if (!L.isValue(d) || !L.isValue(d['payments']) || !L.isValue(d['payments']['list'])){
				return null;
			}
			this.paymentList.update(d['payments']['list']);
		},
		paymentListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'paymentlist'
			}, function(d){
				__self._updatePaymentList(d);
				NS.life(callback);
			});
		},
		paymentSave: function(paymentid, sd, callback){
			var list = this.paymentList, payment = null;
			var __self = this;
			this.ajax({
				'do': 'paymentsave',
				'paymentid': paymentid,
				'savedata': sd
			}, function(d){
				__self._updatePaymentList(d);
				if (L.isValue(d) && L.isValue(d['paymentid'])){
					payment = list.get(d['paymentid']);
				}
				NS.life(callback, payment);
			});
		},
		paymentListOrderSave: function(orders, callback){
			var __self = this;
			this.ajax({
				'do': 'paymentlistorder',
				'paymentorders': orders
			}, function(d){
				__self._updatePaymentList(d);
				NS.life(callback);
			});
		},
		paymentRemove: function(paymentid, callback){
			var __self = this;
			this.ajax({
				'do': 'paymentremove',
				'paymentid': paymentid
			}, function(d){
				__self.paymentList.remove(paymentid);
				NS.life(callback);
			});
		},

		_updateDeliveryList: function(d){
			if (!L.isValue(d) || !L.isValue(d['deliverys']) || !L.isValue(d['deliverys']['list'])){
				return null;
			}
			this.deliveryList.update(d['deliverys']['list']);
		},
		deliveryListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'deliverylist'
			}, function(d){
				__self._updateDeliveryList(d);
				NS.life(callback);
			});
		},
		deliverySave: function(deliveryid, sd, callback){
			var list = this.deliveryList, delivery = null;
			var __self = this;
			this.ajax({
				'do': 'deliverysave',
				'deliveryid': deliveryid,
				'savedata': sd
			}, function(d){
				__self._updateDeliveryList(d);
				if (L.isValue(d) && L.isValue(d['deliveryid'])){
					delivery = list.get(d['deliveryid']);
				}
				NS.life(callback, delivery);
			});
		},
		deliveryListOrderSave: function(orders, callback){
			var __self = this;
			this.ajax({
				'do': 'deliverylistorder',
				'deliveryorders': orders
			}, function(d){
				__self._updateDeliveryList(d);
				NS.life(callback);
			});
		},
		deliveryRemove: function(deliveryid, callback){
			var __self = this;
			this.ajax({
				'do': 'deliveryremove',
				'deliveryid': deliveryid
			}, function(d){
				__self.deliveryList.remove(deliveryid);
				NS.life(callback);
			});
		},

		_updateDiscountList: function(d){
			if (!L.isValue(d) || !L.isValue(d['discounts']) || !L.isValue(d['discounts']['list'])){
				return null;
			}
			this.discountList.update(d['discounts']['list']);
		},
		discountListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'discountlist'
			}, function(d){
				__self._updateDiscountList(d);
				NS.life(callback);
			});
		},
		discountSave: function(discountid, sd, callback){
			var list = this.discountList, discount = null;
			var __self = this;
			this.ajax({
				'do': 'discountsave',
				'discountid': discountid,
				'savedata': sd
			}, function(d){
				__self._updateDiscountList(d);
				if (L.isValue(d) && L.isValue(d['discountid'])){
					discount = list.get(d['discountid']);
				}
				NS.life(callback, discount);
			});
		},
		discountRemove: function(discountid, callback){
			var __self = this;
			this.ajax({
				'do': 'discountremove',
				'discountid': discountid
			}, function(d){
				__self.discountList.remove(discountid);
				NS.life(callback);
			});
		},
		ordering: function(sd, callback){
			var __self = this;
			this.ajax({
				'do': 'ordering',
				'savedata': sd
			}, function(d){
				__self._updateCartProductList(d);
				NS.life(callback);
			});
		},

		orderLoad: function(orderid, callback){
			this.ajax({
				'do': 'order',
				'orderid': orderid
			}, function(d){

				var order = null;
				if (L.isValue(d) && L.isValue(d['order'])){
					order = new NS.Order(d['order']);
				}

				NS.life(callback, order);
			});
		}
	};
	NS.manager = null;

	NS.initManager = function(callback, productToCart){
		productToCart = productToCart|0;
		if (L.isNull(NS.manager)){
			NS.manager = new Manager(callback, productToCart);
		}else{
			if (productToCart > 0){
				NS.manager.cartProductAdd(productToCart, function(){
					NS.life(callback, NS.manager);
				});
			}else{
				NS.life(callback, NS.manager);
			}
		}
	};

};