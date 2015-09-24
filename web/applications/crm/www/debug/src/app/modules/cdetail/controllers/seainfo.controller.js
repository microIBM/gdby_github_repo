'use strict';

angular.module('dachuwang')
.controller('SeainfoController',['$rootScope','$scope', '$state', '$window', 'rpc', 'daChuDialog','Lightbox','urlHistoryService','daChuLocal', function($rootScope,$scope, $state, $window, rpc, daChuDialog, Lightbox, urlHistoryService, daChuLocal) {
  $scope.customer = {};
  $scope.history = {};
  $scope.baseinfo = {};
  $scope.isLoading = true;
  $scope.historymsg = $scope.basemsg = true
  $scope.userinfo = {newmsg:'最新短信',history:'历史信息',baseinfo:'基本信息'}
  rpc.load('cdetail/index','POST',{action:'get_all',uid:$state.params.uid})
  .then(function(data){
    $scope.isLoading = false;
    $scope.imgurl = data.list.basic_info.urls;
    $scope.customer.sms = data.list.sms;
    $scope.history = data.list.his_data;
    $scope.baseinfo = data.list.basic_info;
  });
  //图片点击放大
  $scope.openmodal = function(index){
    var images = [];
    for(var i=0; i<$scope.imgurl.length; i++) {
      images.push({url:$scope.imgurl[i].url});
    }
    $rootScope.imglength = images.length;
    Lightbox.openModal(images, index);
  }
  //不是bd
  $scope.role_id = true;
  var role_id = parseInt(daChuLocal.get('role_id'));
  if(role_id != 12){
     $scope.role_id = false;
  }

  $scope.func = {
    new_register_change_private : function() {
      var cid = parseInt($scope.baseinfo.id);
      $scope.change_button = true;
      rpc.load('shared_customer/new_register_change_private', 'POST', {cid : cid})
        .then(function(res) {
          $window.alert('操作成功');
          $scope.change_button = false;
          urlHistoryService.push(5);
          $state.go('page.manage');
        }, function(res) {
          $window.alert(res);
          $scope.change_button = false;
        });
    },
    history_detail : function() {
      $state.go('page.cdetailhistory' , {uid : $scope.baseinfo.id})
    } 
  }
}]);
