'use strict';
angular
.module('dachuwang')
.controller('padController',['$rootScope' ,'$location', '$window','$scope','$state', '$stateParams','productService', 'categoryService', 'cartlist', 'Lightbox','rpc' , 'posService','userAuthService' ,function( $rootScope,$location, $window,$scope, $state , $stateParams, productService, categoryService, cartlist, Lightbox , rpc , posService , userAuthService) {

  // 命名空间
  var DC = $scope.DC = {

  }

  DC.cartlist = cartlist.getInfo();
  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartChange){
    DC.cartlist = cartChange;
  })


  DC.imgLoad = function(){
      $scope.$apply(function() {
        $scope.showloading = true;
      });
  }
  DC.cartInfo = cartlist.getInfo()
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

  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  $scope.ifnone_products = false;
  $scope.show_loading = true;
  $scope.backUpNum = {};
  $scope.getProduct = function(upid, page) {
    // 商品获取
    $scope.products = null;
    $rootScope.showLoading = true;
    var promise = rpc.load('product/lists' , 'POST' , {
      upid : $stateParams.cateId,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
      locationId : posService.info().id ? posService.info().id : 804
    })
    promise.then(function(data) {

      $rootScope.showLoading = false;
      DC.cate_spec = data.cate_spec;
      DC.topTwoCid = data.top_two_cid;
      $scope.products = data.list;
      $scope.paginationConf.totalItems = data.total;
      angular.forEach($scope.products, function(v) {

        // 把包装规格抽出来
        if(v.spec){
          angular.forEach(v.spec , function(productSpec){
            if(productSpec.name == '包装规格'){
              v.productSpec = productSpec;
            }
          })
        }

        v.quantity = 1;
        if(DC.cartInfo.items['0']){
          angular.forEach(DC.cartInfo.items['0'].list , function(i){
            if(v.id == i.id){
              v.quantity = i.quantity;
            }
          })
        }
      });
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


  // 哪果从首页过来， 默认展示过来的1级分类 ,
  if($stateParams.cateId){
    $scope.getProduct();
  }else{
    $state.go('page.home');
  }



  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch(
    'paginationConf.currentPage + paginationConf.itemsPerPage',
    $scope.getProduct
  );
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
    }else if(item.quantity > 9999){
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
