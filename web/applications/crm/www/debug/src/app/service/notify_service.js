'use strict';
//加载通知的服务

angular
  .module('dachuwang')
  .factory('notifyService',['$http', function($http) {
    var getAll = function() {
      return [
        {
          title : '您的订单201502280123已经在配送路上',
          content : '这里是详情'
        },
        {
          title : '您的订单201502280188已经在配送路上',
          href : '这里是详情'
        }
      ];
    };

    return {
      getAll : getAll
    };
  }]);
