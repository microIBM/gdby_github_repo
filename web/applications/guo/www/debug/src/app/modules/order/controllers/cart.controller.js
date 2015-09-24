'use strict';
angular
  .module('dachuwang')
  .controller('listController', ["$scope", "$cookieStore", "cartlist", "req", "daChuDialog", function($scope, $cookieStore, cartlist,  req, daChuDialog) {

  // 加载购物车信息
  $scope.cartlist = cartlist;

  // dialog
  $scope.dialog =  daChuDialog.tips;
  var callBack = function(data) {
    if(data.status) {
      cartlist.clearInfo();
      req.redirect('/order/list');
    }
  };
  $scope.init = {
    startHour:0,
    dt:0,
    content:''
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

  // 下订单
  $scope.confirm = function() {
    var type = $cookieStore.get('type');
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
    cartlist.changeItem(item, 1);
  }

  // 删
  $scope.remove = function(item) {
    $scope.dialog({
      bodyText:"确定要删除吗?",
      actionText:"确定",
      ok: function() {
        cartlist.changeItem(item, -1, 0);
      }
    });
  }

  $scope.backUpNum = {};
  $scope.clearNum = function(item) {
    $scope.backUpNum[item.id] = item.quantity;
    item.quantity = "";
  }

  // 修改值
  $scope.setNum = function(item, force) {
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
      } else {
        cartlist.changeItem(item, 1, item.quantity);
      }
    }
  }
}]);
