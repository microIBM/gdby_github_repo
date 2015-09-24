'use strict';
angular
.module('hop')
// 添加货物
.controller('ProductAddCtrl',  [ '$rootScope' ,'$scope', 'req', '$location', '$cookieStore', 'dialog', 'daChuLocal', 'daChuTimer', function($rootScope , $scope, req, $location, $cookieStore, daChuDialog, daChuLocal, daChuTimer) {
  $scope.productsChild = [];
  $scope.title = '添加货物';
  // 添加货物需要提交的数据model
  $scope.product = {
    title : '',
    skuNumber : '',
    advWords : '',
    price : 50,
    marketPrice : 80,
    singlePrice : 3,
    unitName : '',
    isRound : 0,
    closeUnit : '',
    storage : -1,
    lineId : 0,
    visiable : 1,
    // 0 全部可见 1普通可见 2 ka可见
    customerVisiable : 0,
    buyLimit : 0,
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
    isRound: false,
    productStatus : ''
  };
 // 获取sku 信息
  $scope.getSkuInfo = function(con) {
    if(parseInt(con) == 0) {
      $scope.init.skuNumber = '';
      $scope.info = '';
    }
    if($scope.init.skuNumber != '') {
      $scope.product.skuNumber = $scope.init.skuNumber;

      $rootScope.is_loading = true ;
      req.getdata('product/get_sku_info', 'POST', function(data) {
        $rootScope.is_loading = false ;
        if(parseInt(data.status) === 0) {
          $scope.info = data.info;
          $scope.locations = data.location;
          $scope.visiables = data.visiable_options;
          $scope.init.location = data.location[0];
          $scope.init.visiable = data.visiable_options[0];
          $scope.init.customerVisiable = data.customer_visiable_options[0];
          $scope.customerVisiableOptions = data.customer_visiable_options;
          $scope.lines = data.line_options[$scope.init.location.id];
          $scope.allLines = data.line_options;
          $scope.init.line = $scope.lines[0];
          $scope.init.unit = data.info.units[1];
          $scope.init.closeUnit = data.info.units[0];
          $scope.init.productStatus = data.info.product_status[1];

          $scope.customer_type_options = data.customer_type_options;
          $scope.collect_type_options = data.collect_type_options;

          // 如果当前用户类型存在， 那么设为默认用户类型
          if($scope.default_type){
            angular.forEach( $scope.customer_type_options, function(v) {
              if(v.value == $scope.default_type.value) {
                $scope.default_type =v ;
              }
            });
          }else {
            $scope.default_type = data.customer_type_options && data.customer_type_options.length != 0  ?  data.customer_type_options[0] : false;
          }
          //设置默认的采集类型
          if($scope.default_collect_type) {
            angular.forEach( $scope.collect_type_options ,function( $value ) {
              if ( $value.value == $scope.default_collect_type.value ) {
                $scope.default_collect_type = $value;
              }
            });
          } else {
            $scope.default_collect_type = data.collect_type_options && data.collect_type_options.length != 0 ? data.collect_type_options[0] : false;
          }
        } else {
          daChuDialog.tips({bodyText: data.msg});
        }
      }, {skuNumber : $scope.product.skuNumber});
    }
  }
  $scope.select_type = function(type){
    $scope.default_type = type;
  }
  $scope.select_collect_type = function(type) {
    $scope.default_collect_type = type;
  }
  // 添加确认
  $scope.add = function(id) {
    $scope.product.unitName = $scope.init.unit.name;
    $scope.product.closeUnit = $scope.init.closeUnit.name;
    $scope.product.status = $scope.init.productStatus.status;
    $scope.product.locationId = $scope.init.location.id;
    $scope.product.visiable = $scope.init.visiable.id;
    $scope.product.customerVisiable = $scope.init.customerVisiable.value;
     // 如果选择了用户类型 ，就传过去， 默认为1 ＝普通用户
    $scope.product.customerType = $scope.default_type ?  $scope.default_type.value : 1
    //预采类型，现采类型 默认1为预采类型,2为现采类型
    $scope.product.collectType = $scope.default_collect_type ? $scope.default_collect_type.value : 1;
    var line_ids = [];
    if($scope.product.visiable == 2) {
      angular.forEach($scope.lines, function(v) {
        if(v.checked != undefined && v.checked === true) {
          line_ids.push(v.id);
        }
      })
    } else {
      line_ids = [0];
    }
    if($scope.product.title == '') {
      alert('标题为空');
      return
    }
    $scope.product.lineId = line_ids;
    if($scope.init.isRound) {
      $scope.product.isRound = 1;
    } else {
      $scope.product.isRound = 0;
    }
    $rootScope.is_loading = true ;
    // 货物保存
    req.getdata('/product/save', 'POST', function(data) {
      $rootScope.is_loading = false ;
      if(parseInt(data.status) === 0) {
        daChuDialog.tips({bodyText:"保存成功！"});
        req.redirect('/product');
      } else {
        daChuDialog.tips({bodyText:"保存失败，请稍后重试。"});
      }
    }, $scope.product);
    };
    // 切换城市
    $scope.selectCity = function() {
      $scope.lines = $scope.allLines[$scope.init.location.id];
    }

}]);
