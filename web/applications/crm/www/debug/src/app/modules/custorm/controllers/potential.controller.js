'use strict';

angular
  .module('dachuwang')
  .controller('PotentialController',['$scope', '$stateParams', '$cookieStore','$window', 'rpc', 'geo', 'daChuDialog', 'urlHistoryService', function($scope, $stateParams, $cookieStore, $window, rpc, geo, dialog, urlHistoryService) {
  // 下拉列表框相关数据
  $scope.addr = {
    province: '',
  };
  
  var setTop = function(top) {
    var tops = [];
    angular.forEach(top, function(s) {
      // 大国
      if($scope.site.id == 1 && s.id != 2) {
        tops.push(s);
      }  else if($scope.site.id == 2 && s.id == 2) {
        tops.push(s);
      }
    });
    $scope.tops = tops;
  }
  $scope.getSite = function() {
    if($scope.site === undefined) {
      return;
    }
    setTop($scope.shopType.top);
  }
  // 初始化，获取客户添加相关数据
  var init = function() {
    var postData = {};
    rpc.load('customer/create_input', 'GET').then(function(msg) {
      $scope.provinces = msg.provinces;
      $scope.allLines = msg.lines;
      $scope.sites = msg.sites;
      $scope.site = $scope.sites[0];
      // 商铺类别数据
      $scope.shopType = msg.shop_type;
      setTop($scope.shopType.top);
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });
  }
 $scope.getShop = function() {
    if($scope.shop === undefined) {
      return;
    }
    var shopChilds = [];
    angular.forEach($scope.shopType.child, function(v) {
      if(parseInt(v.upid) == parseInt($scope.shop.id)) {
        shopChilds.push(v);
      }
    });
    $scope.shopChilds = shopChilds;
  }
  // 获取路线
  $scope.getLines = function() {
    var provinceId = 0;
    if($scope.info.province) {
      provinceId = $scope.info.province.id;
    }
    var lines = [];
    angular.forEach($scope.allLines, function(line) {
      if(provinceId == line.location_id){
        lines.push(line);
      }
    });
    $scope.lines = lines;
  };
  var error_info = [
    {name:'province',info:'请选择省市信息'},
    {name:'line',info:'请选择配送线路'},
    {name:'address',info:'请填写详细地址'},
    {name:'shop_name',info:'请填写商铺名'}
  ];
  function alertError() {
    var i,obj;
    for(i=0; i<error_info.length; i++) {
      obj = error_info[i];
      if($scope.basic_form[obj.name].$invalid) {
        dialog.alert(obj.info);
        return;
      }
    }
  }

  // 添加客户信息
  $scope.create = function() {
    if($scope.basic_form.$invalid) {
      alertError();
      return false;
    }
    var shopType =0 , isLink = 0;
    if($scope.shopChild == undefined )
      {
        if($scope.shop !== undefined) {
          shopType = $scope.shop.id || 0;
        }
      } else {
        shopType = $scope.shopChild.id;
        isLink = $scope.shopChild.is_link;
      }
    var postData = {
      id: $scope.site.id || 0,
      name: $scope.info.name,
      shopType: shopType,
      isLink: isLink,
      mobile: $scope.info.mobile,
      provinceId : ($scope.info.province? $scope.info.province.id : null),
      lineId : ($scope.info.line ? $scope.info.line.id : null),
      address : $scope.info.address,
      shopName : $scope.info.shop_name,
      remark : $scope.info.remark
    };
     rpc.load('potential_customer/create', 'POST', postData).then(function(msg) {
        alert('记录成功');
        //0代表潜在客户
        urlHistoryService.push(0);
        rpc.redirect('/crm');
     },
    //failed
    function(msg) {
      alert(msg);
    });
  };

  // 加载用户信息
  init();


 }]);
