'use strict';
angular
.module('hop')
.controller('CouponRuleAddCtrl', ['$scope', 'req', '$upload', 'daChuLocal', 'dialog' ,function($scope, req, $upload, daChuLocal , dialog) {
  $scope.title = '规则生成';
  var setDefault = function() {
    req.getdata('coupon_rules/input_options', 'GET' , function(data) {
      $scope.ruleTypes = data.list.rules_type;
      $scope.ruleType = $scope.ruleTypes[0];
    });
  }
  setDefault();
  // 保存
  $scope.add = function(form) {
    if(form.$invalid ){
      dialog.tips({
        bodyText : '请填写完整信息！'
      })
      return ;
    }
    if($scope.ruleType.id == 2) {
      $scope.requireAmount = 0;
    }
    var postData = {
      title : $scope.name,
      ruleType : $scope.ruleType.id,
      requireAmount : $scope.requireAmount,
      minusAmount : $scope.minusAmount
    };
    req.getdata('coupon_rules/create', 'POST', function(data) {
      alert(data.msg);
    }, postData)
  }
 }]);
