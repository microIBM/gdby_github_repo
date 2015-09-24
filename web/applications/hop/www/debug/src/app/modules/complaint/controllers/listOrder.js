'use strict'
angular.module('hop').controller('ComplaintListOrderCtrl',['$location', 'dialog', 'req', '$scope','$cookieStore', function($location, dialog, req, $scope, $cookieStore){
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
        $scope.cityList = data.cities;
        $scope.siteList = data.sites;
      }
    });
  };

  // 初始化数据
  init();

  // 重新获取分页数据
  var getList = function() {
    var site      = $scope.site || {id: 0},
        city      = $scope.city || {id: 0};
    var postData = {
      cityId: city.id,
      site_src: site.id,
      searchValue: $scope.searchValue,
      startTime: Date.parse($scope.startTime),
      endTime: Date.parse($scope.endTime),
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('complaint/list_order', 'POST', function(data) {
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
  // 按照日期筛选
  $scope.search = function(){
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.searchValue = '';
    $scope.paginationConf.currentPage = 1;
    getList();
  }
  // 重置搜索条件
  $scope.reset = function() {
    $scope.searchValue = '';
    $scope.startTime = '';
    $scope.endTime = '';
    getList();
  };
}]);
