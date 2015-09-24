'use strict';

//滑动加载
angular
.module('dachuwang')
.directive('scrollClass', ['$document','$window','cartlist', function($document ,$window,cartlist) {
  var win = angular.element($window);

  var doc = angular.element($document);
  return {
    restrict : 'A',
    link: function(scope, ele, attr) {
      var offset = parseInt(attr.threshold) || 0;
      var e = ele[0];
      ele.on('scroll', function () {
        if (scope.$eval(attr.canLoad) && e.scrollTop + e.offsetHeight >= e.scrollHeight - offset) {
          scope.$apply(attr.scrollClass);
        }
      });
    }
  };
}])



