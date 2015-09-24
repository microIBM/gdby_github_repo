'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('UserCenterCtrl', ['$rootScope', '$state', '$scope', 'req', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal',   'userService', 'cartlist' , 'rpc' , function ($rootScope ,$state, $scope, req, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal ,  userService, cartlist , rpc) {

  $state.current.data.showHeader = 4;
  var DC = $scope.DC = {};

  DC.cartlist = cartlist.getInfo();
  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartChange){
    DC.cartlist = cartChange;
  })
  $rootScope.showLoading = true ;
  userAuthService.checkLogin();
  $scope.dialog = daChuDialog.tips;

  DC.myorderType = [
    {status :'1', value:'待审核'},
    {status :'11', value:'待收货'},
    {status :'21', value:'已收货'},
    {status :'31', value:'已取消'}
  ]

  // 退出
  $scope.logout = function() {
    $scope.dialog({
      bodyText:'确定要退出吗？',
      ok: function() {
        userService.login_out();
      },
      actionText:'确定',
      closeText:'取消'
    });
  };
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

  $scope.backUpNum = {};

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

  //删除我的关注的内容
  $scope.delFollow = function(item){
    if(!item) return ;
    var tips = window.confirm('您真的要取消关注此商品');
    if(tips){
      rpc.load('follow_with_interest/update_or_insert' , 'POST' , {product_id : item.id , status : 0}).then(function(data){
        DC.followList();
      })
    }

  }

  // 订单列表
  var callBack = function(data) {
    $rootScope.showLoading = false ;
    $scope.uinfo = data.info;
    $scope.order = data.order;
    $scope.valid_coupon_nums = data.valid_coupon_nums;
  };
  // 个人中心我的订单跳转
  $scope.setStatus = function(status) {
    $state.go('page.orderList')
    $scope.myorderType = daChuLocal.set('type',status);
    $rootScope.orderType = status;
    $scope.showType = status;
    $scope.orderlist = [];
    $scope.isProcessing = true;
    req.getdata('order/lists', 'POST', callBack, {customer_side_status: $scope.showType});
  }


  // 获取账号基本信息
  req.getdata('customer/baseinfo', 'POST', callBack);
  DC.followList = function(){
    req.getdata('follow_with_interest/get_follow_list_by_user','POST',function(data){
      DC.mycomment = data.info;
      angular.forEach(DC.mycomment, function(v) {
        v['quantity'] = 1;
        if(DC.cartlist.count){
          angular.forEach(DC.cartlist.items['0'].list , function(i){
            if(v.id == i.id){
              v['quantity'] = i.quantity
            }
          })
        }
      });
    })
  }
  DC.followList();
}]);
