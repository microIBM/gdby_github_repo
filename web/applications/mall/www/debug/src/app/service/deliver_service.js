'use strict';
angular
  .module('dachuwang')
  .factory('deliver', ['daChuLocal', 'rpc', function(daChuLocal, rpc){
    var fee = {
      fees : {},
      number : {},
      obj : JSON.parse(localStorage.getItem('delivercook'))
    }
    return fee;
  }]);
