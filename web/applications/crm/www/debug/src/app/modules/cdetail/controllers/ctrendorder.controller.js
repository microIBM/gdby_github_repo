'use strict';

angular.module('dachuwang')
.controller('CtrendsOrderController', ['$scope','$state','rpc', function($scope,$state,rpc) {
  $scope.func = {
    userback : function() {
      $state.go('page.customerdetail.trends');
    }
  }
  $scope.isLoading = true;
  $scope.customerlist = {};
  rpc.load('cdetail/index','POST',{action:'get_order_detail',order_number:$state.params.order_number})
  .then(function(data){
    $scope.isLoading = false;
    $scope.data = data.list;
    //订单内容金额显示以元为单位
    angular.forEach($scope.data.content, function(v) {
      v.sum_price = getDivideInt(v.sum_price,100);
    });
    $scope.data.deal_price = getDivideInt($scope.data.deal_price, 100);
    $scope.data.final_price = getDivideInt($scope.data.final_price, 100);
    $scope.data.total_price = getDivideInt($scope.data.total_price, 100);
    $scope.data.created_time = getDivideInt($scope.data.created_time, 0.001);
    $scope.data.deliver_date = getDivideInt($scope.data.deliver_date, 0.001);
    $scope.data.driver_name = ifv($scope.data.driver_name,null,'暂无');
    $scope.data.driver_mobile = ifv($scope.data.driver_mobile,null,'暂无');
    $scope.data.status_class = getStatusClass($scope.data.status);
  });
  function getStatusClass(status) {
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
  function getDivideInt(number,x) {
    return parseInt(number) / x;
  }
  //三元运算符
  function ifv($con, $v1, $v2) {
    if($con) {
      return $v1 || $con;
    }
    return $v2 || '';
  }
}]);
