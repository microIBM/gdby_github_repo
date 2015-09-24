'use strict';
/* ui-router 运营系统的路由配置
 * @author liaoxianwen@dachuwang.com
 * @date 3-5
 */
angular.module('hop')
  .config(function ($stateProvider, $urlRouterProvider, $locationProvider) {
    var moduleDir = 'app/modules';
    $stateProvider
      .state('home', {
        url: '/',
        templateUrl: moduleDir + '/home/home.html',
        controller: 'HomeCtrl'
      })
     .state('home.product', {
        url: 'product',
        templateUrl: moduleDir + '/product/list.html',
        controller: 'ProductCtrl'
      })
     .state('home.storage', {
        url: 'storage',
        templateUrl: moduleDir + '/storage/list.html',
        controller: 'storageCtrl'
      })
      .state('home.productPrice', {
        url: 'product/price',
        templateUrl: moduleDir + '/product/price.html',
        controller: 'ProductPriceCtrl'
      })
      .state('home.productAdd', {
        url: 'product/add',
        templateUrl: moduleDir + '/product/add.html',
        controller: 'ProductAddCtrl'
      })
      .state('home.productEdit', {
        url: 'product/{productId:[0-9]{1,}}',
        templateUrl: moduleDir + '/product/add.html',
        controller: 'ProductEditCtrl'
      })
      .state('home.productDetail', {
        url: 'product/detail/{productId:[0-9]{1,}}',
        templateUrl: moduleDir + '/product/detail.html',
        controller: 'ProductDetailCtrl'
      })
      // 商品快照
      .state('home.productSnap', {
        url: 'product/snapshot/{productId:[0-9]{1,}}',
        templateUrl: moduleDir + '/product/snapshot.html',
        controller: 'ProductSnapCtrl'
      })
      .state('home.memberRebate', {
        url: 'memberRebate/list',
        templateUrl: moduleDir + '/memberRebate/list.html',
        controller: 'MemberRebateCtrl'
      })
      .state('home.memberRebateEdit', {
        url: 'memberRebate/edit/{customerId:[0-9]{1,}}',
        templateUrl: moduleDir + '/memberRebate/edit.html',
        controller: 'MemberRebateEditCtrl'
      })
      .state('home.adsCreate', {
        url: 'ads/create',
        templateUrl: moduleDir + '/ads/add.html',
        controller: 'AdvCreateCtrl'
      })
      .state('home.ads', {
        url: 'ads',
        templateUrl: moduleDir + '/ads/list.html',
        controller: 'AdsCtrl'
      })
      .state('home.posCreate', {
        url: 'position/create',
        templateUrl: moduleDir + '/adsPosition/add.html',
        controller: 'AdsPosCreateCtrl'
      })
      .state('home.adsPosition', {
        url: 'position',
        templateUrl: moduleDir + '/adsPosition/list.html',
        controller: 'AdsPosCtrl'
      })
      .state('home.activity', {
        url: 'activity',
        templateUrl: moduleDir + '/activity/list.html',
        controller: 'ActivityCtrl'
      })
      .state('home.actCreate', {
        url: 'activity/add',
        templateUrl: moduleDir + '/activity/add.html',
        controller: 'ActivityAddCtrl'
      })
      .state('home.subject', {
        url: 'subject',
        templateUrl: moduleDir + '/subjects/list.html',
        controller: 'SubjectCtrl'
      })
      .state('home.subCreate', {
        url: 'subject/add',
        templateUrl: moduleDir + '/subjects/add.html',
        controller: 'SubjectAddCtrl'
      })
      .state('home.recommend', {
        url: 'recommend',
        templateUrl: moduleDir + '/recommends/list.html',
        controller: 'RecommendCtrl'
      })
      .state('home.recommendCreate', {
        url: 'recommend/add',
        templateUrl: moduleDir + '/recommends/add.html',
        controller: 'RecommendAddCtrl'
      })
      .state('home.coupon', {
        url: 'coupon',
        templateUrl: moduleDir + '/coupon/list.html',
        controller: 'CouponCtrl'
      })
      .state('home.couponAdd', {
        url: 'coupon/create/{ruleId:[0-9]{1,}}',
        templateUrl: moduleDir + '/coupon/add.html',
        controller: 'CouponAddCtrl'
      })
      .state('home.couponRule', {
        url: 'coupon_rule',
        templateUrl: moduleDir + '/coupon_rules/list.html',
        controller: 'CouponRuleCtrl'
      })
      .state('home.couponRuleAdd', {
        url: 'coupon_rule/create',
        templateUrl: moduleDir + '/coupon_rules/add.html',
        controller: 'CouponRuleAddCtrl'
      })
      .state('home.customerCoupon', {
        url: 'customer/coupon',
        templateUrl: moduleDir + '/customer_coupon/list.html',
        controller: 'CustomerCouponCtrl'
      })
      .state('home.customerCouponAdd', {
        url: 'customer_coupon/create/{couponId:[0-9]{1,}}',
        templateUrl: moduleDir + '/customer_coupon/add.html',
        controller: 'CustomerCouponAddCtrl'
      })
      .state('login', {
        url: '/login',
        templateUrl: moduleDir + '/user/login.html',
        controller: 'LoginCtrl'
      });
      $urlRouterProvider.otherwise('/');
      $locationProvider.html5Mode(true);
  })
;
