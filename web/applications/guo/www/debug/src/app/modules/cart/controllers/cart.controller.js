'use strict';
angular
.module('dachuwang')
.controller('cartController', ["$scope","$rootScope" , "$filter", "$cookieStore", "$window", "cartlist", "req", "daChuDialog", "posService",'rpc', 'userAuthService' ,function($scope, $rootScope, $filter, $cookieStore, $window, cartlist,  req, daChuDialog, posService , rpc , userAuthService) {
  $scope.isLogined = userAuthService.isLogined();
  // 加载购物车信息
  $scope.cartlist = cartlist;
  var locationInfo = posService.info(),
  locationId = locationInfo.id;
  //---------
  if(cartlist.count > 0) {
    $scope.products = $scope.cartlist.items[0].list;
  }
  // dialog
  $scope.dialog =  daChuDialog.tips;
  var callBack = function(data) {
    if(data.status) {

      $rootScope.showLoading = false;
      //添加后，保留，下次方便快速下单
      //cartlist.clearInfo();
      req.redirect('/order/list');
    }
  };
  $scope.init = {
    startHour : 0,
    dt        : 0,
    content   : ''
  };
  $scope.dates = [];
  var myDate = new Date();
  var today = {'name': '今天', 'val': 'today'};
  var tomorrow = {'name': '明天', 'val': 'tomorrow'};
  $scope.dates.push(today);
  $scope.dates.push(tomorrow);
  $scope.init.dt = $scope.dates[0];

  $scope.hours = [];
  for(var i=0;i<24;i++) {
    var single = {
      name :i+'点',
      val : i
    }
    $scope.hours.push(single);
  }
  var myDate = new Date();
  var current_hour = myDate.getHours();
  $scope.init.startHour = $scope.hours[current_hour];
  $scope.hourChange = function() {
    return false;
  }

  cartlist.limit_tips = [];
  // 生成订单
  $scope.createOrder = function() {

    rpc.load('order/today_bought_products' , 'GET').then(function(data){

      angular.forEach($scope.products , function( v , k){

        // 购物车有限购 && 已买的数量大于等于限购的数量  将此商品title存进cartlist.limit_tips
        if(v.buy_limit && v.buy_limit > 0){
          angular.forEach( data.list , function(sv , sk){
            var beyond =  v.quantity + sv.quantity > v.buy_limit ? true : false ;

            // 已购买的数量等于大于限购数量 || 已加入购物车的数量加已购买的数量 大于 限购数量
            if( (v.id == sv.product_id && v.buy_limit <= sv.quantity ) || (v.id == sv.product_id &&  beyond ) ){

              //记录限购商品信息 方便提示
              cartlist.limit_tips.push({name : v.title , buy_limit : v.buy_limit , unit : v.unit});
            }
          })
        }

      })


      $scope.tipsCon =  '';
      if(cartlist.limit_tips && cartlist.limit_tips.length){

        angular.forEach(cartlist.limit_tips , function(v , k ){

           $scope.tipsCon += v.name +'仅限购买' + v.buy_limit + v.unit + ','
        })

        daChuDialog.tips({
          bodyText : $scope.tipsCon + '请重新下单'
        })

        // 清空缓存
        cartlist.limit_tips = [] ;
        $scope.tipsCon = '';
        return;
      }

     // 判断是否有无货商品并且不能有异市的商品
      var data = [];
      var status = 1, isRemote = 0;
      angular.forEach(cartlist.users, function(user_id) {
        angular.forEach(cartlist.items[user_id].list, function(item) {
          if(item.status == 0) {
            status = 0;
          }
          if(parseInt(item.location_id) != parseInt(locationId)) {
            isRemote = 1;
          }
        })
      });
      if(status == 0) {
        alert('订单中存在已下架的商品，请删除后重新生成订单！');
      } else if(isRemote == 1) {
        alert('订单中存在不同售卖区域的产品，请删除后重新生产订单');
      } else {
        req.redirect('/confirm');
      }

    });
  };
  // 下订单
  $scope.confirm = function() {
    var type = $cookieStore.get('token');
    if(!type) {
      req.redirect('/user/login', 'cart/detail');
      return false;
    }
    var data = [];
    angular.forEach(cartlist.users, function(user_id) {
      angular.forEach(cartlist.items[user_id].list, function(item) {
        if(item.quantity <= 0) {
          $scope.remove(item, true);
        } else {
          data.push({
            id: item.id,
            quantity: item.quantity
          });
        }
      })
    });

    $rootScope.showLoading = true;
    req.getdata('order/add', 'POST', callBack, {
      total_price : cartlist.sum,
      products    : data,
      mindate     : $scope.init.dt,
      content     : $scope.init.content,
      minhour     : $scope.init.startHour
    });
  };
  // 减
  $scope.minus = function(item) {
    if(item.quantity == 1) {
      $scope.remove(item);
    } else {
      cartlist.changeItem(item, -1);
    }
  };

  // 加
  $scope.plus = function(item) {


    if(item.storage != -1 && item.storage <= item.quantity){
      alert('抱歉库存不足');
      item.quantity = parseInt(item.storage) ;
      return ;
    }
    if(item.buy_limit != 0 && item.buy_limit == item.quantity){
      alert('每人只能购买' + item.buy_limit + item.unit)
      return;
    }
    cartlist.changeItem(item, 1);
  }

  // 删
  $scope.remove = function(item) {
    /* $scope.dialog({
bodyText:"确定要删除吗?",
actionText:"确定",
ok: function() {
cartlist.changeItem(item, -1, 0);
}
});*/
    var res = $window.confirm('确认要删除？');
    if(res === true) {
      cartlist.changeItem(item, -1, 0);
    }
  }

  $scope.backUpNum = {};
  $scope.clearNum = function(item) {
    $scope.backUpNum[item.id] = item.quantity;
    item.quantity = "";
  }

  $scope.setNum = function(item, force) {

    if(item.storage != -1 && item.storage < item.quantity){
      item.quantity = parseInt(item.storage) ;
    }
    // 判断用户输入是否超出限购
    if(item.buy_limit != 0 && item.quantity > item.buy_limit){

      //超出限购设置quantity为限购件
      item.quantity = parseInt(item.buy_limit) ;

    }
    force = force ? force : false;
    if(force && item.quantity === "" && $scope.backUpNum[item.id]) {
      item.quantity = $scope.backUpNum[item.id];
      $scope.backUpNum[item.id] = "";
    }
    if(item.quantity != null && item.quantity <= 0) {
      item.quantity = 0;
    } else if(item.quantity != null || force) {
      if(item.quantity <= 0) {
        $scope.remove(item);
      } else if(!/^\d+$/.test(item.quantity)){
        item.quantity = 1;

        cartlist.changeItem(item, 0);
      } else {
        cartlist.changeItem(item, 1, item.quantity);
      }
    }
  }
}]);
