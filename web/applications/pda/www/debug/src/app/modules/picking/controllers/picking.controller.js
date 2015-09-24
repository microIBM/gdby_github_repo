'use strict';

angular
.module('pda')
.controller('PickingCtrl', ['$scope', 'req', '$location', '$cookieStore','daChuLocal', 'HopAuth', function($scope, req, $location, $cookieStore,daChuLocal, HopAuth) {

  //让扫码框自动对焦
  document.getElementById('autofocus').focus();
  // 数据等待
  $scope.wait = false;
  $scope.code_number = '';
  $scope.error = {cls:'alert alert-danger', msg:''};
  var post_data = {};
  post_data.operator_utype = $cookieStore.get('type');
  post_data.operator_uid   = $cookieStore.get('id');
  if(!post_data.operator_uid){
    req.redirect('login');
  }

  $scope.logout = function() {
    HopAuth.logout();
  }
  var common = {
    'length':function (o){
     var t = typeof o;
     if(t == 'string'){
      return o.length;
    }else if(t == 'object'){
      var n = 0;
      for(var i in o){
       n++;
     }
     return n;
   }
	   return false;
	 }
	};
	var callBack = function(data) {
	  $scope.wait = false;
    if(data.status === -1) {
      $scope.error = {cls:'alert alert-danger text-center', msg:data.msg == 'failed' ? data.data : data.msg};
      return false;
    }
	  if(parseInt(data.status) === 0) {
	   if(common.length(data.data) > 0){
	    daChuLocal.set('picking_info', data.data);
	    req.redirect('picking_confirm/'+data.data.prefix+data.data.pick_number);
		  }else{
		    $scope.error = {cls:'alert alert-danger', msg:'无效的分拣单号'};
		  }
		} else {
		  $scope.error = {cls:'alert alert-danger', msg:data.data};
		  return false;
		}
	};
	
	$scope.check_code = function(){
	  $scope.wait = true;
	  if($scope.code_number !=''){
	    post_data.code_number = $scope.code_number;
	    req.getdata('pda/picking_info', 'POST',  callBack, post_data);
	  }else{
	    $scope.wait = false;
	    $scope.error = {cls:'alert alert-danger', msg:'请提供正确的配送单号'};
	  }
	};
}]);

