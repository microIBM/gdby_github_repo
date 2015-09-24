'use strict';
angular
.module('dachuwang')
.controller('activityController',['$window','$scope', '$stateParams','productService', 'categoryService', 'cartlist', 'Lightbox', function($window,$scope, $stateParams, productService, categoryService, cartlist, Lightbox) {
  // 商品列表
  // 分类获取
  // var result = categoryService.getSubCategory($stateParams.cateId);
  $scope.ifnone_products = false;
  $scope.show_loading = true;
  $scope.backUpNum = {};
  $scope.getProduct = function(upid, page) {
    // 商品获取
    var product = productService.getProducts(upid, page);
    product.then(function(promise) {
      $scope.show_loading = false;
      $scope.products = promise;
      angular.forEach($scope.products, function(v) {
        v['quantity'] = 1;
        if(cartlist.items['0']){
          angular.forEach(cartlist.items['0'].list , function(i){
            if(v.id == i.id){
              v['quantity'] = i.quantity
            }
          })
        }
      });
      $scope.ifnone_products = (promise.length>0 ? false:true);
    }, function() {
    }, function() {
    })
  }
  $scope.list_tips = ''
  // 减
  $scope.minus = function(item){
    if(item.quantity == 1) {
      return;
    }
    item.quantity --;
  };

  // 加
  $scope.plus = function(item) {

    if(item.storage != -1 && item.storage <= item.quantity){
      alert('抱歉库存不足');
      item.quantity = parseInt(item.storage) ;
      return ;
    }
    if(item.buy_limit != 0 && item.buy_limit == item.quantity){
      alert('每人只能购买' + item.buy_limit + item.unit)
      return;
    }
    if(item.quantity >= 9999) {
      return;
    }
    item.quantity ++;
  }

  $scope.getProduct(11, 1);
  
  // 购物车管理
  $scope.cartlist = cartlist;
  $scope.isdisabled = true;
  // 添加购物车
  $scope.toggleItems = function(item, num) {

    // 库存不够弹出提示 并且设置局部setClass 方便directive 控制
    if(item.storage != -1 && item.storage < item.quantity){
      alert('抱歉库存不足');
      item.quantity = parseInt(item.storage) ;
      return ;
    }
    if(cartlist.ids.indexOf(item.id) >= 0) {
      cartlist.changeItem(item, -1, 0);
      item.quantity = num; // 恢复输入框数字
    } else {
      cartlist.changeItem(item, 1, num);
    }
  };

  $scope.clearNum = function(item) {
    $scope.backUpNum[item.id] = item.quantity;
    item.quantity = "";
  }

  $scope.setNum = function(item, force) {

    // 判断用户输入是否超出限购
    if(item.buy_limit != 0 && item.quantity > item.buy_limit){

      //超出限购设置quantity为限购件
      item.quantity = parseInt(item.buy_limit) ;

    }
    force = force ? force : false;
    if(force && item.quantity === "" && $scope.backUpNum[item.id]) {
      item.quantity = $scope.backUpNum[item.id];
      $scope.backUpNum[item.id] = "";
      return;
    }
    if(item.quantity != null && item.quantity <= 0) {
      item.quantity = 1;
    } else if(item.quantity != null || force) {
      if(item.quantity <= 1) {
        item.quantity = 1;
      } else if(!/^\d+$/.test(item.quantity)){
        item.quantity = 1;
      }
    }
  }
  // 幻灯
  $scope.lightBox = function(images) {
    angular.forEach(images, function(v) {
      v['url'] = v['pic_url'];
    })
    Lightbox.openModal(images, 0);
  }
}]);
