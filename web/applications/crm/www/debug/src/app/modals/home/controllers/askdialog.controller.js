'use strict';

angular.module('dachuwang')
.controller('AskDialogController', ['$scope','$modalInstance',function($scope,$modalInstance){

  $scope.close = function() {
    $modalInstance.close();
  }

}]);


