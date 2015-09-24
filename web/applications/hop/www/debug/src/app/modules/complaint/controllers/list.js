'use strict'
angular.module('hop').controller('ComplaintListCtrl',['$location', 'dialog', 'req', '$scope','$cookieStore', '$state', 'appConfigure', function($location, dialog, req, $scope, $cookieStore, $state, appConfigure){
  $scope.site_url = appConfigure.url;
  $scope.status = '-1';
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

  var init = function() {
    req.getdata('complaint/list_options', 'POST', function(data) {
      if(data.status == 0) {
        $scope.lineListAll = data.list;
        $scope.cityList = data.cities;
        $scope.siteList = data.sites;
        $scope.ctypeList = data.ctypes;
        $scope.operators = data.operators;
      }
    });
  };

  // 初始化数据
  init();

  // 重新获取分页数据
  var getList = function() {
    var ctype      = $scope.ctype || {code: 0},
        site      = $scope.site || {id: 0},
        city      = $scope.city || {id: 0},
        line      = $scope.line || {id: 0},
        operator  = $scope.operator || {id: 0};
    var postData = {
      status: $scope.status,
      searchValue: $scope.searchValue,
      ctype: ctype.code,
      cityId: city.id,
      siteId: site.id,
      lineId: line.id,
      operator: operator.id,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('complaint/lists', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total;
        // 变更数据条目
        $scope.list = data.list;
        $scope.total = data.total;
      }
    }, postData);
  };
  //分页cookie纪录
  var startPage = $cookieStore.get('paginationCookie');

  $scope.getpage = function(){
    $cookieStore.put('paginationCookie',$scope.paginationConf.currentPage);
  }
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: startPage,
    itemsPerPage: 15
  };

  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // 切换城市或系统
  $scope.switchCity = function(){
    if(!$scope.site && !$scope.city) {
      $scope.lineList = $scope.lineListAll;
      return;
    }
    $scope.lineList = [];
    angular.forEach($scope.lineListAll, function(value, key) {
      var selected = true;
      if($scope.site && value.site_src != $scope.site.id){
        selected = false;
      }
      if($scope.city && value.location_id != $scope.city.id){
        selected = false;
      }

      if(selected) {
        $scope.lineList.push(value);
      }
    })
  };
  // 按照日期筛选
  $scope.search = function(){
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    $cookieStore.remove('complaintListCookie');
    $scope.searchValue = '';
    $cookieStore.put('complaintListTabCookie',$scope.status);
    $scope.paginationConf.currentPage = 1;
    getList();
  }
  // 重置搜索条件
  $scope.create = function() {
    $state.go('home.complaintListOrder');
  };
  // 重置搜索条件
  $scope.reset = function() {
    $scope.otype = '';
    $scope.site = '';
    $scope.city = '';
    $scope.line = '';
    $scope.searchValue = '';
    $scope.startTime = '';
    $scope.endTime = '';
    getList();
  };
  // 删除数据
  $scope.delete = function($index) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定删除投诉单吗?',
      ok: function() {
        req.getdata('complaint/delete', 'POST', function(data) {
          if(data.status == 0) {
            dialog.tips({bodyText:'删除成功！'});
            getList();
          }else{
            dialog.tips({bodyText:'删除失败！' + data.msg});
          }
        }, {id:$scope.list[$index].id}, true);
      }
    });
  };
  // 导出配送单
  $scope.export = function() {
    var id_arr = [];
    var id_str = '';
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        id_arr.push(value.id);
        id_str += value.id + ',';
      }
    });
    if(id_arr && id_arr.length <= 0) {
      dialog.tips({bodyText: '请选择要导出的投诉单！'});
      return;
    }
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定导出选中投诉单吗?',
      ok: function() {
        window.location.href = $scope.site_url+"/complaint/export?ids="+id_str;
      }
    });
  };
  // 导出筛选投诉单
  $scope.exportAll = function() {
    var searchValue = $scope.searchValue || '',
        ctype     = $scope.ctype || {code: 0},
        operator  = $scope.operator || {id: 0},
        city      = $scope.city || {id: 0},
        line      = $scope.line || {id: 0},
        startTime = Date.parse($scope.startTime) || 0,
        endTime = Date.parse($scope.endTime) || 0;
    var params = "searchValue=" + searchValue + "&status=" + $scope.status + "&operator=" + operator.id + "&ctype=" + ctype.code + "&cityId=" + city.id + "&lineId=" + line.id +"&startTime=" + startTime + "&endTime=" + endTime;
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定导出筛选的投诉单吗?',
      ok: function() {
        window.location.href = $scope.site_url+"/complaint/export?" + params;
      }
    });
  };

  // 全选或取消全选
  $scope.checkAll = function() {
    angular.forEach($scope.list, function(value, key) {
      value.checked = $scope.check_all;
    });
  };
}]);
