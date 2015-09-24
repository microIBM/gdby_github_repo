'use strict';
// 封装请求
angular
  .module('dachuwang')
  .factory('rpc', ['$http', '$q', '$location', '$cookieStore', 'appConfigure', 'daChuLocal', function($http, $q, $location, $cookieStore, appConfigure, daChuLocal) {
    return {
      // 对http服务封装
      load : function(url, method, postData) {
        var $this   = this,
            defered = $q.defer();
        if(method == undefined) {
          method = 'POST';
        };
        $http({
          url             : appConfigure.url+ '/' + url + '?r=' + Math.random(),
          method          : method,
          cache           : false,
          withCredentials : true,
          data            : postData,
          headers         : {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        .success(function(data) {
          if(data && data.status === -100) { // 未登录
            daChuLocal.remove('token');
            defered.reject('您还未登录，请先登录。');
            $this.redirect('/user/login', $this.refer);
          } else if(data && data.status === 0) {
            defered.resolve(data);
          } else if(data && data.status !== 0) {
            defered.reject(data.msg+' 服务器拒绝添加');
          } else {
            defered.reject('请求失败，请检查网络。');
          }
        })
        .error(function(err) {
          defered.reject('请求无法发送到服务器');
        });
        return defered.promise;
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
