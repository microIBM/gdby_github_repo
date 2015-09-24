'use strict';
angular.module('hop')
.controller('PickCtrl', ['$scope', 'req', 'dialog', function($scope, req, dialog) {
  // 获取分拣任务列表的的筛选条件
  req.getdata('wave/pick_list_page_options', 'GET', function(data) {
     $scope.sites = data.sites;
     $scope.cities = data.cities;
     $scope.order_types = data.order_type;
  });
  // 查询条件
  $scope.init = {
    waveId : '',
    pickTaskId : '',
    pickNo : ''
  };
  $scope.status = 'all';
  // 任务列表
  var getList = function() {
    var city = $scope.city || {id: 0},
        site = $scope.site || {id: 0},
        ordertype = $scope.order_type || {code : 1};
    var postData = {
      wave_id : $scope.init.waveId,
      city_id : city.id,
      site_src: site.id,
      order_type : ordertype.code,
      status : $scope.status,
      pick_task_id : $scope.init.pickTaskId,
      pick_number : $scope.init.pickNo,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('wave/pick_task_list', 'POST', function(data) {
      $scope.list = data.list;
      $scope.total = data.total_count;
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  // 分页配置
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // search
  $scope.search = function() {
    if(typeof $scope.init != 'undefined') {
      getList();
    }
  }
  // 过滤状态
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    getList();
  }
  // reset
  $scope.reset = function() {
    $scope.init.waveId = '';
    $scope.init.pickTaskId = '';
    $scope.init.pickNo = '';
    getList();
  }

  // 全选或取消全选
  $scope.checkAll = function() {
    angular.forEach($scope.list, function(value, key) {
      value.checked = $scope.check_all;
    });
  };
  // 打印分拣单
  $scope.print = function() {
    var id_arr = [];
    var id_str = '';
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        id_str += value.id + ',';
        id_arr.push(value.pick_number);
      }
    });
    if(id_arr && id_arr.length <= 0) {
      dialog.tips({bodyText: '请选择要打印的分拣单！'});
      return;
    }
    window.open('/print/pickTask/' + id_str);
  };
}]);
