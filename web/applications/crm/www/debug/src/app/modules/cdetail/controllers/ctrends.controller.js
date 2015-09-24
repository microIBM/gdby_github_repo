'use strict';

angular.module('dachuwang')
  .controller('CtrendsController',['$scope', '$state', '$window', 'rpc', 'pagination', 'daChuDialog','daChuLocal','Analysis', function($scope, $state, $window, rpc, pagination, dialog, daChuLocal, Analysis) {
  $scope.startTime = null;
  $scope.endTime = null;
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
    $scope.opened = true;
    $scope.endOpened = false;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = false;
    $scope.endOpened = true;
  };
  function getTimeStamp(time) {
    return (new Date(time)).valueOf()/1000;
  }
  $scope.func = {
    orderinfo : function(item) {
      Analysis.send('查看订单详情');
      $state.go('page.customerdetail.orderdetail',{order_number:item.order_number});
    },
    sift : function() {
      var startTime = null,
          endTime = null;
      if($scope.startTime) {
        startTime = getTimeStamp($scope.startTime);
      }
      if($scope.endTime) {
        endTime = getTimeStamp($scope.endTime);
      }
      if(startTime === null && endTime === null) {
        dialog.alert('起始时间和结束时间至少选一项');
        return;
      }
      if(startTime !==null && endTime!==null && startTime>endTime) {
        dialog.alert('最早时间不能大于最晚时间');
        return;
      }
      Analysis.send('客户订单时间筛选');
      $scope.orderlist = [];
      $scope.pagination.init(getLists);
      $scope.pagination.nextPage();
    },
    clear : function() {
      $scope.startTime = null;
      $scope.endTime = null;
      $scope.orderlist = [];
      $scope.pagination.init(getLists);
      $scope.pagination.nextPage();
    },
    changeShop : function() {
      daChuLocal.set('currentShop',$scope.currentShop.id);
      $scope.orderlist = [];
      Analysis.send('子母店铺切换');
      $scope.pagination.init(getLists);
      $scope.pagination.nextPage();
    },
    getStatusClass : function(status) {
      switch(status) {
        case '待审核':
          return 'my-label-waited';
        case '待生产':
        case '波次中':
        case '带分拣':
        case '已复核':
        case '已分拨':
        case '已出库':
        case '已装车':
        case '待收货':
          return 'my-label-process';
        case '已签收':
        case '已完成':
        case '已关闭':
          return 'my-label-finish';
        case '已退货':
          return 'my-label-back';
        default:
          return 'my-label-none';
      }
    }
  }
  $scope.orderlist = [];
  function getLists(callback) {
    $scope.isloading = true;
    var itemsPerPage = 10,i,
        postData = {
          action : 'get_customer_orders',
          uid : $scope.currentShop.id,
          itemsPerPage : itemsPerPage,
          currentPage : pagination.page
        };
    if($scope.startTime!==null) {
      postData.begin_time = getTimeStamp($scope.startTime);
    }
    if($scope.endTime!==null) {
      postData.end_time = getTimeStamp($scope.endTime);
    }
    rpc.load('cdetail/index','POST',postData)
    .then(function(data){
      $scope.isloading = false;
      for(i=0; i<data.list.length; i++) {
        data.list[i].created_time*=1000;
        $scope.orderlist.push(data.list[i]);
      }
      if(data.list.length < itemsPerPage) {
        callback(true);
      } else {
        callback(false);
      }
    });
  }
  function getCurrentShop() {
    var local_shop_id = daChuLocal.get('currentShop');
    var i,len = $scope.shopLists.length;
    if(local_shop_id) {
      for(i=0; i<len; i++) {
        if($scope.shopLists[i].id == local_shop_id) {
          return $scope.shopLists[i];
        }
      }
    }
    return $scope.shopLists[0];
  }
  function init() {
    var postData = {
      action : 'get_sub_accounts',
      id : parseInt($state.params.uid)
    };
    rpc.load('cdetail/index', 'POST', postData)
      .then(function(data) {
        $scope.shopLists = data.list;
        $scope.shopLists.unshift({id:parseInt($state.params.uid),shop_name:'母账号店铺'});
        $scope.currentShop = getCurrentShop();
        $scope.pagination = pagination;
        $scope.pagination.init(getLists);
        $scope.pagination.nextPage();
      }, function(err) {
      });
  }
  init();
}]);
