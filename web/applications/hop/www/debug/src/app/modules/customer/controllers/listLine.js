'use strict'
angular
  .module('hop')
  .controller('CustomerListLineCtrl',['$location', 'dialog',  'req', '$scope', function($location, dialog, req, $scope){
  var initUrl = $location.path();
  $scope.line_status = 'toAllot';
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endOpened = $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.endOpened = true;
  };
  // 初始化
  var init = function() {
    req.getdata('customer/lists_options', 'POST', function(data) {
      if(data.status == 0) {
        $scope.line_list = data.list;
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
    var lineId = $scope.line ? $scope.line.id : 0;
    var postData = {
      line_status: $scope.line_status,
      lineId: lineId,
      searchKey: $scope.searchKey.val,
      searchValue: $scope.searchValue,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('customer/list_line', 'POST', function(data) {
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
    $scope.line_status = $status;
    getList();
  };
  // 设置状态值
  $scope.setStatus = function($index, status) {
    var text = '禁用';
    if(status == 1) {
      text = '启用';
    }
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定'+text+'['+$scope.list[$index].name+']账号吗?',
      ok: function() {
        req.getdata('customer/toggle_status', 'POST', function(data) {
          if(data.status == 0) {
            alert('操作成功！')
            $scope.list[$index].status = status;
          }else{
            alert('操作失败！');
          }
        }, {uid:$scope.list[$index].id, status:status});
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
  $scope.editLine = function($index) {
    console.log($index);
    dialog.tips({
      actionText: '确定' ,
      ok: function() {
        req.getdata('customer/toggle_status', 'POST', function(data) {
          if(data.status == 0) {
            alert('操作成功！')
            $scope.list[$index].status = status;
          }else{
            alert('操作失败！');
          }
        }, {uid:$scope.list[$index].id, status:status});
      },
      templateUrl: 'line.html',
    });
  };
}]);
