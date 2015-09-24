'use strict';

//配置url地址
angular
  .module('dachuwang')
  .provider('appConfigure', {
    $get: function($location) {
      var domain = $location.$$host;
      if(domain.indexOf('dachuwang') < 0) {
        domain = 'crm.dachuwang.net';
      }
      return {
        bd_and_am : [12,14],
        url: 'http://api.' + domain
      };
    }
  });
