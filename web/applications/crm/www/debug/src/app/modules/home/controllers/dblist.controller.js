'use strict';

angular.
  module('dachuwang').
  controller('DblistController',['$scope', '$window', '$log', '$state', 'rpc', 'daChuDialog', 'daChuLocal', function($scope, $window, $log, $state, rpc, dialog, daChuLocal) {
  $scope.tabs = [
    {status : 1, name : '组员业绩'},
    {status : 0, name : '业绩汇总'}
  ];
  $scope.timeTabs = [
    {status : 0, name : '今日'},
    {status : 1, name : '本周'},
    {status : 2, name : '本月'},
    {status : 3, name : '总计'},
    {status : 4, name : '自选区间'},
  ];
  $scope.showType = 1;
  $scope.timeShowType = 0;
  $scope.setStatus = function(st) {
    $scope.showType = st;
    if(st == 1) {
      getLists();
    } else {
      getTotalStatistics();
    }
  }
  $scope.setTimeStatus = function(st) {
    $scope.timeShowType = st;
  }
  function getLists() {
    $scope.isLoading = true;
    $scope.showLists = true;
    $scope.showTotal = false;
    rpc.load('customer/list_group', 'POST')
    .then(function(res) {
      $scope.isLoading = false;
      $scope.mygroup = res.list.list;
    }, function(err) {
      $scope.isLoading = false;
      dialog.alert('哎呀，服务器连不通啊');
    });
  }

  $scope.viewStatistics = function(uid) {
    $state.go('page.home',{bd_id : uid});
  }
  getLists();
  $scope.openstatus = {
    list : [true,true,true,true,true,true],
    query : false,
    sum : true
  };
  function getTotalStatistics() {
    $scope.isLoading = true;
    $scope.showLists = false;
    $scope.showTotal = true;
    var postData = {
      action : 'get_total_statistics',
      role_id : parseInt(daChuLocal.get('role_id')) 
    };
    rpc.load('statistics/index', 'POST', postData).
      then(function(res) {
      $scope.statistics = res.list;

      $scope.isLoading = false;
      if(res.status == 0) {
        $scope.today = res.list.today;
        $scope.all = res.list.all;
      } else {
        dialog.alert('请求出错');
      }
    }, function(err) {
      dialog.alert('网络不好连不上啊');
    })
  }
}])
