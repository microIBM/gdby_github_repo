'use strict';
angular
.module('dachuwang')
.controller('cartController', ['$timeout', "$scope", "$rootScope" , "$filter", "$cookieStore", "$window", "cartlist", "daChuDialog","req", "posService",'rpc', function( $timeout, $scope, $rootScope,$filter, $cookieStore, $window, cartlist, daChuDialog, req, posService , rpc ) {


  var DC = $scope.DC = {};


  // 全选
  $scope.checkValid = function(){
    DC.isAll = !!DC.isAll ;
    cartlist.checkValid(DC.isAll , true);
  }

  // 默认全选
  $timeout(function(){
     cartlist.checkValid(true, true);
  }, 300)

  //  批量删除
  $scope.checkRemove = function(){
    var tips = window.confirm('您真的删除吗？')
    if(!tips){
       return ;
    }
    if($scope.products){
      angular.forEach($scope.products , function(v ,k){
        if(v.isChecked){
          cartlist.changeItem(v, -1, 0);
          cartlist.checkValid(v);
        }
      })
    }
  }

  // 图片load回调
  $scope.imgLoad = function(){
      $scope.$apply(function() {
        $scope.showloading = true;
      });
  }


  // 监听购物车变化
  $rootScope.$on('cart_data' , function(e , cartChange){
    $scope.validCart = cartChange ;
    if(cartChange.count > 0 ) {
      $scope.products = cartChange.items[0].list;
    }else{
      $scope.products = null;
    }
    if(cartChange.validLen != 0 && cartChange.ids.length == cartChange.validLen){
      $scope.cartlist.allCheck = true ;
    }else{
      $scope.cartlist.allCheck = false ;
    }
  })
  var DC = $scope.DC = {};
  // 加载购物车信息
  $scope.cartlist = cartlist;
  if(cartlist.ids && cartlist.ids.length){
    $scope.validCart = cartlist.getInfo();

    // 购物车是否全选
    $scope.cartlist.allCheck = $scope.validCart.ids.length == $scope.validCart.validLen ? true : false ;
  }
  var locationInfo = posService.info(),
  locationId = locationInfo.id;
  //---------
  var productsInfo = cartlist.getInfo();
  if(productsInfo.count > 0 && productsInfo.items[0] != undefined) {
    $scope.products = productsInfo.items[0].list;
  }
  // dialog
  $scope.dialog =  daChuDialog.tips;
  var callBack = function(data) {
    if(data.status) {
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

    if($scope.cartlist.validLen == 0){
      daChuDialog.tips({
        bodyText : '请确认商品再提交' ,
        ok : function(){
        },
        closeText : '确定',
      })
      return ;
    }
    rpc.load('order/today_bought_products' , 'POST' ,{cartlist : cartlist.getDetail()}).then(function(data){
      if(data.status != 0){
        return false;
      }

      // 限购油3箱活动
      if(data.check_cart_info && data.check_cart_info.length != 0){
        daChuDialog.tips({
          bodyText : data.check_cart_info.msg ,
          ok : function(){
          },
          close : function(){
            req.redirect('/cart')
          },
          closeText : '取消',
          actionText : '确定'
        })
      }
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


  // 减
  $scope.minus = function(item) {
    if(item.quantity == 1) {
      $scope.remove(item);
    } else {
      cartlist.changeItem(item, -1);
      cartlist.checkValid(item);
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
    cartlist.checkValid(item);
  }

  // 删
  $scope.remove = function(item) {
    var res = $window.confirm('确认要删除？');
    if(res === true) {
      cartlist.changeItem(item, -1, 0);
      cartlist.checkValid(item);
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
      item.quantity = 1;
    } else if(item.quantity != null || force) {
      if(item.quantity <= 0) {
        $scope.remove(item);
      } else if(!/^\d+$/.test(item.quantity)){
        item.quantity = 1;
        cartlist.changeItem(item, 0);
      } else {
        cartlist.changeItem(item, 1, item.quantity);
        cartlist.checkValid(item);
      }
    }
  }


}]);
