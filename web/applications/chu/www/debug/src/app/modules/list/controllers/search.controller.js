'use strict';
angular
.module('dachuwang')
.controller('searchController',['$rootScope' , '$timeout' ,'$state' ,'$location', '$window','$scope', '$stateParams','productService', 'categoryService', 'cartlist', 'Lightbox', 'posService' , 'daChuLocal', 'userAuthService', 'daChuDialog',function( $rootScope, $timeout , $state ,$location, $window,$scope, $stateParams, productService, categoryService, cartlist, Lightbox ,posService , daChuLocal, userAuthService, daChuDialog) {

  // 命名空间
  var DC = $scope.DC = {
  }

  DC.show_history = true ;
  if(daChuLocal.get('search_arr')){
    DC.search_arr = daChuLocal.get('search_arr').reverse();
  }

  DC.event = true ;
  DC.search_name = $stateParams.searchVal ;

  DC.back = function(){
    history.back()
    DC.event = false;
  }

  // 取消
  DC.reset = function(){
    DC.search_name = '';
  }

  DC.search = function(data , $event){
    // 解决blur 事件 跟 click 事件冲突
    $timeout(function(){

      // 如果输入跳到search页
      if(!data.$valid || !data.$dirty){
        return ;
      }
      if(!DC.event){
        return
      }
      $scope.getProduct(DC.search_name, 1 , 'product/search');
      $event ?  $event.stopPropagation() : false ;

    } ,250)
  }

  //  初始化页码
  DC.page = 1;
  $scope.ifnone_products = false;

  $scope.canLoad = true;
  $scope.backUpNum = {};
  $scope.getProduct = function(upid, page , url) {

    DC.show_history = false ;
    var search_name = daChuLocal.get('search_name');
    // 避免滑动加载出错 , 记录是第一搜索还是滑动加载
    if(search_name && search_name != upid){
      $scope.canLoad = true;
      DC.page = 1;
      $scope.products = null ;
    }
    upid ? daChuLocal.set('search_name' , upid) : false   ;
    // 商品获取
    $scope.show_loading = true;
    if(!$scope.canLoad){
      return ;
    }
    var product = productService.getProducts(upid, page , url);
    DC.page ++ ;
    product.then(function(promise) {
      var search_arr = daChuLocal.get('search_arr');
      // 缓存搜索历史
      if(search_arr){
        var is_repeat = false ;
        angular.forEach(search_arr , function(v ,k ){
          if(v == upid){
            is_repeat = true;
          }
        })
        // 只存储最近5项
        if(!is_repeat){
          if(search_arr.length >= 8){
            search_arr.shift();
          }
          search_arr.push(upid);
          daChuLocal.set('search_arr' , search_arr);
        }
      }else{
        daChuLocal.set('search_arr' , [upid]);
      }
      $scope.show_loading = false;
      $scope.data = promise;
      // 得到的分页数据扔到一块
      if($scope.products){
        angular.forEach(promise.list , function(v){
          $scope.products.push(v);
        })
      }else{
        $scope.products = promise.list;
      }
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

      // 如果数据长度大于等于总条数关掉scroll
      if($scope.products.length >= promise.total){
        $scope.canLoad = false;
      }
    }, function(msg) {
      $scope.products = [];
      alert(msg);
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
  if($stateParams.searchVal){
    $scope.getProduct($stateParams.searchVal, 1 , 'product/search');
  }

  // 购物车管理
  $scope.cartlist = cartlist;
  $scope.isdisabled = true;
  // 添加购物车
  $scope.toggleItems = function(item, num) {
    //判断是否登录
    if(!userAuthService.isLogined()){
      daChuDialog.tips({
        bodyText:'您还未登录，请登录后操作',
        close:function(){
          $state.go('loginpage')
        }
      })
      return;
    }
 
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
