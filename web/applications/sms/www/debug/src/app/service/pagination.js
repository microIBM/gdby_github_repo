'use strict';

// 分页逻辑服务
angular.module('hop').factory('pagination', function() {

  // 初始化分页对象
  var obj = {
    page : 1,
    isProcessing : false,
    processingText: '努力加载中...',
    isDone : false,
    more : '点击加载更多',
    callback : null, // 设置分页请求函数
  };

  // 加载下一页的函数
  obj.nextPage = function() {
    if(obj.isProcessing || obj.isDone) {
      return;
    }
    obj.more = '正在加载更多...';
    obj.isProcessing = true;
    obj.page ++;
    if(obj.callback) {
      obj.callback(function(checkDone) {
        if(checkDone) {
          obj.isDone = true;
          obj.more = '没有更多了';
        } else {
          obj.more = '点击加载更多';
        }
        obj.isProcessing = false;
      });
    }
  };

  // 初始化函数
  obj.init = function(callback) {
    obj.page = 1;
    obj.isProcessing = false;
    obj.isDone = false;
    obj.more = '点击加载更多';
    obj.callback = callback;
  }

  return obj;
});
