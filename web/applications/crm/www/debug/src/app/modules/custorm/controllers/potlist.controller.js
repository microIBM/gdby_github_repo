'use strict';

angular
  .module('dachuwang')
  .controller('PotlistController',['$scope', '$state', '$stateParams', '$log', '$window', 'rpc', 'pagination', 'daChuDialog', function($scope, $state,  $stateParams, $log, $window, rpc, pagination, dialog) {
    $scope.list = [];
    function getlists(callback) {
      var itemsPerPage = 3;
      $log.debug(pagination.page);
      rpc.load('potential_customer/lists', 'POST', {itemsPerPage : itemsPerPage, currentPage : pagination.page, invite_id : $stateParams.invite_id}).then(function(data) {
          angular.forEach(data.list, function(value) {
            $scope.list.push(value);
          });
          if(data.list.length<itemsPerPage) {
            callback(true);
          } else {
            callback(false);
          }
        $scope.totalItems = data.total>0 ? data.total:0;
      });
    }
    $scope.pagination = pagination;
    $scope.pagination.init(getlists);
    
    $scope.openUser = function(id) {
      $state.go('page.openpot', {potid:id});
    }
    $scope.editUser = function(id) {
      $state.go('page.editpot', {potid:id});
    }
    $scope.deleteUser = function(id) {
      rpc.load('potential_customer/delete','POST',{id:id})
        .then(function(res) {
          if(res.status == 0) {
            dialog.alert('删除成功');
            $window.location.reload(true);
          } else {
            dialog.alert('删除失败 '+res.msg)
          }
        }, function(err) {
          dialog.alert('删除失败 '+err);
        });
    }
 }]);
