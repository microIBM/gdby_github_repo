'use strict';
angular.module('hop').controller('LineCreateCtrl',['$location', 'dialog',  'req', '$scope', function($location, dialog, req, $scope){
  // 初始化，获取添加线路相关数据
  var init = function() {
    req.getdata('line/create_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.provinces = data.list;
        $scope.warehouses = data.warehouses;
        //$scope.sites = data.sites;
      }
    });
  }

  // 添加线路
  $scope.create = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }
    var postData = {
      name: $scope.data.name,
      fullName: $scope.data.full_name,
      description: $scope.data.description,
      locationId: $scope.data.province.id,
      warehouseId: $scope.data.warehouse.id,
      warehouseName: $scope.data.warehouse.name,
      //siteSrc: $scope.data.site.id,
    };
    req.getdata('line/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加线路成功！'});
        req.redirect('/line/list');
      } else {
        dialog.tips({bodyText:'添加线路失败。'});
      }
    }, postData);
  };

  // 加载信息
  init();
}]);
