'use strict'
angular.module('hop').controller('MemberRebateEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams','$cookieStore', function($location, dialog, req, $scope, $stateParams,$cookieStore){

    // 动态客户的服务
    var dynamicService=(function(){
        return {
           // 获取KA客户信息
           getRebateInfo:function() {
              req.getdata('member_rebate/edit_input', 'POST', function(data){
                  if(data.status == 0) {
                  	$scope.info=data.info;
                    $scope.categories = data.categories;
                    // 将分类每两个分成一组
                    $scope.groupCategories = [];
                    var arrCon = [];
                    if($scope.categories.length>0){
                        $scope.categories.filter(function(item,index){
                            arrCon.push(item);
                            if((index+1)%2==0){
                                var copyArr=angular.copy(arrCon);
                                $scope.groupCategories.push({group:copyArr});
                                arrCon.length=[];
                            }
                        })
                        if(arrCon.length > 0) {
                            $scope.groupCategories.push({group:arrCon});
                        }
                    }
                    $scope.originGroupCategories = angular.copy($scope.groupCategories);
                  }
               },{id:$stateParams.customerId});
           }
        }
    });


    var ngFunction={
    	init:function(){
          $scope.openLightboxModal = function (index) {
            // Lightbox.openModal($scope.info.images, index);
          };

          //  获取用户信息
          dynamicService().getRebateInfo();
    	},
      saveRebate:function(){
          var _this=this;
          dialog.tips({
              actionText: '确定' ,
              bodyText: '确定设置客户折扣吗?',
              ok: function() {
                  req.getdata('member_rebate/edit', 'POST', function(data){
                      if(data.status == 0) {
                          dialog.tips({
                              bodyText : '设置成功！'
                          });
                      } else {
                          dialog.tips({
                              bodyText : '设置失败！' + data.msg
                          });
                      }
                  },{customerId:$stateParams.customerId, rebateGroup: $scope.groupCategories});
              }
          });
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
      initParam:function() {
          $scope.status="-1";
          $scope.model_order.searchValue = '';
          $scope.model_order.startTime = '';
          $scope.model_order.endTime = '';
      },
      // 点击重置
      reset:function(){
          $scope.groupCategories = angular.copy($scope.originGroupCategories);
      },
      back:function(){
        history.go(-1);
      }
    };

    ngFunction.init();
    $scope.func = ngFunction;
}]);
