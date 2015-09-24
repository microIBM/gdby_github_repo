'use strict'
angular.module('hop').controller('UserEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams', function($location, dialog, req, $scope, $stateParams){
  // 初始化，获取客户编辑相关数据
  var init = function() {
    $scope.id = $stateParams.id;
    var postData = {id : $scope.id};
    req.getdata('user/edit_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.dept_list = data.dept_list;
        $scope.role_list = data.role_list;
        $scope.province_list = data.province_list;

        angular.forEach($scope.dept_list, function(v){
          if(v.id == data.info.dept_id) {
            $scope.data.dept = v;
          }
        });
        angular.forEach($scope.role_list, function(v){
          if(v.id == data.info.role_id) {
            $scope.data.role = v;
          }
        });
        angular.forEach($scope.province_list, function(v){
          if(v.id == data.info.province_id) {
            $scope.data.province = v;
          }
        });
      }
    }, postData);
  };

  // 修改用户信息
  $scope.edit = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }

    var postData = {
      id: $scope.data.id,
      deptId: $scope.data.dept.id,
      roleId: $scope.data.role.id,
      name: $scope.data.name,
      mobile: $scope.data.mobile,
      provinceId: $scope.data.province.id,
      address: $scope.data.address
    };

    req.getdata('/user/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改用户成功！'});
        req.redirect('/user/list');
      } else {
        dialog.tips({bodyText:'修改用户失败,' + data.msg});
      }
    }, postData);
  };

  // 加载用户信息
  init();
}]);
