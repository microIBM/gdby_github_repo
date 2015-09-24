'use strict';

// 封装请求
angular.module('pda')
 .factory('req', ['$http', '$location', '$cookieStore', 'appConfigure', function($http, $location, $cookieStore, appConfigure) {
  var domain = $location.$$host,
      surl = appConfigure.url,
      stimeout = appConfigure.timeout;

  return {
    // 对http服务封装
    getdata : function(url, method, callback, post_data) {
      if(method == undefined) {
        method = 'POST';
      };
      var $this = this;
      $http({
        url:surl + "/" + url + "?r=" + Math.random(),
        method:method,
        cache:false,
        withCredentials: true,
        timeout: stimeout,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        data:post_data,
      })
      .success(function(data) {
          if(data && data.status === -100) {
            $cookieStore.remove('id');
            $cookieStore.remove('type');
            $this.redirect('/user/login');
          }
          callback(data);
        })
      .error(function() {
        var errordata = {};
        errordata.status = -1;
        errordata.msg = '网络连接错误，请重新刷新尝试';
        callback(errordata);
      });
    },
    init: $location,
    refer:'/',
    redirect : function(url, ref) {
      if(ref != undefined ) {
        this.refer = ref;
      } else if(url == '') {
        $location.path(this.refer);
      }
      $location.path(url);
    }
  }
}]);
