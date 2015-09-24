'use strict';

// 用户服务
angular
.module('dachuwang')
.factory('userService', ['$http', '$q', 'appConfigure', 'daChuLocal', function($http, $q, appConfigure, daChuLocal) {
  var cateArr = [];
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
    var defered = $q.defer();
    $http
      .get(api_url+'/customer/baseinfo')
      .success(function(data) {
      var result = [];
      if(data.status === 0) {
        var result = data.info;
        // 缓存数据
        daChuLocal.set('packaged_cate', {
          pro_time : dateObj.getTime(),
          data : result
        });
        defered.resolve(result);
      } else {
        defered.reject('status not equals 0');
      }
    })
    .error(function(err) {
      //预警
      defered.reject(err);
    });
    return defered.promise;
  };

  // 修改密码
  var changePassword = function(parentCategory) {
    var defered = $q.defer();
    var result = {topName: '', list:[]};
    var cateArr = daChuLocal.get('cateArr');
    if(cateArr && cateArr.list.top.length > 0) {
      var is_ok = false;
      angular.forEach(cateArr.list.second, function(value) {
        angular.forEach(value, function(data) {
          if(data.id === parentCategory) {
            result.topName = data.name;
            is_ok = true;
            return;
          }
        });
        if(is_ok === true) {
          return;
        }
      });
      if(is_ok === false) {
        defered.reject('error parent category id');
      }
      angular.forEach(cateArr.list.second_child[parentCategory], function(value) {
        result.list.push({
          pro_name : value.name,
          pro_id : value.id,
          pro_upid: value.upid
        });
      });
      defered.resolve(result);
    } else {
      defered.reject('you must load home page first');
    }
    return defered.promise;
  };

  return {
    baseInfo : baseInfo,
    changePassword: changePassword,
  };
}]);
