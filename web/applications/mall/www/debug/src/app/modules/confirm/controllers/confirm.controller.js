'use strict';

angular
.module('dachuwang')
.controller('confirmController', ["$state" ,"$modal", "$rootScope","$scope", "$filter", "$cookieStore", "$window", "cartlist","deliver", "req","rpc", "daChuDialog", 'userAuthService', 'daChuConfig' ,  'daChuLocal' , function ( $state ,$modal, $rootScope , $scope, $filter, $cookieStore, $window,  cartlist,deliver, req,rpc, daChuDialog, userAuthService, daChuConfig, daChuLocal) {

  var DC = $scope.DC = {};

  // 加载购物车信息


  DC.cartlist = cartlist.getInfo();

  DC.tableTitle = [
    {'name' : '商品'},
    {'name' : '订货数量'},
    {'name' : '单价'},
    {'name' : '订货金额'},
  ]

  // 时间选择
  DC.checkEnd = function(item , time){
    if(item && time && item.val){
      item.msg = time.msg ;
      item.code = time.code ;
      DC.validTime = item;
    }
  }

  // 取消时间选择
  DC.clearDate = function(){
      DC.validTime = null;
  }

  // 选择收货地址
  DC.checkAddress = function(address){
    DC.address = address;
  }

  // 选择收货地址
  DC.clearAddress = function(address){
    DC.address = null; 
  }
  //选择支付方式
  DC.checkPay = function(pay){
     DC.pay = pay;
  }

  //取消支付方式
  DC.clearPay = function(pay){
     DC.pay = null;
  }

  // 选择优惠劵
  DC.checkCoupon = function(coupon){
    DC.coupon = coupon;
    $scope.$apply(function(){
       $scope.sum_price = sum_price;
       $scope.minus_amount = minus_amount;
       $scope.sum_price = $scope.sum_price - ( coupon.minus_amount * 1);
       $scope.minus_amount = $scope.minus_amount + ( coupon.minus_amount * 1);
    });
  }

  // 取消选择优惠劵
  DC.clearCoupon = function(coupon){
    DC.coupon = null;
    $scope.$apply(function(){
       $scope.sum_price = sum_price;
       $scope.minus_amount = minus_amount;
    });
  }

  // 监听购物车变化
  $rootScope.$on('cart_data' , function(e , cartChange){
    DC.cartlist = cartChange.validOrder ;
  })
  DC.selectDate = function(item){
    if(item){
      DC.activeDate = item ;
    }
  }

  // 查看是否登陆
  userAuthService.checkLogin();

  //选择支付方式，默认都不选择
  $scope.pay = {
    weixin   : false,
    delivery : false,
  }
  //默认不显示微信支付
  $scope.showPayType = false;

  // 进入加载状态
  $rootScope.showLoading = true;

  // 提取当前购物车里的商品
  var data = cartlist.getDetail();

  // 微信支付url
  var weixin_pay_url = '';
  // 微信支付推广活动
  var pay_events = {};
  //支付优惠金额
  $scope.init_pay_reduce = 0;
  var pay_reduce_status = false;
  var offline_reduce_status = false;


  // 定义两个值类型，再取消优惠劵的时候好把优惠值为初始值
  var minus_amount = null;
  var sum_price = null;

  $rootScope.showLoading = true;
  rpc.load('order/confirm_options', 'POST', {cartlist: cartlist.getDetail()}).then(function(data) {
    $rootScope.showLoading = false;
    // 下面的这种负值方式强烈不建议  建议使用 DC.data = data ;
    $scope.uinfo = data.user_info;
    $scope.dates = data.date_dropdown;
    $scope.times = data.time_dropdown;
    $scope.rules = data.promotion_list;
    $scope.payments = data.payments;
    minus_amount = $scope.minus_amount = data.minus_amount;
    sum_price = $scope.sum_price = data.sum ;
    $scope.total_price = data.total_price;
    $scope.free_amount = data.free_amount;
    $scope.fee = data.fee;
    $scope.serviceFee = data.service_fee;
    DC.data = data ;
    DC.data.sub_account_address.unshift(DC.data.cur);
    $scope.deliver_notice = data.deliver_notice;
    $rootScope.showLoading = false;
    $scope.pay_events_title = data.pay_events_title;
    pay_events = data.pay_events;
    weixin_pay_url = data.pay_url;
    isInOpenCitiesForPay();
    do_pay_event(daChuConfig.payby.weixin,true);
  },
  function(msg) {
    $rootScope.showLoading = false;
    alert(msg);
    $state.go('page.home');
  }
  );
  $scope.showTips = function() {
    $modal.open({
      templateUrl: 'components/modal/login-tips.html',
      controller :  function($scope , $modalInstance){
        $scope.cancel = function(){
          $modalInstance.close();
        }
      }
    });
    return;
  }
 $scope.payby = function(payway) {
    $scope.payStyle = payway;
    do_pay_event(payway,false);
  }

  $scope.pay_reduce = 0;
  var do_pay_event = function(payway,auto_reduce){
    //如果是KA客户不做微信减免活动：KA客户customer_type为2
    if($scope.uinfo.customer_type == 2) {
      return;
    }
    $scope.pay_event = {};
    //如果选择货到付款，就将实付和优惠还原
    if(payway == 0 && offline_reduce_status == false){
      $scope.minus_amount -= $scope.pay_reduce;
      $scope.sum_price += $scope.pay_reduce;
      offline_reduce_status = true;
      pay_reduce_status = false;
    }else if(payway ==1 && pay_reduce_status === false){
      //如果选择微信支付，做相应的减免逻辑
      if(pay_events && common.length(pay_events) > 0){
        //遍历每个城市发布上线的微信支付活动
        for(var city_id in pay_events){
          if($scope.uinfo.province_id == city_id && pay_events[city_id].online){
              $scope.pay_event = pay_events[city_id];
              break;
          }
        }

        //如果有效活动中有高优先级的通用减免，就优先执行减免,同时订单支付总额要大于减免总额
        if(parseInt($scope.pay_event.reduce) !=0 && $scope.pay_event.reduce < $scope.sum_price){
          !auto_reduce && ($scope.minus_amount += $scope.pay_event.reduce);
          !auto_reduce && ($scope.sum_price -= $scope.pay_event.reduce);
          !auto_reduce && ($scope.pay_reduce = $scope.pay_event.reduce);
          auto_reduce && ($scope.init_pay_reduce = $scope.pay_event.reduce);
          !auto_reduce && (pay_reduce_status = true);
          !auto_reduce && (offline_reduce_status = false);
        }else if(common.length($scope.pay_event.total_reduce) > 0){
          //否则就执行梯度减免规则
          var full_price = [];
          var reduce_price = [];
          //遍历梯度满额限制和减免额
          for(var key in $scope.pay_event.total_reduce){
            full_price.push(parseInt(key));
            reduce_price.push(parseInt($scope.pay_event.total_reduce[key]));
          }
          //降序排序必满额和减免额
          full_price.sort(sortcallback);
          reduce_price.sort(sortcallback);

          //梯度计算需要减免的金额
          var count = common.length(full_price);
          if(count > 0){
            for(var i = 0 ;i<count; i++){
              if($scope.sum_price >= full_price[i] && $scope.sum_price > full_price[i]){
                !auto_reduce && ($scope.minus_amount += reduce_price[i]);
                !auto_reduce && ($scope.sum_price -= reduce_price[i]);
                !auto_reduce && ($scope.pay_reduce = reduce_price[i]);
                auto_reduce && ($scope.init_pay_reduce = reduce_price[i]);
                !auto_reduce && (pay_reduce_status = true);
                !auto_reduce && (offline_reduce_status = false);
                break;
              }
            }
          }
        }
      }
    }
  }
  //降序排序回调函数
  var sortcallback = function(a,b){
    if(a == b){
      return 0;
    }
    return a < b ? 1 : -1
  }

  // 获取用户所在城市是否支持微信支付并且该用户是否在白名单中
  var isInOpenCitiesForPay = function() {
    req.getdata('payment/open_cities', 'POST', function(data){
      if(data.status != 0) {
        return;
      }
      for(var key in data.data){
        if(key == $scope.uinfo.province_id ){
          if(data.data[key].length == 0 || isInArray($scope.uinfo.mobile, data.data[key])){
            $scope.showPayType = true;
            break;
          }
        }
      }
      if($scope.showPayType != true) {
        $scope.pay.delivery = true;
      }
    });
  };

  var common = {
    'length':function (o){
        var t = typeof o;
        if(t == 'string'){
            return o.length;
        }else if(t == 'object'){
	        var n = 0;
	        for(var i in o){
	            n++;
	        }
	        return n;
	   }
       return false;
     }
  };

  var isInArray = function (needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
      if(haystack[i] == needle) return true;
    }
    return false;
  }



  $scope.remarks = "";
  // dialog
  $scope.dialog =  daChuDialog.tips;

  var callBack = function(data) {
    $rootScope.showLoading = false;
    if(data.status == 0) {
      cartlist.change = 0;
      $window.alert(data.msg);
      cartlist.clearInfo();

      //如果有拆单直接跳转到待确认页面
      var ordersArr = data.number.split(',');
      var ordersNum = ordersArr.length;
      if(ordersNum>1) {
        req.redirect('/order/list/');
        return;
      }

      //如果货到付款跳转待确认页面，如果时微信支付跳转到微信支付页面

      if(DC.pay.code == 0 || DC.pay.code == 2) {
        req.redirect('/order/list/');
      }else if( DC.pay.code == 1) {
        req.redirect('/pay/' + data.order_id);
      }
    } else  if(data.products) {
        // 修改购物车信息
        $scope.products = data.products;
        var sum = 0;
        angular.forEach(cartlist.users, function(user_id) {
          angular.forEach(cartlist.items[user_id].list, function(item) {
            angular.forEach($scope.products, function(product) {
              if(item.id == product.id) {
                item['last_price'] = item['price'];
                item['price'] = product['price_in_db'];
                item['status'] = product['status'];
              }
            });
            sum += item.quantity * item.price;
          })
        });
        cartlist.change = 1;
        cartlist.sum = sum;
        cartlist.setInfo(cartlist);
        // 跳转到购物车页面
        req.redirect('/cart');
        // localStorage.setItem('cartInfo', JSON.stringify(cartInfo));
        //
      return;
    }else{
      $window.alert(data.msg);
    }
  };

  // 送货时间根据送货日期变化
  $scope.dateChange = function() {
    angular.forEach($scope.dates, function(deliver_date){
      if($scope.deliver_date && deliver_date.name == $scope.deliver_date.name) {
        $scope.times = deliver_date.time;
        $scope.deliver_time = undefined;
      }
    });
  }

  // 下订单
  $scope.confirm = function(price) {

    // 运费提示 $scope.is_ok 控制一单提示一次
    if($scope.fee > 0 && !$scope.is_ok){
      daChuDialog.tips({
        bodyText : '亲，一次下单总金额不足'+$scope.free_amount+'元，将收取运费'+$scope.fee+'元。您可以用其他商品凑单哦!',
        ok : function(){
          $scope.is_ok = true;
        },
        close : function(){
          req.redirect('/home')
        },
        closeText : '继续购物',
        actionText : '仍然提交'
      })
      return
    }

    // 查看是否登陆
    userAuthService.checkLogin();
    if($scope.payments.length === 1){
      DC.pay = $scope.payments[0];
    }
    // 判断是否选择支付方式
    if(!DC.validTime) {
      alert('请选择送货日期');
      return;
    }
    if(!DC.pay) {
      alert('请选择支付方式');
      return;
    }
    if(DC.data.sub_account_address.length && !DC.address) {
      alert('请选择收货地址');
      return;
    }

    //如果是首单 并 满足满减规则  判断配送时间是否超过活动最晚配送时间
    var alertCon = '您选择的配送时间是' + DC.validTime.name + ' ' + DC.validTime.msg + ',大厨网将准时进行配送，是否确认？';
    if($scope.rules.length > 0) {
      var stime = DC.validTime.val;
      angular.forEach($scope.rules, function(item) {
        if(item.latest_deliver_timestamp < stime) {
          alertCon = '配送时间不符活动要求，不再享受满减优惠，是否确认？';
        }
      });
    }

    var select = $window.confirm(alertCon);

    // 只取最终符合条件的活动
    var finalRules = [];
    angular.forEach($scope.rules, function(item){
      if(item.latest_deliver_timestamp >= stime) {
        finalRules.push(item);
      }
    });

    if(!select) {
      return false;
    }


    //支付方式标记
    var pay_by = daChuConfig.payby.delivery;
    if($scope.pay.weixin == true) {
      pay_by = daChuConfig.payby.weixin;
    }
    var pay_by = $scope.payStyle;
    var postData =  {
      rules        : finalRules,
      products     : data,
      deliver_date : DC.validTime.val,
      deliver_time : DC.validTime.code,
      remarks      : DC.remarks,
      pay_type     : DC.pay && DC.pay.code,
      coupon_id    : DC.coupon && DC.coupon.id,
      subUserId    : DC.address && DC.address.id || null
    };

    $rootScope.showLoading = true;
    req.getdata('order/create', 'POST', callBack,postData);
  };
}]);
