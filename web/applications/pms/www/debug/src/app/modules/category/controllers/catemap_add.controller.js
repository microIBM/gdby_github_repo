'use strict';
// 前端分类映射
angular
.module('hop')
.controller('CateMapAddCtrl', ['$scope', '$filter' , 'req', 'dialog', function($scope, $filter ,req, dialog) {
 //初始化
 var vm  = $scope.vm = {};

 $scope.title = '前端分类映射';
 // 根据不同的来获取相应的分类
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
  }
  $scope.cateInfo = {
    weight: 0,
    name: ''
  };
 // 获取到映射
   // 分页参数初始化
   $scope.paginationConf = {
     currentPage: 1,
     itemsPerPage: 15
   };
  var getList = function() {
     req.getdata('category/map', 'POST', function(data) {
       $scope.initTop = data.list.top;
       $scope.initSecond = data.list.second;
       $scope.initTopChild = data.list.top_child;
       $scope.initSecondChild = data.list.second_child;
       $scope.locations = data.location;
       $scope.cateInfo.location = data.location[0];
       $scope.sites = data.sites;
       $scope.cateInfo.site = data.sites[0];


          $scope.customer_type_options = data.customer_type_options;

          // 如果当前用户类型存在， 那么设为默认用户类型
          if($scope.default_type){
            angular.forEach( $scope.customer_type_options, function(v) {
              if(v.value == $scope.default_type.value) {
                $scope.default_type =v ;
              }
            });
          }else {
            $scope.default_type = data.customer_type_options.length != 0  ?  data.customer_type_options[0] : false;
          }
       initCate();
     },
     {
       currentPage: $scope.paginationConf.currentPage,
       itemsPerPage: $scope.paginationConf.itemsPerPage,
     });
   }
   // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
   $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
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
      $scope.cateInfo.name = $scope.cateInfo.category.name;
    }
  }
  $scope.getThird = function() {
    if($scope.cateInfo.childCate != undefined) {
      $scope.cateInfo.name = $scope.cateInfo.childCate.name;
    } else {
      $scope.cateInfo.name = $scope.cateInfo.category.name;
    }
  }
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
      locationId : $scope.cateInfo.location.id,
      name: $scope.cateInfo.name,
      initName: initName,
      initUpid: initUpid, // 查是否存在，不存在则upid可以为0
      initId: id
    };

     // 如果选择了用户类型 ，就传过去， 默认为1 ＝普通用户
    postData.customerType = $scope.default_type ?  $scope.default_type.value : 1
    // 提交添加数据
    req.getdata('catemap/create', 'POST', function(data) {
      dialog.tips({bodyText: data.msg});
    }, postData)
  }
  $scope.reback = function() {
    req.redirect('/category/map');
  }


}]);
