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

    var DiscountEditorWidget = function(container, discount, cfg){
        cfg = L.merge({
            'onCancelClick': null,
            'onSave': null
        }, cfg || {});
        DiscountEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, discount, cfg);
    };
    YAHOO.extend(DiscountEditorWidget, BW, {
        init: function(discount, cfg){
            this.discount = discount;
            this.cfg = cfg;
        },
        destroy: function(){
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.unlock();
            }
            DiscountEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(discount){
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.lock();
            }
            this.discount = discount;

            this.elHide('loading');
            this.elShow('view');

            this.elSetValue({
                'tp': discount.type,
                'tl': discount.title,
                'dsc': discount.descript,
                'pc': discount.price,
                'ptp': discount.priceType,
                'fsm': discount.fromSum,
                'esm': discount.endSum,
            });

            this.gel('dis').checked = discount.isDisabled;

            var elTitle = this.gel('tl');
            setTimeout(function(){
                try {
                    elTitle.focus();
                } catch (e) {
                }
            }, 100);

            var __self = this;
            E.on(this.gel('id'), 'keypress', function(e){
                if ((e.keyCode == 13 || e.keyCode == 10) && e.ctrlKey){
                    __self.save();
                    return true;
                }
                return false;
            });
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bsave']:
                    this.save();
                    return true;
                case tp['bcancel']:
                    this.onCancelClick();
                    return true;
            }
            return false;
        },
        onCancelClick: function(){
            NS.life(this.cfg['onCancelClick'], this);
        },
        save: function(){
            var cfg = this.cfg;
            var discount = this.discount;
            var sd = {
                'id': discount.id,
                'tp': this.gel('tp').value,
                'tl': this.gel('tl').value,
                'dsc': this.gel('dsc').value,
                'pc': this.gel('pc').value,
                'ptp': this.gel('ptp').value,
                'fsm': this.gel('fsm').value,
                'esm': this.gel('esm').value,
                'dis': this.gel('dis').checked ? 1 : 0
            };

            this.elHide('btnsc');
            this.elShow('btnpc');

            var __self = this;
            NS.manager.discountSave(discount.id, sd, function(discount){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc');
                NS.life(cfg['onSave'], __self, discount);
            }, discount);
        }
    });
    NS.DiscountEditorWidget = DiscountEditorWidget;
};