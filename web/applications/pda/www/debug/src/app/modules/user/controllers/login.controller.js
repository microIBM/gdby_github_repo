'use strict';

angular
  .module('pda')
  // 登陆控制器
  .controller('LoginCtrl', ['$scope', 'req', '$location', '$cookieStore', 'HopAuth', function($scope, req, $location, $cookieStore, HopAuth) {
    // 登陆model
    HopAuth.auth();
    if(HopAuth.isLogin == true) {
       req.redirect('index');
    }
    $scope.user = {
      mobile: '',
      password: '',
    };
    // 数据等待
    $scope.wait = false;

    // 登陆操作
     $scope.login = function() {
      if($scope.loginForm.$invalid) {
        $scope.loginForm.submitted = true;
        return false;
      }
      $scope.wait = true;
      var callBack = function(data) {
        $scope.wait = false;
        if(data.status === -1) {
          $scope.error = {cls:'alert alert-danger', msg:data.msg};
          return false;
        }
        if(parseInt(data.status) === 0) {
          // 设置用户类别
          $cookieStore.put('type', data.info.type);
          // 写入用户ID
          $cookieStore.put('id', data.info.id);
          // 存储权限
          localStorage.setItem('access', data.access);
          req.redirect('index');
        } else {
          $scope.error = {cls:'alert alert-danger', msg:data.msg};
          return false;
        }
      };

      req.getdata('user/login', 'POST',  callBack, $scope.user);
    };
}]);
