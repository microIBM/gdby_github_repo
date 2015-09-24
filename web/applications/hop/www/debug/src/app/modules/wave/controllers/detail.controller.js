'use strict';
angular
.module('hop')
.controller('WaveDetailCtrl', ['$scope','$stateParams', 'req', function($scope, $stateParams, req) {
 var getList = function() {
    var postData = {
      wave_id: $stateParams.wave_id
    };
    req.getdata('wave/wave_info', 'POST', function(data) {
      $scope.info = data.info;
      $scope.relatedOrders = data.related_orders;
    }, postData);
  }
  getList();
}]);
