'use strict';

angular
  .module('hop')
  .controller('CustomerListTransferUserCtrl',['$location', 'dialog',  'req', '$scope', '$state', '$stateParams', function($location, dialog, req, $scope, $state, $stateParams){
  $scope.status = 'all';
  $scope.cids = $stateParams.cids;

  // 初始化搜索类别
  $scope.keyList = [
    {'name': '手机号', 'val': 'mobile'},
    {'name': '姓名', 'val': 'name'},
  ];
  $scope.bdRoleList = [
    {'name': '请选择销售角色', 'val': 'sale'},
    {'name': 'BD', 'val': 'BD'},
    {'name': 'AM', 'val': 'AM'},
  ];
  $scope.roleType = $scope.bdRoleList[2];
  $scope.searchKey = $scope.keyList[0];

  // 重新获取分页数据
  var getList = function() {
    var searchKey = $scope.searchKey ? $scope.searchKey.val : '';
    var postData = {
      roleType: $scope.roleType.val,
      searchKey: searchKey,
      searchValue: $scope.searchValue,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('user/lists', 'POST', function(data) {
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
    itemsPerPage: 5
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);

  // 按照条件筛选
  $scope.search = function(){
    getList();
  };
  $scope.setSaleUser = function(saleUser) {
    $scope.saleUser = saleUser;
  }

  // 设置状态值
  $scope.transfer = function() {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定移交用户吗?',
      ok: function() {
        // 判断是否选择销售
        if(!$scope.saleUser) {
          alert('请选择销售!');
          return false;
        }
        if(!$scope.cids) {
        console.log($scope.cids);
          alert('请选择客户!');
          return false;
        }
        console.log($scope.saleUser);
        console.log($scope.cids);

        req.getdata('customer/set_sales', 'POST', function(data) {
          if(data.status == 0) {
            alert('操作成功！')
          }else{
            alert('操作失败！');
          }

          $state.go('home.customerTransfer');
        }, {userId:$scope.saleUser, cids:$scope.cids});
      }
    });
  };

  // 重置搜索条件
  $scope.reset = function() {
    $scope.startTime = '';
    $scope.endTime = '';
    $scope.searchKey = $scope.keyList[0];
    $scope.searchValue = '';
    getList();
  };
}]);
