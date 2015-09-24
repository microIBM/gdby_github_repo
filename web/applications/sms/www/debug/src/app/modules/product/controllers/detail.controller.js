'use strict';
angular
.module('hop')
// 添加货物
.controller('ProductDetailCtrl', [ '$rootScope' ,'$scope', 'req', '$stateParams', '$location', '$cookieStore', 'dialog', 'daChuLocal', 'daChuTimer', function($rootScope , $scope, req, $stateParams, $location, $cookieStore, daChuDialog, daChuLocal, daChuTimer) {
  $scope.productsChild = [];
  var id = $stateParams.productId;

  $scope.title = '商品详情';
  $scope.product = {
    title : '',
    id : id,
    skuNumber : '',
    advWords : '',
    price : 50,
    marketPrice : 80,
    singlePrice : 3,
    unitName : '',
    isRound : 1,
    storage : '',
    buyLimit : 0,
    lineId : [0],
    visiable : 1,
    closeUnit : '',
    status : 1
  };
  $scope.rounds = [
    {name: '是', val: 1, checked: 1},
    {name: '否', val: 0, checked : 0}
  ];
  $scope.init = {
    unit :'',
    closeUnit: '',
    skuNumber: '',
    isRound: true,
    productStatus : ''
  };
  // 获取商品品信息
  var getProductInfo = function() {
    req.getdata('product/detail', 'POST', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.log_list = data.log_list;
        $scope.product.skuNumber = data.info.sku_number;
        $scope.product.title = data.info.title;
        $scope.product.status = data.info.status;
        $scope.init.unitId = data.info.unit_id;
        $scope.init.isRound = parseInt(data.info.is_round) ? true : false;
        $scope.init.closeUnitId = data.info.close_unit;
        $scope.product.advWords = data.info.adv_words;
        $scope.product.price = data.info.price;
        $scope.product.lineId = data.info.line_id.split(',');
        $scope.product.visiable = data.info.visiable;
        $scope.product.buyLimit = data.info.buy_limit;
        $scope.product.storage = data.info.storage;
        $scope.product.locationId = data.info.location_id;
        $scope.product.marketPrice = data.info.market_price;
        $scope.product.singlePrice = data.info.single_price;
        $scope.product.default_type = data.info.customer_type;
        $scope.product.default_collect_type = data.info.collect_type;
        $scope.product.opLog = data.info.workflow_log;
        $scope.getSkuInfo(1);      }
    }, {id: id});
  }
  getProductInfo();
  // 获取sku 信息
  $scope.getSkuInfo = function(con) {
    if(parseInt(con) == 0) {
      $scope.product.skuNumber = '';
    }
    if($scope.product.skuNumber != '') {

      $rootScope.is_loading = true;
      req.getdata('product/get_sku_info', 'POST', function(data) {
        $rootScope.is_loading = false ;
        if(parseInt(data.status) === 0) {
          $scope.info = data.info;
          $scope.locations = data.location;
          $scope.allLines = data.line_options;
          $scope.visiables = data.visiable_options;
          $scope.customer_type_options = data.customer_type_options;
          $scope.collect_type_options = data.collect_type_options;
          // 如果当前用户类型存在， 那么设为默认用户类型
          if($scope.product.default_type){
            angular.forEach( $scope.customer_type_options, function(v) {
              if(v.value == $scope.product.default_type) {
                $scope.default_type =v ;
              }
            });
          }else {
            $scope.default_type = data.customer_type_options.length != 0  ?  data.customer_type_options[0] : false;
          }
          //设置采集类型
          if($scope.product.default_collect_type){
            angular.forEach( $scope.collect_type_options, function(v) {
              if(v.value == $scope.product.default_collect_type) {
                $scope.default_collect_type =v;
              }
            });
          }else {
            $scope.default_collect_type = data.collect_type_options.length != 0  ?  data.collect_type_options[0] : false;
          }

          if(id) {
            angular.forEach(data.info.product_status, function(v) {
              if(v.status == $scope.product.status) {
                $scope.init.productStatus = v;
              }
            })
            angular.forEach($scope.locations, function(v) {
              if(v.id == $scope.product.locationId) {
                $scope.init.location = v;
              }
            })
            angular.forEach($scope.visiables, function(v) {
              if(v.id == $scope.product.visiable) {
                $scope.init.visiable = v;
              }
            })
            angular.forEach(data.info.units, function(v) {
              if(v.id == $scope.init.unitId) {
                $scope.init.unit = v;
              }
              if(v.id == $scope.init.closeUnitId) {
                $scope.init.closeUnit = v;
              }
            })

            $scope.lines = data.line_options[$scope.init.location.id];
            angular.forEach($scope.lines, function(v) {
              if($scope.product.lineId.indexOf(v.id) != -1) {
                v['checked'] = true;
              }
            })
          }
          // 默认可见性范围
          if($scope.init.visiable == undefined) {
            $scope.init.visiable = $scope.visiables[0];
          }
          // 初始化线路
        } else {
          daChuDialog.tips({bodyText: data.msg});
        }
      }, {skuNumber : $scope.product.skuNumber})
    }
  }
}]);
