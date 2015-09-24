'use strict';

angular
  .module('hop')
  .controller('AbnormalOrderEditCtrl', ['dialog', '$location', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {
  $scope.id = $stateParams.id;
  $scope.contents = [];
  var getInfo = function() {
    req.getdata('abnormal_order/edit_input', 'POST', function(data){
      if(data.status == 0) {
        $scope.order = data.order;
        $scope.data = data.info;
        $scope.otypes = data.otypes;
        $scope.statusList = data.statuses;
        angular.forEach($scope.otypes, function(value, key) {
          if(value.val == $scope.data.otype){
            $scope.data.otype = value;
          }
        });
        angular.forEach($scope.statusList, function(value, key) {
          if(value.code == $scope.data.status){
            $scope.data.status = value;
          }
        });
        angular.forEach(data.info.contents, function(value, key) {
          angular.forEach(data.order.detail, function(v, k) {
            if(v.product_id == value.product_id) {
              $scope.contents.push({product: v});
            }
          });
        });
      }
    },{id: $scope.id});
  };
  getInfo();

  $scope.back = function() {
    history.go(-1);
  };
  // 增加异常单内容
  $scope.addContent = function() {
    if($scope.contents.length >= $scope.order.detail.length) {
      alert('异常单内容产品数量不能超过' + $scope.order.detail.length + '个');
      return;
    }
    $scope.contents.push({product:{}});
  }
  // 删除异常单内容
  $scope.removeContent = function(index) {
    $scope.contents.splice(index, 1);
  }
  $scope.changeProduct = function(index) {
    var item = $scope.contents[index];
    var selProduct = item.product || {id:0};
    // 判断是否重复
    angular.forEach($scope.contents, function(v, k) {
      if(k != index && v.product.id == selProduct.id) {
        alert('异常单内容中不能选择重复的产品！请重新选择');
        $scope.contents[index] = {product:{}, quantity:1, sumPrice:0};
        return;
      }
    });
  }


  // 修改异常单
  $scope.edit = function(order_number) {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }

    var error = false;
    // 判断是否选择投诉单内容
    angular.forEach($scope.contents, function(v, k) {
      if(!v || !v.product || !v.product.id) {
        error = true;
        return;
      }
    });

    if(error) {
      alert('请选择投诉单内容！');
      return;
    }

    var postData = {
      id: $scope.id,
      otype: $scope.data.otype.val,
      contents: $scope.contents,
      reason : $scope.data.reason,
      solution : $scope.data.solution,
      suggest : $scope.data.suggest,
      status: $scope.data.status.code,
    };
    req.getdata('/abnormal_order/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改异常单成功！'});
        req.redirect('/abnormal_order/list');
      } else {
        dialog.tips({bodyText:'修改异常单失败。'});
      }
    }, postData, true);

  };
  $scope.dialog = dialog.tips;
}]);
