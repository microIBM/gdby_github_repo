'use strict';

angular.module('dachuwang')
  .controller('AcdetailController', ['$scope','$state','$modal','rpc','daChuDialog','Lightbox', function($scope,$state,$modal,rpc, dialog, Lightbox) {
    $scope.func = {
      showGraph : function() {
        var images = [{url : $scope.billing_info.payment_evidence}];
        Lightbox.openModal(images, 0);
      },
      showDetails : function() {
        var modalInstance = $modal.open({
          animation : true,
          templateUrl : 'app/modules/cdetail/showdetails.html',
          controller : 'showDetailsController',
          resolve : {
            account_id : function() {
              return parseInt($scope.billing_info.id);
            }
          }
        });
      },
      getStatusClass : function(status, expire_status) {
        if(parseInt(expire_status) === 1) {
          return 'label-default';
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
      getStatus : function(status, expire_status) {
        if(parseInt(expire_status) === 1) {
          return  '逾期未付';
        }
        return status;
      },
      getLaterDate : function(time,later) {
        if(!(later>1)) {
          later = 1;
        }
        var timestamps = new Date(time).valueOf();
        var date = new Date(timestamps+86400000*later);
        return date.getFullYear()+'-'+(date.getMonth()+1)+'-'+date.getDate();
      }
    };
    function init() {
      $scope.isLoading = true;
      rpc.load('accheck/view', 'POST', {id: parseInt($state.params.account_id)})
        .then(function(data) {
          $scope.billing_info = data.billing_info;
          $scope.customer_info = data.customer_info;
          $scope.isLoading = false;
        }, function(err) {
          dialog.alert(err);
        });
    }
    init();
  }]);
