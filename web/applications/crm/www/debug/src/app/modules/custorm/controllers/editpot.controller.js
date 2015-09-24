'use strict';

angular
.module('dachuwang')
.controller('EditpotController',['$scope','$modal', '$stateParams', '$cookieStore', '$window', '$state', 'rpc', 'daChuDialog','geo', 'daChuLocal', function($scope, $modal,$stateParams, $cookieStore, $window, $state, rpc, dialog, geo, daChuLocal) {
  $scope.urls = null;
  $window.callback_function.upload = function(urls) {
    $scope.urls = JSON.parse(urls);
  }
  $scope.suborderInfo = function(){
    templateUrl:'suborderInfo.html'        
    $modal.open({
      templateUrl: 'suborderInfo.html',
    });
  }
  $scope.upload = function() {
    if(!$window.jsInterface || !$window.jsInterface.pictureUpload) {
      dialog.alert('此版本无法上传图片,请安装安卓客户端','customer_shop');
    } else {
      $window.jsInterface.pictureUpload('window.callback_function.upload');
    }
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
    {name:'accountType',info:'请选择字母账号类型'},
    {name:'shop',info:'请选择商家类别'},
    {name:'dimensions',info:'请选择店铺规模'},
    {name:'estimateVegetable',info:'请选择预估日均果蔬采购量'},
    {name:'estimateRice',info:'请选择预估日均米面粮油采购量'},
    {name:'mobile',info:'请输入客户正确手机号'},
    {name:'username',info:'请输入客户姓名'},
    {name:'recievemobile',info:'请填写收货人联系方式'},
    {name:'recievename',info:'请输入收货人姓名'},
    {name:'province',info:'请选择省市信息'},
    {name:'line',info:'请选择配送线路'},
    {name:'address',info:'请填写详细地址'},
    {name:'direction',info:'请选择商家方位'},
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

  function getObjProperty(obj, property) {
    if(!obj) {
      return null;
    }
    if(!property) {
      return obj;
    }
    return obj[property] ? obj[property] : null;
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

  function getOptionsObj(target_arr, obj, key) {
    var i,len;
    if(!key) {
      key = 'value';
    }
    if(!target_arr) {
      return null;
    }
    len = target_arr.length;
    for(i=0; i<len; i++) {
      if(target_arr[i][key] == obj) {
        return target_arr[i];
      }
    }
  }

  var init = function() {
    var postData = {};
    if($stateParams.potid) {
      postData.id = $stateParams.potid;
    }
    rpc.load('potential_customer/edit_input', 'POST', postData).then(function(msg) {
      $scope.provinces = msg.provinces;
      $scope.allLines = msg.lines;
      $scope.dOptions = msg.directions;
      $scope.oDimensions = msg.dimensions;
      $scope.types = msg.types;
      $scope.is_uploaded = parseInt(msg.info.is_uploaded);
      //初始化餐饮类别下拉框
      $scope.shopTypes = msg.shop_type;
      //展示已选的餐饮类别
      $scope.shop = (function(t){
        var type = parseInt(t);
        var i,len = $scope.shopTypes.length;
        for(i=0; i<len; i++) {
          if($scope.shopTypes[i].id === type) {
            return $scope.shopTypes[i];
          }
        }
        return null;
      })(msg.info.shop_type);
      $scope.info = msg.info;
      $scope.userGeo = {
        lng: msg.info.lng,
        lat: msg.info.lat
      };
      //初始化规模
      $scope.info.dimensions = (function(t) {
        if(!t) {
          return null;
        }
        var i,len = $scope.oDimensions.length;
        for(i=0; i<len; i++) {
          if(t === $scope.oDimensions[i].value){
            return $scope.oDimensions[i];
          }
        }
        return null;
      })(msg.info.dimensions);
      //初始化地址信息
      $scope.info.province = (function(pid) {
        var i,len = $scope.provinces.length;
        for(i=0; i<len; i++) {
          if($scope.provinces[i].id == pid) {
            return $scope.provinces[i];
          }
        }
      })(parseInt(msg.info.province_id));
      //初始化配送线路
      $scope.getLines();
      $scope.info.line = (function(lid) {
        var i,len = $scope.lines.length;
        for(i=0; i<len; i++) {
          if($scope.lines[i].id == lid) {
            return $scope.lines[i];
          }
        }
      })(parseInt(msg.info.line_id));
      //初始化方位
      $scope.info.direction = (function(dire) {
        var i,len = $scope.dOptions.length;
        if(!dire) {
          return null;
        }
        for(i=0; i<len; i++) {
          if($scope.dOptions[i].value == dire) {
            return $scope.dOptions[i];
          }
        }
      })(msg.info.direction);
      // 初始化图片
      $scope.urls = msg.info.pic_urls;
      //初始化客户类型
      angular.forEach($scope.types, function(v){
        if(v.value == msg.info.customer_type){
          $scope.customerType = v;
        }
      });
      // 锁定系统和地区
      var cityId = daChuLocal.get('city_id');
      if(!cityId){
        dialog.alert('登录超时，请重新登录');
        rpc.redirect('user/login');
        return;
      }
      angular.forEach($scope.provinces, function(v){
        if(v.id == cityId){
          $scope.info.province = v;
        }
      });
      $scope.getLines();
      $scope.estimateds = msg.estimated;
      $scope.info.estimateVegetable = (function(value) {
        var i,len;
        len = $scope.estimateds.length;
        for(i=0; i<len; i++) {
          if($scope.estimateds[i].value == value) {
            return $scope.estimateds[i];
          }
        }
      })(msg.info.greens_meat_estimated);
      $scope.info.estimateRice = (function(value) {
        var i,len;
        len = $scope.estimateds.length;
        for(i=0; i<len; i++) {
          if($scope.estimateds[i].value == value) {
            return $scope.estimateds[i];
          }
        }
      })(msg.info.rice_grain_estimated);
      $scope.account_types = msg.account_types;
      $scope.account_type = (function(value) {
        value = parseInt(value);
        var i,len;
        len = $scope.account_types.length;
        for(i=0; i<len; i++) {
          if($scope.account_types[i].value == value) {
            return $scope.account_types[i];
          }
        }
      })(msg.info.account_type);
      //子账号
      if($scope.account_type && parseInt($scope.account_type.value) !== 1) {
        $scope.parent_mobile = msg.info.parent_mobile;
        $scope.show_more_account_option();
      }
      $scope.info.recievename = msg.info.recieve_name;
      $scope.info.recievemobile = msg.info.recieve_mobile;
    },
    //failed
    function(msg) {
      $scope.error = {cls:'alert alert-danger', message : msg};
    });
  }
  var skipGeo = false;

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
    shopType = $scope.shop.id || 0;
    if( !skipGeo && (!$scope.userGeo.lat || !$scope.userGeo.lng) ) {
      dialog.alert('请定位此用户位置信息，再提交！');
      return;
    }
    if($scope.urls === null && $scope.is_uploaded===0) {
      dialog.alert('请上传至少一张商家门店图片');
      return;
    }
    var dimensions = $scope.info.dimensions || {value: ''},
    direction = $scope.info.direction || {value: ''};
    var postData = {
      id : $stateParams.potid,
      name: $scope.info.name,
      shopType: shopType,
      isLink: isLink,
      dimensions : dimensions.value,
      mobile: $scope.info.mobile,
      provinceId : $scope.info.province.id,
      lineId : $scope.info.line.id,
      lat : $scope.userGeo.lat,
      lng : $scope.userGeo.lng,
      address : $scope.info.address,
      direction : direction.value,
      customerType : $scope.customerType.value,
      shopName : $scope.info.shop_name,
      remark : $scope.info.remark,
      is_located : (function() {
        if($scope.userGeo.lat && $scope.userGeo.lng || $scope.info.is_located==1) {
          return 1;
        }
        return 0;
      })(),
      greens_meat_estimated : $scope.info.estimateVegetable.value,
      rice_grain_estimated : $scope.info.estimateRice.value,
      account_type : $scope.account_type.value,
      recieve_mobile : $scope.info.recievemobile,
      recieve_name : $scope.info.recievename
    };
    if($scope.urls !== null) {
      postData.pic_urls = $scope.urls;
    }
    //如果是子账号
    if($scope.parent_mobile) {
      postData.parent_mobile = $scope.parent_mobile;
    }
    $scope.isRequesting = true;
    rpc.load('potential_customer/edit', 'POST', postData).then(function(msg) {
      dialog.alert('修改成功');
      $scope.isRequesting = false;
      $state.go('page.manage');
    },
    //failed
    function(msg) {
      dialog.alert(msg);
      $scope.isRequesting = false;
    });
  };
  $window.geoData.callback = function(data) {
    $scope.userGeo.lat = data.latitude;
    $scope.userGeo.lng = data.longitude;
    $scope.info.address = data.address;
    $scope.$apply();
  }
  $scope.choosePlace = function() {
    if(!$window.jsInterface || !$window.jsInterface.findRestaurantOnMap) {
      var geoinfo = geo.info();
      geoinfo.then(function(position) {
        $scope.userGeo = {};
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
  // 加载用户信息
  init();
  $scope.getRecieveInfo = function() {
    $scope.info.recievename = $scope.info.name;
    $scope.info.recievemobile = $scope.info.mobile;
  }

}]);
