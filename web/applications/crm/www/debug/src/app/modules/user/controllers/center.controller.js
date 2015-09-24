'use strict';

// 用户子模块
// liaoxianwen 做表单，以及将dialog换成daChuDialog
angular
.module('dachuwang')
.controller('UserCenterCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$filter', 'userAuthService', 'daChuLocal', function ($scope, rpc, $cookieStore, daChuDialog, $filter, userAuthService, daChuLocal) {

  userAuthService.checkLogin();

  $scope.dialog = daChuDialog.tips;

  // 退出
  $scope.logout = function() {
    $scope.dialog({
      bodyText:'确定要退出吗？',
      ok: function() {
      rpc.load('user/logout', 'POST', $scope.user).then(function(msg) {
        daChuLocal.remove('token');
        daChuLocal.remove('role_id');
        daChuLocal.remove('city_id');
        daChuLocal.remove('site_id');
        if(rpc.refer && rpc.refer != '/') {
          rpc.redirect(rpc.refer);
        } else {
          rpc.redirect('user/login');
        }
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });

      },
      actionText:'确定',
      closeText:'取消'
    });
  };
  // 获取账号基本信息
  rpc.load('user/baseinfo', 'POST').then(function(data) {
     $scope.uinfo = data.info;
  });
}]);
