'use strict'
angular.module('hop').controller('UserDynamicCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams','$cookieStore', function($location, dialog, req, $scope, $stateParams,$cookieStore){

    // 动态客户的服务
    var dynamicService=(function(){
        return {
           // 获取咨询单
           getUserInfo:function() {
              req.getdata('customer/view', 'POST', function(data){
                  if(data.status == 0) {
                  	$scope.info=data.info;
                  }
               },{id:$stateParams.id});
           },
           getOrderHistory:function(dataParam,isLoading){
           	     req.getdata('customer/history_order', 'POST', function(data){
		                  if(data.status == 0) {
		                    	$scope.orderHistoryList=data.orderlist;
                          $scope.totalArr=data.total;
  
                          $scope.paginationConf.totalItems = data.total_count;
                          $scope.total = data.total_count;
		                  }
		             },dataParam,isLoading);
           },
           getOrderComplaint:function(dataParam,isLoading){
                 req.getdata('customer/history_complaint', 'POST', function(data){
                      if(data.status == 0) {
                          $scope.orderComplaintList=data.list;

                          $scope._paginationConf.totalItems = data.total;
                          $scope.total = data.total;
                      }
                 },dataParam,isLoading);
           }
        }
    });


    var ngFunction={
    	init:function(){

          //----------日期选择控件初始化【历史订单】-----------------
          $scope.model_order = {
              dateOptions :{
                  formatYear: 'yy',
                  startingDay: 1
              },
              endDateOptions:{
                formatYear: 'yy',
                startingDay: 1
              },
              endOpened: false,
              opened: false,
              status: "-1",
              searchValue : '',
              startTime : '',
              endTime : ''
          }
           
           //----------日期选择控件初始化【历史咨询单】--------------
          $scope.model_complaint = {
              dateOptions :{
                  formatYear: 'yy',
                  startingDay: 1
              },
              endDateOptions:{
                formatYear: 'yy',
                startingDay: 1
              },
              endOpened: false,
              opened: false,
              status: "-1",
              searchValue : '',
              startTime : '',
              endTime : ''
          }


          $scope.openLightboxModal = function (index) {
            // Lightbox.openModal($scope.info.images, index);
          };

        
          //  分页初始化
          this.pagerInit();
          this.pagerInitComplaint();

          //  获取用户信息
          dynamicService().getUserInfo();
          //  获取用户订单历史列表
          dynamicService().getOrderHistory({userId:$stateParams.id});
          //  获取用户咨询单历史列表
          dynamicService().getOrderComplaint({userId:$stateParams.id});
    	},
      filterByStatus : function(status) {
          $scope.model_order.status = status;
          $cookieStore.remove('orderHistorylistCookie');
          $scope.model_order.searchValue = '';
          $cookieStore.put('orderHistorylistCookie',$scope.model_order.status);
          $scope.paginationConf.currentPage = 1;
          this.getList(true);
      },
      filterByStatus_complaint :function(status) {
          $scope.model_complaint.status = status;
          $cookieStore.remove('orderHistoryComplaintCookie');
          $scope.model_complaint.searchValue = '';
          $cookieStore.put('orderHistoryComplaintCookie',$scope.model_complaint.status);
          $scope._paginationConf.currentPage = 1;
          this.getList_complaint(true);
      },
      pagerInit:function(){
          // 分页cookie纪录
          var startPage = $cookieStore.get('paginationCookie');

          $scope.getpage=function(){
              $cookieStore.put('paginationCookie',$scope.paginationConf.currentPage);
          }

          // 分页参数初始化
          $scope.paginationConf = {currentPage: startPage, itemsPerPage: 15};

          // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
          $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', this.getList);

      },
      pagerInitComplaint:function(){

          // 分页cookie纪录
          var startPage = $cookieStore.get('_paginationCookie');

          $scope._getpage=function(){
              $cookieStore.put('_paginationCookie',$scope._paginationConf.currentPage);
          }

          // 分页参数初始化
          $scope._paginationConf = {currentPage: startPage, itemsPerPage: 15};

          // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
          $scope.$watch('_paginationConf.currentPage + _paginationConf.itemsPerPage', this.getList_complaint(true));

      },
      goto:function(url){
          $state.go(url);
      },
      open:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.model_order.opened = true;
      },
      endOpen:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.model_order.endOpened = true;
      },
      open_complaint:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.model_complaint.opened = true;
      },
      endOpen_complaint:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.model_complaint.endOpened = true;
      },
      initParam:function() {
          $scope.status="-1";
          $scope.model_order.searchValue = '';
          $scope.model_order.startTime = '';
          $scope.model_order.endTime = '';
      },
      initParam_complaint:function() {
          $scope.model_complaint.status="-1";
          $scope.model_complaint.searchValue = '';
          $scope.model_complaint.startTime = '';
          $scope.model_complaint.endTime = '';
      },
      //  点击重置
      reset:function(type){
          if(type==1){
            this.initParam();
            this.getList();
          }else if(type==2){
            this.initParam_complaint();
            this.getList_complaint();
          }
      },
      //  封装获取列表
      getList:function(isLoading){
          var postDataParam = {
              searchValue: $scope.model_order.searchValue,
              status:$scope.model_order.status,
              startTime: Date.parse($scope.model_order.startTime),
              endTime: Date.parse($scope.model_order.endTime),
              currentPage: $scope.paginationConf.currentPage,
              itemsPerPage: $scope.paginationConf.itemsPerPage,
              userId:$stateParams.id
          };
          // console.log("=====:",postDataParam);
          dynamicService().getOrderHistory(postDataParam,isLoading);
      },
      //  封装获取列表
      getList_complaint:function(isLoading){
          var postDataParam = {
              searchValue: $scope.model_complaint.searchValue,
              status:$scope.model_complaint.status,
              startTime: Date.parse($scope.model_complaint.startTime),
              endTime: Date.parse($scope.model_complaint.endTime),
              currentPage: $scope._paginationConf.currentPage,
              itemsPerPage: $scope._paginationConf.itemsPerPage,
              userId:$stateParams.id
          };
          // console.log("=====:",postDataParam);

          dynamicService().getOrderComplaint(postDataParam,isLoading);
      },
      // 删除数据
      delete :function(_id) {
          var _this=this;
          dialog.tips({
              actionText: '确定' ,
              bodyText: '确定删除投诉单吗?',
              ok: function() {
                req.getdata('complaint/delete', 'POST', function(data) {
                  if(data.status == 0) {
                    dialog.tips({bodyText:'删除成功！'});
                    _this.getList_complaint();
                  }else{
                    dialog.tips({bodyText:'删除失败！' + data.msg});
                  }
                }, {id:_id}, true);
              }
          })
      },
       // 点击查询列表
      search:function(){
          this.getList(true);
      },
      search_complaint:function(){
          this.getList_complaint(true);
      }
    }

    ngFunction.init();
    $scope.func=ngFunction;
}]);
