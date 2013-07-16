/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var UID = Brick.env.user.id,
		NUID = UID;

	var OrderingWidget = function(container, cfg){
		cfg = L.merge({
		}, cfg || {});

		OrderingWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, cfg);
	};
	YAHOO.extend(OrderingWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
			
			this.widgets = {};
		},
		destroy: function(){
			for (var n in this.widgets){
				this.widgets[n].destroy();
			}
			OrderingWidget.superclass.destroy.call(this);
		},
		onLoad: function(cfg){
			var __self = this;
			NS.initManager(function(){
				if (UID == 0){
					Brick.ff('user', 'guest', function(){
						__self._onLoadManager();
					});
				}else{
					__self._onLoadManager();
				}
			});
		},
		_onLoadManager: function(){
			NS.manager.updateCartInfoElements();
			
			this.elHide('loading');
			this.elShow('view');

			var __self = this;
			
			this.widgets['delivery'] = new NS.OrderingDeliveryWidget(this.gel('delivery'), {
				'onNext': function(){
					__self.showPaymentPage();
				}
			});
			
			this.widgets['payment'] = new NS.OrderingPaymentWidget(this.gel('payment'), {
				'onNext': function(){
					__self.showAcceptPage();
				},
				'onPrev': function(){
					__self.showDeliveryPage();
				}
			});
			
			this.widgets['accept'] = new NS.OrderingAcceptWidget(this.gel('accept'), {
				'owner': this,
				'onNext': function(){
					Brick.console('!!! OK !!!');
				},
				'onPrev': function(){
					__self.showPaymentPage();
				}
			});
			
			if (UID == 0){
				this.widgets['auth'] = new NS.OrderingAuthWidget(this.gel('auth'), {
					'onLogin': function(userid){
						NUID = userid;
						__self.showDeliveryPage();
					},
					'onNext': function(){
						__self.showDeliveryPage();
					}
				});
				this.showWidget('auth');
			}else{
				this.showWidget('delivery');
			}
			
			this.showAcceptPage();
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['badd']: this.showNewEditor(); return true;
			}
		},
		showWidget: function(n){
			for (var ni in this.widgets){
				this.elHide(ni);
			}
			this.elShow(n);
		},
		showDeliveryPage: function(){
			this.showWidget('delivery');
		},
		showPaymentPage: function(){
			this.showWidget('payment');
		},
		showAcceptPage: function(){
			this.widgets['accept'].refresh();
			this.showWidget('accept');
		},
		getPayment: function(){
			return this.widgets['payment'].getValue();
		},
		getDelivery: function(){
			return this.widgets['delivery'].getValue();
		},
		getCustomerInfo: function(){
			return this.widgets['delivery'].getCustomerInfo();
		}
	});
	NS.OrderingWidget = OrderingWidget;
	
	var OrderingPanel = function(){
		OrderingPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(OrderingPanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'panel').replace('panel');
		},
		onLoad: function(){
			this.widget = new NS.OrderingWidget(this._TM.getEl('panel.widget'));
		}
	});
	NS.OrderingPanel = OrderingPanel;

	var OrderingAuthWidget = function(container, cfg){
		cfg = L.merge({
			'onLogin': null,
			'onNext': null
		}, cfg || {});

		OrderingAuthWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'auth' 
		}, cfg);
	};
	YAHOO.extend(OrderingAuthWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		destroy: function(){
			this.authWidget.destroy();
			OrderingAuthWidget.superclass.destroy.call(this);
		},
		onLoad: function(cfg){
			this.authWidget = new Brick.mod.user.EasyAuthRegWidget(this.gel('auth'), {
				'onAuthCallback': function(userid){
					NS.life(cfg['onLogin'], userid);
				}
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bnext']: 
				NS.life(this.cfg['onNext']);
				return true;
			}
		}
	});
	NS.OrderingAuthWidget = OrderingAuthWidget;

	var OrderingDeliveryWidget = function(container, cfg){
		cfg = L.merge({
			'onNext': null
		}, cfg || {});

		OrderingDeliveryWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'delivery,deliverytable,deliveryrow,deliveryrowprice' 
		}, cfg);
	};
	YAHOO.extend(OrderingDeliveryWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		onLoad: function(cfg){
			var TM = this._TM, lst = "";
			NS.manager.deliveryList.foreach(function(item){
				lst += TM.replace('deliveryrow', {
					'id': item.id,
					'tl': item.title,
					'pc': item.price > 0 ? TM.replace('deliveryrowprice', {
						'val': NS.numberFormat(item.price)
					}) : ''
				});
				
			});
			this.elSetHTML('table', TM.replace('deliverytable', {
				'rows': lst
			}));
			this.updateFields();
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bnext']: 
				NS.life(this.cfg['onNext']);
				return true;
			}
			this.updateFields();
		},
		updateFields: function(){
			var TId = this._TId, selid = 0, __self = this;
			NS.manager.deliveryList.foreach(function(item){
				var el = Dom.get(TId['deliveryrow']['id']+'-'+item.id);
				if (L.isValue(el) && el.checked){
					selid = item.id;
					return true;
				}
			});
			if (selid == 0){
				__self.elHide('fieldadr');
			}else{
				__self.elShow('fieldadr');
			}
		},
		getValue: function(){
			var TId = this._TId, selItem = null;
			NS.manager.deliveryList.foreach(function(item){
				var el = Dom.get(TId['deliveryrow']['id']+'-'+item.id);
				if (el.checked){
					selItem = item;
					return true;
				}
			});
			return selItem;
		},
		getCustomerInfo: function(){
			return {
				'fnm': this.gel('fnm').value,
				'lnm': this.gel('lnm').value,
				'ph': this.gel('ph').value,
				'adr': this.gel('adr').value,
				'dsc': this.gel('dsc').value
			};
		}
	});
	NS.OrderingDeliveryWidget = OrderingDeliveryWidget;

	var OrderingPaymentWidget = function(container, cfg){
		cfg = L.merge({
			'onNext': null,
			'onPrev': null
		}, cfg || {});

		OrderingPaymentWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'payment,paymenttable,paymentrow' 
		}, cfg);
	};
	YAHOO.extend(OrderingPaymentWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		onLoad: function(cfg){
			var TM = this._TM, TId = this._TId, lst = "";
			NS.manager.paymentList.foreach(function(item){
				lst += TM.replace('paymentrow', {
					'id': item.id,
					'tl': item.title
				});
			});
			this.elSetHTML('table', TM.replace('paymenttable', {
				'rows': lst
			}));
			NS.manager.paymentList.foreach(function(item){
				var el = Dom.get(TId['paymentrow']['id']+'-'+item.id);
				el.checked = true;
				return true;
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bnext']: 
				NS.life(this.cfg['onNext']);
				return true;
			case tp['bprev']: 
				NS.life(this.cfg['onPrev']);
				return true;
			}
		},
		getValue: function(){
			var TId = this._TId, selItem = null;
			NS.manager.paymentList.foreach(function(item){
				var el = Dom.get(TId['paymentrow']['id']+'-'+item.id);
				if (el.checked){
					selItem = item;
					return true;
				}
			});
			return selItem;
		}		
	});
	NS.OrderingPaymentWidget = OrderingPaymentWidget;	

	var OrderingAcceptWidget = function(container, cfg){
		cfg = L.merge({
			'onNext': null,
			'onPrev': null,
			'owner': null
		}, cfg || {});

		OrderingAcceptWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'accept' 
		}, cfg);
	};
	YAHOO.extend(OrderingAcceptWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		onLoad: function(cfg){
			this.cpListWidget = new NS.CartProductListWidget(this.gel('cart'));
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bnext']: 
				NS.life(this.cfg['onNext']);
				return true;
			case tp['bprev']: 
				NS.life(this.cfg['onPrev']);
				return true;
			}
		},
		refresh: function(){
			var owner = this.cfg.owner,
				pay = owner.getPayment(),
				deli = owner.getDelivery(),
				cinfo = owner.getCustomerInfo();
			
			if (L.isValue(deli)){
				this.elHide('mydelivery');
				this.elShow('delivery,contadr');
			}else{
				this.elShow('mydelivery');
				this.elHide('delivery,contadr');
			}
			
			this.elSetHTML({
				'fnm': cinfo['fnm']+' '+cinfo['lnm'],
				'ph': cinfo['ph'],
				'dsc': cinfo['dsc'],
				'delivery': L.isValue(deli) ? deli.title : 'Самостоятельный вывоз',
				'payment': L.isValue(pay) ? pay.title : ''
			});
			
			this.cpListWidget.setDelivery(deli);
		}
	});
	NS.OrderingAcceptWidget = OrderingAcceptWidget;	
	
};