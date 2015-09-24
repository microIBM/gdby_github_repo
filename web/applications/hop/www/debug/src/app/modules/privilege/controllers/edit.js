'use strict'
angular.module('hop').controller('PrivilegeEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams', function($location, dialog, req, $scope, $stateParams){
  // 初始化，获取添加用户相关数据
  var init = function() {
    $scope.id = $stateParams.id;
    var postData = {id : $scope.id};
    req.getdata('privilege/edit_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.list = data.list;
        angular.forEach($scope.list, function(v){
          if(v.id == data.info.parent_id) {
            $scope.data.parent = v;
          }
        });
      }
    }, postData);
  }

  // 修改权限信息
  $scope.edit = function() {
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
      'id': $scope.data.id,
      'name': $scope.data.name,
      'parentId': parentId, 
      'module': $scope.data.module,
      'resource': $scope.data.resource,
      'operation': $scope.data.operation,
    };

    req.getdata('privilege/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改权限成功！'});
        req.redirect('/privilege/list');
      } else {
        dialog.tips({bodyText:'修改权限失败。'});
      }
    }, postData);
  };

  // 加载信息
  init();
}]);
