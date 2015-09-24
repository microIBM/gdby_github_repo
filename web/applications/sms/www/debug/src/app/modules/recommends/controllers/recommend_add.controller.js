'use strict';
angular
.module('hop')
.controller('RecommendAddCtrl', ['$rootScope' , '$scope', 'req', '$upload', 'daChuLocal', 'dialog' ,function($rootScope , $scope, req, $upload, daChuLocal , dialog) {
  // 增加广告
  $scope.title = '新建首页推荐';
  //-----
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endOpened = $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.endOpened = true;
  };

  // 默认的规格选项值输入框
  $scope.initValues =   {
    name: '添加',
    value: '',
    id: '',
    icon: 'glyphicon-plus',
    cls: 'btn-info',
    clk: 'addProduct'
  };
  // 规格值输入框数组初始化
  $scope.products = [$scope.initValues];

  // 添加
  $scope.addProduct = function($index, v) {
    if(v == undefined) {
      v = '';
    }
    var next = {
      name: '删除',
      value: v,
      id: '',
      icon: 'glyphicon-minus',
      cls: 'btn-danger',
      clk: 'remove'
    };
    if($scope.products.length < 5) {
      $scope.products.push(next);
    } else {
      alert('首页推荐必须为5个商品');
    }
  };

  // 删除
  $scope.remove = function(item) {
    var index = $scope.products.indexOf(item);
    $scope.products.splice(index, 1);
  };
  //-------
  $scope.showProduct = function(item) {
    var index = $scope.products.indexOf(item);
    var postData = {
      locationId : $scope.location.id,
      customerType : $scope.defaultType.value,
      searchVal : item.value
    };
    req.getdata('product/manage', 'POST', function(data) {
      item.products = data.list;
    }, postData);
  }
  $scope.selectProduct = function(product, item) {
    var index = $scope.products.indexOf(item);
    $scope.products[index].id = product.id;
    $scope.products[index].value = product.title + '|' + product.sku_number + '|' + product.price +'/' + product.unit;
    item.products = '';
  }
  //--------
  var setDefault = function() {
    req.getdata('recommend/input_options', 'GET', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.locationInfo = data.list.locations;
        $scope.customerTypeOptions = data.list.customer_type_options;
        $scope.defaultType = $scope.customerTypeOptions[0];
        $scope.siteSrcs = data.list.sites;
        $scope.site = $scope.siteSrcs[0];
        $scope.location = $scope.locationInfo[0];
      }
    });
  }
  setDefault();
    // 保存
  $scope.add = function(addForm) {
    //必填项  才发请求
    if(addForm.$invalid ){
      dialog.tips({
        bodyText : '请填写完整信息！'
      })
      return ;
    }
    var postData = {
      site_id : 1,
      location_id : 1,
      products : [],
      startTime : '',
      customerType : $scope.defaultType.value,
      endTime : '',
      title : ''
    };
    postData.site_id = $scope.site.id;
    postData.location_id = $scope.location.id;
    postData.title = $scope.name;
    postData.startTime = Date.parse($scope.startTime)/1000;
    postData.endTime = Date.parse($scope.endTime)/1000;
    angular.forEach($scope.products, function(v) {
      if(parseInt(v.id) > 0) {
        postData.products.push(v.id);
      }
    });
    if(postData.products.length !== 5) {
      alert('商品必须为五个');
      return false;
    }
    $rootScope.is_loading = true ;
    req.getdata('recommend/save', 'POST', function(data) {
      $rootScope.is_loading = false;
      alert(data.msg);
      req.redirect('/recommend');
    }, postData);
  }
}]);
