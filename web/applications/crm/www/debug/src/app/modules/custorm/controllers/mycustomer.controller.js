'use strict';

angular.
  module('dachuwang').
  controller('MyCustomerController',['$scope', function($scope) {
    $scope.showType = 0;
    $scope.setStatus = function(num) {
      $scope.showType = num;
    }
    $scope.tabs = [{status:0,href:'',name:'当前客户'},{status:1,href:'',name:'潜在客户'}];
}]);
