'use strict';

//配置url地址
angular
  .module('dachuwang')
  .provider('appConfigure', {
    $get: function($location) {
      var domain = $location.$$host;
      if(domain.indexOf('dachuwang') < 0) {
        domain = 'chu.dachuwang.net';
      }
      return {
        url: 'http://api.' + domain
      };
    }
  });
