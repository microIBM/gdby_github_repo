'use strict';

angular
  .module('hop')
  .controller('CustomerListTransferCtrl',['$location', 'dialog',  'req', '$scope', '$state', function($location, dialog, req, $scope, $state){
  $scope.show_status = 'all';
  $scope.selCustomers = [];

  // 初始化搜索类别
  $scope.keyList = [
    {'name': '客户手机号', 'val': 'mobile'},
    {'name': '店铺名称', 'val': 'shop_name'},
  ];
  $scope.bdRoleList = [
    {'name': 'BD', 'val': 'BD'},
    {'name': 'AM', 'val': 'AM'},
  ];
  $scope.customerTypeList = [
    {'name': '注册客户', 'val': 'register'},
    {'name': '待分配客户', 'val': 'unallocated'},
  ];
  $scope.searchKey = $scope.keyList[0];
  $scope.roleType = $scope.bdRoleList[0];
  $scope.customerType = $scope.customerTypeList[1];

  // 初始化数据
  var init = function() {
    req.getdata('user/sale_lists', 'POST', function(data) {
      if(data.status == 0) {
        $scope.allSaleList = data.list;
        $scope.saleList = data.list;
        $scope.bdList = [];
        $scope.amList = [];
        angular.forEach($scope.saleList, function(value, key){
          if(value.role_id == 14 || value.role_id == 15 || value.role_id == 16) {
            $scope.amList.push(value);
          } else {
            $scope.bdList.push(value);
          }
        });
        if(!$scope.roleType){
          $scope.saleList = $scope.allSaleList;
        }else if($scope.roleType.val == 'BD') {
          $scope.saleList = $scope.bdList;
        }else{
          $scope.saleList = $scope.amList;
        }
      }
    }, {itemsPerPage: 'all'});
  };
  init();

  // 重新获取分页数据
  var getList = function() {
    var saleId = $scope.sale ? $scope.sale.id : 0;
    var status = 'normal';
    if($scope.customerType && $scope.customerType.val == 'unallocated') {
      status = 'unallocated';
    }
    var postData = {
      status: status,
      saleId: saleId,
      searchKey: $scope.searchKey.val,
      searchValue: $scope.searchValue,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('customer/lists_transfer', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total;
        $scope.total = data.total;
        // 变更数据条目
        $scope.list = data.list;
        angular.forEach($scope.list, function(value, key){
          angular.forEach($scope.selCustomers, function(value2, key2){
            if(value.id == value2.id){
              value.checked = value2.checked;
            }
          });
        });
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
  // 判断按钮是否显示
  /*$scope.auth = {
    create: HopAuth.check_auth('user', 'create'),
    edit: HopAuth.check_auth('user', 'edit'),
    delete: HopAuth.check_auth('user', 'delete'),
  };*/

  // 按照日期筛选
  $scope.search = function(){
    getList();
  };
  // 按照状态筛选
  $scope.filterByStatus = function($status) {
    $scope.show_status = $status;
  };
  // 重置搜索条件
  $scope.reset = function() {
    $scope.roleType = '';
    $scope.sale = '';
    $scope.customerType = '';
    $scope.searchKey = $scope.keyList[0];
    $scope.searchValue = '';
    getList();
  };
  // 全选或取消全选
  $scope.checkAll = function() {
    angular.forEach($scope.list, function(value, key) {
      if(value.checked != $scope.check_all){
        if($scope.check_all){
          $scope.selCustomers.push($scope.list[key]);
        }else{
          angular.forEach($scope.selCustomers, function(value2, key2) {
            if($scope.list[key].id == value2.id){
              $scope.selCustomers.splice(key2, 1);
            }
          });
        }
        value.checked = $scope.check_all;
      }
    });
  }
  $scope.checkOne = function($index) {
    if($scope.list[$index].checked){
      $scope.selCustomers.push($scope.list[$index]);
    }else{
      angular.forEach($scope.selCustomers, function(value, key) {
        if($scope.list[$index].id == value.id){
          $scope.selCustomers.splice(key, 1);
        }
      });
    }
  }
  // 移除当前行
  $scope.remove = function($index) {
    angular.forEach($scope.list, function(value, key) {
      if($scope.selCustomers[$index].id == value.id){
        value.checked = false;
      }
    });
    $scope.selCustomers.splice($index, 1);
  }
  // 清除全部
  $scope.removeAll = function() {
    $scope.selCustomers = [];
    angular.forEach($scope.list, function(value, key) {
      value.checked = false;
    });
  }
  // 移交客户
  $scope.transferCustomer = function() {
    var id_arr = [];
    if($scope.selCustomers.length == 0) {
      dialog.tips({bodyText: '请选择要操作的客户！'});
      return;
    }
    angular.forEach($scope.selCustomers, function(value, key) {
      id_arr.push(value.id);
    });

    $state.go('home.customerTransferUser', {cids: id_arr});
  };

  // 切换销售
  $scope.changeSale = function() {
    if(!$scope.roleType){
      $scope.saleList = $scope.allSaleList;
    }else if($scope.roleType.val == 'BD') {
      $scope.saleList = $scope.bdList;
    }else{
      $scope.saleList = $scope.amList;
    }
  };
}]);
