'use strict';

// 预定设置class
angular
.module('dachuwang')
.directive('setClass', ['cartlist', function(cartlist) {
  return {
    link: function($scope, ele, controller) {


      var setClass = function() {

        // 如果库存少于用户quantity直接return

        if(!angular.isObject(controller.setClass)){
            controller.setClass = JSON.parse(controller.setClass);
        }

        if(controller.setClass && controller.setClass.storage != -1 && controller.setClass.quantity > controller.setClass.storage){
          return ;
        }

        if(ele.hasClass('btn-lightGreen')) {
          ele.removeClass('btn-lightGreen').addClass('btn-default');
          ele.children('.text').html('移出购物车');
        } else if(ele.hasClass('btn-default')) {
          ele.removeClass('btn-default').addClass('btn-lightGreen');
          ele.children('.text').html('加入购物车');
        }

      };
      ele.bind('click', function() {
        setClass();
      });
      ele.children('.text').html('加入购物车');
      if(cartlist.ids) {
        var id = controller.id;
        angular.forEach(cartlist.ids, function(c) {
          if(c == id) {
            setClass();
          }
        })
      }
    }
  };
}]);


