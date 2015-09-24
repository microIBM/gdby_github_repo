'use strict';
// 规格属性列表
angular.module('hop')
.controller('PropertyCtrl', ['$scope', 'req', 'dialog', function($scope, req, daChuDialog) { 
  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  $scope.title = '规格列表';
  var getList = function() {
    var postData = {
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    // 获取规格信息
    req.getdata('property/lists', 'POST', function(data) {
      $scope.list = data.list;
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // 禁用或启用
  $scope.setStatus = function($index, status) {
    var id = $scope.list[$index].id;
    status = parseInt(status);
    if(status === 0 || status === 1) {
      $scope.list[$index].status = status;
      var url = 'property/del',
      tipsMsg = '禁用成功';
      if(status === 1 ) {
        url = 'property/reuse';
        tipsMsg = '启用成功';
      }
      //$scope.properties.splice($index, 1);
      req.getdata(url, 'POST', function(data) {
        daChuDialog.tips({bodyText: tipsMsg});
      }, {id:id});
    }
  };


}]);
