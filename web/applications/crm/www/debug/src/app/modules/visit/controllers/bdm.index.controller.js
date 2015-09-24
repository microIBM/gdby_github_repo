'use strict';
// create by liwei 2015/8/21
angular
  .module('dachuwang')
  // 用户编辑
  .controller('BdmIndexCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state', 'Analysis', function($scope, req, $cookieStore, daChuDialog, $state, Analysis) {
    var common = {
      init: function() {
        this.param();
        this.request.list();
        this.scope();
      },
      param: function() {
        $scope.tabList = [{
          id: 0,
          name: "今日",
          active: true
        }, {
          id: 1,
          name: "本周",
          active: false
        }, {
          id: 2,
          name: "本月",
          active: false
        }];

        $scope.showItem = 1;
      },
      request: {
        list: function() {
          req.load("customer_visit/statistics", "post", {
            "date_type": $scope.showItem
          }).then(function(data) {
            $scope.list = data.list;
          });
        }
      },
      scope: function() {
        $scope.tabSwitch = function(item) {
          Analysis.send('客户拜访bdm界面',{kind:item.name});
          $scope.tabList.filter(function(m) {
            m.active = false;
            if (item == m) {
              m.active = true;
              $scope.showItem = m.id;
            }
          })
          common.request.list();
        }

        $scope.statePath=function(item){
           $state.go("page.visit-bd",{bd:item.bd_id})
        }
      }
    }

    common.init();

  }]);
