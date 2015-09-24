'use strict';

angular
.module('pda')
  // 修改控制器
  .controller('ModifypwdCtrl', ['$scope', 'req', '$location', '$cookieStore', '$timeout', function($scope, req, $location, $cookieStore, $timeout) {
    // 用户model
    $scope.user = {
      mobile: '',
      password: '',
      newPassword:'',
      newRePassword:'',
    };

    //修改密码操作
    $scope.modifypwd = function() {
      if($scope.modifypwdForm.$invalid) {
        $scope.modifypwdForm.submitted = true;
        return false;
      }

      if($scope.user.password === $scope.user.newPassword){
        $scope.error = {cls:'alert alert-danger', msg:'新密码和原密码相同，请重新输入'};
        return false;
      } else if($scope.user.newRePassword === $scope.user.newPassword) {
        var callBack = function(data) {
          if(parseInt(data.status) === 0) {
            $scope.error = {cls:'alert alert-success', msg:'修改密码成功!'};
            $timeout(function(){
              req.redirect('login');
            },2000);
          } else {
            $scope.error = {cls:'alert alert-danger', msg:data.msg};
            return false;
          }
        };
        req.getdata('user/update_password', 'POST',  callBack, $scope.user);
      } else {
        $scope.error = {cls:'alert alert-danger', msg:'确认新密码填写和新密码不一致'};
        return false;
      }
    };

  }]);
