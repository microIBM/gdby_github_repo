'use strict';

angular
.module('hop')
.controller('ProductPriceCtrl', ['$rootScope' , '$scope', 'dialog', 'appConfigure','$cookieStore', '$filter', 'rpc', function($rootScope , $scope, dialog, appConfigure, $cookieStore, $filter, rpc) {

  //超级管理员100，运营10
  var userType = $cookieStore.get('type');
  if(userType == 100) {
    $scope.showSync = true;
  } else if(userType == 10) {
    $scope.showSync = false;
  }

  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 50
  };
  var initPaginationConf = function() {
    $scope.paginationConf.currentPage = 1;
    $scope.paginationConf.itemsPerPage = 50;
  }

  //获取下拉列表数据 城市和一级分类
  $scope.locations = [{
    id : 0,
    name : '全国'
  }];
  $scope.categories = [{
    id : 0,
    name : '全部分类'
  }];
  (function() {
    rpc.load('product/list_price_options').then(function(data) {
      $scope.locations = $scope.locations.concat(data.cities);
      $scope.categories = $scope.categories.concat(data.categories);
      $scope.location = $scope.locations[0];
      $scope.category = $scope.categories[0];
    },
    function(msg) {
      alert(msg);
    });
  }());

  //确认同步
  $scope.sync = function() {
    dialog.tips({
      bodyText: '是否确认同步所有价格更改到线上？',
      actionText: '确认',
      closeText: '取消',
      ok : function() {
        synchronizeChangedPrice();
      }
    });
  }

  //编辑切换
  $scope.title = '商品改价待同步列表';
  $scope.isEdit = false;
  $scope.editName = '编辑';
  $scope.edit = function() {
    if($scope.isEdit == true) {
      $scope.title = '商品改价待同步列表';
      $scope.editName = '编辑';
      $scope.isEdit = false;
      initPaginationConf();
      $scope.products = [];
      getChangedPriceList();
    } else {
      $scope.title = '商品改价列表';
      $scope.editName = '返回';
      $scope.isEdit = true;
      initPaginationConf();
      $scope.products = [];
      getProductList();
    }
  }

  //保存被save的数据
  $scope.save = function(item) {
    var errorMessage = '';
    if(item.price != 0) {
      var validator = validatePrice(item.dest_price);
      if(!validator.status) {
        errorMessage += '普通客户:' + validator.errorMessage;
      }
    } else {
      validator = {status: true};
    }

    if(validator.status) {
      popPriceModal(item);
    } else {
      popErrorModal(errorMessage);
    }
  }

  //价格更改提示框
  var popPriceModal = function(item) {
    var text = '';
    var myPrice = $filter('money')(item.dest_price, 2);

    if(item.price != 0) {
      if(item.dest_price <= item.price/2 || item.dest_price >= item.price * 2 || item.dest_price == item.price) {
        var halfPriceClass = "price-danger";
      }
      text += '<div class="alert alert-warning ' + halfPriceClass + ' ">' + item.title + '<br>普通客户价格 从 ' + item.price + ' 元 改为：'+ myPrice + ' 元; </div>';
    }

    dialog.tips({
      bodyText: text + '是否确认？',
      actionText: '确认',
      closeText: '取消',
      ok: function() {
        //触发单条改价
        updatePrice(item, myPrice);
      }
    });
  }

  //错误提示弹出框
  var popErrorModal = function(errorMessage) {
    dialog.tips({
      bodyText: errorMessage
    });
  }

  //字段校验
  var validatePrice = function(price) {
    var status = false;
    var errorMessage = '';
    if(!price || price == 0) {
      errorMessage += '价格不能为空！请重新填写<br>';
    }
    if(isNaN(price)) {
      errorMessage += '价格不能是非数字！请重新填写<br>';
    }
    if(errorMessage === '') {
      status = true;
    }
    return {
      status: status,
      errorMessage: errorMessage
    }
  }

  //获取改价列表
  var getChangedPriceList = function() {
    var postData = {
      skuNumber    : $scope.key ? $scope.key : '' ,
      categoryId   : $scope.category ? $scope.category.id : '',
      locationId   : $scope.location ? $scope.location.id : '',
      currentPage  : $scope.paginationConf.currentPage,
      itemsPerPage : $scope.paginationConf.itemsPerPage
    }

    rpc.load('/product/list_changed_prices', 'POST', postData).then(function(data) {
      $scope.products = data.list;
      $scope.paginationConf.totalItems = data.total;
    },
    function(msg) {
      alert(msg);
    });
  }

  //获取商品列表
  var getProductList = function() {
    var postData = {
      skuNumber    : $scope.key ? $scope.key : '' ,
      categoryId   : $scope.category ? $scope.category.id : '',
      locationId   : $scope.location ? $scope.location.id : '',
      currentPage  : $scope.paginationConf.currentPage,
      itemsPerPage : $scope.paginationConf.itemsPerPage
    }

    rpc.load('/product/list_prices', 'POST', postData).then(function(data) {
      $scope.products = data.list;
      $scope.paginationConf.totalItems = data.total;
    },
    function(msg) {
      alert(msg);
    });
  }

  //更新单条价格
  var updatePrice = function(item, myPrice) {
    var postData = {
      productId   : item.product_id,
      destPrice   : myPrice
    }

    rpc.load('/product/update_price', 'POST', postData).then(function(data) {
      if (data.status == 0) {
        dialog.tips({
          bodyText: '价格更改成功！'
        });
        getProductList();
      };
    },
    function(msg) {
      alert(msg);
    });
  }

  //批量同步价格
  var synchronizeChangedPrice = function() {
    rpc.load('/product/sync_prices').then(function(data) {
      if (data.status == 0) {
        dialog.tips({
          bodyText: '价格批量同步成功！'
        });
        getChangedPriceList();
      };
    },
    function(msg) {
      alert(msg);
    });
  }

  //监听分页变化
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', function() {
    if($scope.isEdit == true) {
      getProductList();
    } else {
      getChangedPriceList();
    }
  });

  // 查询商品
  $scope.key = '';
  $scope.search = function() {
    if(isNaN($scope.key) && $scope.key != '') {
      dialog.tips({
        bodyText : '货号不能是非数字！请重新填写'
      });
      return;
    }
    if($scope.isEdit) {
      getProductList();
    } else {
      getChangedPriceList();
    }
  }
  // 重置按钮
  $scope.reset = function(){
    $scope.key = '';
    $scope.location = $scope.locations[0];
    $scope.category = $scope.categories[0];
    if($scope.isEdit) {
      getProductList();
    } else {
      getChangedPriceList();
    }
  }
}]);
