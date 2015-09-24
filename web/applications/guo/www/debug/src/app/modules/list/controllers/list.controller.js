'use strict';
angular
.module('dachuwang')
.controller('padController',['$rootScope' , '$window','$scope', '$stateParams','productService', 'categoryService', 'cartlist', 'Lightbox', function($rootScope , $window,$scope, $stateParams, productService, categoryService, cartlist, Lightbox) {
  // 分类获取
  // 命名空间
  var DC = $scope.DC = {

  }
  
  $scope.ifnone_products = false;
  $scope.show_loading = true;
  $scope.backUpNum = {};

  $scope.getProduct = function(upid, page) {
    // 商品获取
    //

    $scope.products = null;
    $scope.show_loading = true;
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
  // 哪果从首页过来， 默认展示过来的1级分类 ,
  if($stateParams.cateId){
    $scope.getProduct($stateParams.cateId, 1);
  }else{
    $scope.getProduct($rootScope.init_cateId, 1);
  }


  // 得到传递过来的id 进行拉取数据
  $rootScope.$on('list_data' , function(e , cateId){
    $scope.getProduct(cateId, 1);
  })
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

  $scope.getProduct($stateParams.cateId, 1);
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


    // 控制幻灯是否显示左右箭头
    $rootScope.lightBox_len = images.length ; 
    Lightbox.openModal(images, 0);
  }
}]);
