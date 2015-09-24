'use strict';
// create by liwei 2015/8/21
angular
  .module('dachuwang')
  // 用户编辑
  .controller('BdIndexCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog','$filter','$state','daChuLocal','pagination','Analysis', function($scope, req, $cookieStore, dialog, $filter, $state, daChuLocal, pagination, Analysis) {
    var common = {
      init: function() {
        this.param();
        this.initPage();
        this.request.getVisitCalender();
        this.scope();
      },
      initPage:function(){
        $scope.list = [];
        $scope.pagination = pagination;
        $scope.pagination.perPage = 6;
        $scope.pagination.init(common.request.list);
        //$scope.pagination.nextPage();
      },
      param: function() {
        $scope.showItem = 1;
        $scope.startTime = "";
        $scope.endTime ="";

        $scope.tabList = [{
          id: 1,
          name: "拜访列表",
          active: true
        }, {
          id: 2,
          name: "拜访日历",
          active: false
        }];

        $scope.bd = $state.params.bd==undefined ? null:$state.params.bd;
      },
      request: {
        list: function(callback) {
          var postData = {
              "currentPage": pagination.page,
              "itemsPerPage": $scope.pagination.perPage,
              "startTime": $scope.startTime,
              "endTime": $scope.endTime,
          }

          if($scope.bd!=undefined){
             postData.bd_id = $scope.bd;
          }

          req.load("customer_visit/lists", "post", postData).then(function(data) {
            $scope.list = $scope.list.concat(data.list);
            
            if(data.list.length < $scope.pagination.perPage) {
              callback(true);
            }else {
              callback(false);
            }

          });
        },
        delete: function(id) {
          req.load("customer_visit/del", "post", {
            visit_id: id
          }).then(function(data) {
             $state.reload();
          });
        },
        getVisitCalender:function(){
          req.load("customer_visit/calendar", "post", {bd_id:$scope.bd}).then(function(data) {
             $scope.calendarLists=data.list;
          });
        }
      },
      alert:function(title,callBack){
         dialog.tips({
              bodyText: title,
              actionText: "确定",
              ok: function() {
                  if(typeof callBack === "function")
                      callBack.apply();
              }
          });
         return false;
      },
      scope: function() {

        // TAB切换操作
        $scope.tabSwitch = function(item) {
          Analysis.send('客户拜访',{kind:item.name});
          $scope.tabList.filter(function(m) {
            m.active = false;
            if (item == m) {
              m.active = true;
              $scope.showItem = m.id;
            }
          })
          // 切换tab,重新加载拜访列表
          if(item.id==1){
            common.param();
            common.initPage();
          }
        }
        
        // 单元格回调函数
        var func=function(){
          Analysis.send('客户拜访',{kind:'日历日期'});
            var cellDate = this,
                currentItem = null;

            // 点击单元格日期,转成时间戳
            cellDate = $filter("date")(cellDate, "yyyy-MM-dd HH:mm");

            // 当天日期转成时间戳
            var todayDate = $filter("date")(new Date(), "yyyy-MM-dd");

            $scope.calendarLists.filter(function(item){
                var itemDate = $filter("date")(item.date, "yyyy-MM-dd HH:mm");
                if(itemDate == cellDate){
                   currentItem=item;
                }
            })

            if(currentItem == null){
                if($scope.bd!=null){
                  common.alert("该日无拜访记录");
                  return;
                }

                // 点击当天的逻辑
                if(cellDate > todayDate){
                   $state.go("page.visit-add");
                   return;
                }else if (cellDate == todayDate ) {
                   daChuLocal.set("visit_add_customer",1);
                   $state.go("page.visit-add");
                   return;
                }else if (cellDate < todayDate){
                   common.alert("该日无拜访记录");
                   return;
                }
            }else{
                  $scope.startTime=(+new Date(cellDate)) / 1000;
                  $scope.endTime=(+new Date(cellDate)) / 1000;
                  $scope.tabList[0].active=true;
                  $scope.tabList[1].active=false;
                  $scope.showItem=1;
                  common.initPage();
                  common.request.getVisitCalender();
            }
        }
        
        // 单元格点击事件
        $scope.cellClick=function(){
            return func;
        }

        // 删除拜访操作
        $scope.delete = function(visitId) {
          Analysis.send('客户拜访',{kind:'删除'});
           common.alert("确定要删除此条拜访记录吗？",function(){
              common.request.delete(visitId);
           });
        }
      }
    }
    $scope.analysis = {
      visit : function() {
        Analysis.send('客户拜访',{kind:'进店拜访'});
      },
      edit : function() {
        Analysis.send('客户拜访',{kind:'修改日期'});
      },
      read : function() {
        Analysis.send('客户拜访',{kind:'查看拜访记录'});
      }
    };

    common.init();

  }]);
