'use strict';

angular
  .module('hop')
  .controller('TopCtrl', ['$scope','$cookieStore', '$location', 'HopAuth', function ($scope, $cookieStore, $location, HopAuth) {
    $scope.status = {
      isopen: false
    };
    HopAuth.auth();
    $scope.isLogin = HopAuth.isLogin;// 检测是否登录

    $scope.title = '销售管理系统';
    if($scope.isLogin) {
      var type = $cookieStore.get('type');
      if(parseInt(type) === 100) {
        $scope.userInfo = '超级管理员';
      } else if(parseInt(type) == 10) {
        $scope.userInfo = '运营';
      } else if(parseInt(type) === 11) {
        $scope.userInfo = '财务';
      } else if(parseInt(type) === 103) {
        $scope.userInfo = '仓管';
      }
      $scope.toggleDropdown = function($event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.status.isopen = !$scope.status.isopen;
      };
      $scope.logout = function() {
        HopAuth.logout();
      }
      // 修改密码
      $scope.changePwd = function() {
        HopAuth.pwd();
      }
    } else {
       $location.path('/login');
    }
}]);
