'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('chooseController', ['$scope', '$rootScope' ,'req', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal', 'coupon' ,  function ($scope, $rootScope ,req, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal , coupon ) {

  var coupon_service  = daChuLocal.get('coupon_active')

  $scope.coupon = coupon_service;

  $rootScope.showLoading = false ;
  // 选择优惠劵
  $scope.choose_sum = function(item){
    coupon.choose_sum = item.detail.minus_amount ;
    coupon.coupon_id = item.id ;
    req.redirect('/confirm')
  }

  userAuthService.checkLogin();
  $scope.dialog = daChuDialog.tips;

  $scope.active = function(){
     $scope.current = false ;
  }

  $scope.no_active = function(){
     $scope.current = true ;
  }
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
}]);
