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
      pageTitle : '大厨网',
      tabIndex : tabMap.home,
      showBack : false,
      showHeader : 1 ,
    },
    views: {
      '' : {
        templateUrl : componentDir+'page/page.html',
        controller: 'pageController'
      },
      '@page' : {
        templateUrl : tempDir+'home/home.html',
        controller : 'homeController',

        resolve : {
          categoryService : 'categoryService',
          cateObj : function(categoryService) {
            return categoryService.getAll();
          }
        }
      }
    }
  })
  .state('page.home', {
    url : 'home',
    templateUrl : tempDir+'home/home.html',
    controller : 'homeController',
    data : {
      pageTitle : '大厨网',
      tabIndex : tabMap.home,
      showBack : false,
      showHeader :1,
    },
    resolve : {
      categoryService : 'categoryService',
      cateObj : function(categoryService) {
        return categoryService.getAll();
      }
    }
  })

  //  登录
  .state('page.login', {
    url : 'user/login',
    templateUrl : tempDir+'user/login.html',
    controller : 'UserLoginController',
    data : {
      pageTitle : '登录',
      tabIndex : tabMap.user,
      showBack : true,
      showHeader :2,
    }
  })

  // 修改密码
  .state('page.password', {
    url : 'user/password',
    data : {
      pageTitle : '修改密码',
      showBack  : true ,

      showHeader :4 ,
    },
    templateUrl : tempDir+'user/password.html',
    controller : 'UserPasswordController'
  })
  // 更换地址
  .state('page.citySelect', {
    url : 'city',
    data : {
      pageTitle : '更换城市',
      showBack : true,

      showHeader :true ,
    },
    templateUrl : tempDir+'city/list.html',
    controller : 'CityController'
  })
  // 订单列表相关
  .state('page.orderList', {
    url : 'order/list/{status:[0-9]{0,8}}',
    data : {
      pageTitle: '我的订单',
      tabIndex : tabMap.user,
      showBack : true,
      backState : 'page.userCenter',
      order : 'order',
      showHeader :4 ,
    },
    templateUrl : function() {
      return tempDir+'order/orderList.html';
    },
    controller : 'orderListController'
  })
  // 订单列表相关-我的账单
  .state('page.billList', {
    url : 'order/billlist/{status:[0-9]{0,8}}',
    data : {
      pageTitle: '我的账单',
      tabIndex : tabMap.user,
      showBack : true,
      backState : 'page.userCenter',
      order :'mybill',
      showHeader :4 ,
    },
    templateUrl : function() {
      return tempDir+'order/myBill.html';
    },
    controller : 'billListController'
  })



  // 商品列表相关
  .state('page.list', {
    url : 'list/{cateId}/',
    data : {
      pageTitle : '商品列表',
      tabIndex : tabMap.order,
      showBack : true,
      showHeader : 1 ,
    },
    templateUrl : tempDir+'list/category.html',
    controller: 'padController',
  })

  // 商品详情相关
  .state('page.detail', {
    url : 'detail/{cateId}/',
    data : {
      pageTitle : '商品详情',
      tabIndex : tabMap.order,
      showBack : true,
      showHeader : 1 ,
    },
    templateUrl : tempDir+'detail/detail.html',
    controller: 'detailController',
  })

  // 商品列表搜索
  .state('page.search', {
    url : 'search/{searchVal}/',
    data : {
      pageTitle : '搜索结果 ',
      tabIndex : tabMap.home,
      showBack : true,
      showHeader :1 ,
    },
    templateUrl : function() {
      return tempDir+'list/search.html';
    },

    controller : 'searchController'
  })

  // 购物车相关
  .state('page.cart', {
    url : 'cart',
    templateUrl : tempDir+'cart/cart.html',
    controller : 'cartController',
    data : {
      pageTitle : '购物车',
      tabIndex : tabMap.cart,
      showBack : true,
      showHeader : 3 ,
      backState : 'page.home'
    }
  })
  // 个人中心相关
  .state('page.userCenter', {
    url : 'user/center',
    templateUrl : tempDir+'user/center.html',
    controller : 'UserCenterCtrl',
    data : {
      pageTitle : '个人中心',
      tabIndex : tabMap.user,
      showBack : true,
      order : 'usecenter',
      showHeader :true ,
      backState : 'page.home'
    }
  })

  // 个人中心详情页
  .state('page.info', {
    url : 'user/info',
    templateUrl : tempDir+'user/info.html',
    controller : 'infoCtrl',
    data : {
      pageTitle : '个人信息',
      tabIndex : tabMap.user,
      showBack : true,

      showHeader :true ,
      backState : 'page.userCenter'
    }
  })
  // 个人中心详情页
  .state('page.often', {
    url : 'user/often',
    templateUrl : tempDir+'user/often.html',
    controller : 'oftenCtrl',
    data : {
      pageTitle : '经常购买',
      tabIndex : tabMap.user,
      showBack : true,
      showHeader :1 ,
      backState : 'page.userCenter'
    }
  })
// 子母单页
  .state('page.single', {
    url : 'user/single',
    templateUrl : tempDir+'user/lashSingle.html',
    controller : 'lashController',
    data : {
      pageTitle : '子母单列表',
      tabIndex : tabMap.user,
      showBack : true,
      order : 'single',
      showHeader : 4 ,
      backState : 'page.userCenter'
    }
  })
  // 优惠劵页
  .state('page.coupon', {
    url : 'user/coupon',
    templateUrl : tempDir+'user/coupon.html',
    controller : 'couponController',
    data : {
      pageTitle : '优惠劵',
      tabIndex : tabMap.user,
      showBack : true,
      order : 'coupon',
      showHeader : 4 ,
      backState : 'page.userCenter'
    }
  })
  // 选择优惠劵页
  .state('page.chooseCoupon', {
    url : 'user/chooseCoupon',
    templateUrl : tempDir+'user/chooseCoupon.html',
    controller : 'chooseController',
    data : {
      pageTitle : '选择优惠劵',
      tabIndex : tabMap.cart,
      showBack : true,

      showHeader :true ,
      backState : 'page.confirm'
    }
  })
  // 确认订单页
  .state('page.confirm', {
    url : 'confirm',
    templateUrl : tempDir+'confirm/confirm.html',
    controller : 'confirmController',
    data : {
      pageTitle : '确认订单',
      tabIndex : tabMap.cart,
      showBack : true,

      showHeader :3 ,
      backState : 'page.cart'
    }
  })

  // 专题详情页
  .state('page.subject', {
    url : 'subject/{subjectId:[0-9]{1,}}',
    templateUrl : tempDir+'subjects/activity.html',
    controller : 'subjectControllers',
    data : {
      pageTitle : '专题详情',
      tabIndex : tabMap.home,
      showBack : true,

      showHeader :true ,
      backState : 'page.home'
    }
  })

  // 微信支付页面
  .state('page.pay', {
    url : 'pay/{orderId:[0-9]{1,}}',
    templateUrl : tempDir+'pay/pay.html',
    controller : 'payController',
    data : {
      pageTitle  : '微信支付',
      showHeader : 3
    }
  })
  // 帮助页面
  .state('page.help', {
    url : 'help/{help}' ,
    views: {
      '' : {
        templateUrl : tempDir+'help/help.html',
        controller : 'helpController',
      },
      '@page.help' : {
        templateUrl : function($stateParmas){
          return tempDir+'help/helpDetail/help'+$stateParmas.help+'.html'
        }
        //templateUrl : tempDir+'help/helpDetail/help2-1.html'
      }
    },
    data : {
      pageTitle : '帮助中心',
      tabIndex : tabMap.home,
      showBack : true,

      showHeader :6,
      backState : 'page.help'
    }
  })


  // 活动详情页
  .state('page.advs', {
    url : 'advs/{advId:[0-9]{1,}}',
    templateUrl : tempDir+'advs/detail.html',
    controller : 'advsController',
    data : {
      pageTitle : '活动详情',
      tabIndex : tabMap.home,
      showBack : true,

      showHeader :true ,
      backState : 'page.home'
    }
  });

  $urlRouterProvider.otherwise('/home');
  $locationProvider.html5Mode(true);
})
;
