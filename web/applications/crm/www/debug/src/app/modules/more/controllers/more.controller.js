'use strict';

angular.
  module('dachuwang').
  controller('MoreController', ['$scope', '$window', 'daChuDialog','Analysis', function($scope, $window, dialog, Analysis) {
    $scope.func = {};
    $scope.func.manual = function() {
      Analysis.send('更多-用户手册');
      dialog.alert('正在开发中...');
    }
    $scope.func.map = function() {
      Analysis.send('更多-离线地图');
      if(!$window.jsInterface || !$window.jsInterface.offlineMapManager) {
        dialog.alert('请安装安卓客户端使用此功能');
      } else {
        $window.jsInterface.offlineMapManager();
      }
    }
    $scope.func.refresh = function() {
      Analysis.send('更多-检查更新');
      $window.location.reload(true);
    }
    $scope.func.userCenter = function() {
      Analysis.send('更多-个人中心');
    }
}]);
