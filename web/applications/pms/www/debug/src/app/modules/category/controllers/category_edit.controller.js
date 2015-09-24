'use strict'
// 编辑分类
angular.module('hop')
.controller('CategoryEditCtrl', ['$scope', 'req', '$cookieStore', 'dialog', 'daChuLocal',
 '$stateParams', function($scope, req, $cookieStore,daChuDialog, daChuLocal, $stateParams) {
  $scope.title = '编辑分类';
  //  提交的信息
  $scope.cateInfo = {
    name: '',
    upid: 0,
    edit_id:0,
    weight:0
  };
  $scope.init = {
    path:[]
  };
  // 设置默认
  function setValue(data) {
    $scope.ups = data.top;

    $scope.second = data.second;

    $scope.secondChild = data.second_child;
    if($scope.init.path.length > 1) {
      angular.forEach($scope.ups, function(v,i) {
        if(parseInt(v.id) === parseInt($scope.init.path[0])) {
          $scope.category = v;
        }
      })
      if($scope.init.path.length > 2) {
        if($scope.init.path[1] !== undefined) {
          $scope.childCates = $scope.second[$scope.category.id]; 
          angular.forEach($scope.childCates, function(v, i) {
            if(parseInt(v.id) === parseInt($scope.init.path[1])) {
              $scope.childCate = v;
            }
          })
        }
      }
      if($scope.init.path.length > 3) {
        if($scope.init.path[2] !== undefined) {
          $scope.thirdCates = $scope.secondChild[$scope.childCate.id];
          angular.forEach($scope.thirdCates, function(v,i) {
            if(parseInt(v.id) === parseInt($scope.init.path[2])) {
              $scope.thirdCate = v;
            }
          })
        }
      }
    }
  }
  // 初始获取数据
  $scope.categoryInit = function() {
     // 需要检测localStorage
   // var cateData = daChuLocal.get('cateList');
    
    //var cateDataTime = daChuLocal.get('cateListTime');
    //if(!cateData || !daChuTimer.compare(cateDataTime)) {
      req.getdata('category/lists', 'GET', function(data) {
       // daChuLocal.set('cateList', data.list);
       // daChuLocal.set('cateListTime', daChuTimer.getNow());
        setValue(data.list);
      });
   // } else {
     // setValue(cateData);
   // }
  }
 // 获取子类
  $scope.getChild = function() {
    if($scope.category != undefined && $scope.category != null) {
      var index = $scope.category.id
      // 获取二级
      $scope.childCates = $scope.second[index]; 
    } else {
      $scope.cateInfo.upid = $scope.upid;
      $scope.category = undefined;
      $scope.childCates = undefined;
      $scope.thirdCates = undefined;
    }
  };
  // 获取三级
  $scope.getThird = function() {
    if($scope.childCate != undefined) {
      var index = $scope.childCate.id;
      $scope.thirdCates = $scope.secondChild[index];
    }
    $scope.thirdCate = undefined;
  }
  // 获取当前分类信息
  req.getdata('/category/get_info', 'POST', function(data) {
    if(parseInt(data.status) !== -1) {
      $scope.cateInfo.name = data.info.name;
      $scope.cateInfo.weight = parseInt(data.info.weight);
      $scope.upid = $scope.cateInfo.upid = data.info.upid;
      $scope.path = data.info.path;
      $scope.cateInfo.edit_id = $stateParams.id; 
      var pathArr = $scope.path.split('.');
      pathArr.pop();
      pathArr.shift();
      $scope.init.path = pathArr;
      // 初始化
      $scope.categoryInit();
    }
  } ,{id:$stateParams.id});

  $scope.reback = function() {
    req.redirect('/category');
  }

  // 提交更新
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
    req.getdata('category/update_category', 'POST', function(data) {
      if(parseInt(data.status) !== -1) {
        daChuDialog.tips({bodyText: data.msg});
        $scope.reback();
      } else {
        daChuDialog.tips({bodyText: data.msg});
      }
    }, $scope.cateInfo);
  };

 
}]);
