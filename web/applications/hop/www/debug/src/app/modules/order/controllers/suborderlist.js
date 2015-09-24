'use strict'
angular.module('hop').controller('SuborderListCtrl',['$location', 'dialog', 'req', '$scope','$cookieStore', 'appConfigure', function($location, dialog, req, $scope, $cookieStore, appConfigure){
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
  $scope.confirmZip = function() {
    var res = window.confirm("是否确认明日需要配送的订单已审核完毕？");
    if(res) {
      window.location.href = appConfigure.url + "/temp_export/write_all_city_orders_to_tmp_dir?need_confirm=1";
    }
  }

  var init = function() {
    req.getdata('order/list_options', 'POST', function(data) {
      if(data.status == 0) {
        $scope.cityList = data.cities;
        $scope.siteList = data.sites;
        $scope.orderTypeList = data.order_type;
        $scope.orderType = $scope.orderTypeList[0];
      }
    });
  };

  // 初始化数据
  init();

  // 重新获取分页数据
  var getList = function() {
    var key = $cookieStore.get('orderlistCookie') || '';
    $scope.searchValue = key;
    var listtabCookie = $cookieStore.get('orderlistTabCookie') || '';
    $scope.status = listtabCookie;
    var site      = $scope.site || {id: 0},
        city      = $scope.city || {id: 0},
        orderType = $scope.orderType || {code: 0};
    var postData = {
      status: $scope.status,
      orderType: orderType.code,
      cityId: city.id,
      site_src: site.id,
      searchValue: $scope.searchValue,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('suborder/lists', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total_count;
        // 变更数据条目
        $scope.list = data.orderlist;
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
  // 判断按钮是否显示
  /*$scope.auth = {
    create: ('order', 'create'),
    edit: HopAuth.check_auth('order', 'edit'),
    delete: HopAuth.check_auth('order', 'delete'),
  };*/
  // 按照日期筛选
  $scope.search = function(){
    $cookieStore.put('orderlistCookie',$scope.searchValue);
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    $cookieStore.remove('orderlistCookie');
    $scope.searchValue = '';
    $cookieStore.put('orderlistTabCookie',$scope.status);
    $scope.paginationConf.currentPage = 1;
    getList();
  }
  // 重置搜索条件
  $scope.reset = function() {
    $cookieStore.remove('orderlistCookie');
    $scope.searchValue = '';
    $scope.startTime = '';
    $scope.endTime = '';
    getList();
  };
}]);
