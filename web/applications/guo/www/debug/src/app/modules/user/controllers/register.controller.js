'use strict'
angular.module('dachuwang')
// 用户注册
.controller('UserRegisterCtrl', ['$scope', 'req', '$cacheFactory', '$timeout', '$location', 'daChuDialog', '$cookieStore', 'daChuLocal', 'daChuTimer', function($scope, req, $cacheFactory, $timeout, $location, daChuDialog, $cookieStore, daChuLocal, daChuTimer) {
  var pattern = /\d+/;
  $scope.dialog = daChuDialog.tips;
  $scope.addr = {
    province: '',
    city: '',
    county: '',
    market: '',
    userType: ''
  };
  $scope.reg = {
    mobile : '',
    password : '',
    name : '',
    provinceId : '',
    cityId : '',
    countyId : '',
    marketId : '',
    detailAddress : '',
    type : '',
    vcode: '',
    inviteid: $cookieStore.get('username')
  };
  // 类型获取
  if(daChuLocal.get('regUserType') && daChuTimer.compare(daChuLocal.get('regUserTypeTime'))) {
    $scope.types = daChuLocal.get('regUserType');
  }else {
    req.getdata('user/user_type', 'POST', function(data) {
      $scope.types = data.user_type;
      daChuLocal.set('regUserType', data.user_type);
      daChuLocal.set('regUserTypeTime', daChuTimer.getNow());
    });
  }
  /*if(localStorage.getItem('provinces')) {
    $scope.provinces = JSON.parse(localStorage.getItem('provinces'));
  } else {
    req.getdata('location/', 'GET', function(data) {
      $scope.provinces = data.provinces; 
      localStorage.setItem('provinces', JSON.stringify(data.provinces));
    });
  }*/

  /*$scope.get_child = function(childs, parent) {
    req.getdata('location/children', 'POST',function(data) {
      $scope[childs] = data.children;
    },{id:$scope.addr[parent].id});
  }
  */
  // 设置缓存
  req.getdata('user/register_options', 'GET',function(data) {
      $scope.cities = data.list.cities;
      $scope.market = data.list.market;
  });
  // 获取市场id
  $scope.getMarket = function() {
    if($scope.addr.city != undefined) {
        $scope.markets = $scope.market[$scope.addr.city.id];
    } else {
      $scope.markets = [];
    }
  }
  // 查看提货码
  //发送短信验证码
  $scope.sms = function() {
    if($scope.reg.mobile == '') {
      $scope.dialog({bodyText:'请输入手机号后再选择发送验证短信!'});
      return false;
    }else {
      $scope.clock = 60;
      var clearTimer = function(timer) {
        $scope.smsinfo = '点此重发验证码';
        $scope.smsdis = '';
        clearInterval(timer);
      }
      var updateClock = function() {
        if($scope.clock >= 1) {
          $scope.clock--;
          $scope.smsinfo = $scope.clock + '秒后可以重发';
          $scope.smsdis = 'disabled';
        }else {
          clearTimer(timer);
        }
      }

      var timer = setInterval(function() {
        $scope.$apply(updateClock); 
      }, 1000);

      req.getdata('user/smscode', 'POST', function(data) {
        if(data.status === 0) {
          $scope.dialog({bodyText:'已经成功发送短信,请您注意查收。'});
        }else {
          clearTimer(timer);
          $scope.dialog({bodyText:'发送短信失败,请您检查手机号是否可用。'});
        }
      }, $scope.reg);
    }
  };
  // 注册新用户
  $scope.register = function() {
    if($scope.regForm.$invalid) {
      $scope.regForm.submitted = true;
      return false;
    }
    $scope.reg.provinceId = $scope.addr.province.id;
    $scope.reg.cityId = $scope.addr.city.id;
    $scope.reg.countyId = $scope.addr.county.id;
    $scope.reg.marketId = $scope.addr.market.id;
    $scope.reg.type = $scope.addr.userType.type;
    $scope.reg.codeInviteId = $cookieStore.get('inviteId');
    $scope.reg.codeInviteStoreNumber = $cookieStore.get('inviteStoreNumber');
    req.getdata('user/register', 'POST', function(data) {
      var bodyText = '恭喜您注册成功';
      if(data.status === 0) {
        if($scope.reg.type == 20) {
          $cookieStore.put('type', 20);
        } else {
          bodyText = '恭喜您注册成功，请等待大果网审核。有任何疑问请联系400-8199-491。';
        }
        $scope.dialog({bodyText:bodyText, actionText:'确定',ok:function(){
          if($scope.reg.type == 20){
            req.redirect('/user/center');
          } else {
            req.redirect('/');
          }
        }, closeText:'首页', close: function() {
          req.redirect('/');
        }});
      } else {
        $scope.dialog({
          bodyText:data.message
        });
      }
    }, $scope.reg);
  }
}]);
