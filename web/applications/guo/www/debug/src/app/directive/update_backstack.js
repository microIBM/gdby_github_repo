angular
.module('dachuwang')
.directive('updateBackstack', ['$rootScope', 'req', function($rootScope, req) {
  return {
    link: function(scope, element) {
      var listener = function(event, toState) {
        var backPath = 'page';
        if (toState.backPath) {
          backPath = toState.backPath;
        }
        element.attr('ui-sref', backPath);
      };
      $rootScope.$on('$stateChangeSuccess', listener);
    }
  };
}
]);
