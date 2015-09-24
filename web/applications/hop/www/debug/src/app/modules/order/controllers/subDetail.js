'use strict';

angular
  .module('hop')
  .controller('SuborderDetailCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams) {
  $scope.data = '';
  $scope.deal_price = {};
  $scope.order_number = $stateParams.order_number;
  // 运营只允许审核订单和取消订单
  var type = $cookieStore.get('type');
  $scope.is_operate = type == 10 ? true : false;
  var getInfo = function() {
    req.getdata('suborder/info', 'POST', function(data){
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.deal_price.key = data.info.total_price;
        //$scope.dates = data.deliver_time;
        //$scope.deliver_date = $scope.dates[0];
        $scope.reasonList = data.cancel_reason;
        $scope.deliver_time = data.deliver_time;
        $scope.deliver_fee = $scope.data.deliver_fee;
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
  $scope.setStatus = function(status) {
    dialog.tips({
      bodyText: '确定修改此订单状态吗？',
      order: $scope.data,
      reasonList: $scope.reasonList,
      ok: function(sign_msg, remark) {
        // 准备需要提交的数据
        var details = [];
        angular.forEach($scope.data.detail, function(item) {
          details.push({
            id: item.id,
            actual_price: item.price,
            actual_quantity: item.quantity,
            actual_sum_price: item.sum_price,
          });
        });
        var signMsg = sign_msg || '';
        if(remark && remark.code && remark.code != 6){
          signMsg = remark.msg;
        }
        var postData = {
          suborder_id : $scope.data.id,
          deal_price: $scope.deal_price.key,
          remark: signMsg,
          order_details: details
        };
        var url = '';
        if(status == 'delivering'){
          url = 'suborder/set_delivering';
        }else if(status == 'signed'){
          url = 'suborder/set_signed';
        }else if(status == 'rejected'){
          url = 'suborder/set_rejected';
        }else if(status == 'success'){
          url = 'suborder/set_success';
        }

        req.getdata(url, 'POST', function() {
          // 更新订单状态
          getInfo();
        }, postData);
      },
      actionText:'确定',
      closeText:'取消'
   }, {templateUrl: 'set_status.html'});
  };

  $scope.setRemark = function(order_id, status) {
    dialog.tips({
      bodyText: "",
      order: $scope.data,
      ok: function(remark_msg) {
          req.getdata('/suborder/add_comment', 'POST', function() {
             getInfo();
          }, {order_id:order_id,remark:remark_msg},true);
      },
      actionText:'确定',
      closeText:'取消'
    }, {templateUrl: 'set_remark.html'});
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

  // 清除数量
  $scope.backUpNum = {};
  $scope.clearNum = function(item) {
    $scope.backUpNum[item.id] = item.quantity;
    item.quantity = "";
    $scope.sumPrice(item);
  }

  // 设置数量
  $scope.setNum = function(item, force) {
    force = force ? force : false;
    if(force && item.quantity === "" && $scope.backUpNum[item.id]) {
      item.quantity = $scope.backUpNum[item.id];
      $scope.backUpNum[item.id] = "";
    }
    if(item.quantity != null && item.quantity <= 0) {
      item.quantity = 0;
    } else if(item.quantity != null || force) {
      if(!/^\d+$/.test(item.quantity)){
        item.quantity = 1;
      }
    }
    $scope.sumPrice(item);
  }

  // 清除价格
  $scope.backUpPrice = {};
  $scope.clearPrice = function(item) {
    $scope.backUpPrice[item.id] = item.price;
    item.price = "";
    $scope.sumPrice(item);
  }

  // 设置价格
  $scope.setPrice = function(item, force) {
    force = force ? force : false;
    if(force && item.price === "" && $scope.backUpPrice[item.id]) {
      item.price = $scope.backUpPrice[item.id];
      $scope.backUpPrice[item.id] = "";
    }
    if(item.price != null && item.price <= 0) {
      item.price = 0;
    } else if(item.price != null || force) {
      if(!/^\d+\.*\d{0,2}$/.test(item.price)){
        item.price = 1;
      }
    }
    $scope.sumPrice(item);
  }

  // 计算订单实收总额
  $scope.sumPrice = function(item) {
    // 保留两位小数
    var price = parseInt(item.price * 100);
    item.sum_price = price * item.quantity / 100;
    var sum = 0;
    angular.forEach($scope.data.detail, function(it) {
      sum += parseInt(it.sum_price * 100);
    });
    $scope.deal_price.key = sum / 100;
  }
  // 设置总价格,处理异常数据
  $scope.setSumPrice = function(item, force) {
    force = force ? force : false;
    if(item.sum_price != null && item.sum_price <= 0) {
      item.sum_price = 0;
    } else if(item.sum_price != null || force) {
      if(!/^\d+\.*\d{0,2}$/.test(item.sum_price)){
        item.sum_price = 0;
      }
    }
  }
  // 设置总价格,处理异常数据
  $scope.setDealPrice = function(force) {
    force = force ? force : false;
    if($scope.deal_price.key != null && $scope.deal_price.key <= 0) {
      $scope.deal_price.key = 0;
    } else if($scope.deal_price.key != null || force) {
      if(!/^\d+\.*\d{0,2}$/.test($scope.deal_price.key)){
        $scope.deal_price.key = 0;
      }
    }
  }


  // 设置运费操作
  $scope.setDeliverFree = function() {
    dialog.tips({
      bodyText: "",
      deliver_fee: $scope.deliver_fee,
      errorCode:1,
      errorMsg:"",
      ok: function(scope,modal) {
        if(scope.deliver_fee<0 ) {
          scope.errorCode=0;
          scope.errorMsg ="费用不能为负值";
          return;
        }else if(!(/^\d+$/.test(scope.deliver_fee))){
          scope.errorCode=0;
          scope.errorMsg ="费用只能为整数类型";
          return;
        }else{
          scope.errorCode=1;
        }

        var postData = {
          suborder_id : $scope.data.id,
          deliver_fee : scope.deliver_fee,
        };
        req.getdata('/suborder/change_deliver_fee', 'POST', function() {
          modal.cancel();
          getInfo();
        }, postData, true);
      },
      actionText:'确定',
      closeText:'取消'
    }, {templateUrl: 'set_deliver_free.html'});
  };
}]);
