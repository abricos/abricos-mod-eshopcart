/**
 * @package Abricos
 * @copyright Copyright (C) 2014 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['node']
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    var find = function(node, act){
        if (node.getData('toggle') === act){
            return node;
        }
        if ((node = node.ancestor('[data-toggle]')) &&
            node.getData('toggle') === act){
            return node;
        }
        return null;
    };

    var setWait = function(target, isWait){
        target.all('[data-wait]').each(function(waitNode){
            switch (waitNode.getData('wait')){
                case "hide":
                    isWait ? waitNode.addClass('hide') : waitNode.removeClass('hide');
                    break;
                case "show":
                    isWait ? waitNode.removeClass('hide') : waitNode.addClass('hide');
                    break;
            }
        });
    };

    Y.one('body').on('click', function(e){

        var node, productId;

        if ((node = find(e.target, 'product-to-cart')) &&
            (productId = node.getData('product') | 0) > 0){

            setWait(node, true);
            Brick.use('eshopcart', 'cart', function(err, NS){
                setWait(node, false);

                new NS.CartViewPanel({'addToCart': productId});
            });
        }

    });

};
