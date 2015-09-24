'use strict';

angular.module('dachuwang')
  .controller('AccountCheckingController', ['$scope', '$state', 'rpc', 'pagination','daChuLocal','$modal','Analysis', 'daChuDialog',function($scope, $state, rpc, pagination, daChuLocal,$modal, Analysis, dialog) {
    $scope.isParent = true;
    function getLists(callback) {
      if(!$scope.isParent) {
        return;
      }
      function success(data) {
       var i,len = data.list.length;
       callback(len<$scope.itemsPerPage ? true : false);
       for(i=0; i<len; i++) {
        $scope.accountLists.push(data.list[i]);
       }
      }
      function error(error) {
        console.log(error);
        $scope.isParent = false;
        $scope.pagination.isProcessing = false;
      }
      var postData = {
        currentPage : $scope.pagination.page,
        itemsPerPage : $scope.itemsPerPage,
        customer_ids : [parseInt($state.params.uid)],
        status : (function() {
          var status = daChuLocal.get('ac_filter');
          return status ? status : null;
        })()
      };
      if($scope.sift_start_time && $scope.sift_end_time) {
        postData.start_time = $scope.sift_start_time;
        postData.end_time = $scope.sift_end_time;
      }
      rpc.load('accheck/lists','POST',postData)
        .then(success, error);
    }
    $scope.itemsPerPage = 10;
    $scope.accountLists = [];
    $scope.filter = {
      index : (function() {
        var index = daChuLocal.get('ac_filter');
        if(index !== null) {
          return index;
        }
        return 0;
      })(),
      changeIndex : function(index) {
        $scope.filter.index = index;
        daChuLocal.set('ac_filter',index);
        $scope.accountLists = [];
        $scope.pagination.init(getLists);
        $scope.pagination.nextPage();
      }
    };
    $scope.pagination = pagination;
    $scope.pagination.init(getLists);
    $scope.pagination.nextPage();
    $scope.func = {
      filterStatus : function(status, expire_status) {
        if(parseInt(expire_status) === 1) {
          return '逾期未付';
        }
        return status;
      },
      goDetail : function(item) {
       $state.go('page.customerdetail.acdetail', {account_id : item.id});
      },
      getStatusClass : function(status, expire_status) {
        if(parseInt(expire_status) === 1) {
          return 'label-default';
        }
        if(!status) {
          status = 2;
        }
        status = parseInt(status);
        switch (status) {
          case 2 :
            return 'label-warning';
          case 3 :
            return 'label-info';
          case 4 :
            return 'label-danger';
          case 5 :
            return 'label-success';
        }
      },
      sift : function() {
        var startTime = new Date().getTime();
        var modalInstance = $modal.open({
          templateUrl: 'app/modules/cdetail/sift.html',
          animation: true,
          controller: 'acSiftController',
          size: 'sm'
        });
        modalInstance.result.then(function(data) {
          var endTime = new Date().getTime();
          Analysis.send('对账管理筛选',null,endTime-startTime);
          $scope.sift_start_time = data.start_time/1000;
          $scope.sift_end_time = data.end_time/1000;
          $scope.pagination.init(getLists);
          $scope.accountLists = [];
          $scope.pagination.nextPage();
        }, function(err) {
          $scope.sift_start_time = null;
          $scope.sift_end_time = null;
          $scope.pagination.init(getLists);
          $scope.accountLists = [];
          $scope.pagination.nextPage();
        });
      },
      getMore : function() {
        if($scope.pagination.isDone) {
          return;
        }
        $scope.pagination.nextPage();
      }
    };
  }]);
