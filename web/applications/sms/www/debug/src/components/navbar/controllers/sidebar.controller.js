'use strict';

angular
  .module('hop')
  .controller('SidebarCtrl', ['$scope', '$location', '$cookieStore', function ($scope, $location,  $cookieStore) {
    $scope.selectedItem = null;
    $scope.changeUrl = function(url) {
      $scope.selectedItem = url;
    }
    var type = parseInt($cookieStore.get('type'));
    var productManage =  {'name': '商品管理','val':[
      {'name':'商品列表', 'val':'home.product'},
      {'name':'库存管理', 'val':'home.storage'},
      {'name':'商品改价', 'val':'home.productPrice'},
      {'name':'KA定价策略', 'val':'home.memberRebate'}
    ]};
    var adsManage =  {'name': '广告管理','val':[
      {'name':'广告管理', 'val':'home.ads'},
      {'name':'广告位管理', 'val':'home.adsPosition'},
    ]};
    var opManage =  {'name': '运营活动管理','val':[
      {'name':'促销活动管理', 'val':'home.activity'},
      {'name':'首页推荐', 'val':'home.recommend'},
      {'name':'专题列表', 'val':'home.subject'}
    ]};
    // 优惠券管理
    var couponManage =  {'name': '优惠券管理','val':[
      {'name':'规则控制器', 'val':'home.couponRule'},
      {'name':'发券活动管理', 'val':'home.coupon'},
      {'name':'券码管理', 'val':'home.customerCoupon'}
    ]};
    if(type === 100) {
      $scope.urls = [productManage, adsManage, opManage, couponManage];
    } else {
      $scope.urls = [productManage, adsManage, opManage];
    }
  }]);
