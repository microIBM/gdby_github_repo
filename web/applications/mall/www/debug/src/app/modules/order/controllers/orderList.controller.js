'use strict';
angular
.module('dachuwang')
.controller('orderListController',["$scope","$state","$rootScope", 'cartlist', "req", "$filter","daChuLocal", "$cookieStore", "$modal", "$window", 'pagination', 'userAuthService', '$stateParams', 'daChuConfig',  'daChuDialog', 'appConfigure' ,'rpc' , function($scope, $state,$rootScope,cartlist, req, $filter,daChuLocal, $cookieStore, $modal, $window, pagination, userAuthService, $stateParams, daChuConfig , daChuDialog, appConfigure, rpc ) {

  // 查看是否登陆
  $scope.getUrl = appConfigure;
  var DC =  $scope.DC  = {};
  $scope.dialog = daChuDialog ;
  userAuthService.checkLogin();
  //日期筛选
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endOpened = $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    if($scope.endOpened){
      $scope.endOpened = false;
    }
    $scope.opened = true;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    if($scope.opened){
      $scope.opened = false;
    }
    $scope.endOpened = true;
  };
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 10
  };


  $scope.orderlist = [];
  $scope.myorderType = daChuLocal.get('type');
  if((toString($scope.myorderType).length>0 || $scope.myorderType == '0')&& $scope.myorderType != null ){
    $scope.showType = $scope.myorderType;
    daChuLocal.remove('type');
  }else{
    $scope.showType = $stateParams.status || -1;
  }
  $scope.tabs = [
    {status: -1, name: '全部订单'},
    {status: 1,   name: '待审核'},
    {status: 11, name: '待收货'},
    {status: 21,   name: '已收货'},
    {status: 31,   name: '已取消'}
  ];
  $scope.isProcessing = true;

  var weixin_pay_url = '',boolLoad=true;
  var callBack = function(data) {
    boolLoad=false;
    $rootScope.showLoading = false;
    var weixin_pay_url = '',boolLoad=true;
    if(data.status === 0) {
      $scope.isProcessing = false;
      $scope.user_type = data.type;
      $scope.total = data.total_count;
      weixin_pay_url = data.pay_url;
      // 变更分页的总数
      $scope.paginationConf.totalItems = data.total_count;
      $scope.orderlist = data.orderlist;
    }

    var billNumber =daChuLocal.get('orderNumber')
    if(billNumber){
      //$scope.setStatus(-1);
      //这里取不到setStatus(-1)异步回调的数据,所以改为全部订单类型
      angular.forEach($scope.orderlist,function(m){
        angular.forEach(m.suborders,function(v){
          if(v.order_number == billNumber ){
            $scope.orderlist = [];
            $scope.orderlist.push(v)
          }
        })
      })
      $scope.total = 0;
      daChuLocal.remove('orderNumber');
      data.total_count = 1;
      $scope.paginationConf.totalItems = data.total_count;
    }
  };
  var reload_list = function(){
    DC.postData = {
      customer_side_status: $scope.showType,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    if(DC.postData.startTime > DC.postData.endTime){
      alert('起始时间不能大于结束时间');
      return;
    }
    boolLoad=true;
    if(!boolLoad) return;
    req.getdata('order/lists', 'POST', callBack, DC.postData);
  }

  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch(
    'paginationConf.currentPage + paginationConf.itemsPerPage',
    reload_list
  );
  var delCallback = function(data) {
    if(data.status === 0) {
      $scope.dialog.tips({
        bodyText:"取消订单成功",
        close:function(){
          reload_list();
        }
      });
    } else {
      $scope.dialog.tips({
        bodyText:"取消失败"
      });
    }
  };
  // 时间筛选
  $scope.filterTime = function(){
    if(!$scope.startTime && !$scope.endTime){
      alert('您还没有输入时间');
      return;
    }
    if(!$scope.startTime){
      alert('请输入起始时间');
      return;
    }
    if(!$scope.endTime){
      alert('请输入结束时间');
      return;
    }
    reload_list();
  }
  $scope.initTime = function(){
    $scope.startTime = '';
    $scope.endTime = '';
  }

  //条件筛选
  $scope.setStatus = function(status) {
    $stateParams = status;
    $scope.showType = status;
    $scope.orderlist = [];
    $scope.isProcessing = true;
    $rootScope.showLoading = true;
    req.getdata('order/lists', 'POST', callBack, {
      customer_side_status: $scope.showType,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage});
      // req.redirect('order/list/'+status);
  }

  // 买家取消订单
  $scope.cancel = function(orderid , minus) {
    if(minus != 0){
      $scope.dialog.tips({
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
    $scope.dialog.tips({
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
    DC.carts = [];
    //获取id传入
    DC.item = item;

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
      order_id: DC.item.id,
      order_type :1}

    rpc.load('order/buy_again','POST', postData).then(function(data){
      buyAgain(data);
    });
  };
  var filterBill = function(){
    if(billNumber){
      $scope.setStatus(-1);
    }
  }
  $scope.sign = function(img){
    $window.open(img)
  }
  //filterBill();
}])
