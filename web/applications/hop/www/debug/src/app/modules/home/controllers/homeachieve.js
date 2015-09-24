'use strict';

angular
  .module('hop')
  .controller('HomeAchieveCtrl', ['dialog', 'req', '$scope', '$modal', '$window','$cookieStore', 'HopAuth', function(dialog, req, $scope, $modal, $window, $cookieStore, HopAuth) {
  $scope.data = '';
  req.getdata('hop/summary/index', 'GET', function(data){
    if(data.status == 0) {
      $scope.data = data.data;
    }
  });
}]);
