'use strict';
//位置服务,获取用户当前位置

angular
  .module('dachuwang')
  .factory('locationService',function() {
    var getCurrent = function() {
      return '北京';
    };

    return {
      getCurrent : getCurrent
    };
  });
