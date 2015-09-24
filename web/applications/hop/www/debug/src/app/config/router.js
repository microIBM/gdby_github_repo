'use strict';
/* ui-router 运营系统的路由配置
 * @author liaoxianwen@dachuwang.com
 * @date 3-5
 */
angular.module('hop')
  .config(function ($stateProvider, $urlRouterProvider, $locationProvider) {
    var moduleDir = 'app/modules';
    $stateProvider
      .state('home', {
        url: '/',
        templateUrl: moduleDir + '/home/home.html',
        controller: 'HomeCtrl'
      })
      .state('home.department', {
        url: 'department/list',
        templateUrl: moduleDir + '/department/list.html',
        controller: 'DepartmentListCtrl'
      })
      .state('home.departmentCreate', {
        url: 'department/create',
        templateUrl: moduleDir + '/department/create.html',
        controller: 'DepartmentCreateCtrl'
      })
      .state('home.departmentEdit', {
        url: 'department/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/department/edit.html',
        controller: 'DepartmentEditCtrl'
      })
      .state('home.user', {
        url: 'user/list',
        templateUrl: moduleDir + '/user/list.html',
        controller: 'UserListCtrl'
      })
      .state('home.userCreate', {
        url: 'user/create',
        templateUrl: moduleDir + '/user/create.html',
        controller: 'UserCreateCtrl'
      })
      .state('home.userEdit', {
        url: 'user/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/user/edit.html',
        controller: 'UserEditCtrl'
      })
       .state('home.userDynamic', {
        url: 'user/dynamic/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/user/dynamic.html',
        controller: 'UserDynamicCtrl'
      })
      .state('home.role', {
        url: 'role/list',
        templateUrl: moduleDir + '/role/list.html',
        controller: 'RoleListCtrl'
      })
      .state('home.roleCreate', {
        url: 'role/create',
        templateUrl: moduleDir + '/role/create.html',
        controller: 'RoleCreateCtrl'
      })
      .state('home.roleEdit', {
        url: 'role/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/role/edit.html',
        controller: 'RoleEditCtrl'
      })
      .state('home.privilege', {
        url: 'privilege/list',
        templateUrl: moduleDir + '/privilege/list.html',
        controller: 'PrivilegeListCtrl'
      })
      .state('home.privilegeCreate', {
        url: 'privilege/create',
        templateUrl: moduleDir + '/privilege/create.html',
        controller: 'PrivilegeCreateCtrl'
      })
      .state('home.privilegeEdit', {
        url: 'privilege/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/privilege/edit.html',
        controller: 'PrivilegeEditCtrl'
      })
      .state('home.customer', {
        url: 'customer/list',
        templateUrl: moduleDir + '/customer/list.html',
        controller: 'CustomerListCtrl'
      })
      .state('home.customerLine', {
        url: 'customer/list_line',
        templateUrl: moduleDir + '/customer/listLine.html',
        controller: 'CustomerListLineCtrl'
      })
      .state('home.customerTransfer', {
        url: 'customer/list_transfer',
        templateUrl: moduleDir + '/customer/newTransfer.html',
        controller: 'CustomerNewTransferCtrl'
      })
      .state('home.customerTransferUser', {
        url: 'customer/list_transfer_user/{cids:[0-9,]{1,}}',
        templateUrl: moduleDir + '/customer/listTransferUser.html',
        controller: 'CustomerListTransferUserCtrl'
      })
      .state('home.customerEditLine', {
        url: 'customer/edit_line/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/customer/editLine.html',
        controller: 'CustomerEditLineCtrl'
      })
      .state('home.customerCreate', {
        url: 'customer/create',
        templateUrl: moduleDir + '/customer/create.html',
        controller: 'CustomerCreateCtrl'
      })
      .state('home.customerEdit', {
        url: 'customer/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/customer/edit.html',
        controller: 'CustomerEditCtrl'
      })
      .state('home.propertyEdit', {
        url: 'property/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/property/add.html',
        controller: 'PropertyEditCtrl'
      })
      .state('home.orderMap', {
        url: 'orderMap/index',
        templateUrl: moduleDir + '/orderMap/index.html',
        controller: 'mapCtrl'
      })
      .state('home.orderReturn', {
        url: 'orderReturn/index',
        templateUrl: moduleDir + '/orderReturn/orderReturnList.html',
        controller: 'orderReturnCtrl'
      })
      .state('home.orderListNew', {
        url: 'orderListNew/index',
        templateUrl: moduleDir + '/orderReturn/orderListNew.html',
        controller: 'orderListNewCtrl'
      })
      .state('home.createReturnOrder', {
        url: 'orderListNew/create/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/orderReturn/createReturnOrder.html',
        controller: 'createReturnOrderCtrl'
      })
      .state('home.infoReturnOrder', {
        url: 'orderList/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/orderReturn/infoReturnOrder.html',
        controller: 'infoReturnOrderCtrl'
      })
      .state('home.propertyAdd', {
        url: 'property/add',
        templateUrl: moduleDir + '/property/add.html',
        controller: 'PropertyAddCtrl'
      })
      .state('home.categoryEdit', {
        url: 'category/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/category/add.html',
        controller: 'CategoryEditCtrl'
      })
      .state('home.category', {
        url: 'category',
        templateUrl: moduleDir + '/category/list.html',
        controller: 'CategoryCtrl'
      })
      .state('home.editMap', {
        url: 'map/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/category/map.html',
        controller: 'CateMapEditCtrl'
      })
      .state('home.cateMapAdd', {
        url: 'category/map_add',
        templateUrl: moduleDir + '/category/map.html',
        controller: 'CateMapAddCtrl'
      })
      .state('home.cateMap', {
        url: 'category/map',
        templateUrl: moduleDir + '/category/map_list.html',
        controller: 'CateMapCtrl'
      })
      .state('home.categoryAdd', {
        url: 'category/add',
        templateUrl: moduleDir + '/category/add.html',
        controller: 'CategoryAddCtrl'
      })
      .state('home.property', {
        url: 'property',
        templateUrl: moduleDir + '/property/list.html',
        controller: 'PropertyCtrl'
      })
      .state('home.product', {
        url: 'product',
        templateUrl: moduleDir + '/product/list.html',
        controller: 'ProductCtrl'
      })
      .state('home.productAdd', {
        url: 'product/add',
        templateUrl: moduleDir + '/product/add.html',
        controller: 'ProductAddCtrl'
      })
      .state('home.productEdit', {
        url: 'product/{productId:[0-9]{1,}}',
        templateUrl: moduleDir + '/product/add.html',
        controller: 'ProductAddCtrl'
      })
      .state('login', {
        url: '/login',
        templateUrl: moduleDir + '/user/login.html',
        controller: 'LoginCtrl'
      })
      .state('home.order', {
        url: 'order/list',
        templateUrl: moduleDir + '/order/list.html',
        controller: 'OrderListCtrl'
      })
      .state('home.suborder', {
        url: 'order/sublist',
        templateUrl: moduleDir + '/order/sublist.html',
        controller: 'SuborderListCtrl'
      })
      .state('home.wave', {
        url: 'wave',
        templateUrl: moduleDir + '/wave/list.html',
        controller: 'WaveCtrl'
      })
      .state('home.pickList', {
        url: 'pick',
        templateUrl: moduleDir + '/pick/list.html',
        controller: 'PickCtrl'
      })
      .state('home.waveAdd', {
        url: 'wave/add',
        templateUrl: moduleDir + '/wave/add.html',
        controller: 'WaveAddCtrl'
      })
       .state('home.pickTaskDetail', {
        url: 'pick/detail/{task_id:[0-9]{1,}}',
        templateUrl: moduleDir + '/pick/detail.html',
        controller: 'PickTaskDetailCtrl'
      })
      .state('home.waveDetail', {
        url: 'wave/detail/{wave_id:[0-9]{1,}}',
        templateUrl: moduleDir + '/wave/detail.html',
        controller: 'WaveDetailCtrl'
      })
      .state('home.orderAudit', {
        url: 'order/listAudit',
        templateUrl: moduleDir + '/order/listAudit.html',
        controller: 'OrderAuditCtrl'
      })
      .state('home.suborderDeliver', {
        url: 'order/sublistDeliver',
        templateUrl: moduleDir + '/order/sublistDeliver.html',
        controller: 'SuborderDeliverCtrl'
      })
      .state('home.suborderSign', {
        url: 'order/sublistSign',
        templateUrl: moduleDir + '/order/sublistSign.html',
        controller: 'SuborderSignCtrl'
      })
      .state('home.suborderPayment', {
        url: 'order/sublistPayment',
        templateUrl: moduleDir + '/order/sublistPayment.html',
        controller: 'SuborderPaymentCtrl'
      })
      .state('home.suborderDetail', {
        url: 'order/subDetail/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/order/subDetail.html',
        controller: 'SuborderDetailCtrl'
      })
      .state('home.orderDetail', {
        url: 'order/detail/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/order/detail.html',
        controller: 'OrderDetailCtrl'
      })
      .state('home.abnormalOrder', {
        url: 'abnormal_order/list',
        templateUrl: moduleDir + '/abnormal_order/list.html',
        controller: 'AbnormalOrderListCtrl'
      })
      .state('home.abnormalListOrder', {
        url: 'abnormal_order/listOrder',
        templateUrl: moduleDir + '/abnormal_order/listOrder.html',
        controller: 'AbnormalListOrderCtrl'
      })
      .state('home.abnormalOrderDetail', {
        url: 'abnormal_order/detail/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/abnormal_order/detail.html',
        controller: 'AbnormalOrderDetailCtrl'
      })
      .state('home.abnormalOrderCreate', {
        url: 'abnormal_order/create/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/abnormal_order/create.html',
        controller: 'AbnormalOrderCreateCtrl'
      })
      .state('home.abnormalOrderEdit', {
        url: 'abnormal_order/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/abnormal_order/edit.html',
        controller: 'AbnormalOrderEditCtrl'
      })
      .state('home.complaint', {
        url: 'complaint/list',
        templateUrl: moduleDir + '/complaint/list.html',
        controller: 'ComplaintListCtrl'
      })
      .state('home.complaintListOrder', {
        url: 'complaint/listOrder',
        templateUrl: moduleDir + '/complaint/listOrder.html',
        controller: 'ComplaintListOrderCtrl'
      })
      .state('home.complaintDetail', {
        url: 'complaint/detail/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/complaint/detail.html',
        controller: 'ComplaintDetailCtrl'
      })
      .state('home.complaintCreate', {
        url: 'complaint/create/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/complaint/create.html',
        controller: 'ComplaintCreateCtrl'
      })
      .state('home.complaintEdit', {
        url: 'complaint/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/complaint/edit.html',
        controller: 'ComplaintEditCtrl'
      })

      .state('home.consult', {
        url: 'consult/list',
        templateUrl: moduleDir + '/consult/list.html',
        controller: 'ConsultListCtrl'
      })
      .state('home.consultListOrder', {
        url: 'consult/listOrder',
        templateUrl: moduleDir + '/consult/listOrder.html',
        controller: 'ConsultListOrderCtrl'
      })
      .state('home.consultDetail', {
        url: 'consult/detail/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/consult/detail.html',
        controller: 'ConsultDetailCtrl'
      })
      .state('home.consultCreate', {
        url: 'consult/create',
        templateUrl: moduleDir + '/consult/create.html',
        controller: 'ConsultCreateCtrl'
      })
      .state('home.consultEdit', {
        url: 'consult/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/consult/edit.html',
        controller: 'ConsultEditCtrl'
      })

      .state('home.orderEdit', {
        url: 'order/edit/{order_number:[0-9]{1,}}',
        templateUrl: moduleDir + '/order/edit.html',
        controller: 'OrderEditCtrl'
      })
      .state('home.weixinpay', {
        url: 'account/weinxinpay',
        templateUrl: moduleDir + '/account/weixinpay.html',
        controller: 'WeixinpayCtrl'
      })
      .state('printpage', {
        url: '/printpage/{dist_numbers:[0-9A-Z,]{1,}}',
        templateUrl: moduleDir + '/printpage/printpage.html',
        controller: 'PrintController',
        resolve : {
          orderService: 'orderService',
          distListInfo: function(orderService, $stateParams){
            return orderService.getDistList($stateParams.dist_numbers);
          },
        }
      })
      .state('printpicktask', {
        url: '/print/pickTask/{pick_numbers:[0-9A-Z,]{1,}}',
        templateUrl: moduleDir + '/printpage/printPickTask.html',
        controller: 'PrintPickTaskController',
        resolve : {
          orderService: 'orderService',
          pickTaskListInfo: function(orderService, $stateParams){
            return orderService.getPickTaskList($stateParams.pick_numbers);
          },
        }
      })
      .state('home.distribution', {
        url: 'distribution/list',
        templateUrl: moduleDir + '/distribution/list.html',
        controller: 'DistributionListCtrl'
      })
      .state('home.distributionDetail', {
        url: 'distribution/detail/{dist_id:[0-9]{1,}}',
        templateUrl: moduleDir + '/distribution/detail.html',
        controller: 'DistributionDetailCtrl'
      })
      .state('home.line', {
        url: 'line/list',
        templateUrl: moduleDir + '/line/list.html',
        controller: 'LineListCtrl'
      })
      .state('home.lineCreate', {
        url: 'line/create',
        templateUrl: moduleDir + '/line/create.html',
        controller: 'LineCreateCtrl'
      })
      .state('home.lineEdit', {
        url: 'line/edit/{id:[0-9]{1,}}',
        templateUrl: moduleDir + '/line/edit.html',
        controller: 'LineEditCtrl'
      });

      $urlRouterProvider.otherwise('/');
      $locationProvider.html5Mode(true);
  })
;
