/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var CartProductListWidget = function(container, cfg){
        cfg = L.merge({
            'cartProductList': null,
            'readOnly': false
        }, cfg || {});

        CartProductListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, cfg);
    };
    YAHOO.extend(CartProductListWidget, BW, {
        init: function(cfg){
            this.cfg = cfg;
            this.wsList = [];
            this.delivery = null;
        },
        destroy: function(){
            this.clearList();
            CartProductListWidget.superclass.destroy.call(this);
        },
        onLoad: function(cfg){

            if (cfg['readOnly']){
                Dom.addClass(this.gel('wrap'), 'rocplst');
            }

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
                __self = this, cfg = this.cfg;

            var cartProductList = L.isValue(cfg['cartProductList'])
                ? cfg['cartProductList'] : NS.manager.cartProductList;

            cartProductList.foreach(function(cartProduct){
                var div = document.createElement('div');
                div['cartProduct'] = cartProduct;

                elList.appendChild(div);
                var w = new NS.CartProductRowWidget(div, cartProduct, {
                    'onRemoveClick': function(w){
                        __self.onCartProductRemoveClick(w);
                    },
                    'onSelectClick': function(w){
                        __self.onCartProductSelectClick(w);
                    }
                });

                ws[ws.length] = w;
            });

            var sum = cartProductList.getSum();

            var deli = this.delivery;
            if (!L.isValue(deli) || deli.price == 0){
                this.elHide('delivery');
            } else {
                this.elShow('delivery');
                sum += deli.price;

                this.elSetHTML({
                    'deliprice': NS.numberFormat(deli.price)
                });
            }

            this.elSetHTML({
                'sm': NS.numberFormat(sum),
                'currency': NS.manager.currency.postfix
            });
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
        onCartProductRemoveClick: function(w){
            var __self = this;
            new CartProductRemovePanel(w.cartProduct, function(){
                __self.renderList();
            });
        },
        onCartProductSelectClick: function(w){
            this.allEditorClose(w);
        },
        onClick: function(el, tp){
            switch (el.id) {
                // case tp['badd']: this.showNewEditor(); return true;
            }
        },
        setDelivery: function(delivery){
            this.delivery = delivery;
            this.renderList();
        }
    });
    NS.CartProductListWidget = CartProductListWidget;

    var CartProductRowWidget = function(container, cartProduct, cfg){
        cfg = L.merge({
            'onRemoveClick': null,
            'onSelectClick': null
        }, cfg || {});
        CartProductRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, cartProduct, cfg);
    };
    YAHOO.extend(CartProductRowWidget, BW, {
        init: function(cartProduct, cfg){
            this.cartProduct = cartProduct;
            this.cfg = cfg;
            this.editorWidget = null;
        },
        buildTData: function(cartProduct, cfg){
            return {
                'url': L.isValue(cartProduct.product) ? cartProduct.product.url() : ""
            };
        },
        render: function(){
            var catprod = this.cartProduct;

            if (L.isValue(catprod.product)){
                var prod = catprod.product;

                this.elSetHTML({
                    'tl': prod.title,
                    'url': prod.url(),
                    'pc': NS.numberFormat(prod.ext['price'])

                });
            }
            this.elSetHTML({
                'qt': catprod.quantity,
                'sm': NS.numberFormat(catprod.getSum())
            });

        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bremove']:
                    this.onRemoveClick();
                    return true;
            }

            return false;
        },
        onRemoveClick: function(){
            NS.life(this.cfg['onRemoveClick'], this);
        },
        onSelectClick: function(){
            NS.life(this.cfg['onSelectClick'], this);
        },
        hide: function(){
            Dom.addClass(this.gel('id'), 'hide');
        },
        show: function(){
            Dom.removeClass(this.gel('id'), 'hide');
        }
    });
    NS.CartProductRowWidget = CartProductRowWidget;

    var CartProductRemovePanel = function(cartProduct, callback){
        this.cartProduct = cartProduct;
        this.callback = callback;
        CartProductRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(CartProductRemovePanel, Brick.widget.Dialog, {
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
            NS.manager.cartProductRemove(this.cartProduct.product.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.CartProductRemovePanel = CartProductRemovePanel;

};