/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['tabview'],
	mod:[
		{name: '{C#MODNAME}', files: ['discountlist.js', 'paymentlist.js', 'deliverylist.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var ConfigWidget = function(container){
		ConfigWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(ConfigWidget, BW, {
		init: function(cfg){
			this.discountWidget = null;
			this.paymentWidget = null;
			this.deliveryWidget = null;
			this.overWidget = null;
		},
		destroy: function(){
			if (L.isValue(this.paymentWidget)){
				this.discountWidget.destroy();
				this.paymentWidget.destroy();
				this.deliveryWidget.destroy();
				this.overWidget.destroy();
			}
			ConfigWidget.superclass.destroy.call(this);
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
			this.discountWidget = new NS.DiscountListWidget(this.gel('discount'));
			this.paymentWidget = new NS.PaymentListWidget(this.gel('payment'));
			this.deliveryWidget = new NS.DeliveryListWidget(this.gel('delivery'));
			this.overWidget = new NS.EMailConfirmConfigWidget(this.gel('over'));
		}
	});
	NS.ConfigWidget = ConfigWidget;
	
	var EMailConfirmConfigWidget = function(container, cfg){
		cfg = L.merge({
		}, cfg || {});
		
		EMailConfirmConfigWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'email' 
		}, cfg);
	};
	YAHOO.extend(EMailConfirmConfigWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
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
		}
	});
	
	NS.EMailConfirmConfigWidget = EMailConfirmConfigWidget;

};