/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['aui-tabview'],
    mod: [
        {name: '{C#MODNAME}', files: ['discountlist.js', 'paymentlist.js', 'deliverylist.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang;

    var buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var ConfigWidget = function(container){
        ConfigWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        });
    };
    YAHOO.extend(ConfigWidget, BW, {
        init: function(){
            this.discountWidget = null;
            this.paymentWidget = null;
            this.deliveryWidget = null;
            this.overWidget = null;
        },
        destroy: function(){
            if (L.isValue(this.paymentWidget)){
                this.discountWidget.destroy();
                this.paymentWidget.destroy();
                this.deliveryWidget.destroy();
                this.overWidget.destroy();
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

            this.discountWidget = new NS.DiscountListWidget(this.gel('discount'));
            this.paymentWidget = new NS.PaymentListWidget(this.gel('payment'));
            this.deliveryWidget = new NS.DeliveryListWidget(this.gel('delivery'));
            this.overWidget = new NS.EMailConfirmConfigWidget(this.gel('over'));

            new Y.TabView({srcNode: this.gel('view')}).render();
        }
    });
    NS.ConfigWidget = ConfigWidget;

    var EMailConfirmConfigWidget = function(container, cfg){
        cfg = Y.merge({}, cfg || {});

        EMailConfirmConfigWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'email'
        }, cfg);
    };
    YAHOO.extend(EMailConfirmConfigWidget, BW, {
        init: function(cfg){
            this.cfg = cfg;
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

            var cfga = NS.manager.configAdmin;

            if (L.isValue(cfga)){
                this.elSetValue({
                    'emls': cfga.emails
                });
            }
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
        save: function(){
            var sd = {
                'emls': this.gel('emls').value
            };

            this.elHide('btnsc');
            this.elShow('btnpc');

            var __self = this;
            NS.manager.configAdminSave(sd, function(){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc');
            });
        }
    });

    NS.EMailConfirmConfigWidget = EMailConfirmConfigWidget;

};