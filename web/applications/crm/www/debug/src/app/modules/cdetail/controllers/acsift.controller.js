'use strict';

angular.module('dachuwang')
  .controller('acSiftController', ['$scope','$modalInstance','daChuDialog','daChuLocal', function($scope, $modalInstance,dialog,daChuLocal) {
    function init() {
      var start = daChuLocal.get('sift_start_time');
      if(start) {
        $scope.start_time = start;
      }
      var end = daChuLocal.get('sift_end_time');
      if(end) {
        $scope.end_time = end;
      }
    }
    function checkValidate() {
      if(!$scope.start_time || !$scope.end_time) {
        return false;
      }
      var start,end;
      start = new Date($scope.start_time).valueOf();
      end = new Date($scope.end_time).valueOf();
      if(start > end) {
        return false;
      }
      return true;
    }
    $scope.datepicker = {
      start : {
        open : false
      },
      end : {
        open : false
      },
      open : function(which, $event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.datepicker[which].open = true;
      }
    };
    $scope.func = {
      ensure : function() {
        if(!checkValidate()) {
          dialog.alert('起始时间不能晚于结束时间');
          return;
        }
        daChuLocal.set('sift_start_time',$scope.start_time);
        daChuLocal.set('sift_end_time',$scope.end_time);
        var start = new Date($scope.start_time).valueOf();
        var end = new Date($scope.end_time).valueOf();
        $modalInstance.close({
          start_time: start,
          end_time: end
        });
      },
      cancel : function() {
        daChuLocal.remove('sift_start_time');
        daChuLocal.remove('sift_end_time');
        $modalInstance.dismiss('cancel');
      }
    };
    init();
  }]);
