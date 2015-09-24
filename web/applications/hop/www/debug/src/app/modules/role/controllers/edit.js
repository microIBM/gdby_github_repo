'use strict'
angular.module('hop').controller('RoleEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams',  function($location, dialog, req, $scope, $stateParams){
  // 初始化，获取修改用户相关数据
  var init = function() {
    $scope.id = $stateParams.id;
    var postData = {id : $scope.id};
    req.getdata('role/edit_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.list = data.list;
        $scope.data = data.info;
      }
    }, postData);
  }

  // 修改角色
  $scope.edit = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }
    var pri_id = [];
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        pri_id.push(value.id);
      }
    });
    var postData = {
      id: $scope.data.id,
      name: $scope.data.name,
      dataset: $scope.data.dataset,
      pri_id: pri_id,
    };
    req.getdata('role/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改角色成功！'});
        req.redirect('/role/list');
      } else {
        dialog.tips({bodyText:'修改角色失败。'});
      }
    }, postData);
  };

  $scope.toggle = function($index) {
    var current = $scope.list[$index];
    angular.forEach($scope.list, function(value, key) {
      if(current.checked) {
        // 选中当前节点的所有上级节点
        if(value.level < current.level && current.path.indexOf(value.path) !== -1) {
          value.checked = true;
        } 
        // 选中当前节点的所有子节点
        if(value.level > current.level && value.path.indexOf(current.path) !== -1) {
          value.checked = true;
        } 
      }else{
        // 将当前节点的子节点取消选中
        if(value.level > current.level && value.path.indexOf(current.path) !== -1) {
          value.checked = false;
        } 
      }
    }); 
  }
  // 加载信息
  init();
}]);
