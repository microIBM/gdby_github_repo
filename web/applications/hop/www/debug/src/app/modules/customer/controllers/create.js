'use strict'
angular.module('hop').controller('CustomerCreateCtrl',['$location', 'dialog',  'req', '$scope',  function($location, dialog, req, $scope){
  // 下拉列表框相关数据
  $scope.addr = {
    province: '',
  };

  // 初始化，获取客户添加相关数据
  var init = function() {
    var postData = {};
    req.getdata('customer/create_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.info = data.info;
        $scope.provinces = data.province_list;
      }
    }, postData);
  }

  // 获取下级地区
  $scope.get_child = function(childs, parent) {
    /*req.getdata('location/children', 'POST',function(data) {
      $scope[childs] = data.children;
    },{id:$scope.addr[parent].id});*/
  };

  // 添加客户信息
  $scope.create = function() {
    var postData = {
      id: $scope.id,
      siteId: $scope.info.site_id,
      name: $scope.info.name,
      mobile: $scope.info.mobile,
      provinceId : $scope.addr.province.id,
      address : $scope.info.address,
      shopName : $scope.info.shop_name,
      remark : $scope.info.remark,
    };

    req.getdata('customer/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加客户资料成功！'});
        req.redirect('/customer/list');
      } else {
        dialog.tips({bodyText:'添加客户资料失败。'});
      }
    }, postData);
  };

  // 加载用户信息
  init();
}]);
