'use strict'
angular.module('hop').controller('PrivilegeCreateCtrl',['$location', 'dialog',  'req', '$scope', function($location, dialog, req, $scope){
  // 初始化
  var init = function() {
    req.getdata('privilege/create_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.list = data.list;
      }
    });
  }

  // 添加权限信息
  $scope.create = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }
    var parentId = 0;
    if($scope.data.parent) {
      parentId = $scope.data.parent.id;
    }
    var postData = {
      'name': $scope.data.name,
      'parentId': parentId, 
      'module': $scope.data.module,
      'resource': $scope.data.resource,
      'operation': $scope.data.operation,
    };
    req.getdata('privilege/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加权限成功！'});
        req.redirect('/privilege/list');
      } else {
        dialog.tips({bodyText:'添加权限失败。'});
      }
    }, postData);
  };

  // 加载信息
  init();
}]);
