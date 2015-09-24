'use strict';

angular
  .module('hop')
  .controller('WeixinpayCtrl', ['$scope', '$cookieStore', 'req', 'daChuConfig', function($scope, $cookieStore, req, daChuConfig){
    $scope.pay_status = 'all';
    $scope.payExportUrl = daChuConfig.url.paylist_export;
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

    var count_code_by_name = {
      'all' : 'count_all',
      '0'   : 'count_waitting',
      '1'   : 'count_success',
      '-1'  : 'count_failed'
    };

    var init = function() {
      req.getdata('order/list_options', 'POST', function(data) {
        if(data.status == 0) {
          $scope.cityList = data.cities;
          $scope.siteList = data.sites;
        }
      });
    };
    // 初始化数据
    init();

    // 得到订单数据
    var getList = function() {
      var key = $cookieStore.get('orderlistCookie') || '';
      $scope.searchValue = key;
      var listtabCookie = $cookieStore.get('orderlistTabCookie') || '';
      $scope.status = listtabCookie;
      var site = $scope.site || {id : 0};
      var city = $scope.city || {id : 0};
      var pay_status = parseInt($scope.pay_status);
      if(isNaN(pay_status)) {
         pay_status = $scope.pay_status;
      }

      var postData = {
        pay_type     : 1, //支付类型先写死为微信支付，后期在这里增加其他类型支付
        pay_status   : pay_status,
        site_src     : site.id,
        cityId       : city.id,
        searchValue  : $scope.searchValue,
        startTime    : $scope.startTime,
        endTime      : $scope.endTime,
        currentPage  : $scope.paginationConf.currentPage,
        itemsPerPage : $scope.paginationConf.itemsPerPage,
      };
      req.getdata('order/lists_online_pay', 'POST', function(data) {
        if(data.status == 0) {
          // 变更分页的总数
          $scope.paginationConf.totalItems = data.count[count_code_by_name[$scope.pay_status]].total;
          // 变更数据条目
          $scope.list = data.data;
          $scope.total = data.count;
        }
      }, postData);
    }

    //分页cookie记录
    var startPage = $cookieStore.get('paginationCookie');
    $scope.getpage = function() {
    $cookieStore.put('paginationCookie',$scope.paginationConf.currentPage);
    }
    // 分页参数初始化
    $scope.paginationConf = {
      currentPage: startPage,
      itemsPerPage: 15
    };

    // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
    $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);

    //筛选
    $scope.search = function() {
      $cookieStore.put('orderlistCookie',$scope.searchValue);
      getList();
    };
    $scope.filterByStatus = function(status) {
      $scope.pay_status = status;
      $cookieStore.remove('orderlistCookie');
      $scope.searchValue = '';
      $cookieStore.put('orderlistTabCookie', $scope.pay_status);
      $scope.paginationConf.currentPage = 1;
      getList();
    }

    //重置搜索条件
    $scope.reset = function() {
      $cookieStore.remove('orderlistCookie');
      $scope.searchValue = '';
      $scope.startTime = '';
      $scope.endTime = '';
      getList();
    };

  }]);
