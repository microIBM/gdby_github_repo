'use strict';
angular.module('dachuwang').directive('tjid',[function(){
    return function(scope, ele, attr) {
      ele.attr('id', 'tj-'+Math.random()); 
    };
}]);
