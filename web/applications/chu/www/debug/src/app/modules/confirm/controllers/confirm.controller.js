'use strict';

angular
.module('dachuwang')
.controller('confirmController', ["$rootScope", "$scope", "$filter", "$cookieStore", "$window", "cartlist", "deliver", "req", "rpc", "daChuDialog", 'userAuthService', 'daChuConfig' , 'coupon', 'daChuLocal' , function ($rootScope , $scope, $filter, $cookieStore, $window, cartlist, deliver, req, rpc, daChuDialog, userAuthService, daChuConfig, coupon, daChuLocal) {
// 如果已经缓存过就直接负值 ， 没有就请求一次， false为已经缓存过
// 查看是否登陆
userAuthService.checkLogin();

$scope.coupon = coupon;
//选择支付方式，默认都不选择
$scope.pay = {
  weixin   : false,
  delivery : false
}
//默认不显示微信支付
$scope.showPayType = false;
$rootScope.showLoading = true;

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
//母账号可以选择收货地址
rpc.load('order/confirm_options', 'POST', {cartlist: cartlist.getDetail()}).then(function(data) {
  $scope.uinfo = data.user_info;
  $scope.dates = data.date_dropdown;
  $scope.subaddress = data.sub_account_address;
  $scope.subaddress.unshift({id:data.cur.id,address:data.cur.address,mobile:data.cur.mobile,name:data.cur.name,shop_name:data.cur.shop_name})
  $scope.payments = data.payments;
  if( $scope.payments.length == 3&& $scope.uinfo.billing_cycle != '') {
    $scope.paytype = 2;
    $scope.payStyle = $scope.payments[2].code;
  }
  $scope.subAddress = $scope.subaddress[0];
  $scope.times = data.time_dropdown;
  $scope.rules = data.promotion_list;
  $scope.minus_amount = data.minus_amount;
  $scope.total_price = data.total_price;
  $scope.free_amount = data.free_amount;
  $scope.fee = data.fee;
  $scope.serviceFee = data.service_fee;
  coupon.active_data = data.coupons;

  // 如果没有优惠劵 ，则清洗优惠劵信息
  if(data.coupons && !data.coupons.length){
    coupon.choose_sum = coupon.coupon_id = null ;
  }
  daChuLocal.set('coupon_active' , data.coupons);
  // 如果选择优惠劵减去优惠劵
  $scope.sum_price = coupon.choose_sum ? parseFloat(data.sum) - parseFloat(coupon.choose_sum) : parseFloat(data.sum);

  if(coupon.choose_sum) {
    $scope.minus_amount += coupon.choose_sum
  }
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
}
);
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

// 加载购物车信息
$scope.cartlist = cartlist;
$scope.remarks = "";
// dialog
$scope.dialog =  daChuDialog.tips;

var callBack = function(data) {
  $rootScope.showLoading = false;
  if(data.status == 0) {

    cartlist.change = 0;
    $window.alert(data.msg);
    cartlist.clearInfo();

    // 如果使用了优惠劵将service里的那张也去掉 ，

    if(coupon.coupon_id){
      angular.forEach(coupon.ucenterList.active_data , function( v , k ){
        if(v.id == coupon.coupon_id){
          coupon.ucenterList.active_data.splice( k , 1);
          coupon.ucenterList.inactive_data.push(v);
        }
      })

      // 同时清空选中的优惠劵id 及面值
      coupon.choose_sum = coupon.coupon_id = null ;
    }

    //如果有拆单直接跳转到待确认页面
    var ordersArr = data.number.split(',');
    var ordersNum = ordersArr.length;
    if(ordersNum>1) {
      req.redirect('/order/list/');
      return;
    }

    //如果货到付款跳转待确认页面，如果时微信支付跳转到微信支付页面
    if($scope.payStyle == '0' || $scope.payStyle == '2') {
      req.redirect('/order/list/');
    }else if($scope.payStyle == '1') {
      $rootScope.showLoading = true;
      window.location.href = weixin_pay_url+'?order_number='+data.number;
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
$scope.confirm = function(price,subaddress) {
  // 运费提示 $scope.is_ok 控制一单提示一次
  //if($scope.fee > 0 && !$scope.is_ok){
    //daChuDialog.tips({
      //bodyText : '亲，一次下单总金额不足'+$scope.free_amount+'元，将收取运费'+$scope.fee+'元。您可以用其他商品凑单哦!',
      //ok : function(){
        //$scope.is_ok = true;
      //},
      //close : function(){
        //req.redirect('/home')
      //},
      //closeText : '继续购物',
      //actionText : '仍然提交'
    //})
    //return
  //}


  // 查看是否登陆
  userAuthService.checkLogin();
  // 判断表单是否填写完整
  // 判断是否选择支付方式
  if($scope.payStyle == undefined) {
    alert('请选择支付方式');
    return;
  }
  if(!$scope.deliver_date) {
    alert('请选择送货日期');
    return;
  }
  if(!$scope.deliver_time) {
    alert('请选择送货时间');
    return;
  }

  //如果是首单 并 满足满减规则  判断配送时间是否超过活动最晚配送时间
  var alertCon = '您选择的配送时间是' + $scope.deliver_date.name + ' ' + $scope.deliver_time.msg + ',大厨网将准时进行配送，是否确认？';
  if($scope.rules.length > 0) {
    var stime = $scope.deliver_date.val;
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
    deliver_date : $scope.deliver_date.val,
    deliver_time : $scope.deliver_time.code,
    remarks      : $scope.remarks,
    pay_type     : pay_by,
    coupon_id    : coupon.coupon_id,
    subUserId    : subaddress.id
  };
  $rootScope.showLoading = true;
  req.getdata('order/create', 'POST', callBack,postData);
};
}]);
