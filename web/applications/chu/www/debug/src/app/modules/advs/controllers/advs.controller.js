'use strict';

angular.module('dachuwang')
.controller('advsController', ['$scope', '$stateParams', 'advService', function($scope, $stateParams, advService) {
  // 广告详情

  advService.detail($stateParams.advId).then(function(promise) {
    $scope.item = promise.info;
  }, function(msg) {
  });
}]);
