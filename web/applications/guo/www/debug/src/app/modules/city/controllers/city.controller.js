'use strict';
// 切换城市
angular.module('dachuwang')
.controller('CityController', ['$scope', '$state', '$cookieStore', 'daChuLocal', 'rpc', 'daChuTimer', 'posService', 'userAuthService', function($scope, $state, $cookieStore, daChuLocal, rpc, timeService, posService, userAuthService) {
  var setDefault = function() {
    var data = daChuLocal.get('localCity')  || {};
    if(typeof data.list === 'undefined' || !timeService.compare(data.currentTime)) {
      // 检测缓存
      rpc.load('/location/city', 'GET').then(function(data) {

      $scope.cities = data.list;
        var localCity = {currentTime: timeService.getNow(), list: data.list};
        daChuLocal.set('localCity', localCity);
      });
    } else {
      $scope.cities = data.list;

    }
    // 默认一样的则不能点击标红
  }
  setDefault();
  // 选择城市
  $scope.citySelect = function(index) {
    // 当前位置
    // 若登录不允许出现选择
    if(!userAuthService.isLogined()) {
      daChuLocal.remove('cateArr');
      posService.setInfo($scope.cities[index].id,$scope.cities[index].name);
      daChuLocal.remove('packaged_cate');
    } else {

      daChuLocal.remove('packaged_cate');
      alert("您的地理位置不能修改")
    }
    $state.go('page.home');
  }
}]);
