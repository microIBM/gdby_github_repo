'use strict';
// 封装请求
angular
.module('dachuwang')
.factory('req', ['$http', '$location', '$cookieStore', 'appConfigure', 'daChuLocal', function($http, $location, $cookieStore, appConfigure, daChuLocal) {
  return {
    // 对http服务封装
    getdata : function(url, method, callback, postData) {
      var  surl = "", creditBool=true;
      if(url.indexOf("http")>-1){
        surl = url + "?r=" + Math.random();
        creditBool = false;
      } else {
        surl = appConfigure.url + "/" + url + "?r=" + Math.random();
      }
      if(method == undefined) {
        method = 'POST';
      };
      var $this = this;
      $http({
        url:surl,
        method:method,
        cache:false,
        withCredentials:creditBool,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        data : postData
      })
      .success(function(data) {
        if(data && data.status === -100) {
          daChuLocal.remove('token');
          $this.redirect('/user/login', $this.refer);
        }
        callback(data);
      })
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
      return false;
    }
  }
}]);
