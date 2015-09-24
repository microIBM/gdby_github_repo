'use strict';

// 登录控制器
angular
.module('dachuwang')
.controller('UserLoginController', ['$rootScope' , '$scope', '$modal' , '$state', 'userAuthService', 'rpc', '$cookieStore', 'daChuLocal','daChuDialog','deliver', "cartlist", function( $rootScope, $scope,$modal,$state, userAuthService, rpc, $cookieStore, daChuLocal,daChuDialog, deliver, cartlist) {

  $scope.fee_deliver = '';
  $scope.fee_fee = '';
  if(userAuthService.isLogined()) {
    $state.go('page.userCenter');
    return false;
  }

  // 登录model
  $scope.user = {
    mobile   : '',
    password : ''
  };


  // ..
  // 提示信息
  $scope.message = {
    text   : '',
    status : ''
  };
  //提示信息
  $scope.error = {

  }
  $scope.autoLogin = true ;
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
      daChuLocal.set('userInfo' , msg.info);
      $rootScope.$emit('userInfo' , msg.info);
      daChuLocal.set('token', msg.token);
      daChuLocal.remove('packaged_cate');
      daChuLocal.set('customer_type', msg.customer_type);
      localStorage.setItem('delivercook', JSON.stringify(msg.deliver_fee_rule));
      deliver.fees=  msg.deliver_fee_rule.fee;
      deliver.number =  msg.deliver_fee_rule.free_amount;

      var default_cus_type = 1;
      var customer_type = daChuLocal.get('customer_type') ? daChuLocal.get('customer_type') : default_cus_type;
      //判断购物车中的商品所属的客户类型是否与该客户一致，清除购物车中不一致的商品
      cartlist.clearItemsByCusType(customer_type);

      if(rpc.refer && rpc.refer != '/') {
        rpc.redirect(rpc.refer);
      } else {
        history.back();
      }
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });

  };
  $scope.cancel = function() {
    $scope.$modalInstance.close();
  };
  $scope.showTips = function() {
    $modal.open({
      templateUrl: 'components/modal/login-tips.html',
      controller :  function($scope , $modalInstance){
        $scope.cancel = function(){
          $modalInstance.close();
        }
      }
    });
    return;
  }

}]);
