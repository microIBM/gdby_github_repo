'use strict';

/**
 * 金钱格式过滤器，统一成最多小数点后两位
 * @author : caiyilong@ymt360.com
 * @since : 2015-01-29
 */
angular
.module('dachuwang')
.filter('money', function() {
  return function(input, fixedLength) {
    input = parseFloat(input) || 0;
    fixedLength = parseInt(fixedLength) != NaN ? parseInt(fixedLength) : 1;
    return input.toFixed(fixedLength);
  }
});
