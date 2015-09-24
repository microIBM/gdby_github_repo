'use strict';

angular
  .module('hop')
  .controller('GoodsCtrl', ['$scope', 'req', 'dialog', 'appConfigure','$cookieStore', function($scope, req, daChuDialog, appConfigure,$cookieStore) {
    $scope.site_url = appConfigure.url;
    $scope.status = 'all';// 展示全部
    var getList = function() {
      var key= $cookieStore.get('productCookie') || '';
      $scope.key = key;
      var postData = {
        currentPage: $scope.paginationConf.currentPage,
        itemsPerPage: $scope.paginationConf.itemsPerPage,
        searchVal: $scope.key,
        status: $scope.status
      };

      req.getdata('/sku/manage', 'POST', function(data) {
        $scope.products = data.list;
        $scope.paginationConf.totalItems = data.total;
      }, postData);
    }
    $scope.title = '商品列表';
    // 分页参数初始化
    $scope.paginationConf = {
      currentPage: 1,
      itemsPerPage: 15
    };
    // 查询商品
    $scope.search = function() {
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
    req.getdata('sku/set_status', 'POST', function(data) {
        if(parseInt(status) === 1) {
          $scope.dialog({bodyText: '可以发布此货号的商品了。'});
        } else {
          $scope.dialog({bodyText: '不能发布、编辑此货号商品了。'});
        }
    },{
      status : status,
      id     : product_id
    });
  };

 }]);
