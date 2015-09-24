'use strict';

//配置url地址
angular
  .module('pda')
  .provider('appConfigure', {
    $get: function($location) {
      var domain = $location.$$host;
      var timeout = 10000;
      if(domain.indexOf('dachuwang') < 0) {
        domain = 'pda.dachuwang.net';
        timeout = 10000;
      }
      return {
        url: 'http://api.' + domain,
        timeout: timeout
      };
    }
  });
