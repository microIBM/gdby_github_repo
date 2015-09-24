'use strict';

angular
.module('hop')
.controller('ProductCtrl', ['$rootScope' , '$scope', 'daChuLocal' ,'req', 'dialog', 'appConfigure','$cookieStore', function($rootScope , $scope, daChuLocal ,req, daChuDialog, appConfigure,$cookieStore) {
  $scope.site_url = appConfigure.url;
  $scope.status = 'all';// 展示全部
  var getList = function() {
    var key= $cookieStore.get('productCookie') || '';
    $scope.key = key;
    var locationId = 0;

    if(typeof $scope.locate != 'undefined') {
      locationId = parseInt($scope.locate.id);
    }
    var postData = {
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
      searchVal: $scope.key,
      // 取缓存城市
      locationId : daChuLocal.get('locate') ? daChuLocal.get('locate').id :locationId,
      status: $scope.status
    };

    $rootScope.is_loading = true ;
    req.getdata('/product/manage', 'POST', function(data) {
      $rootScope.is_loading = false ;
      $scope.products = data.list;
      $scope.locations = data.location;
      $scope.customer_type_options = data.customer_type_options;
      // 取缓存城市
      if(daChuLocal.get('locate')){
        var current_loca = daChuLocal.get('locate');
        angular.forEach(data.location, function(v){
          if(v.id == current_loca.id){
            $scope.locate = v;
          }
        })
      } else {
        $scope.locate = data.location[0];
      }
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  $scope.title = '商品列表';
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 50
  };
  // 查询商品
  $scope.search = function() {
    daChuLocal.set('locate' , $scope.locate );
    $cookieStore.put('productCookie',$scope.key)
    getList();
  }
  // 重置按钮
  $scope.reset = function(){
    $cookieStore.remove('productCookie')
    $scope.key = '';
    getList();
  }
  // 根据条件来筛选
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    getList();
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // 提示框
  $scope.dialog = daChuDialog.tips;

  // 下架上架
  $scope.setstatus = function(product_id, status, $index) {
    $scope.products[$index].status = status;
    req.getdata('product/set_status', 'POST', function(data) {
      if(parseInt(status) === 1) {
        $scope.dialog({bodyText: '商品已设置为有货，将在商城展示。'});
      } else {
        $scope.dialog({bodyText: '商品已设置为无货，将不在商城展示。'});
      }
    },{
      status : status,
      id     : product_id
    });
  };
}]);
