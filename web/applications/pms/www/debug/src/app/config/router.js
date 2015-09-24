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
     .state('home.propertyEdit', {
        url: 'property/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/property/add.html',
        controller: 'PropertyEditCtrl'
      })
      .state('home.propertyAdd', {
        url: 'property/add',
        templateUrl: moduleDir + '/property/add.html',
        controller: 'PropertyAddCtrl'
      })
      .state('home.categoryEdit', {
        url: 'category/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/category/add.html',
        controller: 'CategoryEditCtrl'
      })
      .state('home.category', {
        url: 'category',
        templateUrl: moduleDir + '/category/list.html',
        controller: 'CategoryCtrl'
      })
      .state('home.editMap', {
        url: 'map/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/category/map.html',
        controller: 'CateMapEditCtrl'
      })
      .state('home.cateMapAdd', {
        url: 'category/map_add',
        templateUrl: moduleDir + '/category/map.html',
        controller: 'CateMapAddCtrl'
      })
      .state('home.cateMap', {
        url: 'category/map',
        templateUrl: moduleDir + '/category/map_list.html',
        controller: 'CateMapCtrl'
      })
      .state('home.categoryAdd', {
        url: 'category/add',
        templateUrl: moduleDir + '/category/add.html',
        controller: 'CategoryAddCtrl'
      })
      .state('home.property', {
        url: 'property',
        templateUrl: moduleDir + '/property/list.html',
        controller: 'PropertyCtrl'
      })
      .state('home.goods', {
        url: 'goods',
        templateUrl: moduleDir + '/goods/list.html',
        controller: 'GoodsCtrl'
      })
      .state('home.goodsAdd', {
        url: 'goods/add',
        templateUrl: moduleDir + '/goods/add.html',
        controller: 'GoodsAddCtrl'
      })
      .state('home.GoodsEdit', {
        url: 'product/{productId:[0-9]{1,}}',
        templateUrl: moduleDir + '/goods/add.html',
        controller: 'GoodsEditCtrl'
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
