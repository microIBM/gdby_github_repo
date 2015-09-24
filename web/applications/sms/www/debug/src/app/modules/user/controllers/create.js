'use strict'
angular.module('hop').controller('UserCreateCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams', function($location, dialog, req, $scope, $routeParams){
  // 初始化，获取客户编辑相关数据
  var init = function() {
    req.getdata('/user/create_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.dept_list = data.dept_list;
        $scope.role_list = data.role_list;
        $scope.province_list = data.province_list;
      }
    });
  }

  // 添加用户信息
  $scope.create = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }

    var postData = {
      deptId: $scope.data.dept.id,
      roleId: $scope.data.role.id,
      name: $scope.data.name,
      mobile: $scope.data.mobile,
      provinceId : $scope.data.province.id,
      address : $scope.data.address,
    };

    req.getdata('/user/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加用户成功！'});
        req.redirect('/user/list');
      } else {
        dialog.tips({bodyText:'添加用户失败。'});
      }
    }, postData);
  };

  // 加载用户信息
  init();
}]);
