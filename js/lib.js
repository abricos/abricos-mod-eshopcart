/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'widget', files: ['notice.js']},
        {name: '{C#MODNAME}', files: ['roles.js']}
	]		
};
Component.entryPoint = function(NS){

	var L = YAHOO.lang,
		R = NS.roles;
	
	var SysNS = Brick.mod.sys;

	var buildTemplate = this.buildTemplate;
	buildTemplate({},'');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	var Payment = function(d){
		d = L.merge({
			'tl': '',
			'def': 0,
			'ord': 0,
			'clr': ''
		}, d || {});
		Payment.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Payment, SysNS.Item, {
		update: function(d){
			this.title = d['tl'];
			this.color = d['clr'];
			this.isDefault = (d['def']|0)>0;
			this.order = d['ord']|0;
		}
	});
	NS.Payment = Payment;
	
	var PaymentList = function(d){
		PaymentList.superclass.constructor.call(this, d, Payment);
	};
	YAHOO.extend(PaymentList, SysNS.ItemList, {
		getDefaultId: function(){
			var defid = 0;
			this.foreach(function(priotiry){
				if (priotiry.isDefault){
					defid = priotiry.id;
					return true;
				}
			});
			return defid;
		}
	});
	NS.PaymentList = PaymentList;
	
	var Manager = function (callback){
		this.init(callback);
	};
	Manager.prototype = {
		init: function(callback){
			NS.manager = this;
			
			this.paymentList = new PaymentList();
			
			var __self = this;
			R.load(function(){
				__self.ajax({
					'do': 'initdata'
				}, function(d){
					__self._updatePaymentList(d);
					NS.life(callback, __self);
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
		paymentRemove: function(paymentid, callback){
			var __self = this;
			this.ajax({
				'do': 'paymentremove',
				'paymentid': paymentid
			}, function(d){
				__self.paymentList.remove(paymentid);
				NS.life(callback);
			});			
		}
	};
	NS.manager = null;
	
	NS.initManager = function(callback){
		if (L.isNull(NS.manager)){
			NS.manager = new Manager(callback);
		}else{
			NS.life(callback, NS.manager);
		}
	};
	
};