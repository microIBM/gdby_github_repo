'use strict'
angular.module('hop').controller('LineEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams',  function($location, dialog, req, $scope, $stateParams){
  // 初始化，获取修改线路相关数据
  var init = function() {
    $scope.id = $stateParams.id;
    var postData = {id : $scope.id};
    req.getdata('line/edit_input', 'POST', function(data) {
      if(data.status == 0) {
        $scope.provinces = data.list;
        $scope.warehouses = data.warehouses;
        $scope.data = data.info;
        //$scope.sites = data.sites;
        angular.forEach($scope.provinces, function(v){
          if(v.id == $scope.data.location_id) {
            $scope.data.province = v;
          }
        });
        angular.forEach($scope.warehouses, function(v){
          if(v.id == $scope.data.warehouse_id) {
            $scope.data.warehouse = v;
          }
        });
        /*
        angular.forEach($scope.sites, function(v) {
          if(v.id == $scope.data.site_src) {
            $scope.data.site = v;
          }
        });
        */
      }
    }, postData);
  }

  // 修改线路
  $scope.edit = function() {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }
    var postData = {
      id: $scope.data.id,
      name: $scope.data.name,
      fullName: $scope.data.full_name,
      description: $scope.data.description,
      locationId: $scope.data.province.id,
      warehouseId: $scope.data.warehouse.id,
      warehouseName: $scope.data.warehouse.name,
      //siteSrc: $scope.data.site.id,
    };
    req.getdata('line/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改线路成功！'});
        req.redirect('/line/list');
      } else {
        dialog.tips({bodyText:'修改线路失败。'});
      }
    }, postData);
  };

  // 加载信息
  init();
}]);
