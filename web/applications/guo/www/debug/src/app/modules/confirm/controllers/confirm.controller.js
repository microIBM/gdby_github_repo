'use strict';

angular
.module('dachuwang')
.controller('confirmController', ["$scope", "$rootScope" ,"$cookieStore", "$window", "cartlist", "req", "daChuDialog", 'userAuthService','coupon', 'daChuLocal' , function($scope, $rootScope ,$cookieStore, $window,  cartlist, req, daChuDialog, userAuthService , coupon , daChuLocal) {
  $rootScope.showLoading = true ;
  // 查看是否登陆
  userAuthService.checkLogin();
  $scope.coupon = coupon;
  // 获取送货时间
  req.getdata('order/deliver_dropdown', 'POST', function(data){

    $rootScope.showLoading = false ;
    if(data.status == 0) {
      $scope.dates= data.list;
      $scope.times = data.list[0]['time'];
      $scope.uinfo = data.user_info;
      $scope.minus_amount = data.minus_amount ? data.minus_amount : 0;
      coupon.active_data = data.coupons;
      // 如果没有优惠劵 ，则清洗优惠劵信息 
      if(data.coupons && !data.coupons.length){
        coupon.choose_sum = coupon.coupon_id = null ;
      }
      daChuLocal.set('coupon_active' , data.coupons);
      // 如果选择优惠劵减去优惠劵
      $scope.sum_price = coupon.choose_sum ? data.sum - coupon.choose_sum : data.sum;
      if(coupon.choose_sum) {
        $scope.minus_amount += coupon.choose_sum
      }
    }
  },{cartlist: cartlist.getDetail()});
  // 加载购物车信息
  $scope.cartlist = cartlist;
  $scope.remarks = "";
  // dialog
  $scope.dialog =  daChuDialog.tips;
  var callBack = function(data) {
    if(data.status == 0) {

      $rootScope.showLoading = false ;
      cartlist.change = 0;
      $window.alert('订单生成成功');

      // 如果使用了优惠劵将service里的那张也去掉，并将其扔进不可用 
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
      cartlist.clearInfo();
      req.redirect('/order/list/');
    }else{
      alert(data.msg);
      if(data.products) {
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
      }
      return;
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

  function check_time() {
    var mydate = new Date();
    var hour = mydate.getHours();
    var current_time = Math.floor(mydate.getTime()/1000);
    //如果下单时间是23点后，但选择配送时间为明天，则不能生成订单，刷新页面重新取数据
    if(hour === 23 && $scope.deliver_date.val-current_time<= 86400) {
      $window.alert('抱歉，23点后最早只能选择后天配送');
      return false;
    } else {
      return true;
    }
  }

  // 下订单
  $scope.confirm = function() {

    // 查看是否登陆
    userAuthService.checkLogin();
    // 判断表单是否填写完整
    if(!$scope.deliver_date) {
      alert('请选择送货日期');
      return;
    }
    if(!$scope.deliver_time) {
      alert('请选择送货时间');
      return;
    }

    var data = [];
    angular.forEach(cartlist.users, function(user_id) {
      angular.forEach(cartlist.items[user_id].list, function(item) {
        if(item.quantity <= 0) {
          $scope.remove(item, true);
        } else {
          data.push({
            id: item.id,
            location_id: item.location_id,
            title : item.title,
            price: item.price,
            quantity: item.quantity
          });
        }
      })
    });
    if(check_time() === false) {
      req.redirect('/cart');
      return;
    }
    var select = $window.confirm('您选择的配送时间是' + $scope.deliver_date.name + ' ' + $scope.deliver_time.msg + ',大果网将准时进行配送，是否确认？');
    if(!select){
      return;
    }

    $rootScope.showLoading = true ;
    req.getdata('order/create', 'POST', callBack, {
      total_price  : cartlist.sum,
      products     : data,
      deliver_date : $scope.deliver_date.val,
      deliver_time : $scope.deliver_time.code,
      remarks      : $scope.remarks,
      coupon_id    : coupon.coupon_id
    });
  };

}]);
