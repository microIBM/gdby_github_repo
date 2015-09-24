
'use strict';
angular
.module('dachuwang')
.controller('billListController',["$scope", '$upload',"$state", "req", "$cookieStore", "$modal", "$window", 'pagination', 'userAuthService', '$stateParams', 'daChuConfig','daChuLocal', 'daChuDialog','appConfigure', function($scope, $upload ,  $state, req, $cookieStore, $modal, $window, pagination, userAuthService, $stateParams, daChuConfig,daChuLocal,daChuDialog,appConfigure) {
  // 查看是否登陆
  $scope.getUrl = appConfigure;
  userAuthService.checkLogin();
  //请求类型

  var DC = $scope.DC = {};
  req.getdata('/billing/search_options', 'POST',function(data){
    DC.tabs = $scope.tabs = [];

    angular.forEach(data.list, function(key,value){
      if(value == '2'){
        key = '待对账'
      }else if(value == '3'){
        key = '待结款'
      }else if(value == '4'){
        key = '待审核'
      }else if(value == '5'){
        key = '已结款'
      }
      $scope.tabs.push({value:value,name:key});
    })
    $scope.tabs.unshift({value:0,name:'全部'})
  },DC.postData)
  //日期筛选
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endOpened = false;
  $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
    $scope.endOpened = false;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.endOpened = true;
    $scope.opened = false;
  };
  // 时间筛选
  $scope.filterTime = function(){
    if(!$scope.startTime && !$scope.endTime){
      alert('您还没有输入时间');
      return;
    }
    if(!$scope.startTime){
      alert('请输入起始时间');
      return;
    }
    if(!$scope.endTime){
      alert('请输入结束时间');
      return;
    }
    DC.postData = {
      status: $scope.showType,
      startTime: Date.parse($scope.startTime)/1000,
      endTime: Date.parse($scope.endTime)/1000,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('/billing/lists', 'POST',callBack,DC.postData);
  }

  $scope.initTime = function(){
    $scope.startTime = '';
    $scope.endTime = '';
  }
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  $scope.orderlist = [];
  $scope.showType = $stateParams.status || 0;
  $scope.isProcessing = true;

  var weixin_pay_url = '';

  var callBack = function(data) {
    if(data.status === 0) {
      $scope.orderlist = data.list;
      angular.forEach( $scope.orderlist , function(v){
        v.imgUploads = false ;

      })
      $scope.isProcessing = false;
      $scope.user_type = data.type;
      $scope.total = data.total;
      weixin_pay_url = data.pay_url;
      $scope.paginationConf.totalItems = data.total;
    }
    $scope.pagination = pagination;
  };
  var reload_list = function(){
    if(!$scope.paginationConf.currentPage) return ;
    req.getdata('billing/lists', 'POST', callBack, {
      status: $scope.showType,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage
    });
  }
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch(
    'paginationConf.currentPage + paginationConf.itemsPerPage',
    reload_list
  );


  //按类型搜索
  $scope.setStatus = function(status) {
    $stateParams = status;
    $scope.showType = status;
    $scope.orderlist = [];
    $scope.isProcessing = true;
    req.getdata('billing/lists', 'POST', callBack, {
      status: $scope.showType,
      startTime: Date.parse($scope.startTime)/1000,
      endTime: Date.parse($scope.endTime)/1000,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage});
  }
  //同意结款
  $scope.cancel = function() {
    $scope.$modalInstance.close();
  };
  $scope.agreePay = function(billid,endTime){
    $modal.open({
      templateUrl : 'components/modal/agree-pay.html',
      controller:  function($scope , $modalInstance){
        $scope.cancel = function(){
          $modalInstance.close();
        };
        $scope.agreepay = function(){
          $modalInstance.close();
          req.getdata('billing/shop_agree_pay', 'POST' ,function(data){
            if(data.status < 0) {
              daChuDialog.tips({
                bodyText: data.msg,
                close : function(){
                  return;
                },
                closeText:'取消'
              });
              return
            }else{
              daChuDialog.tips({
                bodyText: '对账成功',
                close : function(){
                  reload_list();
                },
                closeText:'关闭'
              });
            }
          },{id:billid})
        }
      }
    });

  }
  //查看订单详情
  $scope.orderInfo = function(number,status){
    if(status == 0){
      daChuDialog.tips({
        bodyText: '该订单已被关闭，不可查看！',
        close : function(){
          return;
        },
        closeText:'关闭'
      });
      return
    }

    daChuLocal.set('orderNumber',number);
    $state.go('page.orderList');
  }
  //上传凭证
  $scope.$watch('files', function () {
    if($scope.files != undefined){

      $scope.uploadImg();
    }
  });
  DC.billId = '';
  $scope.getId = function( order){
    DC.order = order ;
    DC.billId = order.id;
  }
  $scope.uploadText = '上传结款凭证';
  $scope.uploadImg = function () {
    var imgUpload = [];
    $upload.upload({
      url: 'http://img.dachuwang.com/upload?bucket=product',
      file: $scope.files[0],
      fileFormDataName: 'files[]'
    }).progress(function (evt) {

      var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
      $scope.files[0]['progressPercentage'] = progressPercentage;
    }).success(function (data, status, headers, config) {
      // 成功后预览
      if(!DC.order){
        return ;
      }
      DC.order.imgUploads = true ;
      imgUpload.push($scope.files[0]);
      $scope.imgUploads = imgUpload;
      DC.imgUrl = data.files[0].url;

      req.getdata('billing/payment_evidence','POST',function(msg){
        if(msg.status == 0){
          daChuDialog.tips({
            bodyText: '上传成功',
            close : function(){
              return;
            },
            closeText:'关闭'
          });
        }
      },{id:DC.billId,evidence:data.files[0].url})
    });
  };
  //查看凭证
  $scope.vouderInfo = function(){
    $window.open(DC.imgUrl);
  }
  $scope.vouderImg = function(img){
    $window.open(img)
  }
  //$scope.uploader = new FileUploader();
}])
