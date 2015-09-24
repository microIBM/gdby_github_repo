'use strict';

angular
.module('pda')
  //
  .controller('IndexCtrl', ['$scope', 'req', '$location', '$cookieStore', 'HopAuth', function($scope, req, $location, $cookieStore, HopAuth) {
    var post_data = {};
    post_data.operator_utype = $cookieStore.get('type');
    post_data.operator_uid   = $cookieStore.get('id');
    if(!post_data.operator_uid){
      req.redirect('login');
    }

    $scope.logout = function() {
      HopAuth.logout();
    }
  }]);
