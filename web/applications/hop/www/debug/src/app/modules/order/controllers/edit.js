'use strict';

angular
  .module('hop')
  .controller('OrderEditCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams) {
  $scope.data = '';
  $scope.order_number = $stateParams.order_number;
  var getInfo = function() {
    req.getdata('order/info', 'POST', function(data){
      if(data.status == 0) {
        // 初始化数据
        $scope.data = data.info;
        $scope.dates = data.deliver_time;

        angular.forEach($scope.dates, function(v){
          if(v.name == data.info.deliver_date) {
            $scope.data.deliver_date = v;
            $scope.times = v.time;
            angular.forEach($scope.times, function(v){
              if(v.msg == data.info.deliver_time) {
                $scope.data.deliver_time = v;
              }
            });
          }
        });

        // 判断是否可以修改送货时间
        var now = new Date();
        var hour = now.getHours();
        $scope.deliver_flag = false;
        if(hour < 23 && ($scope.data.status == 2 || $scope.data.status == 3)){
          $scope.deliver_flag = true; 
        }
      }
    },{order_number: $scope.order_number});
  };
  getInfo();

  // 送货时间根据送货日期变化
  $scope.dateChange = function() {
    angular.forEach($scope.dates, function(v){
      if($scope.data.deliver_date && v.name == $scope.data.deliver_date.name) {
        $scope.times = v.time;
        $scope.data.deliver_time = '';
      }
    });
  };

  $scope.back = function() {
    history.go(-1);
  };
  $scope.dialog = dialog.tips;
  $scope.edit = function() {
    // 判断表单是否填写完整
    if(!$scope.data.deliver_date) {
      alert('请选择送货日期');
      return;
    }
    if(!$scope.data.deliver_time) {
      alert('请选择送货时间');
      return;
    }

    var postData = {
      id : $scope.data.id,
      deliver_date : $scope.data.deliver_date.val,
      deliver_time : $scope.data.deliver_time.code,
    };
    console.log(postData);
    $scope.dialog({
      bodyText:'确定修改配送时间为'+$scope.data.deliver_date.name+' '+$scope.data.deliver_time.msg+'吗？',
      ok: function() {
        req.getdata('order/edit', 'POST', function() {
          // 更新订单状态
          getInfo();
          $scope.dialog({
            bodyText: '订单修改成功',
          });
        }, postData);
      },
      actionText:'确定',
      closeText:'取消'
    });
  };
}]);
