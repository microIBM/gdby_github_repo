'use strict';
//商品分类查询的服务
/*
 * 此次将$http服务干掉，换成已经封装好得rpc
 * description: 修复无法post值，而且不够灵活 
 * datetime: 4-8
 */
angular
  .module('dachuwang')
  .factory('categoryService', ['$http', '$q', '$cookieStore', 'daChuLocal', 'rpc', 'posService', function($http, $q, $cookieStore, daChuLocal, rpc, posService) {
    /*获取所有分类
     * return array 分类列表
     */
    var cache_time = 30000;
    var getAll = function() {
      var dateObj = new Date();
      var packaged_cate = daChuLocal.get('packaged_cate');
      if(packaged_cate && !isNaN(packaged_cate.pro_time) && (packaged_cate.pro_time+cache_time > dateObj.getTime())) {
        return $q.when(packaged_cate.cate);
      } else {
        var defered = $q.defer();
        // 检测当前城市
        var localInfo = daChuLocal.get('currentLocation'),
          localId = localInfo ? localInfo.id : 0;
        rpc.load('category/lists', 'POST', {locationId: localId})
          .then(function(data) {
          var result = [];
          if(typeof data.list.user_info.location_id != 'undefined') {
            posService.setInfo(data.list.user_info.location_id, data.list.user_info.name);
          };
          daChuLocal.set('cateArr',data);
            daChuLocal.set('advSwitch', data.adv_switch_index);
          angular.forEach(data.list.top, function(value) {
            var sub = [];
            angular.forEach(data.list.second[value.id], function(data) {
              sub.push({
                name : data.name,
                id : data.id
              });
            });
            result.push({
              cate_parent : value.name,
              cate_parent_id : value.id,
              cate_child : sub
            });
          });
          daChuLocal.set('packaged_cate', {
            pro_time : dateObj.getTime(),
            cate : result
          });
          defered.resolve(result);
        })
        return defered.promise;
      }
    };

    /*获取某个分类的子分类
     * @param string 父分类名
     * return array 子分类列表
     */
    var getSubCategory = function(parentCategory) {
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
        // bak
        /*angular.forEach(cateArr.list.second_child[parentCategory], function(value) {
          result.list.push({
            pro_name : value.name,
            pro_id : value.id,
            pro_upid: value.upid
          });
        });*/
        defered.resolve(result);
      } else {
        defered.reject('you must load home page first');
      }
      return defered.promise;
    };

    return {
      getAll : getAll,
      getSubCategory : getSubCategory
    };
  }]);
