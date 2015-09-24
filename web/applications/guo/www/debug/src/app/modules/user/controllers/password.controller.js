'use strict'
// moved by liaoxianwen
angular.module('dachuwang')
.controller('UserPasswordController', ['$scope', 'req', '$cacheFactory', '$timeout', '$location', 'daChuDialog', '$cookieStore', function($scope, req, $cacheFactory, $timeout, $location, daChuDialog, $cookieStore) {

  $scope.reg = {
    password : '',
    newPassword : '',
    newRePassword : '',
  };

  $scope.dialog =  daChuDialog.tips;


  $scope.changePassword = function() {
$scope.show_error = true;
    if($scope.passwordForm.$invalid){
        return ;
    }
    if($scope.reg.newPassword != $scope.reg.newRePassword) {
      $scope.dialog({bodyText:'两次输入密码不一致。'});
      return;
    }
    req.getdata('customer/change_password', 'POST', function(data) {
      if(data.status === 0) {
        $scope.dialog({
          bodyText:data.msg
        });
        req.getdata('customer/logout', 'POST', function() {
          $cookieStore.remove('token');
          req.redirect('/user/login');
        });
      }else {
        $scope.dialog({
          bodyText:data.msg
        });
      }
    }, $scope.reg);
  }
}]);
