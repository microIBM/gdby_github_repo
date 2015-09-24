'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('couponController', [ '$rootScope' ,'$scope', 'req', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal', 'coupon', 'userService', function ($rootScope ,$scope, req, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal , coupon , userService) {

  $rootScope.showLoading = true ;
  var getLists = function(status) {
    coupon.query(function(data) {

      // is_load 为已经拉取过数据 ，
      if(data.lists && status == 1){
        coupon.valid_load = true ;
      }
      if(data.lists && status == 0){
        coupon.no_valid_load = true ;
      }

      $rootScope.showLoading = false ;

      // 如果status不为零 。 弹出msg 并退出
      if(data.status != 0){
       // alert(data.msg);
        return ;
      }
      coupon.list = data.lists;
      angular.forEach( coupon.list , function( v , k){
        // 找出各种类型的优惠劵`
        if(v.status == 1 || v.status == 2){
          coupon.ucenterList.active_data.push(v);
        };
        if(v.status == 3 || v.status == 4){
          coupon.ucenterList.inactive_data.push(v);
        }
      })
    }, {status: status});

  }


  userAuthService.checkLogin();
  /// 可用优惠劵
  $scope.dialog = daChuDialog.tips;

  $scope.active = function(){
    $scope.current = false ;
    if(!coupon.valid_load){
      getLists(1);
    }
  }

  // 不可用优惠劵
  $scope.no_active = function(){
    $scope.current = true ;
    if(!coupon.no_valid_load){
      getLists(0);
    }
  }

  // 如果已经缓存过就直接负值 ， 没有就请求一次， false为已经缓存过
if(!coupon.ucenterList.active_data.length){
    $scope.active();
    if(!coupon.no_valid_load){
      getLists(0);
    }

    $rootScope.showLoading = false ;
    $scope.coupon = coupon;
  }

    $scope.coupon = coupon;
    $rootScope.showLoading = false;



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
