'use strict';

angular
  .module('hop')
  .controller('DistributionDetailCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams) {
  $scope.data = '';
  $scope.deal_price = {};
  $scope.dist_id = $stateParams.dist_id;
  var getInfo = function() {
    req.getdata('distribution/view', 'POST', function(data){
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.orderList = data.list;
      }
    },{id: $scope.dist_id});
  };
  getInfo();

  $scope.back = function() {
    history.go(-1);
  };
}]);
