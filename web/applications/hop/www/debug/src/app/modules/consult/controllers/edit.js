'use strict';

angular
  .module('hop')
  .controller('ConsultEditCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {
  
   // 咨询单的编辑服务
  var editReqService=(function(){
        return {
           // 获取咨询单
           getInfo:function(_this) {
              req.getdata('consult/edit_input', 'POST', function(data){
                  if(data.status == 0) {
                      $scope.order = data.info;
                      $scope.ctypes = data.ctypes;
                      $scope.sources = data.sources;
                      $scope.statues = data.statues;
                      $scope.channnels = data.channnels;


                      _this.initSelect();
                      //_this.initSelect($scope.order.source, $scope.sources, $scope.sourceType);
                           // .initSelect($scope.order.status, $scope.statues, $scope.statusType)
                           // .initSelect($scope.order.channel, $scope.channnels, $scope.channelType)
                           // .initSelect($scope.order.ctype, $scope.ctypes, $scope.cType);
                  }
              },{id:$scope.id});
           },
           // 编辑咨询单
           edit:function(postParam){ 
              req.getdata('/consult/edit', 'POST', function(data) {
                  if(data.status == 0) {
                       dialog.tips({bodyText:'修改咨询单成功！'});
                       req.redirect('/consult/list');
                  } else {
                       dialog.tips({bodyText:'修改咨询单失败。'});
                  }
              }, postParam, true);
           }
        }
  })

  // 事件的操作管理
  var common = {
      // 修改异常单
      edit:function() {
          $scope.show_error = true;
          $scope.basic_form.$setDirty();
          

          //  验证操作
          if($scope.basic_form.$invalid){ 
            if($scope.order.mobile=="" && $scope.order.qq=="" && $scope.order.wechat==""){
              $scope.order.validaContact='1';
            }else{
              $scope.order.validaContact='0';
            }
            return;
          }

          if($scope.order.mobile=="" && $scope.order.qq=="" && $scope.order.wechat==""){
              $scope.order.validaContact='1';
              return;
          }else{
              $scope.order.validaContact='0';
          }
          
          //  了解渠道是否为空
          var channelCode=-1;
          if($scope.channelType!=undefined)
              channelCode = $scope.channelType.code;
          
          //  咨询内容验证
          if($scope.order.content.length>100){
              dialog.tips({
                  bodyText:'咨询内容长度不能超过100',
                  actionText: '确定' ,
              }); 
          }
    
          var postData = {
                id: $scope.id,
                ctype:$scope.cType.code,
                source:$scope.sourceType.code,
                name:$scope.order.name,
                mobile:$scope.order.mobile,
                qq:$scope.order.qq,
                wechat:$scope.order.wechat,
                channel:channelCode,
                companyName:$scope.order.company_name,
                companyArea:$scope.order.company_area,
                companyAddress:$scope.order.company_address,
                content:$scope.order.content,
                solution:$scope.order.solution,
                status:$scope.statusType.code
          };
          // console.log("------------");
          // console.log(postData);
          // return;
          editReqService().edit(postData);
      },
      cancel:function(){
          dialog.tips({
              bodyText:'确定要取消吗?',
              actionText: '确定' ,
              ok:function(){
                 req.redirect('/consult/list');
              }
          });   
      },
      initParam:function(){
          $scope.id = $stateParams.id;
          $scope.sourceType=0;
          $scope.statusType=0;
          $scope.channelType=0;
          $scope.cType=0;
      },
      initCtrl:function(){  
          this.initParam();
          editReqService().getInfo(this);
      },
      initSelect:function() {
          angular.forEach($scope.sources, function(item, key) {
               if($scope.order.source==item.code){
                  $scope.sourceType=$scope.sources[key];
               }
          });

          angular.forEach($scope.statues, function(item, key) {
               if($scope.order.status==item.code){
                  $scope.statusType=$scope.statues[key];
               }
          });

          angular.forEach($scope.channnels, function(item, key) {
               if($scope.order.channel==item.code){
                  $scope.channelType=$scope.channnels[key];
               }
          });

          angular.forEach($scope.ctypes, function(item, key) {
               if($scope.order.ctype==item.code){
                  $scope.cType=$scope.ctypes[key];
               }
          });
      }
  };

  common.initCtrl();
  $scope.dialog = dialog.tips;
  $scope.func=common;
}])
