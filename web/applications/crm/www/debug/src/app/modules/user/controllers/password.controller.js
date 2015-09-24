'use strict';
// moved by liaoxianwen
angular
.module('dachuwang')
.controller('userPassCtrl', ['$scope', 'rpc', '$cacheFactory', '$timeout', '$location', 'daChuDialog', '$cookieStore', function($scope, rpc, $cacheFactory, $timeout, $location, daChuDialog, $cookieStore) {

  $scope.reg = {
    password : '',
    newPassword : '',
    newRePassword : '',
  };

  $scope.dialog =  daChuDialog.tips;

  $scope.passwordCheck = function() {
    if($scope.reg.newPassword != $scope.reg.newRePassword) {
      $scope.checked = 1;
      $scope.dialog({bodyText:'两次输入密码不一致。'});
      return false;
    }else {
      $scope.checked = 0;
    }
  }

  $scope.changePassword = function() {
    $scope.passwordCheck();
    rpc.load('user/update_password', 'POST', $scope.reg).then(function(data) {
      alert(data.msg);
      rpc.load('user/logout', 'POST').then(function() {
        $cookieStore.remove('token');
        rpc.redirect('/user/login');
      });

    }, function(msg) {
      alert(msg);
    });
  }
}]);
