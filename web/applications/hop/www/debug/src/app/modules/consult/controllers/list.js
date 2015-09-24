'use strict'

angular.module('hop').controller('ConsultListCtrl',['$location', 'dialog', 'req', '$scope','$cookieStore', '$state', 'appConfigure', function($location, dialog, req, $scope, $cookieStore, $state, appConfigure){

  // 咨询单的列表服务
  var consultReqService = (function() {
     return {
          //  查询咨询单列表
          queryListData:function(param){
              req.getdata('consult/lists', 'POST', function(data) {
                if(data.status == 0) {
                  // 变更分页的总数
                  $scope.paginationConf.totalItems = data.total;
                  //alert(data.list);
                  // 变更数据条目
                  $scope.list = data.list || [];
                  $scope.total = data.total_count;
                }
              }, param);
          },
          //  删除咨询单
          deleteOrder:function(_id,_com){
              dialog.tips({
                actionText: '确定' ,
                bodyText: '确定删除投诉单吗?',
                ok: function() {
                    req.getdata('consult/delete', 'POST', function(data) {
                      if(data.status == 0) {
                         dialog.tips({bodyText:'删除成功！'});
                         _com.getList();
                      }else{
                        dialog.tips({bodyText:'删除失败！' + data.msg});
                      }
                    }, {id:_id},true);
                }
              });
          },
          //  初始化获得类型参数
          initGetTypes:function(){
              req.getdata('/consult/list_options','POST',function(data){
                 $scope.ctypes=data.ctypes;
                 $scope.operators = data.operators;
                 $scope.statuses = data.statuses;
              });
          }
     }
  })

  // 事件的操作管理
  var common ={
      initCtrl:function(){
          $scope.site_url = appConfigure.url;
          $scope.searchValue="";
          $scope.status="-1";

          // 日期选择控件初始化
          $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
          };

          $scope.endDateOptions = {
            formatYear: 'yy',
            startingDay: 1
          };

          $scope.endOpened = $scope.opened = false;

          this.pagerInitSetting();
          // 初始化获取订单列表
          consultReqService().queryListData();
          consultReqService().initGetTypes();
      },
      pagerInitSetting:function(){
          // 分页cookie纪录
          var startPage = $cookieStore.get('paginationCookie');
          $scope.getpage=function(){
              $cookieStore.put('paginationCookie',$scope.paginationConf.currentPage);
          }
          // 分页参数初始化
          $scope.paginationConf = {currentPage: startPage, itemsPerPage: 15};

          // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
          $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', this.getList);
      },
      goto:function(url){
          $state.go(url);
      },
      open:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.opened = true;
      },
      endOpen:function(event){
          event.preventDefault();
          event.stopPropagation();
          $scope.endOpened = true;
      },
      filterByStatus :function(status) {
          $scope.status = status;
          $cookieStore.remove('consultListCookie');
          // $scope.searchValue = '';
          $cookieStore.put('consultListCookie',$scope.status);
          $scope.paginationConf.currentPage = 1;
          this.getList();
      },
      //  点击全选
      checkAll:function(){
          angular.forEach($scope.list, function(value, key) {
             value.checked = $scope.check_all;
          });
      },
      delete:function($index){
          consultReqService().deleteOrder($index,this);
      },
      initParam:function() {
          $scope.status= -1;
          $scope.excuteStatusType = '';
          $scope.consultStatusType = '';
          $scope.searchValue = '';
          $scope.startTime = '';
          $scope.endTime = '';
      },
      //  点击重置
      reset:function(){
          this.initParam();

          this.getList();
      },
      //  封装获取列表
      getList:function(){
        var _type = $scope.consultStatusType || {code: 0};
        var _operator = $scope.operator || {id: 0};
        var postDataParam = {
            searchValue: $scope.searchValue,
            ctype:_type.code,
            operator:_operator.id,
            status:$scope.status,
            startTime: Date.parse($scope.startTime),
            endTime: Date.parse($scope.endTime),
            currentPage: $scope.paginationConf.currentPage,
            itemsPerPage: $scope.paginationConf.itemsPerPage,
        };

        // console.log(postDataParam);

        consultReqService().queryListData(postDataParam);
      },
       // 点击查询列表
      search:function(){
          $scope.status = -1;
          this.getList();
      },
      //  导出咨询单
      export:function(){
          var id_arr = [];
          var id_str = '';
          angular.forEach($scope.list, function(value, key) {
            if(value.checked) {
              id_arr.push(value.id);
              id_str += value.id + ',';
            }
          });
          if(id_arr && id_arr.length <= 0) {
            dialog.tips({bodyText: '请选择要导出的投诉单！'});
            return;
          }

          dialog.tips({
            actionText: '确定' ,
            bodyText: '确定导出选中咨询单吗?',
            ok: function() {
              window.location.href = $scope.site_url+"/consult/export?ids="+id_str;
            }
          });
      },
      //  导出筛选咨询单
      exportAll:function(){
        var _type      = $scope.consultStatusType || {code: 0};
        var _operator = $scope.operator || {id: 0};
        var startTime = Date.parse($scope.startTime) || 0;
        var endTime = Date.parse($scope.endTime) || 0;
        var params = "searchValue=" + $scope.searchValue + "&status=" + $scope.status + "&operator=" + _operator.id + "&ctype=" + _type.code + "&startTime=" + startTime + "&endTime=" + endTime;
        dialog.tips({
          actionText: '确定' ,
          bodyText: '确定导出筛选的咨询单吗?',
          ok: function() {
            window.location.href = $scope.site_url+"/consult/export?"+params;
          }
        });
      }
  }

  // 初始化controll的一些参数
  common.initCtrl();

  $scope.func = common;

}])
.filter("filterStatus",function(){
    return function(code,arr){
          // console.log("---:"+arr[0].msg);
          var msg="未知"
          angular.forEach(arr, function(item, key) {
                if(code==item.code){
                    msg=item.msg;
                }
          });
          return msg;
    }
})
