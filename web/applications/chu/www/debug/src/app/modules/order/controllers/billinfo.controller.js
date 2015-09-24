'use strict';
angular
.module('dachuwang')
.controller('billinfoController',["$rootScope","$scope",  '$upload',"req", "$cookieStore","$state",'daChuDialog', 'Lightbox' ,"$modal", "$window", 'daChuLocal', 'pagination', 'userAuthService', '$stateParams', 'daChuConfig', 'cartlist', function($rootScope,$scope,$upload, req, $cookieStore, $state, daChuDialog, Lightbox, $modal, $window, daChuLocal, pagination, userAuthService, $stateParams, daChuConfig, cartlist) {

  var DC = $scope.DC = {}

  DC.billinfo = daChuLocal.get('billinfo');
  DC.billType=[
    {
    name:'按店铺',
    value : '1',
    icon : 'type_shop'
  },
  {
    name : '按日期',
    value : '0',
    icon : 'type_date'
  }
  ]
  $scope.showType =1;
  //根据不同类型筛选

  $scope.chooseType = function(value){
    $scope.showType = value;
    DC.billId = DC.billinfo.id;
    req.getdata('billing/get_billing_detail','POST',callBack,{id : DC.billId,type : $scope.showType});
  }

  var callBack = function(data){
    if(data.status == '0'){
      DC.billData = data;
      data = data.list || data.data;
      DC.billdata = data;
    }
  }

  DC.billId = DC.billinfo.id;
  var postData = {
    id : DC.billId,
    type : $scope.showType
  }
  var reload = function(){
    req.getdata('billing/get_billing_detail','POST',callBack,postData);
  }
  reload();
  //跳转到订单详情
  //订单详情
  $scope.orderInfo = function(id){
    daChuLocal.set('orderId',id);
    $state.go('page.orderInfo')
  }
  $scope.cancel = function() {
    $scope.$modalInstance.close();
  };
  //同意结款
  $scope.agreePay = function(billid){
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
                  reload();
                },
                closeText:'关闭'
              });
            }
          },{id:billid})
        }
      }
    });

  }
  //上传凭证
  $scope.$watch('files', function () {
    if($scope.files != undefined){
      $scope.uploadImg();
    }
  });

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
      //DC.order.imgUploads = true ;
      imgUpload.push($scope.files[0]);
      $scope.imgUploads = imgUpload;
      DC.imgUrl = data.files[0].url;

      req.getdata('billing/payment_evidence','POST',function(msg){
        if(msg.status == 0){
          daChuDialog.tips({
            bodyText: '上传成功',
            close : function(){
              reload();
            },
            closeText:'关闭'
          });
        }
      },{id:DC.billId,evidence:data.files[0].url})
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
}])
