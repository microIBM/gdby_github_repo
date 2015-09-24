'use strict';

angular.
  module('dachuwang').
  controller('manageController',['$scope', '$state', '$window', 'daChuLocal', 'daChuDialog', 'rpc', function($scope, $state, $window, daChuLocal, dialog, rpc) {
    $scope.isLoading = true;
    rpc.load('customer/list_group','POST')
      .then(function(res) {
        $scope.isLoading = false;
        $scope.my = res.list.customer;
        $scope.mygroup = res.list.list;
      },function(err) {
        dialog.alert('无法连接服务器，可能是网络较差');
      });
    $scope.viewCustom = function(people_id) {
      $state.go('page.crm',{invite_id : people_id});
    }
  }]);
