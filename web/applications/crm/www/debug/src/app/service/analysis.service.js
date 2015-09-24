'use strict';

angular.module('dachuwang')
  .factory('Analysis', ['$window','daChuDialog', Analysis]);

function Analysis($window, dialog) {
  var appId = 'yyx9y0ihl0mfwwyasaoy7kegavl9dztbejrwtj13r5xkc993';
  var appKey = '86ubaqohj3a99moyk6zar0rseamffrf3mwaxuvz46dbuiw0s';
  //安卓客户端提供的统计接口，没办法了Q^Q
  var EventHashMap = [
    {name:'页面访问记录', event:'pageViewStatistics'},
    {name:'新增下单数', event:'achStatistics_dataStatistics_newOrderNum'},
    {name:'新增回款流水', event:'achStatistics_dataStatistics_newBackAccount'},
    {name:'已下单客户数', event:'achStatistics_dataStatistics_orderedConNum'},
    {name:'未下单客户数', event:'achStatistics_dataStatistics_unorderedConNum'},
    {name:'业绩统计自选时间段', event:'achStatistics_dataStatistics_custom'},
    {name:'客户列表筛选', event:'consumerManagement_filter'},
    {name:'客户列表路线', event:'consumerManagement_PS_route'},
    {name:'客户列表电话', event:'consumerManagement_PS_phone'},
    {name:'历史移交记录', event:'consumerManagement_PS_conDetail_conInfo_history'},
    {name:'客户详情路线', event:'consumerManagement_PS_conDetail_conInfo_addDetail'},
    {name:'子母店铺切换', event:'consumerManagement_PS_conDetail_conOrder_resFilter'},
    {name:'客户订单时间筛选', event:'consumerManagement_PS_conDetail_conOrder_timeFilter'},
    {name:'查看订单详情', event:'consumerManagement_PS_conDetail_conOrder_checkOrderDetail'},
    {name:'对账管理筛选', event:'consumerManagement_PS_conDetail_checkAccountManagement_orderDetail'},
    {name:'录入潜在客户', event:'addCon_totalTime'},
    {name:'与客户信息一致', event:'addCon_consumerInfoCheckout'},
    {name:'更多-个人中心', event:'more_personalCenter'},
    {name:'更多-用户手册', event:'more_userSheet'},
    {name:'更多-离线地图', event:'more_offlineMap'},
    {name:'更多-检查更新', event:'more_checkUpdate'},
    {name:'业绩统计模块', event:'achievementStatistics_time'},
    {name:'客户管理模块', event:'consumerManagement_time'},
    {name:'添加客户模块', event:'addConsumer_time'},
    {name:'更多模块', event:'more_time'},
    {name:'客户拜访', event:'visitList'},
    {name:'客户订单分析', event:'customer_order_analysis'},
    {name:'客户拜访bdm界面', event:'bdmUI'}
  ];
  var analytics = (function(win) {
    //客户端中采用安卓客户端的统计接口
    if(win.jsInterface && win.jsInterface.onItemClick && win.jsInterface.onEventCompute) {
      return createAndroidAnalytics();
    }
    //非客户端采用LeanCloud的统计接口
    return win.AV.analytics({
      appId : appId,
      appKey : appKey,
      version : '0.0.1',
      channel : 'webApp'
    });
  })($window);

  function createAndroidAnalytics() {
    function getEventName(event) {
      if(!event) {
        return null;
      }
      var i;
      for(i=0; i<EventHashMap.length; i++) {
        if(EventHashMap[i].name === event) {
          return EventHashMap[i].event;
        }
      }
      return null;
    }

    function isPageViewEvent(event) {
      if(event === 'pageViewStatistics') {
        return true;
      }
      return false;
    }

    //调用安卓提供的统计接口，调用方式兼容leanCloud的调用方式
    function send(opptions) {
      var event = getEventName(opptions.event);
      if(!event) {
        return;
      }
      var attr = opptions.attr;
      var duration = opptions.duration;
      //安卓暴露的统计接口点击事件单独处理
      if(!attr && !duration) {
        $window.jsInterface.onItemClick(event);
        return;
      }
      if(attr) {
        attr = (function(obj) {
          var i;
          for(i in obj) {
            if(!angular.isArray(obj[i])) {
              obj[i] = [obj[i]];
            }
          }
          return JSON.stringify(obj);
        })(attr);
      } else {
        attr = null;
      }
      if(!duration || isNaN(duration)) {
        duration = 0;
      }
      //安卓客户端暴露的统计接口要求第二个字段是字符串
      if(isPageViewEvent(event)) {
        //统计页面访问停留时间调用此接口
        $window.jsInterface.onEventTime(event, attr, duration);
      } else {
        $window.jsInterface.onEventCompute(event, attr, duration);
      }
    }
    return {
      send : send
    };
  }

  function send(event, attr, duration) {
    if(!event) {
      return;
    }
    var obj = {};
    obj.event = event;
    if(attr) {
      obj.attr = attr;
    }
    if(duration) {
      obj.duration = duration;
    }
    analytics.send(obj);
  }

  return {
    send : send
  };
}
