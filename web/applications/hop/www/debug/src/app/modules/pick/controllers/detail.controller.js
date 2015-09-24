'use strict';
angular
.module('hop')
.controller('PickTaskDetailCtrl', ['$scope', '$stateParams', 'req', 'dialog', function($scope, $stateParams, req, dialog) {
 var getList = function() {
    var postData = {
      pick_task_id: $stateParams.task_id
    };
    req.getdata('wave/pick_task_info', 'POST', function(data) {
      $scope.info = data.info;
      $scope.skuList = data.sku_list;
    }, postData);
  }
  getList();

  // 分拣完成
  $scope.finishTask = function() {
    $scope.clicked = true;
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定完成分拣任务？',
      ok: function() {
        req.getdata('wave/finish_task', 'POST', function(data) {
          if(parseInt(data.status) === 0) {
            $scope.clicked = true;
            alert('分拣完成');
            req.redirect('/pick');
          } else {
            $scope.clicked = false;
            console.log(data);
            alert('分拣失败');
          }
        }, {pick_task_id: $scope.info.id});
      },
      close: function() {
        $scope.clicked = false;
      }
    });
  }
}]);
