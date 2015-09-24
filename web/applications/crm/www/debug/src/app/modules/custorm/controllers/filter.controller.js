'use strict';

angular.module('dachuwang')
  .controller('FilterController', ['$scope','$modalInstance','$window','rpc', 'daChuDialog', 'daChuLocal', function($scope, $modalInstance, $window, rpc, dialog, daChuLocal) {
    var lines;
    $scope.order_lists = [{name:'注册时间',val:'created_time'},{name:'下单时间',val:'order_time'}];
    $scope.order = {
      by : null,
      way : null
    };
    $scope.property = {
      dimensions : [],
      order_type : [{name:'从未下单',value:0},{name:'有下单记录',value:1}]
    };
    $scope.sift = {};
    $scope.isloading = true;
    //初始化地区联动数据
    (function() {
      rpc.load('customer/create_input','GET').then(function(msg) {
        if(parseInt(msg.status) === 0) {
          $scope.property.provinces = (function(t) {
            var arr = [];
            var i,len = t.length;
            for(i=0; i<len; i++) {
              arr.push(getObjectProperties(t[i], ['id','name','path']));
            }
            return arr;
          })(msg.provinces);
          lines = msg.lines;
          // 锁定系统和地区
          var cityId = daChuLocal.get('city_id');
          if(!cityId){
            dialog.alert('登录超时，请重新登录');
            rpc.redirect('user/login');
            return;
          }
          angular.forEach($scope.property.provinces, function(v){
            if(v.id == cityId){
              $scope.sift.province = v;
            }
          });
          $scope.func.getLines();
          $scope.property.dimensions = msg.dimensions;
          $scope.property.shop_types = msg.shop_type;
          $scope.property.types = msg.types;

          // 加载之前的筛选条件
          var sift = daChuLocal.get('filter_sift');
          if(sift && sift.line && sift.line.id){
            angular.forEach($scope.property.lines, function(v) {
              if(v.id == sift.line.id){
                $scope.sift.line = v;
              }
            });
          }
          if(sift && sift.dimensions){
            angular.forEach($scope.property.dimensions, function(v) {
              if(v.value == sift.dimensions.value){
                $scope.sift.dimensions = v;
              }
            });
          }
          if(sift && sift.shop_type){
            angular.forEach($scope.property.shop_types, function(v) {
              if(v.id == sift.shop_type.id){
                $scope.sift.shop_type = v;
              }
            });
          }
          if(sift && sift.customer_type) {
            angular.forEach($scope.property.types, function(v) {
              if(v.value == sift.customer_type.value) {
                $scope.sift.customer_type = v;
              }
            });
          }
          if(sift && sift.order_type) {
            angular.forEach($scope.property.order_type, function(v) {
              if(v.value == sift.order_type.value) {
                $scope.sift.order_type = v;
              }
            });
          }
        }
      }, function(err) {
        dialog.alert(err);
      }).then(function() {
        $scope.isloading = false;
      });
    })();
    function getObjectProperties(target, arr) {
      if(target === null) {
        return null;
      }
      var obj = {};
      var i,len=arr.length;
      for(i=0; i<len; i++) {
        if(target[arr[i]]) {
          obj[arr[i]] = target[arr[i]];
        }
      }
      return obj;
    }
    /*
     * 判断一个对象是否为空
     * null返回true
     */
    function isEmptyObject(obj) {
      var i;
      for(i in obj) {
        return false;
      }
      return true;
    }
    //比较两个值的大小
    function intequal(a,b) {
      return parseInt(a)===parseInt(b) ? true:false;
    }
    $scope.func = {
      ensure : function() {
        var obj = {
          order : $scope.order,
          sift : $scope.sift
        };
        if(isEmptyObject(obj.sift)) {
          this.cancel();
          return false;
        }
        // 记录本次筛选条件
        daChuLocal.set('filter_sift', $scope.sift);
        $modalInstance.close(obj);
      },
      cancel : function() {
        daChuLocal.remove('filter_sift');
        daChuLocal.remove('time_type');
        $modalInstance.close(null);
      },
      /*
       * 获取路线，需要系统和城市同时选择后才能选择路线
       */
      getLines : function() {
        //如果没有选城市或者系统
        if(!$scope.sift.province) {
          return false;
        }
        $scope.property.lines = [];
        var i,len = lines.length;
        for(i=0; i<len; i++) {
          if(intequal(lines[i].location_id, $scope.sift.province.id)) {
            $scope.property.lines.push({id:lines[i].id, name:lines[i].name});
          }
        }
      },
      reset : function() {
        var province = $scope.sift.province;
        $scope.sift = {
          province : province
        };
        daChuLocal.remove('filter_sift');
        daChuLocal.remove('time_type');
      }
    };
  }]);
