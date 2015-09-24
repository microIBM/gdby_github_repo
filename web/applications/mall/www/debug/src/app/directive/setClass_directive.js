'use strict';

// 预定设置class
angular
.module('dachuwang')
.directive('setClass', ['cartlist', function(cartlist) {
  return {
    link: function($scope, ele, attr) {

      var setClass = function() {
        // 如果库存少于用户quantity直接return
        if(!angular.isObject(attr.setClass)){
          attr.setClass = JSON.parse(attr.setClass);
        }
        if(attr.setClass && attr.setClass.storage != -1 && attr.setClass.quantity > attr.setClass.storage){
          return ;
        }
        if(ele.hasClass('btn-plus')) {
          ele.removeClass('btn-plus').addClass('btn-default');
          ele.children('.text').html('移出购物车');
        } else if(ele.hasClass('btn-default')) {
          ele.removeClass('btn-default').addClass('btn-plus');
          ele.children('.text').html('加入购物车');
        }
      };

      ele.bind('click', function() {
        setClass();
      });

      // 初始化按钮状态
      var cartList = cartlist.getInfo();
      if(cartList.ids) {
        var id = attr.id;
          angular.forEach(cartList.ids, function(c) {
            if(c == id) {
              setClass();
            }
          })
        }
    }
  };
}])



