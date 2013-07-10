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
			
			this.authWidget = null;
		},
		destroy: function(){
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
			this.elHide('loading');
			this.elShow('view');

			if (UID == 0){
				this.authWidget = new NS.OrderingAuthWidget(this.gel('auth'));
			}
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['badd']: this.showNewEditor(); return true;
			}
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
		cfg = L.merge({}, cfg || {});

		OrderingAuthWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'auth' 
		}, cfg);
	};
	YAHOO.extend(OrderingAuthWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
		},
		destroy: function(){
			OrderingAuthWidget.superclass.destroy.call(this);
		},
		onLoad: function(cfg){
			this.authWidget = new Brick.mod.user.EasyAuthRegWidget(this.gel('auth'), {
				'onAuthCallback': function(){
					Brick.console(arguments);
					// Brick.Page.reload(NS.navigator.performer.discussproj(perf.id));
					// Brick.Page.reload();
				}
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['badd']: this.showNewEditor(); return true;
			}
		}
	});
	NS.OrderingAuthWidget = OrderingAuthWidget;

};