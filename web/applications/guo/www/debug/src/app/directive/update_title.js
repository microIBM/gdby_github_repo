angular
.module('dachuwang')
.directive('updateTitle', ['$rootScope', '$timeout', function($rootScope, $timeout) {
  return {
    link: function(scope, element) {
      var listener = function(event, toState, toParams) {
        var title = '大果网';
        if (toState.data && toState.data.pageTitle) {
          title = toState.data.pageTitle;
        }
        $timeout(function() {
          element.text(title);
        }, 0, false);
      };
      $rootScope.$on('$stateChangeSuccess', listener);
    }
  };
}
]);
