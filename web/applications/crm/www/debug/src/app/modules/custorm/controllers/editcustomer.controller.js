'use strict';

angular.module('dachuwang')
  .controller('EditCustomerController',['$scope','$state','$stateParams','$window','rpc', 'daChuDialog', function($scope,$state,$stateParams,$window,rpc, dialog) {
    //数据初始化
    var cus_id = $stateParams.customer_id;
    var former_address;
    $scope.is_uploaded = 0;
    $scope.userGeo = {
      lat : null,
      lng : null
    };
    $scope.isRequesting = false;
    //$scope的函数都绑定在func对象内
    $scope.func = {};
    $scope.urls = null;
    //加载用户数据
    (function(x) {
      var pdata = {id:x};
      rpc.load('customer/edit_input','POST',pdata).then(function(msg) {
        $scope.oDimensions = msg.dimensions;
        //ng-options必须返回ng-repeat的对象
        $scope.dimensions = (function(t) {
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
        // 初始化图片
        $scope.urls = msg.info.pic_urls;
        //初始化客户类型
        $scope.shop_types = msg.shop_type;
        angular.forEach($scope.shop_types, function(v){
          if(v.id == msg.info.shop_type){
            $scope.shop_type = v;
          }
        });
        $scope.estimateds = msg.estimated;
        $scope.greens_meat_estimated = (function(x) {
          var i,len = $scope.estimateds.length;
          for(i=0; i<len; i++) {
            if($scope.estimateds[i].value == x) {
              return $scope.estimateds[i];
            }
          }
        })(msg.info.greens_meat_estimated);
        $scope.rice_grain_estimated = (function(x) {
          var i,len = $scope.estimateds.length;
          for(i=0; i<len; i++) {
            if($scope.estimateds[i].value == x) {
              return $scope.estimateds[i];
            }
          }
        })(msg.info.rice_grain_estimated);
        var lists = ['address','remark'];
        var i;
        for(i=0; i<lists.length; i++) {
          $scope[lists[i]] = msg.info[lists[i]];
        }
        $scope.recieve_name = msg.info.recieve_name ? msg.info.recieve_name : msg.info.name;
        $scope.recieve_mobile = msg.info.recieve_mobile ? msg.info.recieve_mobile : msg.info.mobile;
        //保存目前的地址，防止丢失
        former_address = $scope.address;
        $scope.userGeo = {
          lat : msg.info.lat,
          lng : msg.info.lng
        };
      },function(err) {
        dialog.alert(err);
        $state.go('page.manage');
      });
    })(cus_id);
    var error_info = [
      {name:'shop_type',info:'餐饮类型不能为空'},
      {name:'dimensions',info:'店铺规模不能为空'},
      {name:'recieve_name',info:'收货人姓名不能为空'},
      {name:'recieve_mobile',info:'收货人电话不能为空'},
      {name:'greens_meat_estimated',info:'预估日均采购量不能为空'},
      {name:'rice_grain_estimated',info:'预估日均采购量不能为空'},
      {name:'address',info:'地址不能为空'}
    ];
    function alertError() {
      var i;
      for(i=0; i<error_info.length; i++) {
        if($scope.basic_form[error_info[i].name].$invalid) {
          dialog.alert(error_info[i].info);
          return;
        }
      }
    }
    //提交修改
    $scope.func.create = function() {
      //检测是否有删除用户信息的行为
      if($scope.basic_form.address.$dirty && $scope.address=="") {
        dialog.alert('地址信息不能为空');
        $scope.address = former_address;
        return false;
      }
      if($scope.basic_form.$invalid) {
        alertError();
        return false;
      }
      var postData = {
        id : cus_id,
        shopType : $scope.shop_type.id,
        dimensions : $scope.dimensions.value,
        recieve_name : $scope.recieve_name,
        recieve_mobile : $scope.recieve_mobile,
        greens_meat_estimated : $scope.greens_meat_estimated.value,
        rice_grain_estimated : $scope.rice_grain_estimated.value,
        address : $scope.address,
        remark : $scope.remark
      };
      //如果上传了照片
      if($scope.urls !== null) {
        postData.pic_urls = $scope.urls;
      }
      //重新修改了定位信息
      if($scope.userGeo.lat !== null || $scope.userGeo.lng!==null) {
        postData.is_located = 1;
        postData.lat = $scope.userGeo.lat;
        postData.lng = $scope.userGeo.lng;
      }
      //异步请求
      $scope.isRequesting = true;
      rpc.load('customer/edit','POST',postData).then(function(msg) {
        dialog.alert('修改成功');
        $scope.isRequesting = false;
        $state.go('page.manage');
      },function(err) {
        dialog.alert('修改失败：'+err);
        $scope.isRequesting = false;
      });
    };
    //点击choosePlace之后的回调函数
    $window.geoData.callback = function(data) {
      $scope.userGeo.lat = data.latitude;
      $scope.userGeo.lng = data.longitude;
      $scope.address = data.address;
      $scope.$apply();
    };
    //上传照片的回调
    $window.callback_function.upload = function(urls) {
      $scope.urls = JSON.parse(urls);
      $scope.$apply();
    };

    //修改地址调用安卓接口进行定位
    $scope.func.choosePlace = function() {
      if(!$window.jsInterface || !$window.jsInterface.findRestaurantOnMap) {
        dialog.alert('请使用安卓客户端');
        return;
      } else {
        $window.jsInterface.findRestaurantOnMap();
      }
    };

    //上传照片
    $scope.func.upload = function() {
      if(!$window.jsInterface || !$window.jsInterface.pictureUpload) {
        dialog.alert('无法上传图片,请安装安卓客户端','customer_shop');
      } else {
        $window.jsInterface.pictureUpload('window.callback_function.upload');
      }
    };
  }]);
