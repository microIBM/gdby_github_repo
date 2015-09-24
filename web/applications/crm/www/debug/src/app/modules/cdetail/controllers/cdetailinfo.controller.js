'use strict';

angular.module('dachuwang')
.controller('CdetailInfoController',['$rootScope','$scope', '$state', '$window','$modal', 'rpc', 'daChuDialog','Lightbox','urlHistoryService','daChuLocal','Analysis', function($rootScope,$scope, $state, $window,$modal, rpc, dialog, Lightbox, urlHistoryService, daChuLocal, Analysis) {
  $scope.customer = {};
  $scope.history = {};
  $scope.baseinfo = {};
  $scope.isLoading = true;
  $scope.ka_info = true;
  $scope.analysis_open=true;
  $scope.statistics = {
    analysis_times : [{name:'本周',value:'week'},{name:'本月',value:'month'},{name:'总计',value:'all'}],
    analysis_time : null,
    analysis_shop : null,
  };
  $scope.is_show_analysis = false;
  function initSubShop() {
    rpc.load('cdetail/index', 'POST', {action:'get_sub_accounts',id:$state.params.uid})
      .then(function(data) {
        $scope.statistics.analysis_shops = [];
        angular.forEach(data.list, function(v) {
          $scope.statistics.analysis_shops.push({name:v.shop_name,value:v.id});
        });
        $scope.statistics.analysis_shops.unshift({name:'本店',value:$state.params.uid});
        $scope.statistics.analysis_shop = $scope.statistics.analysis_shops[0];
      });
    $scope.statistics.analysis_time = $scope.statistics.analysis_times[0];
  }
  initSubShop();
  rpc.load('cdetail/index','POST',{action:'get_all',uid:$state.params.uid})
  .then(function(data){
    $scope.isLoading = false;
    $scope.imgurl = data.list.basic_info.urls;
    $scope.customer.sms = data.list.sms;
    $scope.history = data.list.his_data;
    $scope.baseinfo = data.list.basic_info;
  });
  //图片点击放大
  $scope.openmodal = function(index){
    var images = [];
    for(var i=0; i<$scope.imgurl.length; i++) {
      images.push({url:$scope.imgurl[i].url});
    }
    $rootScope.imglength = images.length; 
    Lightbox.openModal(images, index); 
  }
  //不是bd
  $scope.role_id = true;
  var role_id = parseInt(daChuLocal.get('role_id'));
  if(role_id != 12){
     $scope.role_id = false;
  }
  function getStartAndEndTime(time) {
    if(!time || time==='all') {
      return null;
    }
    var start,end;
    if(time === 'week') {
      start = (function() {
        var now = new Date();
        return Math.floor(new Date(now-(now.getDay()-1)*86400000).valueOf()/1000);
      })();
    } else {
      start = (function() {
        var now = new Date();
        return Math.floor(new Date(now.getFullYear(),now.getMonth(),1).valueOf()/1000);
      })();
    }
    end = Math.floor(new Date().valueOf()/1000);
    return {
      start : start,
      end : end
    };
  }

  function calculateRate() {
    var all_amount = 0;
    angular.forEach($scope.statistics.analysis_table, function(val) {
      all_amount += val.value;
    });
    angular.forEach($scope.statistics.analysis_table, function(val) {
      val.rate = Math.round(val.value/all_amount*10000)/100;
      val.rate = '' + val.rate + '%';
    });
  }
  function getGraphData() {
    $scope.statistics.analysis_data = [];
    $scope.statistics.analysis_labels = [];
    angular.forEach($scope.statistics.analysis_table, function(val) {
      if(val.value != 0) {
        $scope.statistics.analysis_data.push(val.value/100);
        $scope.statistics.analysis_labels.push(val.name);
      }
    });
  }
  $scope.func = {
    edit_customer : function() {
      if($scope.baseinfo.ka_info) {
        dialog.alert('不允许编辑KA客户');
        return;
      }
      $state.go('page.editcustomer',{customer_id:$state.params.uid});
    },
    buy_analysis : function(analysis_time,analysis_shop) {
      if(!analysis_time || !analysis_shop) {
        dialog.alert('请选择查询条件');
        return;
      }
      Analysis.send('客户订单分析',{kind:'查询'});
      $scope.is_req_loading = true;
      $scope.is_show_analysis = true;
      var time,shop;
      time = getStartAndEndTime(analysis_time.value);
      shop = analysis_shop.value;
      var postData = {
        action : 'get_buy_analysis',
        uid : shop
      };
      if(time) {
        postData.start_time = time.start;
        postData.end_time = time.end;
      }
      rpc.load('cdetail/index', 'POST', postData).then(function(data) {
        $scope.is_req_loading = false;
        if(data.list.length===0) {
          $scope.is_show_analysis = false;
          $scope.is_show_none = true;
        } else {
          $scope.is_show_none = false;
          $scope.statistics.analysis_table = data.list.buy_consist;
          $scope.statistics.buy_data = data.list.buy_data;
          calculateRate();
          getGraphData();
        }
      },function(err) {
        $scope.is_req_loading = false;
        $scope.is_show_analysis = false;
        dialog.alert('请求出错，请联系管理员 '+err);
      });
    },
    reset_password : function() {
      $scope.resetClk = true;
      dialog.tips(
      {
        bodyText: '是否要重置该用户密码',
        actionText: "确定",
        ok: function() {
          var postData = {uid: $state.params.uid};
          rpc.load('customer/update_password', 'POST', postData).then(function(data) {
            $scope.resetClk = false;
            dialog.alert(data.content);
          }, function(msg) {
            dialog.alert('重置密码失败，请联系技术人员');
          });
        }
      }
      );
    },
    history_detail : function() {
      Analysis.send('历史移交记录');
      $state.go('page.customerdetail.cdetailhistory');
    },
    view_map : function() {
      var geoinfo = {
        lat : $scope.baseinfo.lat,
        lng : $scope.baseinfo.lng
      };
      Analysis.send('客户详情路线');
      if(!geoinfo.lat || !geoinfo.lng) {
        dialog.alert('位置信息不全无法查看,请更新位置信息');
      } else {
        if($window.jsInterface && $window.jsInterface.searchLine) {
          $window.jsInterface.searchLine(JSON.stringify({latitude:geoinfo.lat,longitude:geoinfo.lng}));
        } else {
          dialog.alert('lat:'+geoinfo.lat+', lng:'+geoinfo.lng+' 暂不支持查看地图');
        }
      }
    },
    new_register_change_shared : function() {
      dialog.tips(
        {
          bodyText: '是否确定要把客户放到公海?',
          actionText: "确定",
          ok: function() {
            $scope.change_button = true;
            var cid = parseInt($scope.baseinfo.id);
            rpc.load('shared_customer/new_register_change_shared', 'POST', {cid : cid})
            .then(function(res) {
              if(res.status == 0) {
                dialog.alert('操作成功');
                //跳转到私海新注册用户
                urlHistoryService.push(1);
                $state.go('page.manage');
              } else {
                dialog.alert('操作失败：'+res.msg);
              }
            })
            .then(function() {
              $scope.change_button = false;
            }, function() {
              dialog.alert('操作失败：网络不好或服务器内部错误');
              $scope.change_button = false;
            });
          }
        }
      );
    },
    ask_dialog : function(){
      $modal.open({
        templateUrl : 'app/modals/home/askdialog.html',
        controller : 'AskDialogController'
      })
    }
  };
  $scope.btnAnalysis = function(which) {
    if(which == 1) {
      Analysis.send('客户订单分析',{kind:'时间选择'});
    } else {
      Analysis.send('客户订单分析',{kind:'子母店选择'});
    }
  }
}]);
