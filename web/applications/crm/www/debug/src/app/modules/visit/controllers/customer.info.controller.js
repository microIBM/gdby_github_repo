'use strict';
// create by liwei 2015/8/21
angular
  .module('dachuwang')
  // 用户编辑
  .controller('VisitCustomerInfoCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state', function($scope, req, $cookieStore, daChuDialog, $state) {
    var common = {
      init: function() {
        this.param();
        this.request.list();
        this.scope();
      },
      param: function() {

      },
      request: {
        list: function() {
          req.load("customer_visit/view", "post", {
            visit_id: $state.params.vid
          }).then(function(data) {
            $scope.info = data.info;
            $scope.focusCategories = data.info.focus_categories;
            $scope.suggestions = data.info.suggestion_type;
          });
        }
      },
      scope: function() {

      }
    }

    common.init();

  }]);