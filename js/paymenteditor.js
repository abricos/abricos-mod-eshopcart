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

	var PaymentEditorWidget = function(container, pay, cfg){
		cfg = L.merge({
			'onCancelClick': null,
			'onSave': null
		}, cfg || {});
		PaymentEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, pay, cfg);
	};
	YAHOO.extend(PaymentEditorWidget, BW, {
		init: function(pay, cfg){
			this.pay = pay;
			this.cfg = cfg;
		},
		destroy: function(){
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.unlock();
			} 
			PaymentEditorWidget.superclass.destroy.call(this);
		},
		onLoad: function(pay){
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.lock();
			} 
			this.pay = pay;

			this.elHide('loading');
			this.elShow('view');
			
			this.elSetValue({
				'tl': pay.title,
				'dsc': pay.descript
			});
			this.gel('def').checked = !!pay.isDefault;
			
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
			var pay = this.pay;
			var sd = {
				'id': pay.id,
				'tl': this.gel('tl').value,
				'dsc': this.gel('dsc').value,
				'def': this.gel('def').checked ? 1 : 0
			};
			
			this.elHide('btnsc');
			this.elShow('btnpc');

			var __self = this;
			NS.manager.paymentSave(pay.id, sd, function(pay){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSave'], __self, pay);
			}, pay);
		}
	});
	NS.PaymentEditorWidget = PaymentEditorWidget;
};