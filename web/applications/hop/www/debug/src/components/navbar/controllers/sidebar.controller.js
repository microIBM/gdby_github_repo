'use strict';

angular
  .module('hop')
  .controller('SidebarCtrl', ['$scope', '$location', '$cookieStore', function ($scope, $location,  $cookieStore) {
    $scope.currentUrl = $location.$$url.split('_')[0];
    $scope.selectedItem = null;
    $scope.changeUrl = function(url) {
      $scope.selectedItem = url;
    }
    var type = parseInt($cookieStore.get('type'));
    var userManage =
    {'name': '组织权限管理',
      'val': [
        {'name':'部门列表', 'val':'home.department'},
        {'name':'用户列表', 'val':'home.user'},
      ]
    },
    userManageAll =
    {'name': '组织权限管理',
      'val': [
        {'name':'部门列表', 'val':'home.department'},
        {'name':'用户列表', 'val':'home.user'},
        {'name':'角色列表', 'val':'home.role'},
        {'name':'权限列表', 'val':'home.privilege'},
      ]
    },
    customerManage =  {'name': '客户管理','val':[
      {'name':'客户列表', 'val':'home.customer'},
      {'name':'客户线路管理', 'val':'home.customerLine'},
      {'name':'客户移交', 'val':'home.customerTransfer'},
    ]},
    customerManage2 =  {'name': '客户管理','val':[
      {'name':'客户线路管理', 'val':'home.customerLine'},
    ]},
    customerManage3 =  {'name': '客户管理','val':[
      {'name':'客户移交', 'val':'home.customerTransfer'},
    ]},
    lineManage =  {'name': '线路管理','val':[
      {'name':'线路列表', 'val':'home.line'},
    ]},
    propertyManage =  {'name': '规格管理','val':[
      {'name':'规格列表', 'val':'home.property'},
    ]},
    orderManage =  {'name':'订单管理','val':[
      {'name':'待审核订单', 'val':'home.orderAudit'},
      {'name':'待生产子订单', 'val':'home.suborderDeliver'},
      {'name':'波次浏览', 'val':'home.wave'},
      {'name':'分拣任务列表', 'val':'home.pickList'},
      {'name':'配送路线单列表', 'val':'home.distribution'},
      {'name':'待回款子订单', 'val':'home.suborderPayment'},
      {'name':'子订单列表', 'val':'home.suborder'},
      {'name':'订单列表', 'val':'home.order'},
    ]},
    orderManage2 =  {'name':'订单管理','val':[
      {'name':'待生产子订单', 'val':'home.suborderDeliver'},
      {'name':'待签收子订单', 'val':'home.suborderSign'},
      {'name':'待回款子订单', 'val':'home.suborderPayment'},
      {'name':'子订单列表', 'val':'home.suborder'},
      {'name':'订单列表', 'val':'home.order'},
    ]},
    ccManage =  {'name':'客服系统','val':[
      {'name':'异常单管理', 'val':'home.abnormalOrder'},
      {'name':'退货退款单管理', 'val':'home.orderReturn'},
      {'name':'投诉单管理', 'val':'home.complaint'},
      {'name':'咨询单管理', 'val':'home.consult'}
    ]},
    ccManage2 =  {'name':'客服系统','val':[
      {'name':'异常单管理', 'val':'home.abnormalOrder'},
      {'name':'退货退款单管理', 'val':'home.orderReturn'}
    ]},
    accountManage = {'name':'对账管理','val':[
      {'name':'微信支付管理', 'val':'home.weixinpay'},
    ]},
    financeManage = {'name':'客服系统','val':[
       {'name':'退货退款单管理', 'val':'home.orderReturn'},
     ]};

    $scope.urls = [];
    if(type === 10) {
      // 运营
      $scope.urls = [orderManage, ccManage, customerManage, userManage, accountManage];
    }else if(type === 103) {
      // 仓管
      $scope.urls = [orderManage, ccManage2, customerManage2, lineManage];
    }else if(type === 100) {
      // 超级管理员
      $scope.urls = [orderManage, ccManage, customerManage, lineManage, userManageAll, accountManage];
    }else if(type === 11) {
      // 财务
      $scope.urls = [orderManage2, accountManage,financeManage];
    }else if(type === 15) {
      // SAM
      $scope.urls = [customerManage3];
    }else if(type === 16) {
      // CM城市经理
      $scope.urls = [customerManage3];
    }
  }]);
