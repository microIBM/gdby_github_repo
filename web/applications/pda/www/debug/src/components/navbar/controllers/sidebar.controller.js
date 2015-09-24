'use strict';

angular
  .module('pda')
  .controller('SidebarCtrl', ['$scope', '$location', '$cookieStore', function ($scope, $location,  $cookieStore) {
    $scope.selectedItem = null;
    $scope.changeUrl = function(url) {
      $scope.selectedItem = url;
    }
    var type = parseInt($cookieStore.get('type'));
    var cateManage =  {'name': '分类管理','val':[
      {'name':'分类列表', 'val':'home.category'},
      {'name':'前端分类映射', 'val':'home.cateMap'},
    ]},
    productManage =  {'name': '货物管理','val':[
      {'name':'货物列表', 'val':'home.goods'},
    ]},
    propertyManage =  {'name': '规格管理','val':[
      {'name':'规格列表', 'val':'home.property'},
    ]};
    $scope.urls = [cateManage, propertyManage, productManage];
  }]);
