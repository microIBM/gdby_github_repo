'use strict';
/* ui-router 运营系统的路由配置
 * @author liaoxianwen@dachuwang.com
 * @date 3-5
 */
 angular.module('pda')
 .config(function ($stateProvider, $urlRouterProvider, $locationProvider) {
  var moduleDir = 'app/modules';
  $stateProvider
  .state('login', {
    url: '/login',
    templateUrl: moduleDir + '/user/login.html',
    controller: 'LoginCtrl'
  })
  .state('root', {
    url: '/',
    templateUrl: moduleDir + '/user/login.html',
    controller: 'LoginCtrl'
  })
  .state('modifypwd', {
    url: '/modifypwd',
    templateUrl: moduleDir + '/user/modifypwd.html',
    controller: 'ModifypwdCtrl'
  })
  .state('index', {
    url: '/index',
    templateUrl: moduleDir + '/index/index.html',
    controller: 'IndexCtrl'
  })
  .state('picking', {
    url: '/picking',
    templateUrl: moduleDir + '/picking/picking.html',
    controller: 'PickingCtrl'
  })
  .state('picking_confirm', {
    url: '/picking_confirm/{code}',
    templateUrl: moduleDir + '/picking/picking_confirm.html',
    controller: 'PickingConfirm'
  })
  .state('dispatch', {
    url: '/dispatch',
    templateUrl: moduleDir + '/dispatch/dispatch.html',
    controller: 'DispatchCtrl'
  })
  .state('dispatch_confirm', {
    url: '/dispatch_confirm/{code}',
    templateUrl: moduleDir + '/dispatch/dispatch_confirm.html',
    controller: 'DispatchConfirm'
  });

  $urlRouterProvider.otherwise('/');
  $locationProvider.html5Mode(true);
})
;