'use strict';
// 添加分类
angular.module('hop')
.controller('CategoryAddCtrl',
['$scope', 'req', '$cookieStore', '$filter' ,'dialog' ,
function($scope, req, $cookieStore, $filter , dialog) {

 
  var vm  = $scope.vm = {};
  //  提交的信息
  $scope.cateInfo = {
    name: '',
    upid: 0,
    id:0,
    weight:0
  };
  $scope.title = '添加分类';
  // 设置默认
  function setValue(data) {
      $scope.ups = data.top;
      $scope.second = data.second;
      $scope.secondChild = data.second_child;
  }
  // 初始获取数据
  $scope.categoryInit = function() {
      req.getdata('category/lists', 'GET', function(data) {
        setValue(data.list);
      });
  }
  // 初始化
  $scope.categoryInit();
  // 获取子类
  $scope.getChild = function() {
    $scope.childCates = [];
    $scope.thirdCates = [];
    if($scope.category != undefined) {
      var index = $scope.category.id
      // 获取二级
      if($scope.second[index] !== undefined) {
        $scope.childCates = $scope.second[index];
      }
    }
  };
  // 获取三级
  $scope.getThird = function() {
    $scope.thirdCates = [];
    if($scope.childCate != undefined) {
    var index = $scope.childCate.id;
    if($scope.secondChild !== undefined) {
      $scope.thirdCates = $scope.secondChild[index];
    }
    }
  }
  // 分类添加
  $scope.cateAdd = function() {
    if($scope.category != undefined) {
      $scope.cateInfo.upid = $scope.category.id;
    }
    if($scope.childCate != undefined){
      $scope.cateInfo.upid = $scope.childCate.id;
    }
    if($scope.thirdCate != undefined) {
      $scope.cateInfo.upid = $scope.thirdCate.id;
    }
    // 保存数据
    req.getdata('category/save', 'POST', function(data) {
      if(parseInt(data.status) !== -1) {
        // 提示成功

        dialog.tips({bodyText: '添加分类成功'});
        $scope.reback();
      } else {
        // 提示失败
        dialog.tips({bodyText: data.msg});
      }
    }, $scope.cateInfo);
  };

  $scope.reback = function() {
    req.redirect('/category');
  }
}]);
