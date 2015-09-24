'use strict';

// 用户权限
angular
  .module('dachuwang')
  .factory('userAuthService', ['rpc', '$cookieStore', 'daChuLocal', function(rpc, $cookieStore, daChuLocal) {

    // 判断是否已登录
    var checkLogin = function() {
      var token = daChuLocal.get('token');
      if(!token) {
        rpc.redirect('user/login');
      }
    };

    var isLogined = function() {
      var token = daChuLocal.get('token');
      return !!token;
    }

    return {
      checkLogin : checkLogin,
      isLogined : isLogined
    };
}]);
