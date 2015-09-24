'use strict';

angular
.module('dachuwang')
.controller('homeCtrl', ['$scope', '$state', '$window', '$log', '$stateParams', '$filter', '$modal','daChuLocal', 'rpc','userAuthService','daChuDialog','Analysis', function($scope, $state, $window, $log, $stateParams, $filter, $modal, daChuLocal, rpc, userAuthService, dialog, Analysis) {
  userAuthService.checkLogin();
  var detailLists = [
    {
      index : 2,
      func : showNewAddOrderLists,
      analysis : '新增下单数'
    },
    {
      index : 3,
      func : showBackFlowWater,
      analysis : '新增回款流水'
    },
    {
      index : 4,
      func : showOrderedCustomerLists,
      analysis : '已下单客户数'
    },
    {
      index : 5,
      func : showNotOrderedCustomerLists,
      analysis : '未下单客户数'
    }
  ];
  var timeTypeMap = [
    {key : '今日', val : 'by_day'},
    {key : '本周', val : 'by_week'},
    {key : '本月', val : 'by_month'}
  ];
  function getTimeTypeVal(timetype_key) {
    var i,len = timeTypeMap.length;
    for(i=0; i<len; i++) {
      if(timeTypeMap[i].key == timetype_key) {
        return {time_type : timeTypeMap[i].val};
      }
    }
  }
  function showNewAddOrderLists(timetype) {
    var modalInstance = $modal.open({
      animation : true,
      templateUrl : 'app/modals/home/newaddorderlists.html',
      controller : 'NewAddOrderListsController',
      resolve : {
        time_type : function() {
          return getTimeTypeVal(timetype);
        },
        //-1表示不需要post bd_id字段
        bd_id : function() {
          return $stateParams.bd_id ? $stateParams.bd_id : -1;
        }
      }
    });
  }
  function showBackFlowWater(timetype) {
    var modalInstance = $modal.open({
      animation : true,
      templateUrl : 'app/modals/home/backflowwater.html',
      controller : 'BackFlowWaterController',
      resolve : {
        time_type : function() {
          return getTimeTypeVal(timetype);
        },
        bd_id : function() {
          return $stateParams.bd_id ? $stateParams.bd_id : -1;
        }
      }
    });
  }
  function showOrderedCustomerLists(timetype) {
    var time_type = getTimeTypeVal(timetype);
    daChuLocal.set('time_type',time_type);
    daChuLocal.set('filter_sift',{
      order_type:{
        value : 1
      }
    });
    var role_id = parseInt(daChuLocal.get('role_id'));
    if(role_id === 16) {
      $state.go('page.crm', {invite_id:$stateParams.bd_id});
    } else {
      $state.go('page.manage');
    }
  }
  function showNotOrderedCustomerLists(timetype) {
    var time_type = getTimeTypeVal(timetype);
    daChuLocal.set('time_type',time_type);
    daChuLocal.set('filter_sift',{
      order_type:{
        value : 0
      }
    });
    var role_id = parseInt(daChuLocal.get('role_id'));
    if(role_id === 16) {
      $state.go('page.crm', {invite_id:$stateParams.bd_id});
    } else {
      $state.go('page.manage');
    }
  }
  $scope.tabLists = [{status:0,name:'数据统计'},{status:1,name:'本月业绩'}];
  $scope.showType = 0;
  $scope.showTabs = true;
  //显示可选区间模块的table
  $scope.show_metabolic = false;
  $scope.dpicker = {
    begin : {},
    end : {}
  };
  $scope.format = {
    min : new Date('2015-03-01'),
    max : new Date()
  };
  var undifine = '未知';
  $scope.statistics = [
    {
      heading : '今日',
      data : [{key:'新注册客户数',val:undifine},{key:'首单客户数',val:undifine}]
    },
    {
      heading : '本周',
      data : [{key:'新注册客户数',val:undifine},{key:'首单客户数',val:undifine}],
    },
    {
      heading : '本月',
      data : [{key:'新注册客户数',val:undifine},{key:'首单客户数',val:undifine}]
    }
  ];
  //可展开面板每一项默认的展开和关闭状态 true表示展开
  $scope.openstatus = {
    list : [true,true,true,true,true,true],
    query : false,
    sum : true
  };
  $scope.capacity_openstatus = {
    list : [true],
    query : false,
    sum : true
  };
  $scope.metabolic = [{key:'新注册客户数',val:undifine},{key:'首单客户数',val:undifine}];
  $scope.sum = [{key:'首单客户数',val:500}];
  $scope.begintime = {};
  $scope.endtime = {};
  $scope.date = {};
  $scope.func = {
    query : function() {
      if(!$scope.date.begin_time || !$scope.date.end_time) {
        dialog.alert('请选择时段');
        return false;
      }
      var bt = new Date($scope.date.begin_time).valueOf();
      var et = new Date($scope.date.end_time).valueOf();
      //起始时间是否小于等于结束时间
      if(bt>et) {
        dialog.alert('起始时间不能大于结束时间');
        return false;
      }
      $scope.isquering = true;
      $scope.show_metabolic = false;
      var postData = {
        role_id : parseInt(daChuLocal.get('role_id')),
        action : 'get_query',
        begin_time : bt/1000,
        end_time : et/1000
      };
      if($stateParams.bd_id) {
        postData.bd_id = $stateParams.bd_id;
      }
      Analysis.send('业绩统计自选时间段');
      rpc.load('statistics/index','POST',postData)
        .then(function(msg) {
          $scope.metabolic = msg.list;
          $scope.show_metabolic = true;
        },function(err) {
          dialog.alert('查询失败请稍候再试，原因: '+err);
        })
        .then(function() {
          $scope.isquering = false;
        });
    },
    openDatepicker : function($event,which) {
      $event.preventDefault();
      $event.stopPropagation();
      if(which === 1) {
        $scope.dpicker.end.status = false;
        $scope.dpicker.begin.status = true;
      } else {
        $scope.dpicker.begin.status = false;
        $scope.dpicker.end.status = true;
      }
    },
    changeStatus : function(status) {
      $scope.showType = status;
    },
    showDetail : function(index, timetype, val) {
      if(index < 2 || val<=0) {
        return;
      }
      var i,len = detailLists.length;
      for(i=0; i<len; i++) {
        if(detailLists[i].index == index) {
          Analysis.send(detailLists[i].analysis, {"时间": timetype});
          detailLists[i].func(timetype);
          return;
        }
      }
    }
  };
  $scope.isloading = true;
  $scope.isquering = false;
  function getStatistics() {
    var postData = {
      role_id : parseInt(daChuLocal.get('role_id')),
      action : 'get_statistics'
    };
    if($stateParams.bd_id) {
      postData.bd_id = $stateParams.bd_id;
    }
    rpc.load('statistics/index','POST',postData)
      .then(function(msg) {
        //返回数据条数大于3，则第4条为客容量信息
        if(msg.list.length>3) {
          $scope.statistics = msg.list.splice(0, msg.list.length-1);
          $scope.capacity = msg.list.splice(msg.list.length-1, 1);
        } else {
          $scope.statistics = msg.list;
        }
        angular.forEach($scope.statistics, function(v) {
          //回款流水单位是分，按元显示
          v.data[3].val /=100;
        });
      },function(err) {
        console.error(err);
      })
      .then(function() {
        $scope.isloading = false;
      });
  }
  getStatistics();
}])
