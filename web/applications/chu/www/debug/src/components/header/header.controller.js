'use strict';

angular
  .module('dachuwang')
  .controller('headerController', ['$scope', '$state', 'posService', 'userAuthService', function($scope, $state, posService, userAuthService) {
    $scope.$state = $state;
    $scope.localInfo = posService.info();
    $scope.isLogined = userAuthService.isLogined();
  }]);
