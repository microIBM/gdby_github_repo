'use strict';

angular.module('hop').controller('SuborderDeliverCtrl',['$location', 'dialog', 'req', '$scope', 'appConfigure','$cookieStore', function($location, dialog, req, $scope, appConfigure, $cookieStore) {
  $scope.status = '-1';
   // 导出订单
  $scope.site_url = appConfigure.url;
  var init = function() {
    req.getdata('order/list_options', 'POST', function(data) {
      if(data.status == 0) {
        // 变更数据条目
        $scope.orderTypeList = data.order_type;
        $scope.cityList = data.cities;
        // $scope.timeList = data.deliver_time;
        $scope.orderType = $scope.orderTypeList[0];
      }
    });
  };

  init();

  // 重新获取分页数据
  var getList = function() {
    var cook = $cookieStore.get('orderDeliverCookie') || '';
    $scope.searchValue = cook;
    var postData = {
      status: $scope.status,
      searchValue: $scope.searchValue,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('suborder/lists_deliver', 'POST', function(data) {
      if(data.status == 0) {
        // 变更分页的总数
        $scope.paginationConf.totalItems = data.total_count;
        // 变更数据条目
        $scope.list = data.orderlist;
        $scope.total = data.total;
        $scope.cityList = data.cities;
      }
    }, postData);
  };
  $scope.getpage = function(){
    $cookieStore.put('WaitingOrderPgCookie',$scope.paginationConf.currentPage);
  }
  // 分页参数初始化

  $scope.paginationConf = {
    currentPage: $cookieStore.get('WaitingOrderPgCookie'),
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
    $cookieStore.put('orderDeliverCookie',$scope.searchValue);
    getList();
  };
  $scope.filterByStatus = function(status) {
    $scope.status = status;
    getList();
  }
  // 重置搜索条件
  $scope.reset = function() {
    $cookieStore.remove('orderDeliverCookie');
    $scope.searchValue = '';
    getList();
  };
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
  };
  $scope.city = 0;
  $scope.time = 0;
  $scope.timeList = [
    {id: 'today', val: '全天'},
    {id: 1, val: '上午'},
    {id: 2, val: '下午'},
  ];
  //导出制定日期详单
  $scope.downloadAll =  function(){
    var city = $scope.city || {id: 0},
        time = $scope.time || {id: 0},
        orderType = $scope.orderType || {code: 0};
    if(orderType.code == 0) {
      alert('请先选择订单类型');
      return;
    }
    if(city.id == 0 || time.id == 0) {
      alert('请先选择城市和配送时段再导出数据');
      return;
    }
    window.location.href =$scope.site_url+"/temp_export/everyday_order?deliver_date="+date_formate($scope.dateValue)+'&city_id='+city.id+'&deliver_time='+time.id;
  };
  $scope.downloadDetail =  function(){
    var city = $scope.city || {id: 0},
        time = $scope.time || {id: 0},
        orderType = $scope.orderType || {code: 0};
    if(orderType.code == 0) {
      alert('请先选择订单类型');
      return;
    }
    if(city.id == 0) {
      alert('请先选择城市和配送时段再导出数据');
      return;
    }
    window.location.href =$scope.site_url+"/temp_export/export_orders_to_deliver?deliver_date="+date_formate($scope.dateValue)+'&city_id='+city.id+'&deliver_time='+time.id+'&order_type='+orderType.code;
  };
  $scope.dateValue = date_formate(new Date());

  function date_formate(value){
    var date=Date.parse(value);
    var new_date= new Date(date);
    var Year = new_date.getFullYear();
    var Month= new_date.getMonth()+1;
    var Date_tian = new_date.getDate();
    return Year+"-"+Month+"-"+Date_tian;
  }

}]);

