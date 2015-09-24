'use strict';
angular
.module('hop')
.controller('CouponRuleCtrl', ['$scope', 'req', function($scope, req) {
  // 分页参数初始化
  $scope.title = '规则列表';
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
    req.getdata('coupon_rules/lists', 'POST', function(data) {
      $scope.couponRules = data.list;
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
    req.getdata('coupon_rules/set_status', 'POST', function(data) {
      var index = $scope.couponRules.indexOf(item);
      $scope.couponRules[index].status = status;
      alert(data.msg);
    }, {id: item.id, status: status});
  }
}]);
