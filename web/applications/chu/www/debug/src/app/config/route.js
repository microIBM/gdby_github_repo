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
      showHeader :false ,
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

      showHeader :false ,
    },
    resolve : {
      categoryService : 'categoryService',
      cateObj : function(categoryService) {
        return categoryService.getAll();
      }
    }
  })
  // 登录页
  .state('loginpage', {
    url : '/user/login',
    data : {
      pageTitle : '登录',
      showBack  : true,

      showHeader :true ,
    },
    templateUrl : tempDir+'user/login.html',
    controller : 'UserLoginController'
  })
  // 修改密码
  .state('page.password', {
    url : 'user/password',
    data : {
      pageTitle : '修改密码',
      showBack  : true ,

      showHeader :true ,
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

      showHeader :true ,
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
  // 订单列表相关-订单详情
  .state('page.orderInfo', {
    url : 'order/orderinfo/',
    data : {
      pageTitle: '订单详情',
      tabIndex : tabMap.user,
      showBack : true,
      //backState : 'page.orderList',
      showHeader :4 ,
    },
    templateUrl : function() {
      return tempDir+'order/orderinfo.html';
    },
    controller : 'orderinfoController'
  })
  // 订单列表相关-订单详情
  .state('page.billInfo', {
    url : 'order/billinfo/',
    data : {
      pageTitle: '账单详情',
      tabIndex : tabMap.user,
      showBack : true,
      backState : 'page.billList',
      showHeader :4 ,
    },
    templateUrl : function() {
      return tempDir+'order/billinfo.html';
    },
    controller : 'billinfoController'
  })

 
  // 商品列表相关
  .state('page.list', {
    url : 'category/{cateId:[0-9]{1,8}}/',
    data : {
      pageTitle : '商品列表',
      tabIndex : tabMap.order,
      showBack : true,

      showHeader :false ,
      //backState : 'page.home'
    },
    views: {
      '' : {
        templateUrl : tempDir+'list/category.html',
        controller: 'cateController',
        resolve : {
          categoryService : 'categoryService',
          cateObj : function(categoryService) {
            return categoryService.getAll();
          }
        },
      },
      'list@page.list' : {
        templateUrl : tempDir+'list/list.html',
        controller : 'padController',
      }
    },
  })
  // 商品列表相关
  .state('page.category', {
    url : 'category/{cateId:[.\\n]*}/',
    data : {
      pageTitle : '商品列表',
      tabIndex : tabMap.order,
      showBack : true,

      showHeader :false ,
      //backState : 'page.home'
    },
    views: {
      '' : {
        templateUrl : tempDir+'list/category.html',
        controller: 'cateController',
        resolve : {
          categoryService : 'categoryService',
          cateObj : function(categoryService) {
            return categoryService.getAll();
          }
        },
      },
      'list@page.category' : {
        templateUrl : tempDir+'list/list.html',
        controller : 'padController',
      }
    },
  })


  // 商品列表搜索
  .state('page.search', {
    url : 'search/{searchVal}/',
    data : {
      pageTitle : '商品列表',
      tabIndex : tabMap.home,
      showBack : true,

      showHeader :false ,
      //backState : 'page.home'
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

      showHeader :true ,
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

      showHeader :true ,
      backState : 'page.home'
    }/*,
resolve : {
userService: 'userService',
userInfo: function(userService){
return userService.baseInfo();
},
}*/
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
      showHeader :true ,
      backState : 'page.userCenter'
    }
  })
  // 子帐号管理
  .state('page.subaccount', {
    url : 'user/subaccount',
    templateUrl : tempDir+'user/subaccount.html',
    controller : 'subaccountController',
    data : {
      pageTitle : '子帐号管理',
      tabIndex : tabMap.user,
      showBack : true,

      showHeader :true ,
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

      showHeader :true ,
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

      showHeader :true ,
      backState : 'page.cart'
    }
  })

  // 专题详情页
  .state('page.subject', {
    url : 'subject/{subjectId:[0-9]{1,}}',
    templateUrl : tempDir+'subjects/activity.html',
    controller : 'subjectController',
    data : {
      pageTitle : '专题详情',
      tabIndex : tabMap.home,
      showBack : true,

      showHeader :true ,
      backState : 'page.home'
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
