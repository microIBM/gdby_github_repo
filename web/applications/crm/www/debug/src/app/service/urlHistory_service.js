'use strict';
//url历史记录的服务
//返回手动保存的url列表

angular
  .module('dachuwang')
  .factory('urlHistoryService', function() {
    var urllists = [];
    var maxlength = 20;
    function push(url) {
      if(urllists.length >= maxlength) {
        urllists.shift();
      }
      urllists.push(url);
    };
    function pop() {
      if(urllists.length<=0) {
        return null;
      }
      var res = urllists.pop();
      return res;
    };
    function getLast() {
      if(urllists.length<=0) {
        return null;
      }
      return urllists[urllists.length-1];
    };
    return {
      push : push,
      pop : pop,
      getLast : getLast
    };
  });
