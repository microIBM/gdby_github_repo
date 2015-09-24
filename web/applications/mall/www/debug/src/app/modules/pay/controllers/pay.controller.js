'use strict';

angular
.module('dachuwang')
.controller('payController', ['$scope' ,'$stateParams', 'rpc', 'daChuDialog', function($scope ,$stateParams, rpc, daChuDialog) {

  $scope.order = {
    id : $stateParams.orderId,
    total : '',
    payStatus : 0
  };

  //获取价格
  var getOrderInfo = function(callback) {
    rpc.load('/order/info', 'POST', { order_id : $stateParams.orderId }).then(function(data){
      $scope.order.total = data.info.final_price;
      $scope.order.payStatus = data.info.pay_status;
      if(typeof callback !== 'undefined') {
        callback();
      }
    },
    function(msg){
      alert(msg);
    });
  };
  getOrderInfo();

  //获取支付二维码
  $scope.showloading = true;
  $scope.pay = {
    imgsrc : 'http://pay.dachuwang.com/weixin/wxpay/qrcode.php?order_id=' + $scope.order.id,
    imgonload : function() {
      $scope.$apply(function() {
        $scope.showloading = false;
      });
    },
    //支付成功
    success : function() {
      getOrderInfo(function(){
        if($scope.order.payStatus == 0) {
          daChuDialog.tips({
            headerText : '提示',
            bodyText : '系统暂时没有收到您的付款信息，订单将保留在待支付状态，请您不要担心，稍后刷新页面看看。若10分钟后订单仍处于待支付状态，请联系客服。客服电话 400-8199-491',
            closeText : '我知道了，稍后查看',
            close : function() {
              rpc.redirect('/order/list/');
            }
          });
        } else if($scope.order.payStatus == 1) {
          rpc.redirect('/order/list/');
        }
      });
    },
    //支付失败
    error : function() {
      daChuDialog.tips({
        headerText : '支付遇到问题了？',
        bodyText : '可能是由于数据没有即时传输，请不要担心，您可以稍后试试或联系客服。客服电话 400-8199-491',
        actionText : '再扫一次',
        closeText : '返回订单',
        close : function() {
          rpc.redirect('/order/list/');
        }
      });
    }
  }

}]);
