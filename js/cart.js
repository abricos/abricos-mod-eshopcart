/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		// {name: 'sys', files: ['container.js']},
		{name: '{C#MODNAME}', files: ['cartproductlist.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;

	var CartViewWidget = function(container, cfg){
		cfg = L.merge({
			'addToCart': 0
		}, cfg || {});

		CartViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, cfg);
	};
	YAHOO.extend(CartViewWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		destroy: function(){
			CartViewWidget.superclass.destroy.call(this);
		},
		onLoad: function(cfg){
			var __self = this;
			NS.initManager(function(){
				__self._onLoadManager();
			}, cfg['addToCart']);
		},
		_onLoadManager: function(){
			this.elHide('loading');
			this.elShow('view');
			
			this.listWidget = new NS.CartProductListWidget(this.gel('list'));
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['badd']: this.showNewEditor(); return true;
			}
		}
	});
	NS.CartViewWidget = CartViewWidget;
	
	var CartViewPanel = function(cartConfig){
		 this.cartConfig = L.merge({
			'buttonElement': null // вызов корзины по нажатию на кнопку -купить-
		}, cartConfig || {});
		CartViewPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(CartViewPanel, Brick.widget.Dialog, {
		
		initTemplate: function(){
			return buildTemplate(this, 'panel').replace('panel');
		},
		onLoad: function(){
			var cfg = this.cartConfig;
			if (L.isValue(cfg['buttonElement']) && cfg['buttonElement'].className){
				
				var arr = cfg['buttonElement'].className.split(' ');
				for (var i=0;i<arr.length;i++){
					var aa = arr[i].split('-');
					if (aa[0] == 'product'){
						cfg['addToCart'] = aa[1]|0;
						break;
					}
				}
			}
				
			new NS.CartViewWidget(this._TM.getEl('panel.widget'), cfg);
		}
	});
	NS.CartViewPanel = CartViewPanel;


};