'use strict';
angular
.module('dachuwang')
.controller('lashController',['$rootScope' , '$state' ,'$location', 'rpc','$scope' ,function( $rootScope, $state ,$location, rpc ,$scope) {
   var vm = $scope.vm = {};

  // 分页参数初始化
  vm.paginationConf = {
    currentPage: 1,
    itemsPerPage: 10
  };
  vm.show_loading = true;
  // 获取子母单
  vm.getList = function(){

    $rootScope.showLoading = true;
    var promise = rpc.load('customer/sub_account_list', 'POST' , {
      currentPage: vm.paginationConf.currentPage,
      itemsPerPage: vm.paginationConf.itemsPerPage
    })

    promise.then(function(data){
      $rootScope.showLoading = false ;
      vm.paginationConf.totalItems = data.total;
      vm.singles = data.list ;
    }, function(data){
       $state.go('page.home');
    })
  }

  vm.getList();

  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch(
    'vm.paginationConf.currentPage + vm.paginationConf.itemsPerPage',
    vm.getList
  );

}]);
