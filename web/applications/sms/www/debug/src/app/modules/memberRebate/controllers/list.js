'use strict'
angular
  .module('hop')
  .controller('MemberRebateCtrl',['$location', 'dialog',  'req', '$scope', function($location, dialog, req, $scope){
  $scope.status = 'all';
  $scope.locations = [{
    id : 0,
    name : '全国'
  }];

  // 初始化
  var init = function() {
    // req.getdata('customer/line_list', 'POST', function(data) {
    req.getdata('member_rebate/list_options', 'POST', function(data) {
      if(data.status == 0) {
        $scope.locations = $scope.locations.concat(data.cities);
        $scope.location = $scope.locations[0];
      }
    });
  }
  init();

  // 初始化搜索类别
  $scope.keyList = [
    {'name': '手机号', 'val': 'mobile'},
    {'name': '姓名', 'val': 'name'},
    {'name': '店铺名称', 'val': 'shop_name'},
  ];
  $scope.searchKey = $scope.keyList[0];

  // 重新获取分页数据
  var getList = function() {

      var provinceId = $scope.location ? $scope.location.id : 0;
      var postData = {
          provinceId: provinceId,
          key: $scope.key,
          currentPage: $scope.paginationConf.currentPage,
          itemsPerPage: $scope.paginationConf.itemsPerPage,
      };

      if($scope.customerType!=undefined)
          postData.customer_type=$scope.customerType.value;
      req.getdata('member_rebate/lists', 'POST', function(data) {
          if(data.status == 0) {
              // 变更分页的总数
              $scope.paginationConf.totalItems = data.total;
              // 变更数据条目
              $scope.list = data.list;
          }
      }, postData);
  };
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);

  // 按照条件筛选
  $scope.search = function(){
    getList();
  };
  // 重置搜索条件
  $scope.reset = function() {
    $scope.key = '';
    $scope.location = $scope.locations[0];
    getList();
  };
}]);
