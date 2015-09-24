'use strict';

angular
  .module('dachuwang')
  .controller('footerController', ['$scope', 'cartlist', '$state', function($scope, cartlist, $state) {

    /*var tabList = [
      {
        name : '首页',
        glyphicon : 'sprite-home',
        href : 'page.home'
      },
      {

        name : '分类',
        glyphicon : 'sprite-cate',
        href : 'page.category',
      },
      {
        name : '购物车',
        glyphicon : 'sprite-shop',
        href : 'page.cart',
        showBadge : true
      },
      {
        name : '个人中心',
        glyphicon : 'sprite-user',
        href : 'page.userCenter'
      }
    ];*/
    var tabList = [
      {
        name : '首页',
        glyphicon : 'sprite-home',
        href : 'page.home'
      }
   ];
   $scope.tabList = tabList;
    $scope.cartlist = cartlist;
    $scope.$state = $state;
  }]);
