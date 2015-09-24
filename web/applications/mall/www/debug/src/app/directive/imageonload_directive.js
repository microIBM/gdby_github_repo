'use strict';

// 返回
angular
  .module('dachuwang')
  .directive('imageOnload', function() {
    return {
      restrict : 'A',
      scope : {
        callback : '&loadCallback'
      },
      link: function(scope, ele, attrs) {
        ele.css({
          'opacity' : 0,
          'transition' : 'opacity 1s'
        })
        ele.bind('load', function() {
          ele.addClass('animated');
          scope.callback();
        });
      }
    };
});
