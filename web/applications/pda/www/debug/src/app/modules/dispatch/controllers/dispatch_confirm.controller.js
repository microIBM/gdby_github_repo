'use strict';

angular
.module('pda')
.controller('DispatchConfirm', ['$scope', 'req', '$location', '$cookieStore','$stateParams','$timeout','daChuLocal', 'HopAuth', function($scope, req, $location, $cookieStore, $stateParams, $timeout, daChuLocal, HopAuth) {
  // 数据等待
  $scope.wait = false;
  $scope.code_number = '';
  $scope.order_count = 0;
  $scope.error = {class:'alert alert-danger', msg:''};
  var post_data = {};
  post_data.operator_utype = $cookieStore.get('type');
  post_data.operator_uid   = $cookieStore.get('id');
  if(!post_data.operator_uid){
    req.redirect('login');
  }

  var callBack = function(data) {
    if(parseInt(data.status) === 0) {
      $scope.order_count = data.data.order_count || '';
      $scope.code_number = data.data.dist_number ? data.data.prefix+data.data.dist_number : '';
    } else {
      $scope.error = {class:'alert alert-danger', msg:data.data};
      return false;
    }
  };

  //先从本地存储获取配送信息，本地无数据就调接口获取
  var dispatch_info = daChuLocal.get('dispatch_info');
  if(dispatch_info.length > 0){
    $scope.order_count = dispatch_info.order_count || '';
    $scope.code_number = data.data.dist_number ? data.data.prefix+data.data.dist_number : '';
  }else{
    post_data.code_number = $stateParams.code;
    req.getdata('pda/dispatch_info', 'POST',  callBack, post_data);
  }

  var confirm_update = function(data){
    $scope.wait = false;
    if(data.status === -1) {
      $scope.error = {cls:'alert alert-danger text-center', msg:data.msg};
      return false;
    }
    if(parseInt(data.status) === 0 && parseInt(data.data) > 0) {
      $scope.error = {cls:'alert alert-success', msg:'订单更新成功'};
      $timeout(function(){
        req.redirect('dispatch');
      },1500);
    } else {
      $scope.error = {class:'alert alert-danger', msg:data.data};
      return false;
    }
  }
  $scope.submit_update = function(){
    $scope.wait = true;
    if($scope.code_number !=''){
      post_data.code_number = $scope.code_number;
      req.getdata('pda/dispatch', 'POST', confirm_update, post_data);
    }else{
      $scope.wait = false;
      $scope.error = {cls:'alert alert-danger', msg:'请提供正确的配送单号'};
    }
  };

  $scope.logout = function() {
    HopAuth.logout();
  }
}]);