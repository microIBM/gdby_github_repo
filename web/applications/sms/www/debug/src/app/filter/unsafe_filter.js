'use strict';

/**
 * html unsafe过滤器，用来显示需要html标签起作用的文字
 * @author : zhangxiao@dachuwang.com
 * @since : 2015-07-08
 */
angular
.module('hop')
.filter('unsafe', ['$sce', function($sce) {
  return function(val) {
    return $sce.trustAsHtml(val);
  }
}]);