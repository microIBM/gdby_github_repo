'use strict';

// 封装请求
angular.module('hop')
 .factory('req', ['$http', '$location', '$cookieStore', 'appConfigure','$rootScope', function($http, $location, $cookieStore, appConfigure,$rootScope) {
  var domain = $location.$$host;

  return {
    // 对http服务封装
    getdata : function(url, method, callback, post_data, isAjaxLoading) {
      var  surl = "", creditBool=true;
      // 是否直接调用假接口
      surl=surl.indexOf("http")>-1? surl: appConfigure.url;
     
     if(method == undefined) {
        method = 'POST';
      };
      var $this = this;
       $rootScope.isAjaxLoading=false; 
      
       
      if(isAjaxLoading!=undefined && (typeof isAjaxLoading==='boolean'))
           $rootScope.isAjaxLoading=true;

      if(url.indexOf("http")>-1){
         surl = url + "?r=" + Math.random();
         creditBool = false;
      } else {
         surl = surl + "/" + url + "?r=" + Math.random();
      }
     
       $http({
        url:surl,
        method:method,
        cache:false,
        withCredentials: creditBool,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        data:post_data
      })
      .success(function(data) {
          $rootScope.isAjaxLoading=false;

          if(data && data.status === -100) {
            $cookieStore.remove('id');
            $cookieStore.remove('type');
            $this.redirect('/user/login');
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
    }
  }
}]);
