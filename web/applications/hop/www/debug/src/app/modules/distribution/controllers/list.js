'use strict';

angular.module('hop').controller('DistributionListCtrl',['$location', 'dialog', 'req', '$scope','$cookieStore', 'appConfigure', function($location, dialog, req, $scope, $cookieStore, appConfigure){
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
    req.getdata('distribution/list_options', 'POST', function(data) {
      if(data.status == 0) {
        $scope.cityList = data.cities;
        $scope.siteList = data.sites;
        $scope.orderTypeList = data.order_type;
      }
    });
  };

  init();

  // 重新获取分页数据
  var getList = function() {
    var key = $cookieStore.get('distListCookie') || '';
    $scope.searchValue = key;
    var listtabCookie = $cookieStore.get('distListTabCookie') || '-1';
    $scope.status = listtabCookie;
    var city = $scope.city || {id: 0},
        site = $scope.site || {id: 0};
    var postData = {
      cityId: city.id,
      siteId: site.id,
      orderType: $scope.orderType ? $scope.orderType.code : 0,
      status: $scope.status,
      orderId:$scope.orderId,
      searchValue: $scope.searchValue,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('distribution/lists', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total;
        // 变更数据条目
        $scope.list = data.list;
        $scope.total = data.total;
        $scope.totals = data.totals;
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
  // 按照日期筛选
  $scope.search = function(){
    $cookieStore.put('distListCookie',$scope.searchValue);
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    $cookieStore.remove('distListCookie');
    $scope.searchValue = '';
    $cookieStore.put('distListTabCookie',$scope.status);
    $scope.paginationConf.currentPage = 1;
    getList();
  }
  // 重置搜索条件
  $scope.reset = function() {
    $cookieStore.remove('distListCookie');
    $scope.searchValue = '';
    $scope.startTime = '';
    $scope.endTime = '';
    getList();
  };
  // 删除数据
  $scope.delete = function($index) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定删除配送单[' + $scope.list[$index].name + ']吗?',
      ok: function() {
        req.getdata('distribution/delete', 'POST', function(data) {
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
  // 全选或取消全选
  $scope.checkAll = function() {
    angular.forEach($scope.list, function(value, key) {
      value.checked = $scope.check_all;
    });
  };
  // 打印配送单
  $scope.print = function() {
    var id_arr = [];
    var id_str = '';
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        id_arr.push(value.id);
        id_str += value.id + ',';
      }
    });
    if(id_arr && id_arr.length <= 0) {
      dialog.tips({bodyText: '请选择要打印的配送单！'});
      return;
    }
    window.open('/printpage/' + id_str);

    // 更新配送单打印状态
    req.getdata('distribution/prints', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'打印配送单成功！'});
        getList();
      }else{
        dialog.tips({bodyText:'打印配送单失败！' + data.msg});
      }
    }, {dist_ids: id_arr});
  };
  // 导出配送单
  $scope.export = function() {
    var id_arr = [];
    var id_str = '';
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        id_arr.push(value.dist_number);
        id_str += value.dist_number + ',';
      }
    });
    if(id_arr && id_arr.length <= 0) {
      dialog.tips({bodyText: '请选择要导出的配送单！'});
      return;
    }
//    alert($scope.site_url+"/distribution/export?dist_numbers="+id_str);
    window.location.href = $scope.site_url+"/distribution/export?dist_numbers="+id_str;
  };
}]);
