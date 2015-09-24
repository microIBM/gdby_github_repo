'use strict';

angular
  .module('hop')
  .controller('CustomerEditLineCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams) {
  $scope.data = '';
  $scope.customer_id = $stateParams.id;
  var getInfo = function() {
    req.getdata('customer/edit_line_input', 'POST', function(data){
      if(data.status == 0) {
        // 初始化数据
        $scope.data = data.info;
        $scope.lines = data.lines;

        angular.forEach($scope.lines, function(v){
          if(v.id == data.info.line_id) {
            $scope.data.line = v;
          }
        });
      }
    },{id: $scope.customer_id});
  };
  getInfo();

  $scope.back = function() {
    history.go(-1);
  };
  $scope.dialog = dialog.tips;
  $scope.editLine = function() {
    // 判断表单是否填写完整
    if(!$scope.data.line) {
      alert('请选择配送线路');
      return;
    }

    var postData = {
      id : $scope.data.id,
      line_id : $scope.data.line.id,
    };
    $scope.dialog({
      bodyText:'确定修改配送线路为'+$scope.data.line.name+'吗？',
      ok: function() {
        req.getdata('customer/edit_line', 'POST', function() {
          // 更新配送线路
          $scope.dialog({
            bodyText: '配送线路修改成功',
            ok: function() {
              req.redirect('/customer/list_line');
            },
            actionText:'确定',
            closeText:'取消'
          });
        }, postData);
      },
      actionText:'确定',
      closeText:'取消'
    });
  };
}]);
