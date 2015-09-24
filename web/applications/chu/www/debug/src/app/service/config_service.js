'use strict'
// 配置
angular
  .module('dachuwang')
  .factory('daChuConfig',['$location',function($location) {
    //配置支付方式
    var dilivery = 0;
    var weixin = 1;
    var payby = {
      weixin : weixin,
      delivery : dilivery
    };

    //组装配置
    var config = {
      payby : payby,
    };

    return config;

}]);