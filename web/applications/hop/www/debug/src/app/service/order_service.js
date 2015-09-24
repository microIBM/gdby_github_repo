'use strict';

// 订单服务
angular
.module('hop')
.factory('orderService', ['$http', '$q', 'rpc', function($http, $q, rpc) {
  // 获取用户基本信息
  var distListInfo = function(dist_ids) {
    // 从本地缓存获取数据
    /*var dateObj = new Date();
    var distList = daChuLocal.get('distList');
    if(distList && !isNaN(distList.pro_time) 
       && (distList.pro_time+cache_time > dateObj.getTime())) {
      // 返回本地缓存数据
      return $q.when(distList.data);
    }*/

    var result = rpc.load('/distribution/lists', 'POST', {dist_ids: dist_ids, itemsPerPage: 'all'});
    return result;
  };
  var pickTaskListInfo = function(pick_ids) {
    var result = rpc.load('/wave/pick_task_list', 'POST', {pick_ids: pick_ids, itemsPerPage: 'all'});
    return result;
  };

  return {
    getDistList : distListInfo,
    getPickTaskList : pickTaskListInfo,
  };
}]);
