'use strict'
angular.module('hop').controller('DepartmentCreateCtrl',['$location', 'dialog',  'req', '$scope', function($location, dialog, req, $scope){
  // 下拉列表框相关数据
  $scope.addr = {
    province: '',
  };

  // 初始化，获取添加部门相关数据
  var init = function() {
    req.getdata('department/create_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.departments = data.list;
      }
    });
  }

  // 修改部门信息
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
      name: $scope.data.dept_name,
      parentId: parentId,
      description: $scope.data.description,
    };

    req.getdata('department/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加部门成功！'});
        req.redirect('/department/list');
      } else {
        dialog.tips({bodyText:'添加部门失败。'});
      }
    }, postData);
  };

  // 加载信息
  init();
}]);
