'use strict';

angular
  .module('hop')
  .controller('OrderDetailCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$filter', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams, $filter) {
  $scope.data = '';
  $scope.deal_price = {};
  $scope.order_number = $stateParams.order_number;

  // 运营只允许审核订单和取消订单
  var type = $cookieStore.get('type');
  $scope.is_operate = type == 10 ? true : false;
  var getInfo = function() {
    req.getdata('order/info', 'POST', function(data){
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.deal_price.key = data.info.total_price;
        $scope.dates = data.deliver_time;
        $scope.deliver_date = $scope.dates[0];
        $scope.reasonList = data.cancel_reason;
        $scope.deliver_time = data.deliver_time;
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
  $scope.dialog = dialog.tips;
  $scope.setStatus = function(status) {
    if(status !==0 && $scope.data.pay_type == 1 && $scope.data.pay_status == 0){
      alert('未支付成功的订单不能通过审核');return;
    }
    $scope.dialog({
      bodyText: (status != 0 ? '确定修改此订单状态吗？' : '取消订单后，将不可恢复，是否确认关闭订单？'),
      order: $scope.data,
      status: status,
      reasonList: $scope.reasonList,
      ok: function(sign_msg, remark) {
        var signMsg = sign_msg || '';
        if(remark && remark.code && remark.code != 6){
          signMsg = remark.msg;
        }
        var postData = {
          order_id : $scope.data.id,
          remark: signMsg,
        };
        var url = (status == 'confirm' ? 'order/set_order_confirmed' : 'order/cancel_order');
        req.getdata(url, 'POST', function() {
          // 更新订单状态
          getInfo();
        }, postData);
      },
      actionText:'确定',
      closeText:'取消'
   }, {templateUrl: 'set_status.html'});

  };

  $scope.setRemark = function() {
    dialog.tips({
      bodyText: "",
      order: $scope.data,
      ok: function(remark_msg) {
          req.getdata('/order/add_comment', 'POST', function() {
             getInfo();
          }, {order_id:$scope.data.id, remark:remark_msg},true);
      },
      actionText:'确定',
      closeText:'取消'
    }, {templateUrl: 'set_remark.html'});
  };

  $scope.setDeliverTime = function() {
    dialog.tips({
      bodyText: "",
      deliver_times: $scope.deliver_time,
      ok: function(_deliver_date, _deliver_time) {
        var postData = {
          order_id : $scope.data.id,
          deliver_date : _deliver_date.val,
          deliver_time : _deliver_time.code,
        };
        req.getdata('/order/change_deliver_time', 'POST', function() {
          getInfo();
        }, postData, true);
      },
      actionText:'确定',
      closeText:'取消'
    }, {templateUrl: 'set_time.html'});
  };
  $scope.change_pay_type = function(order_id) {
    $scope.dialog({
      headerText:"修改支付方式",
      bodyText: "支付方式",
      order: {'pay_type':1,'pay_types':[1,0],'remark_msg':'','order_id':order_id},
      ok: function(orderInfo) {
          orderInfo.remark_msg = '更改支付方式为货到付款.' + orderInfo.remark_msg;
          req.getdata('/order/set_pay_type', 'POST', function(data) {
            if(data.status == 0 ){
              alert('修改成功');getInfo();
            }else{
              alert(data.msg);
            }
          }, orderInfo,true);
      },
      actionText:'确定',
      closeText:'取消'
    }, {templateUrl: 'change_pay_type.html'});

  };
  // 加数量
  $scope.plus = function(item) {
    item.quantity = parseInt(item.quantity) + 1;
    $scope.sumPrice(item);
  }

  // 减数量
  $scope.minus = function(item) {
    if(parseInt(item.quantity) > 0) {
      item.quantity = parseInt(item.quantity) - 1;
    }
    $scope.sumPrice(item);
  };

}]);
