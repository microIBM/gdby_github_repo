'use strict';
angular
.module('hop')
.controller('RecommendCtrl', ['$rootScope' , '$scope', 'req', function($rootScope , $scope, req) {
  // 分页参数初始化
  $scope.title = '首页推荐列表';
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
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
    $rootScope.is_loading = true;
    req.getdata('recommend/lists', 'POST', function(data) {
      $rootScope.is_loading = false ;
      $scope.advs = data.list;
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  setDefault();

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
    req.getdata('recommend/set_status', 'POST', function(data) {
      var index = $scope.advs.indexOf(item);
      $scope.advs[index].status = status;
      alert(data.msg);
    }, {id: item.id, status: status});
  }
}]);
