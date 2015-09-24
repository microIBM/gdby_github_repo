'use strict'
angular.module('hop').controller('UserListCtrl',['$location', 'dialog', 'req', '$scope', function($location, dialog, req, $scope){
  $scope.status = 'all';
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
    req.getdata('user/role_list', 'POST', function(data) {
      if(data.status == 0) {
        $scope.role_list = data.role_list;
      }
    });
  }

  // 重新获取分页数据
  var getList = function() {
    var roleId = $scope.role ? $scope.role.id : 0;
    var postData = {
      status: $scope.status,
      roleId: roleId,
      searchValue: $scope.searchValue,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
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
  // 按照状态筛选
  $scope.filterByStatus = function($status) {
    $scope.status = $status;
    getList();
  };

  // 按照日期筛选
  $scope.search = function(){
    getList();
  };
  // 重置搜索条件
  $scope.reset = function() {
    $scope.searchValue = '';
    $scope.startTime = '';
    $scope.endTime = '';
    getList();
  };
  // 设置状态值
  $scope.setStatus = function($index, status) {
    var text = '禁用';
    var url = 'user/disable';
    if(status == 1) {
      text = '启用';
      url = 'user/enable';
    }
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定'+text+'['+$scope.list[$index].name+']账号吗?',
      ok: function() {
        req.getdata(url, 'POST', function(data) {
          if(data.status == 0) {
            dialog.tips({bodyText:'操作成功！'});
            $scope.list[$index].status = status;
          }else{
            dialog.tips({bodyText:'操作失败！' + data.msg});
          }
        }, {uid:$scope.list[$index].id, status:status});
      }
    });
  };
  // 重置密码
  $scope.resetPassword = function($index) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定重置用户[' + $scope.list[$index].name + ']的密码吗?',
      ok: function() {
        req.getdata('user/reset_password', 'POST', function(data) {
          if(data.status == 0) {
            dialog.tips({bodyText:'密码重置成功！'});
          }else{
            dialog.tips({bodyText:'密码重置失败！'});
          }
        }, {uid:$scope.list[$index].id});
      }
    });
  };
  // 删除数据
  $scope.delete = function($index) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定删除用户[' + $scope.list[$index].name + ']吗?',
      ok: function() {
        req.getdata('user/delete', 'POST', function(data) {
          if(data.status == 0) {
            dialog.tips({bodyText:'删除成功！'});
            getList();
          }else{
            dialog.tips({bodyText:'删除失败！' + data.msg});
          }
        }, {id:$scope.list[$index].id});
      }
    });
  };

  // 导出数据
  var exportData = function() {
    var domain = $location.$$host,
    surl = 'http://api.dachuwang.com/';
    if(domain.indexOf('hop.dachuwang.com') !== 0) {
      surl = 'http://api.dachuwang.net/';
    }
    // 根据用户过滤条件设置url的参数
    var url = surl + 'hop/user/export_data?1=1';
    if($scope.role) {
      url += '&userType=' + $scope.role.id;
    }
    if($scope.status) {
      url += '&status=' + $scope.status;
    }
    if($scope.key) {
      url += '&key=' + $scope.key;
    }
    if($scope.startTime) {
      url += '&startTime=' + Date.parse($scope.startTime);
    }
    if($scope.endTime) {
      url += '&endTime=' + Date.parse($scope.endTime);
    }
    window.open(url);
  };
  $scope.exportData = function() {
    dialog.tips({
      headerText: '导出数据',
      actionText: '确定' ,
      bodyText: '确定导出数据吗?',
      ok: function() {
        exportData();
      }
    });
  };
  init();
}]);
