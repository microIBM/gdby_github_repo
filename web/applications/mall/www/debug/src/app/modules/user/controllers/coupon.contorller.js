'use strict';

angular
.module('dachuwang')
.controller('couponController', [ '$rootScope' ,'$scope', 'rpc', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal',  function ($rootScope ,$scope, rpc, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal) {

  var vm = $scope.vm = {};
  var couponType = null;

  //  导航配置
  vm.couponNav = [
    {name : '全部优惠劵',
      status : null
    },
    {name : '可用',
      status : 1
    },
    {name : '不可用',
      status : 0
    }
  ]

  // 分页参数初始化
  vm.paginationConf = {
    currentPage: 1,
    itemsPerPage: 10
  };

  vm.getCouponType = function(status){
    //daChuLocal.set('couponType' , status);
    couponType = status;
    vm.getList();
  }

  // 获取优惠劵
  vm.getList = function(){
    //daChuLocal.get('couponType') != undefined ? vm.couponType = daChuLocal.get('couponType') : vm.couponType =  null;
    $rootScope.showLoading = true;
    var promise = rpc.load('coupon/lists', 'POST' , {
      //status : vm.couponType,
      status: couponType,
      currentPage: vm.paginationConf.currentPage,
      itemsPerPage: vm.paginationConf.itemsPerPage
    })

    promise.then(function(data){
      $rootScope.showLoading = false ;
      vm.paginationConf.totalItems = data.total;
      vm.coupons = data.lists;
    }, function(data){

    })
  }

  vm.getList();

  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch(
    'vm.paginationConf.currentPage + vm.paginationConf.itemsPerPage',
    vm.getList
  );

}]);
