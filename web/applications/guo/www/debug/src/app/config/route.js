'use strict';

angular
.module('dachuwang')
.config(function ($stateProvider, $urlRouterProvider, $locationProvider) {
  var tempDir = 'app/modules/';
  var componentDir = 'components/';
  var tabMap = {
    'home' : 0,
    'cart' : 2,
    'order': 1,
    'user' : 3
  };
  $stateProvider
  // 通用模板
  .state('page', {
    url : '/',
    data : {
      pageTitle : '大果网',
      tabIndex : tabMap.home,
      showBack : false
    },
    views: {
      '' : {
        templateUrl : componentDir+'page/page.html',
        controller: 'pageController'
      },
      '@page' : {
        templateUrl : tempDir+'home/home.html',
        controller : 'homeController',
     }
    }
  })
  .state('page.home', {
    url : 'home',
    templateUrl : tempDir+'home/home.html',
    controller : 'homeController',
    data : {
      pageTitle : '大果网',
      tabIndex : tabMap.home,
      showBack : false
    }
  });
  $urlRouterProvider.otherwise('/home');
  $locationProvider.html5Mode(true);
})
;
