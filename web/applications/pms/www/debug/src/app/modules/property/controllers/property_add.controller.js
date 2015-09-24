'use strict'
// 规格属性添加
angular.module("hop")
.controller('PropertyAddCtrl', ['$scope', 'req', 'dialog', 'daChuLocal', 'daChuTimer', function ($scope, req, dialog, daChuLocal, daChuTimer) {
  // 获取分类id 两级
  $scope.topCategory = [];
  $scope.properties = [];
  $scope.propertyShow = [];
  $scope.propertyType = 0;
  $scope.top = '';
  $scope.title = '添加规格';
  // 是否必填
  $scope.requires = [
  {
    name: '是',
    val: 1
  },
  {
    name: '否',
    val: 0
  }
  ];
  // 需要提交的数据
  $scope.saveData = {
    input_type:1,
    category_id: 0,
    name:'',
    id:0,
    is_required: 0,
    options:[]
  };
  
  // 默认的规格选项值输入框
  $scope.initValues =   {
    name: '添加',
    value: '',
    icon: 'glyphicon-plus',
    cls: 'btn-info',
    clk: 'add'
  };
  
  // 规格值输入框数组初始化
  $scope.propertyValues = [$scope.initValues];

  function init() {
    $scope.secondCategory = [];
    $scope.productCategory = [];
  };
  init();
  function setDefault(data) {
      $scope.topCategory = data.top;
      $scope.seconds = data.second;
      $scope.secondChild  = data.second_child;
      
  }
  // 获取top  
  function cateList() {
    var cateData = daChuLocal.get('cateList');
    var cateDataTime = daChuLocal.get('cateListTime');
    if(!cateData || !daChuTimer.compare(cateDataTime)) {
      req.getdata('/category/lists', 'GET', function(data){
        if(parseInt(data.status) != -1 && data.list.top.length > 0) {
          daChuLocal.set('cateList', data.list);
          daChuLocal.set('cateListTime', daChuTimer.getNow());
          setDefault(data.list);
        }
      });
    } else {
      setDefault(cateData);
    }
  }
  // 获取second
  $scope.getSecond = function() {
   if($scope.top !== undefined) {
    var index = parseInt($scope.top.id);
      $scope.secondCategory = $scope.seconds[index];
    }
  };
  // 获取product
  $scope.getProduct = function() {
    if($scope.second !== undefined) {
      var index = parseInt($scope.second.id);
      $scope.productCategory = $scope.secondChild[index];
    }
  };

  function getTypeList(val){
    // 获取展示
    req.getdata('property/get_type', 'GET', function(data) {
      $scope.properties = data.list;

      angular.forEach($scope.properties, function(v) {
        if(v.val == val) {
          $scope.propertyShow = v;
        }
      })
    });
  }
  cateList();
  getTypeList();
  
  // 添加
  $scope.add = function($index, v) {
    if(v == undefined) {
      v = '';
    }
    var next = {
      name: '删除',
      value: v,
      icon: 'glyphicon-minus',
      cls: 'btn-danger',
      clk: 'remove'
    };
    $scope.propertyValues.push(next);
  };

  // 删除
  $scope.remove = function($index) {
    $scope.propertyValues.splice($index,1);
  };
  // 规格
  $scope.setProType = function() {

    if($scope.propertyShow.val == 0) {

      $scope.propertyValues = [];
    } else {
      $scope.propertyValues = [$scope.initValues]
    }
  }
  // 规格属性的添加
  $scope.createProperty = function() {
    var options = [];
    $scope.saveData.is_required  = $scope.propertyRequired.val
    $scope.saveData.category_id = $scope.products == undefined ? ($scope.second == undefined ? $scope.top.id: $scope.second.id) : $scope.product.id;
    $scope.saveData.input_type = $scope.propertyShow.val;
    angular.forEach($scope.propertyValues, function(v) {
      options.push(v.value);
    })
    $scope.saveData.options = options;
    if(!$scope.saveData.name.length) {
      return false;
    }
    req.getdata('property/create', 'POST', function(data) {
      if(data.status == 0) {
        //$scope.property_show = [];
        //$scope.top_category = [];
        init();
        dialog.tips({bodyText:data.msg, close:function() {
          req.redirect("/property");
        }});
      }
    }, $scope.saveData);
  };
  $scope.reback = function() {
    req.redirect('/user/center');
  }
}]);
