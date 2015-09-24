'use strict';

angular.module('dachuwang')
  .controller('HisController',['$scope', '$state', 'rpc', function($scope,$state,rpc) {
    $scope.func = {
      goback : function() {
        $state.go('page.customerdetail');
      }
    }
    $scope.isLoading = true;
    $scope.historylist = {};
   rpc.load('cdetail/index','POST',{action:'get_history_belong_detail',uid:$state.params.uid})
  .then(function(data){
    $scope.isLoading = false;
    $scope.data = data.list;
    $scope.historylist.list = $scope.data;
    for(var i in $scope.historylist.list){
      $scope.historylist.list[i].time = $scope.historylist.list[i].time * 1000 ;
    }
  })
}]);
