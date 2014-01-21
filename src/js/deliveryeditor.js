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

	var DeliveryEditorWidget = function(container, delivery, cfg){
		cfg = L.merge({
			'onCancelClick': null,
			'onSave': null
		}, cfg || {});
		DeliveryEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, delivery, cfg);
	};
	YAHOO.extend(DeliveryEditorWidget, BW, {
		init: function(delivery, cfg){
			this.delivery = delivery;
			this.cfg = cfg;
		},
		destroy: function(){
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.unlock();
			} 
			DeliveryEditorWidget.superclass.destroy.call(this);
		},
		onLoad: function(delivery){
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.lock();
			} 
			this.delivery = delivery;

			this.elHide('loading');
			this.elShow('view');
			
			this.elSetValue({
				'tl': delivery.title,
				'pc': delivery.price,
				'zr': delivery.fromZero,
				'dsc': delivery.descript
			});
			this.gel('def').checked = !!delivery.isDefault;
			
			var elTitle = this.gel('tl');
			setTimeout(function(){try{elTitle.focus();}catch(e){}}, 100);
			
			var __self = this;
			E.on(this.gel('id'), 'keypress', function(e){
				if ((e.keyCode == 13 || e.keyCode == 10) && e.ctrlKey){ 
					__self.save(); return true; 
				}
				return false;
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.onCancelClick(); return true;
			}
			return false;
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		save: function(){
			var cfg = this.cfg;
			var delivery = this.delivery;
			var sd = {
				'id': delivery.id,
				'tl': this.gel('tl').value,
				'pc': this.gel('pc').value,
				'zr': this.gel('zr').value,
				'dsc': this.gel('dsc').value,
				'def': this.gel('def').checked ? 1 : 0
			};
			
			this.elHide('btnsc');
			this.elShow('btnpc');

			var __self = this;
			NS.manager.deliverySave(delivery.id, sd, function(delivery){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSave'], __self, delivery);
			}, delivery);
		}
	});
	NS.DeliveryEditorWidget = DeliveryEditorWidget;
};