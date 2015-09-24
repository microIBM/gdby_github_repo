'use strict';

angular
.module('dachuwang')
.controller('custormCtrl',['$scope','$modal', '$state', '$stateParams', '$cookieStore', '$window', '$interval', 'rpc', 'daChuDialog', 'geo', 'urlHistoryService', 'daChuLocal','Analysis', function($scope, $modal, $state, $stateParams, $cookieStore, $window, $interval, rpc, dialog, geo, urlHistoryService, daChuLocal, Analysis) {
  //根据统计需求，当表单第一次被填写记为录入的开始时间
  var beginTime = null;
  $scope.$watch('basic_form.$dirty', function(newValue) {
    if(beginTime || !newValue) {
      return;
    }
    beginTime = new Date().getTime();
  });
  $scope.urls = null;
  $window.callback_function.upload = function(urls) {
    $scope.urls = JSON.parse(urls);
    $scope.$apply();
  }
  $scope.upload = function() {
    if(!$window.jsInterface || !$window.jsInterface.pictureUpload) {
      dialog.alert('此版本无法上传图片,请安装安卓客户端');
    } else {
      $window.jsInterface.pictureUpload('window.callback_function.upload');
    }
  }
  // 下拉列表框相关数据
  $scope.addr = {
    province: '',
  };

  $scope.direction = null;
  $scope.addPotential = function() {
    $state.go('page.potential');
  }

  $scope.userGeo = {
    lat : 0,
    lng : 0,
    addr : ""
  };
  $scope.suborderInfo = function(){
    templateUrl:'suborderInfo.html'
    $modal.open({
      templateUrl: 'suborderInfo.html',
    });
  }

  // 是否忽略GEO信息
  var skipGeo = false;

  $window.geoData.callback = function(data) {
    $scope.userGeo.lat = data.latitude;
    $scope.userGeo.lng = data.longitude;
    $scope.userGeo.addr = data.address;
    $scope.$apply();
  }

  var getProvince = function() {
    $scope.addr.province = '';
    $scope.lines = '';
  }
  var getLines = function() {
    var provinceId = 0;
    if($scope.addr.province) {
      provinceId = $scope.addr.province.id;
    }
    var lines = [];
    angular.forEach($scope.allLines, function(line) {
      if(provinceId == line.location_id){
        lines.push(line);
      }
    });
    $scope.lines = lines;
    // var last_line = daChuLocal.get('last_line');
    /*var last_line = '';
      if(!last_line) {
      $scope.addr.line = '';
      } else {
      for(var i=0; i<$scope.lines.length; i++) {
      if($scope.lines[i].id == last_line.id) {
      $scope.addr.line = $scope.lines[i];
      break;
      }
      }
      }*/
  };

  //记录用户上次选择的线路
  $scope.storeLine = function() {
    var obj = {id : $scope.addr.line.id};
    daChuLocal.set('last_line', obj);
  }

  $scope.show_ka = function() {
    if($scope.customerType && $scope.customerType.name === 'KA客户' && parseInt($scope.customerType.value) === 2) {
      $scope.show_ka_option = true;
    } else {
      $scope.show_ka_option = false;
    }
  }

  $scope.show_more_account_option = function() {
    if(!$scope.account_type) {
      $scope.show_account_son = false;
      return;
    }
    if(parseInt($scope.account_type.value) !== 1) {
      $scope.show_account_son = true;
    } else {
      $scope.show_account_son = false;
    }
  }

  // 初始化，获取客户添加相关数据
  var init = function() {
    var postData = {};
    rpc.load('customer/create_input', 'GET').then(function(msg) {
      $scope.provinces = msg.provinces;
      $scope.allLines = msg.lines;
      $scope.dOptions = msg.directions;
      $scope.oDimensions = msg.dimensions;
      // 锁定地区
      var cityId = daChuLocal.get('city_id');
      if(!cityId){
        dialog.alert('登录超时，请重新登录');
        rpc.redirect('user/login');
        return;
      }
      angular.forEach($scope.provinces, function(v){
        if(v.id == cityId){
          $scope.addr.province = v;
        }
      });
      // 商铺类别数据
      $scope.shopTypes = msg.shop_type;
      $scope.types = msg.types;
      $scope.account_types = msg.account_types;
      $scope.estimateds = msg.estimated;
      getLines();
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });
  }
  $scope.getProvince = function() {
    getProvince();
  };
  // 获取路线
  $scope.getLines = function(){
    getLines();
  };
  $scope.testnum = 10;
  $scope.choosePlace = function() {
    if(!$window.jsInterface || !$window.jsInterface.findRestaurantOnMap) {
      var geoinfo = geo.info();
      geoinfo.then(function(position) {
        $scope.userGeo.lat = position.lat;
        $scope.userGeo.lng = position.lng;
        dialog.alert('客户地址定位成功！');
      }, function(err) {
        dialog.alert('获取位置错误, ' + err);
        skipGeo = true;
      });
    } else {
      $window.jsInterface.findRestaurantOnMap();
    }
  }

  $scope.bindProperty = function(property, value) {
    $scope[property] = value;
  }

  $scope.get_cycle = function() {
    if(!$scope.billing_cycle) {
      return;
    }
    var temp = $scope.billing_cycle.value;
    $scope.m_check_dates = $scope.check_dates[temp];
    $scope.m_invoice_dates = $scope.invoice_dates[temp];
    $scope.m_pay_dates = $scope.pay_dates[temp];
  }

  var error_info = [
    {name:'customerType',info:'请选择客户类型'},
    {name:'accountType',info:'请选择子母账号类型'},
    {name:'shop',info:'请选择餐饮类别'},
    {name:'dimensions',info:'请选择店铺规模'},
    {name:'estimateVegetable',info:'请选择预估日均果蔬采购量'},
    {name:'estimateRice',info:'请选择预估日均米面粮油采购量'},
    {name:'mobile',info:'请填写客户正确手机号'},
    {name:'username',info:'请输入客户姓名'},
    {name:'recievemobile',info:'请填写收货人联系方式'},
    {name:'recievename',info:'请输入收货人姓名'},
    {name:'province',info:'请选择省市信息'},
    {name:'line',info:'请选择配送线路'},
    {name:'address',info:'请填写详细地址'},
    {name:'direction',info:'请选择商家方位'},
    {name:'shop_name',info:'请填写商铺名'},
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

  function isAndroid() {
    if($window.jsInterface && $window.jsInterface.findRestaurantOnMap) {
      return true;
    }
    return false;
  }

  function getObjProperty(obj, property) {
    if(!obj) {
      return null;
    }
    if(!property) {
      return obj;
    }
    return obj[property] ? obj[property] : null;
  }

  // 添加客户信息
  $scope.create = function() {
    if($scope.basic_form.$invalid) {
        alertError();
        return false;
    }
    if(parseInt($scope.account_type.value)!==1 && !$scope.parent_mobile) {
      dialog.alert('请填写关联母账号');
      return;
    }
    var shopType =0 , isLink = 0;
    if($scope.shopChild == undefined ){
      shopType = $scope.shop.id;
    } else {
      shopType = $scope.shopChild;
      isLink = $scope.shopChild.is_link;
    }

    // 测试数据
    /*
       $scope.userGeo = {};
       $scope.userGeo.lat = '119.2838383';
       $scope.userGeo.lng = '39.188398';
       $scope.userGeo.addr = '模拟测试地址';
       $scope.urls = ["http://img.dachuwang.com/shop/6ead1c0e70743538cd1e53b7c23e90aa.jpg", "http://img.dachuwang.com/shop/c95ebb443803c8840ce270c95213cba0.jpg"];
       */
    if(!skipGeo && (!$scope.userGeo.lat || !$scope.userGeo.lng) ) {
      dialog.alert('请定位此用户位置信息，再提交！');
      return;
    }
    if($scope.urls === null) {
      dialog.alert('请上传至少一张商家门店图片');
      return;
    }
    var dimensions = $scope.info.dimensions || {value: ''},
    direction = $scope.direction || {value: ''};

    var postData = {
      name: $scope.info.name,
      shopType: shopType,
      isLink: isLink,
      dimensions : dimensions.value,
      mobile: $scope.info.mobile,
      provinceId : $scope.addr.province.id,
      lineId: $scope.addr.line.id,
      address : $scope.userGeo.addr,
      lat : $scope.userGeo.lat,
      lng : $scope.userGeo.lng,
      shopName : $scope.info.shop_name,
      direction : direction.value,
      customerType : $scope.customerType.value,
      remark : $scope.info.remark,
      pic_urls : $scope.urls,
      is_located : isAndroid()===true ? 1:0,
      greens_meat_estimated : $scope.info.estimateVegetable.value,
      rice_grain_estimated : $scope.info.estimateRice.value,
      account_type : $scope.account_type.value,
      recieve_mobile : $scope.info.recievemobile,
      recieve_name : $scope.info.recievename
    };
    //如果是子账号
    if($scope.parent_mobile) {
      postData.parent_mobile = $scope.parent_mobile;
    }

    $scope.isRequesting = true;
    //行为分析
    Analysis.send('录入潜在客户',null,new Date().getTime()-beginTime);
    rpc.load('potential_customer/create', 'POST', postData).then(function(msg) {
      dialog.alert('恭喜，潜在客户添加成功！请在客户管理中开通和编辑客户');
      $scope.isRequesting = false;
      $state.go('page.manage');
      return false;
    },
    //failed
    function(msg) {
      dialog.alert('潜在客户添加失败，失败原因:' + msg);
      $scope.isRequesting = false;
    });
  };
  // 加载用户信息
  init();
  $scope.getRecieveInfo = function() {
    Analysis.send('与客户信息一致');
    $scope.info.recievename = $scope.info.name;
    $scope.info.recievemobile = $scope.info.mobile;
  }
}]);
