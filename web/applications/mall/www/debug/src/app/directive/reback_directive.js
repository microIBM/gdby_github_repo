'use strict';

// 返回
angular
  .module('dachuwang')
  .directive('reback', ['$state', function($state) {
    return {
      link: function($scope, ele) {
        ele.bind('click', function() {
          var data = $state.current.data;
          if(data.backState) {
            $state.go(data.backState);
          } else {
            history.go(-1);
          }
        })
      }
    };
}]);
