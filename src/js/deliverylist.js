/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'catalog', files: ['dragdrop.js']},
        {name: '{C#MODNAME}', files: ['deliveryeditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget,
        NSCat = Brick.mod.catalog;

    var DeliveryListWidget = function(container, cfg){
        cfg = L.merge({}, cfg || {});

        DeliveryListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, cfg);
    };
    YAHOO.extend(DeliveryListWidget, BW, {
        init: function(cfg){
            this.cfg = cfg;
            this.wsList = [];

            this.newEditorWidget = null;
        },
        destroy: function(){
            this.clearList();
            DeliveryListWidget.superclass.destroy.call(this);
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
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.elSetHTML('list', '');
        },
        renderList: function(){
            this.clearList();

            var elList = this.gel('list'), ws = this.wsList,
                __self = this;

            NS.manager.deliveryList.foreach(function(delivery){
                var div = document.createElement('div');
                div['delivery'] = delivery;

                elList.appendChild(div);
                var w = new NS.DeliveryRowWidget(div, delivery, {
                    'onEditClick': function(w){
                        __self.onDeliveryEditClick(w);
                    },
                    'onRemoveClick': function(w){
                        __self.onDeliveryRemoveClick(w);
                    },
                    'onSelectClick': function(w){
                        __self.onDeliverySelectClick(w);
                    },
                    'onSave': function(w){
                        __self.renderList();
                    }
                });

                new NSCat.RowDragItem(div, {
                    'endDragCallback': function(dgi, elDiv){
                        var chs = elList.childNodes, ordb = NS.manager.deliveryList.count();
                        var orders = [];
                        for (var i = 0; i < chs.length; i++){
                            var delivery = chs[i]['delivery'];
                            if (delivery){
                                delivery.order = ordb;
                                orders[orders.length] = {
                                    'id': delivery.id,
                                    'o': ordb
                                };
                                ordb--;
                            }
                        }
                        NS.manager.deliveryList.reorder();
                        NS.manager.deliveryListOrderSave(orders);
                        __self.renderList();
                    }
                });

                ws[ws.length] = w;
            });

            new YAHOO.util.DDTarget(elList);
        },
        foreach: function(f){
            if (!L.isFunction(f)){
                return;
            }
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                if (f(ws[i])){
                    return;
                }
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
        onDeliveryEditClick: function(w){
            this.allEditorClose(w);
            w.editorShow();
        },
        onDeliveryRemoveClick: function(w){
            var __self = this;
            new DeliveryRemovePanel(w.delivery, function(){
                __self.renderList();
            });
        },
        onDeliverySelectClick: function(w){
            this.allEditorClose(w);
        },
        showNewEditor: function(){
            if (!L.isNull(this.newEditorWidget)){
                return;
            }

            this.allEditorClose();
            var __self = this;
            var delivery = new NS.Delivery();

            this.newEditorWidget = new NS.DeliveryEditorWidget(this.gel('neweditor'), delivery, {
                'onCancelClick': function(wEditor){
                    __self.newEditorClose();
                },
                'onSave': function(wEditor, delivery){
                    __self.newEditorClose();
                    __self.renderList();
                }
            });
        },
        newEditorClose: function(){
            if (L.isNull(this.newEditorWidget)){
                return;
            }
            this.newEditorWidget.destroy();
            this.newEditorWidget = null;
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['badd']:
                    this.showNewEditor();
                    return true;
            }
        }
    });
    NS.DeliveryListWidget = DeliveryListWidget;

    var DeliveryRowWidget = function(container, delivery, cfg){
        cfg = L.merge({
            'onEditClick': null,
            'onRemoveClick': null,
            'onSelectClick': null,
            'onSave': null
        }, cfg || {});
        DeliveryRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, delivery, cfg);
    };
    YAHOO.extend(DeliveryRowWidget, BW, {
        init: function(delivery, cfg){
            this.delivery = delivery;
            this.cfg = cfg;
            this.editorWidget = null;
        },
        onLoad: function(delivery){
            var __self = this;

            E.on(this.gel('id'), 'dblclick', function(e){
                __self.onEditClick();
            });
        },
        render: function(){
            var delivery = this.delivery;

            var tl = delivery.title;
            if (delivery.price > 0){
                tl += ", " + delivery.price;
            }
            if (delivery.fromZero > 0){
                tl += ", " + delivery.fromZero;
            }

            this.elSetHTML({
                'tl': tl,
                'desc': delivery.descript
            });
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bedit']:
                case tp['beditc']:
                    this.onEditClick();
                    return true;
                case tp['bremove']:
                case tp['bremovec']:
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
            if (!L.isNull(this.editorWidget)){
                return;
            }
            var __self = this;
            this.editorWidget =
                new NS.DeliveryEditorWidget(this.gel('easyeditor'), this.delivery, {
                    'onCancelClick': function(wEditor){
                        __self.editorClose();
                    },
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
            if (L.isNull(this.editorWidget)){
                return;
            }

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
    NS.DeliveryRowWidget = DeliveryRowWidget;

    var DeliveryRemovePanel = function(delivery, callback){
        this.delivery = delivery;
        this.callback = callback;
        DeliveryRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(DeliveryRemovePanel, Brick.widget.Dialog, {
        initTemplate: function(){
            return buildTemplate(this, 'removepanel').replace('removepanel');
        },
        onClick: function(el){
            var tp = this._TId['removepanel'];
            switch (el.id) {
                case tp['bcancel']:
                    this.close();
                    return true;
                case tp['bremove']:
                    this.remove();
                    return true;
            }
            return false;
        },
        remove: function(){
            var TM = this._TM, gel = function(n){
                    return TM.getEl('removepanel.' + n);
                },
                __self = this;
            Dom.setStyle(gel('btns'), 'display', 'none');
            Dom.setStyle(gel('bloading'), 'display', '');
            NS.manager.deliveryRemove(this.delivery.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.DeliveryRemovePanel = DeliveryRemovePanel;

};