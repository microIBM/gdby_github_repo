'use strict';
angular
.module('hop')
.controller('CouponCtrl', ['$scope', 'req', function($scope, req) {
  // 分页参数初始化
  $scope.title = '券码列表';
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 50
  };
  $scope.status = 'all';
  // 列表页
  var setDefault = function() {
    var postData = {
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
      searchVal: $scope.key,
      status: $scope.status
    };
    req.getdata('coupon/lists', 'POST', function(data) {
      $scope.coupons = data.list;
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', setDefault);
  $scope.search = function() {
      setDefault();
  }
  $scope.reset = function() {
    $scope.key = '';
    setDefault();
  }
  // 状态过滤
 $scope.filterByStatus = function(status) {
      $scope.status = status;
      setDefault();
  }

  $scope.setStatus = function(item, status) {
    req.getdata('coupon/set_status', 'POST', function(data) {
      var index = $scope.coupons.indexOf(item);
      $scope.coupons[index].status = status;
      alert(data.msg);
    }, {id: item.id, status: status});
  }
}]);
