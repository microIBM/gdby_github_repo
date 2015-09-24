'use strict';

angular.module('dachuwang')
  .controller('showDetailsController', ['$scope','$modalInstance','rpc','daChuDialog','account_id', function($scope, $modalInstance,rpc,dialog,account_id) {
    $scope.func = {
      close : function() {
        $modalInstance.close();
      }
    };
    rpc.load('accheck/showConstitute', 'POST', {id:account_id})
      .then(function(data) {
        $scope.shopLists = data.list;
      }, function(err) {
        dialog.alert(err);
      });
  }]);
