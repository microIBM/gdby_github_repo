'use strict';
angular
.module('hop')
.controller('ActivityCtrl', ['$rootScope', '$scope', 'rpc', function($rootScope ,$scope, rpc) {
  // 分页参数初始化
  $scope.title = '运营活动列表';
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
    $rootScope.is_loading = true ;
    rpc.load('promotion/lists', 'POST', postData).then(function(data) {
      $rootScope.is_loading = false ;
      $scope.advs = data.list;
      $scope.paginationConf.totalItems = data.total;
    });
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
    rpc.load('promotion/set_status','POST', {id: item.id, status: status}).then(function(data){
      item.status = status;
    });
  }
}]);
