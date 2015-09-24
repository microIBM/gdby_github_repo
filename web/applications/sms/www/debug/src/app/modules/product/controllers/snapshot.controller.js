'use strict';

angular
.module('hop')
.controller('ProductSnapCtrl', ['$rootScope', '$scope', '$stateParams', 'appConfigure', 'rpc', function($rootScope, $scope, $stateParams, appConfigure, rpc) {
  $scope.title = '商品快照列表';
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 50
  };$scope.site_url = appConfigure.url;
  $scope.status = 'all';// 展示全部
  // 获取数据
  var getList = function() {
    var postData = {
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
      productId: $stateParams.productId
    };

    $rootScope.is_loading = true ;
    rpc.load('/product_snapshot/lists', 'POST', postData).then(function(data) {
      $rootScope.is_loading = false ;
      $scope.products = data.list;
      $scope.paginationConf.totalItems = data.total;
    });
  }
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
}]);
