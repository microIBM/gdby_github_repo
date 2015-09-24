  'use strict'
  // 配置
  angular
    .module('hop')
    .factory('daChuConfig',['$location',function($location) {
      //配置支付方式
      var dilivery = 0;
      var weixin = 1;
      var payby = {
        weixin : weixin,
        delivery : dilivery
      };

      //微信对账单导出地址
      var paylist_export_url = 'http://pay.dachuwang.com/weixin/wxpay/download.php';

      //微信支付地址
      var domain = $location.$$host;
      if(domain.indexOf('test4') > 0){
        paylist_export_url = 'http://pay.test4.dachuwang.com/weixin/wxpay/download.php';
      }else if(domain.indexOf('test3') > 0){
        paylist_export_url = 'http://pay.test3.dachuwang.com/weixin/wxpay/download.php';
      }else if(domain.indexOf('test2') > 0){
        paylist_export_url = 'http://pay.test2.dachuwang.com/weixin/wxpay/download.php';
      }else if(domain.indexOf('test') > 0){
        paylist_export_url = 'http://pay.test.dachuwang.com/weixin/wxpay/download.php';
      }

      var url = {
        paylist_export : paylist_export_url,
      }

      //组装配置
      var config = {
        payby : payby,
        url: url
      };

      return config;

  }]);