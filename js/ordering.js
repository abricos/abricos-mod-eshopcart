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
			var TM = this._TM, lst = "";
			NS.manager.paymentList.foreach(function(item){
				lst += TM.replace('paymentrow', {
					'id': item.id,
					'tl': item.title
				});
			});
			this.elSetHTML('table', TM.replace('paymenttable', {
				'rows': lst
			}));
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
		}
	});
	NS.OrderingPaymentWidget = OrderingPaymentWidget;	

};