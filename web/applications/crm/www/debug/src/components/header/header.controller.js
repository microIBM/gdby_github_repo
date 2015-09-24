'use strict';

angular
  .module('dachuwang')
  .controller('headerCtrl', ['$scope', '$state', '$window', 'daChuLocal', function($scope, $state, $window, daChuLocal) {
    $scope.$state = $state;
    var roleId = parseInt(daChuLocal.get('role_id'));
    $scope.roleID = roleId;
    $scope.showMap = function() {
      // $window.jsInterface.toast('version:1.6');
      if(!$window.jsInterface || !$window.jsInterface.cloudMap){
        $window.alert('请使用客户端操作！');
      } else {
        $window.jsInterface.cloudMap(roleId);
      }
    };
    $scope.addCustomer = function() {
        $state.go('page.custorm');
    };
    $scope.addVisit = function() {
        $state.go('page.visit-add');
    };
  }]);
