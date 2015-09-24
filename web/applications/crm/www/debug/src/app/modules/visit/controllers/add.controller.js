'use strict';
// create by liwei 2015/8/21
angular
  .module('dachuwang')
  // 用户编辑
  .controller('VisitAddCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state', '$filter', 'daChuLocal','Analysis', function($scope, req, $cookieStore, dialog, $state, $filter, daChuLocal, Analysis) {

    var start_time = new Date().valueOf();
    var common = {
      init: function() {
        this.param();
        this.scope();
      },
      param: function() {
        $scope.status = { 
           datePickOpen : false
        }

        $scope.dateOptions = {
          formatYear: 'yy',
          startingDay: 1
        };

        // tab 参数
        $scope.tabList = [{
          id: 0,
          name: "添加拜访计划",
          active: true
        }, {
          id: 1,
          name: "添加拜访记录",
          active: false
        }];

        // 拆分参数
        var userParam = decodeURIComponent($state.params.shop).split('-');
        $scope.vm = {
          shopName: userParam[0] || "选择私海客户",
          userID: userParam[1],
          selectDate: ""
        }

        // 获取缓存中的tab数据
        var model = daChuLocal.get("visit_add_customer");
        if (model == 1 ) {
          $scope.tabList[0].active = false;
          $scope.tabList[1].active = true;
          common.setChangeDate(model);
          daChuLocal.remove("visit_add_customer");
        }

        // 默认加载第一项
        $scope.showItem = model == 1 ? 1 : 0;

        // 获取缓存中的tab数据
        var modelCacheDate = daChuLocal.get("visit_add_date");
        if ($scope.showItem == 0 && modelCacheDate!=null) {
           $scope.vm.selectDate = modelCacheDate.visitDate;
           daChuLocal.remove("visit_add_date");
        }
        
      },
      alert: function(title) {
        dialog.tips({
            bodyText: title,
            actionText: "确定",
            ok: function() {
              return;
            }
        });
        return;
      },
      request: {
        create: function() {
          // 日期转换
          var date = $filter("date")($scope.vm.selectDate, "yyyy-MM-dd HH:mm");
          
          if (date == "") {
            common.alert("请先选择添加拜访日期!")
          } else if ($state.params.shop == "") {
            common.alert("请先选择拜访的私海客户!")
          }

          date = (+new Date(date)) / 1000;

          // 获取参数
          var userParam = decodeURIComponent($state.params.shop);

          if ($state.params.shop.indexOf('-') > -1) {
            var userSplit = userParam.split('-');
            var postData = {
              type: $scope.showItem,
              visit_date: date,
              user_id: userSplit[1],
              is_potential: parseInt(userSplit[2])
            }

            // 创建拜访
            req.load("customer_visit/create", "post", postData).then(function(data) {
              if (data.status == 0) {
                $state.go("page.visit");
              }
            });
          }
        }
      },
      setChangeDate: function(model) {
        if (model === 0) {
            $scope.vm.selectDate = "";
            $scope.status.datePickOpen = false;
        } else if (model === 1) {
            var date = new Date();
            $scope.vm.selectDate = $filter("date")(date, "yyyy-MM-dd");
        }
      },
      scope: function() {
        // 打开日历
        $scope.openDate = function(eve) {
            setTimeout(function() {
              $scope.status.datePickOpen = true;
              $scope.$apply();
            }, 100);

        }

        // 添加拜访
        $scope.create = function() {

          // 日期转换
          var date = $filter("date")($scope.vm.selectDate, "yyyy-MM-dd HH:mm");

          var curDate = $filter("date")(new Date(), "yyyy-MM-dd");

          if (date == "") {
              common.alert("请先选择添加拜访日期!")
              return;
          } else if ($state.params.shop == "") {
              common.alert("请先选择拜访的私海客户!")
              return;
          }

          if(date<curDate){
              common.alert("拜访计划不能小于当天日期");
              return;
          }

          if ($scope.showItem == 0) {
            var end_time = new Date().valueOf();
            Analysis.send('客户拜访', {kind:'添加拜访'}, end_time-start_time);
            common.request.create();
          } else {
            var userParam = decodeURIComponent($state.params.shop).split('-');
            daChuLocal.set("visit_add_date", {
                visitDate:date,
                shopName:userParam[0]
            });

            $state.go("page.visit-customer", {
              uid: parseInt(userParam[1]),
              vid: 0,
              pid: parseInt(userParam[2]) 
            });
            
          }
        }

        // 视图跳转
        $scope.statePath = function() {
          if ($scope.showItem == 1) {
            daChuLocal.set("visit_add_customer", $scope.showItem);
            
          } else {
            daChuLocal.remove("visit_add_customer");
            if($scope.vm.selectDate!=null)
               daChuLocal.set("visit_add_date",{visitDate:$scope.vm.selectDate});  
          }
          $state.go("page.visit-add-customer");
        }

        // tab 切换逻辑
        $scope.tabSwitch = function(item) {
          Analysis.send('客户拜访',{kind:item.name});
          common.setChangeDate(item.id);
          $scope.tabList.filter(function(m) {
            m.active = false;
            if (item == m) {
              m.active = true;
              $scope.showItem = m.id;
            }
          })
          daChuLocal.remove("visit_add_customer");
        }
      }
    }

    common.init();

  }]);
