'use strict';

angular
.module('hop')
.controller('CateMapEditCtrl', ['$scope', '$stateParams', 'req', 'dialog', function($scope, $stateParams, req, dialog) {
  $scope.title = '编辑映射分类';
  $scope.cateInfo = {
    weight: 0,
    name: ''
  };

  var getInfo = function() {
    req.getdata('catemap/info', 'POST', function(data) {
      $scope.initTop = data.list.top;
      $scope.initSecond = data.list.second;
      $scope.initTopChild = data.list.top_child;
      $scope.initSecondChild = data.list.second_child;
      $scope.sites = data.sites;
      $scope.locations = data.location;
      $scope.info = data.info;
      $scope.customer_type_options = data.customer_type_options;

      // 如果当前用户类型存在， 那么设为默认用户类型
      if(data.info.customer_type){
        angular.forEach( $scope.customer_type_options, function(v) {
          if(v.value == data.info.customer_type) {
            $scope.default_type =v ; 
          }
        });
      }else {
        $scope.default_type = data.customer_type_options.length != 0  ?  data.customer_type_options[0] : false;
      }
      initCate();
    }, {id: $stateParams.id});
  }
  // 获取编辑信息
  getInfo();
  var initCate = function() {
    $scope.newTop = [];
    $scope.topIds = [];

    angular.forEach($scope.initTop, function(v) {
      $scope.newTop.push(v);
      $scope.topIds.push(v.id);
    })
    angular.forEach($scope.initSecond, function(v) {
      $scope.newTop.push(v);
    })
    setDefault();
  }
  // 设置默认的显示
  var setDefault = function() {
    $scope.cateInfo = {
      weight: parseInt($scope.info.weight),
      name: $scope.info.name,
      site: null,
      category: null,
      childCate: null,
    };
    angular.forEach($scope.sites, function(v) {
      if(parseInt(v.id) == parseInt($scope.info.site_id)) {
        $scope.cateInfo.site = v;
      }
    })
    angular.forEach($scope.locations, function(v) {
      if(v.id == $scope.info.location_id) {
        $scope.cateInfo.location = v;
      }
    });

    var upid = parseInt($scope.info.origin_upid),
    id = parseInt($scope.info.origin_id);
    if(upid) {
      angular.forEach($scope.newTop, function(v) {
        if(upid === parseInt(v.id)) {
          $scope.cateInfo.category = v;
        }
      })
    } else {
      angular.forEach($scope.newTop, function(v) {
        if(id === parseInt(v.id)) {
          $scope.cateInfo.category = v;
        }
      })
    }
    $scope.getSecond();
    if(upid) {
      angular.forEach($scope.newSecond, function(v) {
        if(id == parseInt(v.id)) {
          $scope.cateInfo.childCate = v;
        }
      })
    }
  }
  // 获取二级分类
  $scope.getSecond = function() {
    $scope.cateInfo.childCate = [];
    if($scope.cateInfo.category != undefined ) {
      var id = $scope.cateInfo.category.id;
      var second = [];
      angular.forEach($scope.topIds, function(v) {
        if(v == id) {
          second = $scope.initTopChild;
        }
      })
      if(second.length > 0) {
        $scope.newSecond = $scope.initTopChild;
      } else {
        $scope.newSecond = $scope.initSecondChild;
      }
    }
  }
  $scope.getThird = function() {}
  // 添加
  $scope.cateAdd = function() {
    var initUpid = 0, id, initName;
    if($scope.cateInfo.childCate == undefined) {
      id = $scope.cateInfo.category.id;
      initName = $scope.cateInfo.category.name;
    } else {
      id = $scope.cateInfo.childCate.id;
      initName = $scope.cateInfo.childCate.name;
      initUpid = $scope.cateInfo.category.id;
    }
    if(id === undefined) {
      id = initUpid;
      initUpid = 0;
      initName = $scope.cateInfo.category.name;
    }
    var postData = {
      siteId: $scope.cateInfo.site.id,
      weight: $scope.cateInfo.weight,
      name: $scope.cateInfo.name,
      initName: initName,
      locationId : $scope.cateInfo.location.id,
      initUpid: initUpid, // 查是否存在，不存在则upid可以为0
      initId: id,
      id: $stateParams.id ,

      // 如果选择了用户类型 ，就传过去， 默认为1 ＝普通用户
      customerType : $scope.default_type ?  $scope.default_type.value : 1
    };

    // 提交添加数据
    req.getdata('catemap/save', 'POST', function(data) {
      dialog.tips({bodyText: data.msg});
    }, postData)
  }
  $scope.reback = function() {
    req.redirect('/category/map');
  }



}]);
