'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('infoCtrl', ['$rootScope' , '$scope', 'req', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal',  'coupon' , 'userService', 'cartlist' , function ($rootScope , $scope, req, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal , coupon , userService, cartlist) {

  $rootScope.showLoading = true ;
  userAuthService.checkLogin();
  $scope.dialog = daChuDialog.tips;

  // 退出
  $scope.logout = function() {
    $scope.dialog({
      bodyText:'确定要退出吗？',
      ok: function() {
        userService.login_out();
      },
      actionText:'确定',
      closeText:'取消'
    });
  };

  // 订单列表
  var callBack = function(data) {
    $rootScope.showLoading = false ;
    $scope.uinfo = data.info;
    $scope.order = data.order;
    $scope.valid_coupon_nums = data.valid_coupon_nums;
  };

  // 获取账号基本信息
  req.getdata('customer/baseinfo', 'POST', callBack);
}]);
