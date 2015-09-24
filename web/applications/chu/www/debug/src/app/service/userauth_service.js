'use strict';

// 用户权限
angular
.module('dachuwang')
.factory('userAuthService', ['rpc', '$cookieStore', 'daChuLocal',  'userService',function(rpc, $cookieStore, daChuLocal , userService) {

  // 判断是否已登录
  var checkLogin = function() {
    var token = daChuLocal.get('token'); 

    if(!token && !userService.checkCookie() ) {
      rpc.redirect('user/login');
      return false;
    }
  };
  var isLogined = function() {
    var token = daChuLocal.get('token'); 
    if(userService.checkCookie() || !!token){
      return true;
    }
    return false ;
  }



  return {
    checkLogin : checkLogin,
    isLogined : isLogined
  };
}]);
