'use strict';
// modified by liaoxianwen 2015/1/21
angular
.module('dachuwang')
// 用户编辑
.controller('UserEditCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', function($scope, req, $cookieStore, daChuDialog) {

  if($cookieStore.get('token') == undefined) {
    rpc.redirect('/user/login');
    return false;
  }

  $scope.discard = function() {
    rpc.redirect('/user/center');
  }

  $scope.addr = {
    province: '',
    city: '',
    county: '',
    market: '',
  };

  $scope.reg = {
    provinceId : '',
    name : '',
    cityId : '',
    countyId : '',
    marketId : '',
    detailAddress : '',
  };

  if($scope.reg.type == 30 || $scope.reg.type == 20) {
    $scope.normalUser = 1;
  }else {
    $scope.normalUser = 0;
  }

  rpc.getdata('user/getinfo', 'POST', function(data) {
    $scope.reg.detailAddress = data.info.detail_address;
    $scope.reg.name = data.info.name;

    $scope.markets = data.markets;
    $scope.provinces = data.provinces;
    $scope.citys = data.citys;
    $scope.countys = data.countys;

    angular.forEach($scope.markets,function(v){
      if(v.id == data.info.market_id) {
        $scope.addr.market = v;
      }
    });

    angular.forEach($scope.provinces,function(v){
      if(v.id == data.info.province_id) {
        $scope.addr.province = v;
      }
    });

    angular.forEach($scope.citys,function(v){
      if(v.id == data.info.city_id) {
        $scope.addr.city = v;
      }
    });

    if(data.info.county_id != null) {
      angular.forEach($scope.countys,function(v){
        if(v.id == data.info.county_id) {
          $scope.addr.county = v;
        }
      });
    }
  });

  if(localStorage.getItem('provinces')) {
    $scope.provinces = JSON.parse(localStorage.getItem('provinces'));
  }else {
    rpc.getdata('location/', 'GET', function(data) {
      $scope.provinces = data.provinces; 
      localStorage.setItem('provinces', JSON.stringify(data.provinces));
    });
  }

  $scope.get_child = function(childs, parent) {
    rpc.getdata('location/children', 'POST',function(data) {
      $scope[childs] = data.children;
    },{id:$scope.addr[parent].id});
  }

  var market_query_flag = 0;//已经执行过直辖市查询的标志变量

  $scope.get_market = function(parent) {
    if(parent = 'province'){
      if($scope.addr.province.id <= 4) {
        market_query_flag = 1;
        rpc.getdata('market/get_market', 'POST',function(data) {
          $scope.markets = data;
        },{type : 'province', id : $scope.addr.province.id});
      }else {
        market_query_flag = 0;
      }
    }else {
      if(!market_query_flag) {
        rpc.getdata('market/get_market', 'POST',function(data) {
          $scope.markets = data;
        },{type : 'city', id : $scope.addr.city.id});
      }
    }
  }

  $scope.save = function() {
    $scope.reg.marketId = $scope.addr.market.id;
    $scope.reg.provinceId = $scope.addr.province.id;
    $scope.reg.cityId = $scope.addr.city.id;
    $scope.reg.countyId = $scope.addr.county.id;

    rpc.getdata('/user/update_myself', 'POST', function(data) {
      if(data.status) {
        daChuDialog.tips({bodyText:'更新个人资料成功！'});
        rpc.redirect('/user/center');
      } else {
        daChuDialog.tips({bodyText:'更新个人资料失败。'});
      }
    },$scope.reg);
  }
}]);
