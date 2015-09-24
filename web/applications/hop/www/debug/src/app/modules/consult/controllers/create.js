'use strict';

angular
  .module('hop')
  .controller('ConsultCreateCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {

   // 咨询单的添加服务
  var createReqService=(function(){
       return {
           // 获取获取类型数据列表
            initGetInfo:function() {
              req.getdata('consult/create_input', 'POST', function(data){
                  if(data.status == 0) {
                      $scope.cur_name = data.cur_name;
                      $scope.ctypes = data.ctypes;
                      $scope.sources = data.sources;
                      $scope.statues = data.statues;
                      $scope.channnels = data.channnels;
                      $scope.data={};

                      $scope.data.status_type=$scope.statues[0];
                  }
              },{id:$scope.id});
           },
           // 添加咨询单
           add:function(postParam){
               req.getdata('/consult/create', 'POST', function(data) {
                  if(data.status == 0) {
                       dialog.tips({bodyText:'添加咨询单成功！'});
                       req.redirect('/consult/list');
                  } else {
                       dialog.tips({bodyText:'添加咨询单失败。'});
                  }
               }, postParam, true);
           }
       }
  })


  // 事件的操作管理
  var common = {
      add:function() {
          $scope.show_error = true;
          $scope.basic_form.$setDirty();

          //   验证操作
          if($scope.basic_form.$invalid){
            if(($scope.data.mobile==undefined || $scope.data.mobile=='')
              && ($scope.data.qq==undefined || $scope.data.qq=='')
              && ($scope.data.wechat==undefined || $scope.data.wechat=='')){
              $scope.data.validaContact='1';
            }else{
              $scope.data.validaContact='0';
            }
            return;
          }

          if(($scope.data.mobile==undefined || $scope.data.mobile=='')
              && ($scope.data.qq==undefined || $scope.data.qq=='')
              && ($scope.data.wechat==undefined || $scope.data.wechat=='')){
              $scope.data.validaContact='1';
              return;
          }else{
              $scope.data.validaContact='0';
          }

          //  了解渠道是否为空
          var channelCode=-1;
          if($scope.data.channel_type!=undefined)
              channelCode = $scope.data.channel_type.code;

          // 咨询内容验证
          if($scope.data.content.length>100){
              dialog.tips({
                  bodyText:'咨询内容长度不能超过100',
                  actionText: '确定' ,
              });
          }

          var postData = {
                ctype:$scope.data.c_type.code,
                source:$scope.data.source_type.code,
                name:$scope.data.name,
                mobile:$scope.data.mobile,
                qq:$scope.data.qq,
                wechat:$scope.data.wechat,
                channel:channelCode,
                companyName:$scope.data.company_name,
                companyArea:$scope.data.company_area,
                companyAddress:$scope.data.company_address,
                content:$scope.data.content,
                solution:$scope.data.solution,
                status:$scope.data.status_type.code
          };
          // console.log("------------");
          // console.log(postData);

          createReqService().add(postData);
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
          var data={
            name:"",
            mobile:"",
            qq:"",
            wechat:"",
            content:"",
            company_name:"",
            company_area:"",
            company_address:"",
            solution:"",
            source_type:-1,
            status_type:-1,
            channel_type:-1,
            c_type:-1
          }
          $scope.data=data;
      },
      initCtrl:function(){
          this.initParam();
          createReqService().initGetInfo();
      }
  };

  common.initCtrl();
  $scope.dialog = dialog.tips;
  $scope.func=common;
}]);
