'use strict';

angular
  .module('hop')
  // 登陆控制器
  .controller('LoginCtrl', ['$scope', 'req', '$location', '$cookieStore', function($scope, req, $location, $cookieStore) {
    // 登陆model
    $scope.user = {
      mobile: '',
      password: '',
    };
    // 提示信息
    $scope.message = {text : '', status :''};

    // 登陆操作
    $scope.login = function() {
      if($scope.loginForm.$invalid) {
        $scope.loginForm.submitted = true;
        return false;
      }
      var callBack = function(data) {
        if(parseInt(data.status) === 0) {
          // 设置用户类别
          $cookieStore.put('type', data.info.type);
          // 写入用户ID
          $cookieStore.put('id', data.info.id);
          // 存储权限
          localStorage.setItem('access', data.access);
          req.redirect(req.refer);
        } else {
          $scope.error = {cls:'alert alert-danger', msg:data.msg};
          return false;
        }
      };
      req.getdata('user/login', 'POST',  callBack, $scope.user);
    };
}]);
