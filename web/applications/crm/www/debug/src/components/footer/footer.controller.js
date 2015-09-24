'use strict';

angular
  .module('dachuwang')
  .controller('footerCtrl', ['$scope', '$state', 'daChuLocal', '$cookieStore', 'daChuDialog','Analysis', function($scope, $state, daChuLocal,$cookieStore,dialog, Analysis) {
    var tabList = [
      {
        name : '业绩统计',
        glyphicon : 'glyphicon-home',
        href : 'page.homemanage',
        status : 'home'
      },
      {
        name : '客户管理',
        glyphicon : 'glyphicon-cloud',
        href : 'page.manage',
        status : 'crm',
        showBadge : true
      },
      {
        name : '拜访',
        glyphicon : 'glyphicon-calendar',
        href : 'page.visit',
        status : 'visit',
      },
      {
        name : '更多',
        glyphicon : 'glyphicon-option-horizontal',
        status : 'more',
        href : 'page.more'
      }
    ];
    var lastTime = null;
    $scope.$on('pageChanged',function(event, data) {
      if([0,1,2,3].indexOf(data.tabIndex) !== -1) {
        changeTab(data.tabIndex);
      }
    });

    $scope.tabList = tabList;
    $scope.collen = 12/$scope.tabList.length;

    $scope.showType = (function(index) {
      return $scope.tabList[index].status;
    })($state.current.data.tabIndex);
    function changeTab(index) {
      if($scope.showType === $scope.tabList[index].status) {
        return;
      }
      $scope.showType = $scope.tabList[index].status;
      if(index !== 1) {
        clearLocalData();
      }
    }
    function pageView(href) {
      if(!lastTime) {
        lastTime = new Date().getTime();
        return;
      }
      var name = (function() {
        var i;
        for(i=0; i<tabList.length; i++) {
          if(tabList[i].href === href) {
            return tabList[i].name + '模块';
          }
        }
        return null;
      })();
      if(!name) {
        return;
      }
      var thisTime = new Date().getTime();
      var duration = thisTime - lastTime;
      lastTime = thisTime;
      Analysis.send(name, null, duration);
      console.log(name + '  ' + duration);
    }
    function clearLocalData() {
      daChuLocal.remove('filter_sift');
      daChuLocal.remove('search_key');
      daChuLocal.remove('time_type');
    }
    $scope.changeState = function(pageName) {
      if($state.current.name === pageName) {
        return;
      }
      if($state.current.name === 'page.custorm') {
        dialog.tips({
          bodyText: '返回将不保存本次编辑的内容',
          actionText: "确定",
          ok: function() {
            pageView($state.current.name);
            $state.go(pageName);
          }
        });
      } else {
        pageView($state.current.name);
        $state.go(pageName);
      }
    }
  }]);
