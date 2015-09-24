'use strict';

angular.module('dachuwang')
  .controller('NewAddOrderListsController', ['$scope','$modalInstance','rpc', 'time_type', 'daChuLocal','bd_id',NewAddOrderListsController]);

function NewAddOrderListsController($scope, $modalInstance, rpc, time_type, daChuLocal, bd_id) {
  function init() {
    $scope.page = {
      currentPage : 1,
      itemsPerPage : 7
    };
  }
  function newPage() {
    var postData = angular.extend({}, {action:'get_new_add_orders', role_id:parseInt(daChuLocal.get('role_id')), currentPage:$scope.page.currentPage, itemsPerPage:$scope.page.itemsPerPage}, time_type);
    if(bd_id !== -1) {
      postData.bd_id = bd_id;
    }
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
