'use strict';
// 大厨dialog
angular
  .module('dachuwang')
  .factory('daChuDialog',['$modal', '$window', function($modal, $window) {
    var dialog = function(config, modal_config) {
      var _config = {
        headerText:'提示信息',
        bodyText: '设置成功',
        closeText: '关闭'
      };
      var _modal_config = {
        templateUrl: 'myModalContent.html'
      };
      angular.extend(_modal_config, modal_config);
      angular.extend(_config, config);
      var modalInstance = $modal.open({
        templateUrl: _modal_config.templateUrl,
        controller: 'ModalInstanceCtrl',
        resolve: {
          items: function () {
            return _config;
          }
        }
      });
    };
    var alert = function(msg) {
      if(!$window.jsInterface) {
        $window.alert(msg);
      } else if($window.jsInterface && $window.jsInterface.toast) {
        $window.jsInterface.toast(msg);
      }
    };
    return {
      tips:dialog,
      alert:alert
    };
}]);

