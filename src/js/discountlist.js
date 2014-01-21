/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['dragdrop.js','discounteditor.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;

	var DiscountListWidget = function(container, cfg){
		cfg = L.merge({
		}, cfg || {});
		
		DiscountListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, cfg);
	};
	YAHOO.extend(DiscountListWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
			this.wsList = [];
			
			this.newEditorWidget = null;
		},
		destroy: function(){
			this.clearList();
			DiscountListWidget.superclass.destroy.call(this);			
		},
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				__self._onLoadManager();
			});
		},
		_onLoadManager: function(){
			this.renderList();
		},
		clearList: function(){
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.elSetHTML('list', '');
		},
		renderList: function(){
			this.clearList();
			
			var elList = this.gel('list'), ws = this.wsList, 
				__self = this;
			
			NS.manager.discountList.foreach(function(discount){
				var div = document.createElement('div');
				div['discount'] = discount;

				elList.appendChild(div);
				var w = new NS.DiscountRowWidget(div, discount, {
					'onEditClick': function(w){__self.onDiscountEditClick(w);},
					'onRemoveClick': function(w){__self.onDiscountRemoveClick(w);},
					'onSelectClick': function(w){__self.onDiscountSelectClick(w);},
					'onSave': function(w){ __self.renderList(); }
				});
				
				new NS.RowDragItem(div, {
					'endDragCallback': function(dgi, elDiv){
						var chs = elList.childNodes, ordb = NS.manager.discountList.count();
						var orders = [];
						for (var i=0;i<chs.length;i++){
							var discount = chs[i]['discount'];
							if (discount){
								discount.order = ordb;
								orders[orders.length] = {
									'id': discount.id,
									'o': ordb
								};
								ordb--;
							}
						}
						NS.manager.discountList.reorder();
						NS.manager.discountListOrderSave(orders);
						__self.renderList();
					}
				});
		
				ws[ws.length] = w;
			});
			
			new YAHOO.util.DDTarget(elList);
		},
		foreach: function(f){
			if (!L.isFunction(f)){ return; }
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				if (f(ws[i])){ return; }
			}
		},
		allEditorClose: function(wExclude){
			this.newEditorClose();
			this.foreach(function(w){
				if (w != wExclude){
					w.editorClose();
				}
			});
		},
		onDiscountEditClick: function(w){
			this.allEditorClose(w);
			w.editorShow();
		},
		onDiscountRemoveClick: function(w){
			var __self = this;
			new DiscountRemovePanel(w.discount, function(){
				__self.renderList();
			});
		},
		onDiscountSelectClick: function(w){
			this.allEditorClose(w);
		},
		showNewEditor: function(){
			if (!L.isNull(this.newEditorWidget)){ return; }
			
			this.allEditorClose();
			var __self = this;
			var discount = new NS.Discount();

			this.newEditorWidget = new NS.DiscountEditorWidget(this.gel('neweditor'), discount, {
				'onCancelClick': function(wEditor){ __self.newEditorClose(); },
				'onSave': function(wEditor, discount){
					__self.newEditorClose(); 
					__self.renderList();
				}
			});
		},
		newEditorClose: function(){
			if (L.isNull(this.newEditorWidget)){ return; }
			this.newEditorWidget.destroy();
			this.newEditorWidget = null;
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['badd']:
				this.showNewEditor();
				return true;
			}
		}
	});
	NS.DiscountListWidget = DiscountListWidget;
	
	var DiscountRowWidget = function(container, discount, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onRemoveClick': null,
			'onSelectClick': null,
			'onSave': null
		}, cfg || {});
		DiscountRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, discount, cfg);
	};
	YAHOO.extend(DiscountRowWidget, BW, {
		init: function(discount, cfg){
			this.discount = discount;
			this.cfg = cfg;
			this.editorWidget = null;
		},
		onLoad: function(discount){
			var __self = this;
			
			E.on(this.gel('id'), 'dblclick', function(e){
				__self.onEditClick();
			});
		},
		render: function(){
			var discount = this.discount;
			
			this.elSetHTML({
				'tl': discount.title,
				'desc': discount.descript
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bedit']: case tp['beditc']:
				this.onEditClick();
				return true;
			case tp['bremove']: case tp['bremovec']:
				this.onRemoveClick();
				return true;
			}
			
			return false;
		},
		onEditClick: function(){
			NS.life(this.cfg['onEditClick'], this);
		},
		onRemoveClick: function(){
			NS.life(this.cfg['onRemoveClick'], this);
		},
		onSelectClick: function(){
			NS.life(this.cfg['onSelectClick'], this);
		},
		onSave: function(){
			NS.life(this.cfg['onSave'], this);
		},
		editorShow: function(){
			if (!L.isNull(this.editorWidget)){ return; }
			var __self = this;
			this.editorWidget = 
				new NS.DiscountEditorWidget(this.gel('easyeditor'), this.discount, {
					'onCancelClick': function(wEditor){ __self.editorClose(); },
					'onSave': function(wEditor){ 
						__self.editorClose(); 
						__self.onSave();
					}
				});

			Dom.addClass(this.gel('wrap'), 'rborder');
			Dom.addClass(this.gel('id'), 'rowselect');
			this.elHide('menu');
			this.render();
		},
		editorClose: function(){
			if (L.isNull(this.editorWidget)){ return; }

			Dom.removeClass(this.gel('wrap'), 'rborder');
			Dom.removeClass(this.gel('id'), 'rowselect');
			this.elShow('menu');

			this.editorWidget.destroy();
			this.editorWidget = null;
			this.render();
		},
		hide: function(){
			Dom.addClass(this.gel('id'), 'hide');
		},
		show: function(){
			Dom.removeClass(this.gel('id'), 'hide');
		}
	});
	NS.DiscountRowWidget = DiscountRowWidget;	

	var DiscountRemovePanel = function(discount, callback){
		this.discount = discount;
		this.callback = callback;
		DiscountRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(DiscountRemovePanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'removepanel').replace('removepanel');
		},
		onClick: function(el){
			var tp = this._TId['removepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bremove']: this.remove(); return true;
			}
			return false;
		},
		remove: function(){
			var TM = this._TM, gel = function(n){ return  TM.getEl('removepanel.'+n); },
				__self = this;
			Dom.setStyle(gel('btns'), 'display', 'none');
			Dom.setStyle(gel('bloading'), 'display', '');
			NS.manager.discountRemove(this.discount.id, function(){
				__self.close();
				NS.life(__self.callback);
			});
		}
	});
	NS.DiscountRemovePanel = DiscountRemovePanel;

};