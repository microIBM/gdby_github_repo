'use strict';

angular
  .module('hop')
  .controller('NavbarCtrl', ['$scope','$cookieStore', '$location', function ($scope, $cookieStore, $location) {
    $scope.currentUrl = $location.$$url.split('_')[0];
    $scope.status = {
      isopen: false
    };
    $scope.title = 'SMS';
}]);
