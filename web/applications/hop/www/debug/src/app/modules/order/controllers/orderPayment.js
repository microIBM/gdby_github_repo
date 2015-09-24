'use strict'
angular.module('hop').controller('SuborderPaymentCtrl',['$location', 'dialog', 'req', '$scope', '$cookieStore', function($location, dialog, req, $scope, $cookieStore){
  $scope.status = '-1';
  // 重新获取分页数据
  var getList = function() {
    var cook = $cookieStore.get('orderPaymentCookie');
    $scope.searchValue = cook;
    var postData = {
      status: $scope.status,
      searchValue: $scope.searchValue,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('suborder/lists_payment', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total_count;
        // 变更数据条目
        $scope.list = data.orderlist;
        $scope.total = data.total;
      }
    }, postData);
  };
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // 按照日期筛选
  $scope.search = function(){
    $cookieStore.put('orderPaymentCookie',$scope.searchValue);
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    getList();
  }
  // 重置搜索条件
  $scope.reset = function() {
    $cookieStore.remove('orderPaymentCookie');
    $scope.searchValue = '';
    getList();
  };
}]);
