/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['tabview'],
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var BillingWidget = function(container){
		BillingWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(BillingWidget, BW, {
		init: function(cfg){
			this.orderListNewWidget = null;
			this.orderListExecWidget = null;
			this.orderListArchiveWidget = null;
			this.orderListRecycleWidget = null;
		},
		destroy: function(){
			if (L.isValue(this.orderListNewWidget)){
				this.orderListNewWidget.destroy();
				this.orderListExecWidget.destroy();
				this.orderListArchiveWidget.destroy();
				this.orderListRecycleWidget.destroy();
			}
			BillingWidget.superclass.destroy.call(this);
		},
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				__self._onLoadManager();
			});
		},
		_onLoadManager: function(){
			this.elHide('loading');
			this.elShow('view');
			
			new YAHOO.widget.TabView(this.gel('view'));
			this.orderListNewWidget = new NS.OrderListWiget(this.gel('new'), {
				'status': NS.ORDERSTATUS.NEW
			});
		}
	});
	NS.BillingWidget = BillingWidget;
	
	var OrderListWidget = function(container, cfg){
		cfg = L.merge({
			'status': NS.ORDERSTATUS.NEW
		}, cfg || {});
		OrderListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'list' 
		}, cfg);
	};
	YAHOO.extend(OrderListWidget, BW, {
		init: function(cfg){
			
		}
	});
	NS.OrderListWidget = OrderListWidget;
};