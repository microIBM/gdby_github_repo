'use strict';

// 登录控制器
angular
  .module('dachuwang')
  .controller('UserLoginController', ['$scope', '$state', 'userAuthService', 'rpc', '$cookieStore', 'daChuLocal', 'daChuDialog', function($scope, $state, userAuthService, rpc, $cookieStore, daChuLocal , daChuDialog) {


  if(userAuthService.isLogined()) {
    rpc.redirect('/user/center');
    return false;
  }

  // 登录model
  $scope.user = {
    mobile   : '',
    password : ''
  };

  // 提示信息
  $scope.message = {
    text   : '',
    status : ''
  };

  //提示信息
  $scope.error = {

  }
  $scope.init_diy = function(){
    $scope.error.message = '';
    $scope.isL = false;

  }
  $scope.focus = function(){
    $scope.init_diy();
    $scope.isgreens = 1;
  }

  $scope.focuss = function(){
    $scope.init_diy();

    $scope.isgreen = 1;
  }

  // 登录操作
  $scope.login = function() {

    $scope.show_error = true;
    if($scope.loginForm.$invalid) {
      $scope.loginForm.submitted = true;
      $scope.isL = false;
      return false;
    }
    $scope.isL = true;
    rpc.load('customer/login', 'POST', $scope.user).then(function(msg) {
      daChuLocal.set('token', msg.token);
      daChuLocal.remove('packaged_cate');
      if(rpc.refer && rpc.refer != '/') {
        rpc.redirect(rpc.refer);
      } else {
        rpc.redirect('user/center');
      }
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });
  };

  $scope.showTips = function() {
    daChuDialog.tips({bodyText : '您可以通过大果网官方客服电话：400-8199-491、官方QQ：975550226、大果网官方微信号提出申请，稍后我们会有专员前往考察，通过后，会邀请您注册成为大果网用户，即可轻松下单，享受新鲜食材快速送达！',    
    telText : '立即拨打',
    tel : '400-8199-491'
    })
  
  }
}]);
