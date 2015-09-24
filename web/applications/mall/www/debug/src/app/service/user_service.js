'use strict';

// 用户服务
angular
.module('dachuwang')
.factory('userService', ['$rootScope' , '$http', '$q', 'req' ,'appConfigure', 'daChuLocal', 'rpc', function($rootScope , $http, $q, req  , appConfigure, daChuLocal, rpc) {
  var api_url = appConfigure.url;
  // 缓存时间
  var cache_time = 30 * 1000;
  // 获取用户基本信息
  var baseInfo = function() {
    // 从本地缓存获取数据
    var dateObj = new Date();
    var baseInfo = daChuLocal.get('baseInfo');
    if(baseInfo && !isNaN(baseInfo.pro_time)
       && (baseInfo.pro_time+cache_time > dateObj.getTime())) {
      // 返回本地缓存数据
      return $q.when(baseInfo.data);
    }

    // 缓存不存在或超时则通过网络获取数据
    var result = rpc.load('/customer/baseinfo');
    return result;
  };


    // 检查是否cookie是否存在
  var checkCookie = function(){
    var cookieArr = document.cookie.split(';')
    var cookie = null ;
    for(var i =0 ; i<cookieArr.length ; i++ ){
      var item = cookieArr[i].split('=')[0].replace(/^\s+|\s+$/ , '');
      if( item == 'remember' ){
        cookie = true;
        break ; 
      }
        cookie = false;
    }
    if(cookie){
      return true ;
    }
      return false ;
  }
  //用户登出
  var login_out = function(){
        req.getdata('customer/logout', 'POST', function() {
          daChuLocal.remove('token');
          daChuLocal.remove('userInfo');
          daChuLocal.remove('currentLocation');
          daChuLocal.remove('packaged_cate');
          daChuLocal.remove('coupon_active');
          daChuLocal.remove('customer_type');
          req.redirect('/user/login');
        });
        $rootScope.$emit('userInfo' , '');
  }

  return {
    baseInfo : baseInfo,
    login_out : login_out,
    checkCookie : checkCookie 
  };
}]);
