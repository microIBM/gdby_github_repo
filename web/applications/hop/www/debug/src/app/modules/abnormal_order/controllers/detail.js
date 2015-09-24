'use strict';

angular
  .module('hop')
  .controller('AbnormalOrderDetailCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {
  $scope.data = '';
  $scope.deal_price = {};
  $scope.order_number = $stateParams.order_number;
  var getInfo = function() {
    req.getdata('abnormal_order/order_info', 'POST', function(data){
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.deal_price.key = data.info.total_price;
        $scope.dates = data.deliver_time;
        $scope.deliver_date = $scope.dates[0];
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

  $scope.back = function() {
    history.go(-1);
  };

  $scope.create = function(order_number) {
    $state.go('home.abnormalOrderCreate', {order_number: order_number});
  };
  $scope.dialog = dialog.tips;
}]);
