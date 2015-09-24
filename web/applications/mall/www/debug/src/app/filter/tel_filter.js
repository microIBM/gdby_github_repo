'use strict';

/**
 * 手机号格式过滤器 134****1212 格式
 * @author : zhangbo@dachuwang.com
 * @since : 2015-07-17
 */
angular
.module('dachuwang')
.filter('tel', function() {
  return function(tel) {
    if(tel){
      return  tel.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');
    }
  }
});
