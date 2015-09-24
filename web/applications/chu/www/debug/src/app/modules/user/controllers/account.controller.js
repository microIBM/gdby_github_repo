'use strict';

// 子帐号管理
angular
.module('dachuwang')
.controller('subaccountController', ['$rootScope' , '$scope', 'req', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal',  'coupon' , 'userService', 'cartlist' , function ($rootScope , $scope, req, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal , coupon , userService, cartlist) {

  $rootScope.showLoading = true ;
  userAuthService.checkLogin();
  $scope.dialog = daChuDialog.tips;
  
  var DC = $scope.DC ={};

  // 订单列表
  var callBack = function(data) {
    if(data.status == 0){
      $rootScope.showLoading = false ;
      DC.subList = data.list;
    }
  };

  // 获取账号基本信息
  req.getdata('customer/sub_account_list', 'POST', callBack);
}]);
