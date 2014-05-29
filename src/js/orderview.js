/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['cart.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var OrderViewWidget = function(container, orderid, cfg){
		cfg = L.merge({
			'order': null
		}, cfg || {});

		OrderViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, orderid, cfg);
	};
	YAHOO.extend(OrderViewWidget, BW, {
		init: function(orderid, cfg){
			this.orderid = orderid;
			this.cfg = cfg;
			this.order = cfg['order'] || null;
		},
		onLoad: function(orderid, cfg){
			var __self = this;
			NS.initManager(function(){
				NS.manager.orderLoad(orderid, function(order){
					__self._onLoadOrder(order);
				});
			});
		},
		_onLoadOrder: function(order){
			this.elHide('loading');
			this.elShow('view');
			
			this.order = order;
			this.render();
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['bnext']: this.save(); return true;
			}
		},
		render: function(){
			var order = this.order;
			if (!L.isValue(order)){ return; }

			var pay = order.getPayment(),
				deli = order.getDelivery();

			if (L.isValue(deli)){
				this.elHide('mydelivery');
				this.elShow('delivery,contadr');
			}else{
				this.elShow('mydelivery');
				this.elHide('delivery,contadr');
			}
			
			this.elSetHTML({
				'fio': order.firstName+' '+order.lastName,
				'ph': order.phone,
				'adr': order.address,
				'dsc': order.descript,
				'delivery': L.isValue(deli) ? deli.title : 'Самостоятельный вывоз',
				'payment': L.isValue(pay) ? pay.title : ''
			});
			
			this.cpListWidget = new NS.CartProductListWidget(this.gel('cart'), {
				'cartProductList': order.cartProductList,
				'readOnly': true
			});
		}
	});
	NS.OrderViewWidget = OrderViewWidget;		
};