'use strict';

angular
  .module('hop')
  .controller('storageCtrl', ['$rootScope' , '$filter' , '$scope', 'req', 'dialog', 'appConfigure','$cookieStore', function($rootScope , $filter , $scope, req, daChuDialog, appConfigure,$cookieStore) {

    // 编辑
    $scope.edit_storage = function(item){
       item.editing = true ;
    }

    // 取消
    $scope.cancel_storage = function(item){
    //   item.editing = true ;
       getList();
    }
    // 保存
    //
    //
    $scope.save_storage = function(item){
     // item.editing = true;
      var update_data = {};
      $rootScope.is_loading = true ;

      req.getdata('/stock/update' , 'POST' , function(data){

        //响应隐藏loading图标
        $rootScope.is_loading = false ;
        if(data.status == 0){
          $scope.dialog({bodyText: data.msg});
          getList();
        }

      },item)
    }

    //初始化刷选条件
    //
    $scope.locations = [
      {name : 'sku货号' ,
       type : 'sku_number'
    },

      {name : '仓库id' ,
       type : 'warehouse_id'
      }
    ]
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
        searchType : $scope.location.type,
        status: $scope.status,

      };

      req.getdata('stock/lists', 'POST', function(data) {
        $scope.products = data.list;
        $scope.paginationConf.totalItems = data.total_count;
      }, postData);
    }
    $scope.title = '库存管理';
    // 分页参数初始化
    $scope.paginationConf = {
      currentPage: 1,
      itemsPerPage: 50
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
