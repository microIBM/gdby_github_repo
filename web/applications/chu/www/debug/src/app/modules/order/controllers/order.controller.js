'use strict';
angular
  .module('dachuwang')
  .controller('orderController', ['$scope', 'cartlist', function($scope, cartlist) {
    $scope.text = 'this is order page';
    $scope.products = cartlist.items[0].list;
    // 减
    $scope.minus = function(item) {
      if(item.quantity == 1) {
        $scope.remove(item);
      } else {
        cartlist.changeItem(item, -1);
      }
    };

    // 加
    $scope.plus = function(item) {
      cartlist.changeItem(item, 1);
    }

    // 删
    $scope.remove = function(item) {
      $scope.dialog({
        bodyText:"确定要删除吗?",
        actionText:"确定",
        ok: function() {
          cartlist.changeItem(item, -1, 0);
        }
      });
    }

    $scope.backUpNum = {};
    $scope.clearNum = function(item) {
      $scope.backUpNum[item.id] = item.quantity;
      item.quantity = "";
    }

    // 修改值
    $scope.setNum = function(item, force) {
      force = force ? force : false;
      if(force && item.quantity === "" && $scope.backUpNum[item.id]) {
        item.quantity = $scope.backUpNum[item.id];
        $scope.backUpNum[item.id] = "";
      }
      if(item.quantity != null && item.quantity <= 0) {
        item.quantity = 0;
      } else if(item.quantity != null || force) {
        if(item.quantity <= 0) {
          $scope.remove(item);
        } else if(!/^\d+$/.test(item.quantity)){
          item.quantity = 1;
        } else {
          cartlist.changeItem(item, 1, item.quantity);
        }
      }
    }
  }]);
