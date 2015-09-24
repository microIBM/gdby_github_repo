'use strict';
angular
.module('dachuwang')
.controller('orderListController',["$rootScope","$scope", "req", "$cookieStore","$state", "$modal", "$window", 'daChuLocal', 'pagination', 'userAuthService', '$stateParams','rpc', 'daChuConfig', 'cartlist', function($rootScope,$scope, req, $cookieStore, $state, $modal, $window, daChuLocal, pagination, userAuthService, $stateParams, rpc, daChuConfig, cartlist) {
  // 查看是否登陆
  userAuthService.checkLogin();

  var DC = $scope.DC = {};
  var vm = $scope.vm = {};
  $scope.dialog = function(config, modal_config) {
    var _config = {
      headerText:"提示信息",
      bodyText: "设置成功",
      closeText: '关闭'
    };
    var _modal_config = {
      templateUrl: 'myModalContent.html'
    };
    angular.extend(_modal_config, modal_config);
    angular.extend(_config, config);
    var modalInstance = $modal.open({
      templateUrl: _modal_config.templateUrl,
      controller: 'ModalInstanceCtrl',
      resolve: {
        items: function () {
          return _config;
        }
      }
    });
    modalInstance.result.then(function (selectedItem) {
      //$scope.selected = selectedItem;
    }, function () {
      //$log.info('Modal dismissed at: ' + new Date());
    });
  }

  $scope.showType = $stateParams.status || 1;
  $scope.tabs = [
    {status: 1,   name: '待审核'},
    {status: 11, name: '待收货'},
    {status: 21,   name: '已收货'},
    {status: 31,   name: '已取消'}
  ];
  $scope.isProcessing = true;

  var weixin_pay_url = '';

  var callBack = function(data) {
      if($scope.orderlist && $scope.orderlist.length){
       angular.forEach($scope.orderlist , function(v){
         $scope.orderlist.push(v);
       })
      }else{
        $scope.orderlist = data.orderlist;
      }

      if($scope.orderlist.length >= data.total_count){
        vm.canLoad = false ;
      }

      $scope.isProcessing = false;
      $scope.user_type = data.type;
      $scope.total = data.total;
      // 获取商品总数
      angular.forEach($scope.orderlist,function(order){
        angular.forEach(order.suborders,function(model){
          if(model.details.length>1){
            var copyArr=model.details;
            model.numberTotal=copyArr.length;
          }else{
            model.numberTotal=model.details.length;
          }
        })
        if(order.suborders.length > 1){
          var count=0
          angular.forEach(order.suborders,function(m,index){
            count+=m.numberTotal;
            if(index==order.suborders.length){
              count=0;
            }
          })
          order.num = count;
        }else{
          order.num = order.suborders[0].numberTotal;
        }
      })
      weixin_pay_url = data.pay_url;
  };
  vm.scrollConfig = function(){
    vm.canLoad = true;
    vm.page = 1;
    $scope.orderlist = [];
  }

  vm.scrollConfig();

  vm.reload_list = function(){

     vm.postData ={
       customer_side_status : $scope.showType,
       currentPage : vm.page
     }
     var promise = rpc.load('order/lists', 'POST',  vm.postData);

     vm.page ++;
     promise.then(function(data){
       callBack(data);
     }, function(data){
       if(data){
         alert(data);
         rpc.redirect('home')
       }
     })
  }

  $scope.setStatus = function(status) {
    $stateParams = status;
    $scope.showType = status;
    $scope.isProcessing = true;
    $scope.orderlist = [];
    DC.chooseStatus = '1';
    if(status == '21'){
      DC.chooseStatus = 21; 
      daChuLocal.set('OrderStatus' ,DC.chooseStatus)
    }else{
      if(daChuLocal.get('OrderStatus')){
        daChuLocal.remove('OrderStatus')
      }
    }
    vm.scrollConfig();
    vm.reload_list();

  }

  $scope.setStatus($scope.showType);

  var delCallback = function(data) {
    if(data.status === 0) {
      $scope.dialog({
        bodyText:"取消订单成功",
        close:function(){
          vm.scrollConfig();
          vm.reload_list();
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
      $scope.dialog({
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
    $scope.dialog({
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

  //卖家继续微信支付订单
  $scope.pay = function(order_number) {
    $rootScope.showLoading = true;
    $window.location.href = weixin_pay_url+'?order_number='+order_number;
  }

  DC.cartlist = cartlist.getInfo();
  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartChange){
    DC.cartlist = cartChange;
  })

  // 购物车管理
  $scope.cartlist = cartlist;
  $scope.isdisabled = true;
  // 添加购物车

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
      buyAgain(data);
    });


 };

  //订单详情
  $scope.orderinfo = function(id ,order){
    daChuLocal.set('Order',order);
    daChuLocal.set('orderId',id);
    $state.go('page.orderInfo')
  }
}])
