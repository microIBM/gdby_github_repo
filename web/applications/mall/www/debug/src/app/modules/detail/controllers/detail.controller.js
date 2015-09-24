'use strict';
angular
.module('dachuwang')
.controller('detailController',['$rootScope' ,'$state','$location', '$window','$scope', '$stateParams','productService', 'categoryService', 'cartlist', 'Lightbox', 'rpc', 'posService' , 'userAuthService' ,function( $rootScope, $state ,$location, $window,$scope, $stateParams, productService, categoryService, cartlist, Lightbox , rpc , posService ,userAuthService) {

  // 命名空间
  var DC = $scope.DC = {

  }

  DC.cartlist = cartlist.getInfo();
  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartChange){
    DC.cartlist = cartChange;
  })

  // 默认图片index
  DC.activeIndex = 0;

  $scope.cartlist = cartlist;

  // 关注
  DC.follow = function(item){

    if(!userAuthService.isLogined()){
      $state.go('page.login');
      return ;
    }
    if(!item){
      return ;
    }
    var id = item.id ;
    var status ;
    if(item.follow_status  == 0){
      status = 1;
      item.follow_status = 1;
    }else{
      status = 0;
      item.follow_status = 0;
    }
    rpc.load('follow_with_interest/update_or_insert' , 'POST' , {product_id : id , status : status}).then(function(data){

    })
  }

  // 检测用户是否登陆
  DC.isLogin =  userAuthService.isLogined();
  // 尾部配置
  DC.detail_config = [
    { classname : 'youzhi', name :"优质货源"},
    { classname : 'ruqi', name :"如期送达"},
    { classname : 'zhangdan', name :"电子账单服务"},
    { classname : 'jushou',name :"现场无理由拒收"}
  ]
  $scope.ifnone_products = false;
  $scope.show_loading = true;
  $scope.backUpNum = {};

  // 更新详情页上刷新的件数与购物车一致
  DC.checkQuantity = function(product){

    if(!DC.cartlist.count) return ;

    angular.forEach(DC.cartlist.items[0].list , function(v ,k ){
      if(v.id == product.id){
        DC.detail.quantity = v.quantity ;
      }

    })
  }

  $scope.getProduct = function(id, locationId) {
    $rootScope.showLoading = true;
    // 商品获取
    $scope.products = null;
    var pormise = rpc.load('product/get_product_detail' , 'POST' , { product_id : id , locationId : locationId})
    pormise.then(function(data){
      $rootScope.showLoading = false;
      DC.detail = data.info ;
      DC.detail.quantity = 1;
      DC.checkQuantity(DC.detail);
    }, function(data){
      alert(data);
      $state.go('page.home');
    })

  }

  // 如果router有id 即展示， 没有就返到首页
  if($stateParams.cateId){
    $scope.getProduct($stateParams.cateId, posService.info().id);
  }else{
    rpc.redirect('/home')
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

  // 添加购物车
  $scope.toggleItems = function(item, num) {
    // 库存不够弹出提示 并且设置局部setClass 方便directive 控制
    if(item.storage != -1 && item.storage < item.quantity){
      alert('抱歉库存不足');
      item.quantity = parseInt(item.storage) ;
      return ;
    }
    if(DC.cartlist.ids.indexOf(item.id) >= 0) {
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
    } else if(item.quantity > 9999){
       item.quantity = 9999;
    }else if(item.quantity != null || force) {
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
