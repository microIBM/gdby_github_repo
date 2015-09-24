'use strict';

angular.module('dachuwang')
  .controller('BackFlowWaterController', ['$scope','$modalInstance','daChuLocal','rpc','time_type', BackFlowWaterController]);

function BackFlowWaterController($scope,$modalInstance,daChuLocal,rpc,time_type) {
  function init() {
    $scope.page = {
      currentPage : 1,
      itemsPerPage : 7
    };
  }
  function newPage() {
    var postData = angular.extend({}, {action:'get_back_flow_water', role_id:parseInt(daChuLocal.get('role_id')), currentPage:$scope.page.currentPage, itemsPerPage:$scope.page.itemsPerPage}, time_type);
    function success(data) {
      $scope.dataLists = data.list.list;
      $scope.page.total = data.list.total;
    }
    function error(err) {
      console.log(err);
    }
    rpc.load('statistics/index','POST',postData)
      .then(success, error);
  }
  init();
  $scope.$watch('page.currentPage', function() {
    newPage();
  });
  $scope.close = function() {
    $modalInstance.close();
  }
}
