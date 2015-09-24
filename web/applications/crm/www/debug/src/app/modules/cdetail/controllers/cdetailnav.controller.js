'use strict';

angular.module('dachuwang')
  .controller('CdetailNavController',['$scope','$state','daChuLocal', function($scope, $state,daChuLocal) {
    $scope.showType = 0;
    $scope.statusopen = true;
    $scope.func = {
      historyGo : function() {
        $state.go('page.customerdetail.cdetailhistory');
      }
    }
    $scope.newmsg = $scope.historymsg = $scope.basemsg = true;
    $scope.setStatus = function(num) {
      daChuLocal.remove('ac_filter');
      $scope.showType = num;
    }
    $scope.tabs = [
      {status:0,href:'page.customerdetail',name:'客户信息'},
      {status:1,href:'page.customerdetail.trends',name:'客户订单'},
      {status:2,href:'page.customerdetail.accountChecking',name:'对账管理'}
    ];
    $scope.userinfo = {newmsg:'最新短信',history:'历史信息',baseinfo:'基本信息'}
}]);
