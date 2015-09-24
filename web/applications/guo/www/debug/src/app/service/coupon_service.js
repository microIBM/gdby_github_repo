'use strict';

// 大厨dialog
angular
.module('dachuwang')
.factory('coupon',['$rootScope' ,'$modal' ,'req', function( $rootScope ,$modal , req) {

  var coupon = {
    active_data : [],
    no_active_data : [],
    ucenterList : {
      active_data : [],
      inactive_data : []
    }
  }
  coupon.query = function(callback, post_data){
    req.getdata('coupon/lists', 'POST', callback, post_data);
  }
  return coupon ;
}])
