/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['tabview'],
	mod:[
		{name: '{C#MODNAME}', files: ['paymentlist.js', 'deliverylist.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		buildTemplate = this.buildTemplate;
	
	var ConfigWidget = function(container){
		ConfigWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(ConfigWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.paymentWidget = null;
			this.deliveryWidget = null;
		},
		destroy: function(){
			if (L.isValue(this.paymentWidget)){
				this.paymentWidget.destroy();
				this.deliveryWidget.destroy();
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
			this.paymentWidget = new NS.PaymentListWidget(this.gel('payment'));
			this.deliveryWidget = new NS.DeliveryListWidget(this.gel('delivery'));
		}
	});
	NS.ConfigWidget = ConfigWidget;
};