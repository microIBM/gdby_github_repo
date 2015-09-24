
'use strict';
angular
.module('dachuwang')
.controller('helpController',["$scope","$state","$rootScope", 'cartlist', "req", "$filter","daChuLocal", "$cookieStore", "$location", "$modal", "$window", 'pagination', 'userAuthService', '$stateParams', 'daChuConfig',  'daChuDialog' ,  function($scope, $state,$rootScope,cartlist, req, $filter,daChuLocal, $cookieStore, $location, $modal, $window, pagination, userAuthService, $stateParams, daChuConfig , daChuDialog ) {

  var helpList = [
    {
    "h1": "新手指南",
    "h2": [
      {
      'name' : 'newNumber',
      "title": "账号开通",
      "href": "1"
    },
    {
      'name' : 'newOrder',
      "title": "下单流程",
      "href": "2"
    },
    {
      'name' : 'newPassword',
      "title": "重置密码",
      "href": "3"
    },
    {
      'name' : 'newInfo',
      "title": "帐号信息",
      "href": "4"
    }
    ]
  },
  {
    "h1": "配送说明",
    "h2": [
      {
      'name' : 'deliverRange',
      "title": "配送范围",
      "href": "6"
    },
    {
      'name' : 'deliverTiem',
      "title": "配送时间",
      "href": "7"
    },
    {
      'name' : 'deliverStand',
      "title": "配送收费",
      "href": "8"
    },
    {
      'name' : 'deliverType',
      "title": "配送方式",
      "href": "9"
    },
    ]
  },
  {
    "h1": "支付方式",
    "h2": [
      {
      'name' : 'payCash',
      "title": "货到付款",
      "href": "10"
    },
    {
      'name' : 'payWeixin',
      "title": "微信支付",
      "href": "11"
    },
    {
      'name' : 'newFlowMoney',
      "title": "帐期付款",
      "href": "20"
    },
    {
      'name' : 'newCycle',
      "title": "帐期付款流程",
      "href": "21"
    },

    {
      'name' : 'newFlowMoney',
      "title": "退款流程",
      "href": "12"
    },
    {
      'name' : 'newCycle',
      "title": "退款周期",
      "href": "13"
    }

    ]
  },
  {
    "h1": "售后服务",
    "h2": [
      {
      'name' : 'cusReturn',
      "title": "退换货政策",
      "href": "14"
    },
    {
      'name' : 'cusFlow',
      "title": "退换货流程",
      "href": "15"
    },
    {
      'name' : 'newQuestion',
      "title": "常见问题",
      "href": "5"
    }

    ]
  }
 ];
  $scope.helpList = helpList;
  $scope.help = {};
  $scope.helpNavs = function(name,value,model){
    $scope.help.name = name;
    $scope.help.value = value;
    $scope.help.a = model;
    daChuLocal.set('help',$scope.help)
    $state.go('page.help',{help: value});
  };

  var helpNav = daChuLocal.get('help')
  if(helpNav){
    angular.forEach($scope.helpList,function(v){
      v.status=false;
      if(v.h1 == helpNav.name){
        v.h2.filter(function(m){
          if(m.title == helpNav.a){
            m.class= true;
          }
        })
        if($location.$$path == '/help/16' || $location.$$path == '/help/17' || $location.$$path == '/help/18' || $location.$$path == '/help/19'){
          v.status = false;  
          return
        }
        v.status = true;
      }

    })
  }

}])






