'use strict';
angular
.module('hop')
.controller('AdsPosCreateCtrl', ['$rootScope' ,'$scope', 'req', function($rootScope , $scope, req) {
  // 增加广告位
  $scope.title = '添加广告位';
  var getDefault = function() {
    req.getdata('ads_position/input_options', 'GET', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.options = data.list;
        $scope.pos_status = $scope.options.status[0];
      }
    });
  }
  getDefault();
  // 位置model
  $scope.pos = {
    title : '',
    status : 1
  };
  // 增加操作
  $scope.add = function() {
    $scope.pos.status = $scope.pos_status.value;
    $rootScope.is_loading = true ;
    req.getdata('ads_position/save', 'POST', function(data) {
      $rootScope.is_loading = false;
      alert(data.msg);
      if(data.status == 0){

        req.redirect('/position');
      }
    }, $scope.pos);
  }
}]);
