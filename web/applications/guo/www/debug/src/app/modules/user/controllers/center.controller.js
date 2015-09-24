'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('UserCenterCtrl', ['$scope', '$rootScope' ,'req', 'userService' ,'$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal', function ($scope, $rootScope , req, userService , $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal) {

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
    $scope.uinfo = data.info;

    $rootScope.showLoading = false ;
    $scope.valid_coupon_nums = data.valid_coupon_nums;
    $scope.order = data.order;
  };

  // 获取账号基本信息
  req.getdata('customer/baseinfo', 'POST', callBack);
}]);
