'use strict';
angular
.module('hop')
.controller('CustomerCouponAddCtrl', ['$scope', '$stateParams', 'req', '$upload', 'daChuLocal', 'dialog' ,function($scope, $stateParams, req, $upload, daChuLocal , dialog) {
  // 增加广告
  $scope.title = '发放券码';
  //--------
  var setDefault = function() {
    req.getdata('coupon/info', 'POST', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.couponName = data.info.title;
        $scope.locationId = data.info.location_id;
        $scope.siteId = data.info.site_id;
        $scope.siteName = data.info.site_cn;
        $scope.couponNums = 1;
        $scope.locationName = data.info.location_cn;
        $scope.visiables = data.info.visiables;
        $scope.visiable = data.info.visiables[0];
      }
    }, {id: $stateParams.couponId});
  }
  setDefault();
// 默认的规格选项值输入框
  $scope.initValues =   {
    name: '添加',
    value: '',
    id: '',
    icon: 'glyphicon-plus',
    cls: 'btn-info',
    clk: 'addUser'
  };
  // 规格值输入框数组初始化
  $scope.users = [$scope.initValues];
  // 删除
  $scope.remove = function(item) {
    var index = $scope.users.indexOf(item);
    $scope.users.splice(index, 1);
  };

  // 添加
  $scope.addUser = function($index, v) {
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
    $scope.users.push(next);
  };

  //-------
  $scope.showUsers= function(item) {
    var index = $scope.users.indexOf(item);
    var postData = {
      locationId : $scope.locationId,
      siteId : $scope.siteId,
      searchVal : item.value
    };
    req.getdata('customer/manage', 'POST', function(data) {
      item.users = data.list;
    }, postData);
  }
  $scope.selectUser = function(user, item) {
    var index = $scope.users.indexOf(item);
    $scope.users[index].id = user.id;
    $scope.users[index].value = user.mobile + '|' + user.shop_name;
    item.users = '';
  }

 // 保存
  $scope.add = function(addForm) {
    if(addForm.$invalid){
       dialog.tips({
         bodyText : '请填写完整信息！'
       })
       return ;
    }
    var postData = {
      couponId : $stateParams.couponId,
      mobiles : [],
      visiable : $scope.visiable.id
    };
   angular.forEach($scope.users, function(v) {
      if(v.value != '') {
        postData.mobiles.push(v.value);
      }
    });
   req.getdata('customer_coupon/create', 'POST', function(data) {
      alert(data.msg);
      if(parseInt(data.status) === 0) {
        req.redirect('/customer/coupon');
      }
    }, postData);
  }
  }]);
