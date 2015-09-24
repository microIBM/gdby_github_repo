'use strict';
angular
.module('dachuwang')
.controller('orderinfoController',["$rootScope","$scope", "req", "$cookieStore","$state",'daChuDialog', "$modal", "$window", 'daChuLocal', 'pagination', 'userAuthService', '$stateParams', 'daChuConfig', 'cartlist', 'rpc', function($rootScope,$scope, req, $cookieStore, $state, daChuDialog, $modal, $window, daChuLocal, pagination, userAuthService, $stateParams, daChuConfig, cartlist,rpc) {

  var DC = $scope.DC = {}

  DC.orderId = daChuLocal.get('orderId');
  DC.Order = daChuLocal.get('Order');
  DC.OrderStatus = daChuLocal.get('OrderStatus');
  var callBack = function(data){
    if(data.status == '0'){
      data = data.info;
      DC.maOrder = data;
      DC.orderinfo = data.list;
    }

  }
  //卖家继续微信支付订单
  $scope.pay = function(order_number) {
    $rootScope.showLoading = true;
    $window.location.href = DC.maOrder.pay_url+'?order_number='+order_number;
  }


  var postData = {
    order_id : DC.orderId
  }
  req.getdata('order/info','POST',callBack,postData);


  var delCallback = function(data) {
    if(data.status === 0) {
      daChuDialog.tips({
        bodyText:"取消订单成功",
        close:function(){
          $state.go('page.orderList')
        }
      });
    } else {
      $scope.dialog({
        bodyText:"取消失败"
      });
    }
  };

  // 买家取消订单
  $scope.cancel = function(orderid , minus) {
    if(minus != 0){
      daChuDialog.tips({
        bodyText: "本单已减免"+minus+"元,取消订单将视为自动放弃优惠,确认取消订单吗？",
        action: "confirm",
        ok: function() {
          $scope.orderCancel(orderid);
        },
        actionText:'确认',
        closeText:'取消'
      });
      return ;
    }
    daChuDialog.tips({
      bodyText: "确认取消订单吗？",
      action: "confirm",
      ok: function() {
        $scope.orderCancel(orderid);
      },
      actionText:'确认',
      closeText:'取消'
    });
  };

  $scope.orderCancel = function(orderid) {
    req.getdata('order/cancel','POST', delCallback, {order_id: orderid});
  };

  //订单详情的再次购买
  DC.cartlist = cartlist.getInfo();
  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartChange){
    DC.cartlist = cartChange;
  })

  // 购物车管理
  $scope.cartlist = cartlist;
  $scope.isdisabled = true;
  // 添加购物车

  $scope.toggleItems = function(item, num) {
    var buyAgain = function(data){
      angular.forEach(data.list ,function(v , k){

        // 库存不够弹出提示 并且设置局部setClass 方便directive 控制
        if(v.storage != -1 && v.storage < v.quantity){
          alert('抱歉库存不足');
          v.quantity = parseInt(v.storage) ;
          return ;
        }

        if(DC.cartlist.ids.indexOf(v.id) >= 0) {
          cartlist.changeItem(v, -1, 0);
          v.quantity = num; // 恢复输入框数字
        } else {
          cartlist.changeItem(v, 1, num);
        }
      })
      $state.go('page.cart')
    }
    var postData = {
      order_id: item.id,
      order_type :1}

      rpc.load('order/buy_again','POST', postData).then(function(data){
        if(data.status == 0){
          buyAgain(data);
        }else{
          daChuDialog.tips({
            bodyText: data.msg,
            close:function(){
              $state.go('page.home')
            },
            closeText:'去首页'
          })
        }
      });
  };
}])
