angular.
  module('hop').
  controller('PrintController',['$location', 'dialog', 'req', '$scope','$http', '$timeout', 'distListInfo', function($location, dialog, req, $scope, $http, $timeout, distListInfo){
  // 获取通过resolve获取到的配送单列表
    $scope.list = distListInfo.list;

    //打印地图部分
    $timeout(function(){
      // 执行时地图页面上的地图图片未加载完成，暂时取消自动打印
      // window.print();
    });

    //打印地图
    var printext = {
      show:'打印地图',
      hide:'隐藏地图'
    }
    $scope.togglemap = true;
    $scope.text = printext.hide;
    $scope.printmap = function(){
      if($scope.togglemap == false){
        $scope.text = printext.hide;
        $scope.togglemap = true;
      }else
      {
        $scope.text = printext.show;
        $scope.togglemap = false;
      }

    }
}]);
