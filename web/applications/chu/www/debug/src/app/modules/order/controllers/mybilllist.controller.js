
'use strict';
angular
.module('dachuwang')
.controller('billListController',["$rootScope","$scope", '$upload',"$state", "req", "$cookieStore","Lightbox", "$modal", "$window", 'pagination', 'userAuthService', '$stateParams', 'daChuConfig','daChuLocal', 'daChuDialog','appConfigure', function($rootScope,$scope, $upload ,  $state, req, $cookieStore,Lightbox, $modal, $window, pagination, userAuthService, $stateParams, daChuConfig,daChuLocal,daChuDialog,appConfigure) {
  // 查看是否登陆
  $scope.getUrl = appConfigure;
  userAuthService.checkLogin();
  //请求类型

  var DC = $scope.DC = {};
 // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  $scope.orderlist = [];
  $scope.isProcessing = true;

  var weixin_pay_url = '';

  var callBack = function(data) {
      $scope.billlist = data.list;
      angular.forEach( $scope.billlist , function(v){
        v.imgUploads = false ;
      })
      $scope.isProcessing = false;
      $scope.user_type = data.type;
      $scope.total = data.total;
      weixin_pay_url = data.pay_url;
      $scope.paginationConf.totalItems = data.total;
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
      if(!DC.order){
        return ;
      }
      // 成功后预览
      DC.order.imgUploads = true ;
      imgUpload.push($scope.files[0]);
      $scope.imgUploads = imgUpload;
      DC.imgUrl = data.files[0].url;

      req.getdata('billing/payment_evidence','POST',function(msg){
        if(msg.status == 0){
          daChuDialog.tips({
            bodyText: '上传成功',
            close : function(){
              reload_list();
            },
            closeText:'关闭'
          });
        }
      },{id:DC.billId,evidence:data.files[0].url})
      reload_list();
    });
  };
  //查看凭证
  $scope.lightBox = function(img) {
    if(!img){
      img = DC.imgUrl;
    }
    var images = [
      { url:'' }
    ]
    angular.forEach(images, function(v) {
      v['url'] = img;
    })
    // 控制幻灯是否显示左右箭头
    $rootScope.lightBox_len = images.length ;
    Lightbox.openModal(images, 0);
  }

  //账单详情
  $scope.billinfo = function(billinfo){
    daChuLocal.set('billinfo',billinfo);
    $state.go('page.billInfo')
  }
  //$scope.uploader = new FileUploader();
}])
