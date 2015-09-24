'use strict'
// 规格属性编辑
angular
.module('hop')
.controller('PropertyEditCtrl', ['$scope', 'req', 'daChuDialog', 'daChuLocal', 'daChuTimer', function($scope, req, daChuDialog, daChuLocal, daChuTimer) {

 var id = 0;
  if(id){
    req.getdata('property/prop_single', 'POST', function(data) {

      if(data.info.options.length > 0) {
        $scope.property_values = [];
      }
      $scope.saveData.id = data.info.id;
      $scope.saveData.unit = data.info.unit;
      $scope.saveData.name = data.info.name;
      angular.forEach($scope.top_category, function(v) {
        if(v.id == data.info.top_category.id) {
          $scope.top = v;
        }
      })
      $scope.getSecond($scope.top.id, data.info.second_category.id);
      $scope.getroduct(data.info.second_category.id, data.info.self.id);
      getTypeList(data.info.type);  

      angular.forEach($scope.requires, function(v) {
        if(v.val == data.info.isrequired) {
          $scope.property_required = v;
        }
      })

      angular.forEach(data.info.options, function(v, i){

        if(i == 0) {
          var prop =  {
            name: '添加',
            value: v.name,
            icon: 'glyphicon-plus',
            cls: 'btn-info',
            clk: 'add'
          };
          $scope.property_values.push(prop);
        } else {
          $scope.add(i,v.name);
        }
      })
    }, {id:id});
  }
}]);
