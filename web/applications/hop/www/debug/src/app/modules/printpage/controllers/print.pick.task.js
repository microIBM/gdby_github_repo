angular.
  module('hop').
  controller('PrintPickTaskController',['$location', 'dialog', 'req', '$scope','$http', '$timeout', 'pickTaskListInfo', function($location, dialog, req, $scope, $http, $timeout, pickTaskListInfo){
  // 获取通过resolve获取到的配送单列表
    $scope.list = pickTaskListInfo.list;

    //打印地图部分
    $timeout(function(){
      // 执行时地图页面上的地图图片未加载完成，暂时取消自动打印
      // window.print();
    });

}]);
