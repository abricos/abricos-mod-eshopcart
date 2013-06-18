/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
		{name: '{C#MODNAME}', files: ['lib.js']}
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
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				__self._onLoadManager();
			});
		},
		_onLoadManager: function(){
			this.elHide('loading');
			this.elShow('view');
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['badd']: this.showNewEditor(); return true;
			}
		}
	});
	NS.CartViewWidget = CartViewWidget;
	
	var CartViewPanel = function(){
		CartViewPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(CartViewPanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'panel').replace('panel');
		},
		onLoad: function(){
			new NS.CartViewWidget(this._TM.getEl('panel.widget'));
		}
	});
	NS.CartViewPanel = CartViewPanel;


};