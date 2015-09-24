'use strict';
angular
  .module('hop')
  // 添加货物
  .controller('GoodsAddCtrl', ['$scope', '$upload', 'req', '$location', '$cookieStore', 'dialog', 'daChuLocal', 'daChuTimer', function($scope, $upload,req, $location, $cookieStore, daChuDialog, daChuLocal, daChuTimer) {
   $scope.productsChild = [];
   $scope.title = '添加货物';
    // 添加货物需要提交的数据model
    $scope.product = {
      title: '',
      id: 0,
      category_id: '',
      spec:[],
      imgs:''
    };
    $scope.init = {
      category_id:'',
      childid: '',
      productid: '',
      selfProduct: '',
      spec:[]
    };
  //----------------------------------
    function setDefault(data, pathArr) {
      $scope.topCategory = data.top;
      $scope.seconds = data.second;
      $scope.thirdChild = data.third_child;
      $scope.secondChild  = data.second_child;
      $scope.units  = data.units;
      // imgs
      $scope.imgUploads = daChuLocal.get('imgUpload') || [];
      if(pathArr) {
        angular.forEach($scope.topCategory, function(v) {
          if(parseInt(v.id) === parseInt(pathArr[0])) {
            $scope.init.category_id = v;
          }
        })
        if($scope.init.category_id) {
          $scope.secondCategory = $scope.seconds[$scope.init.category_id.id];
          angular.forEach($scope.secondCategory, function(v) {
            if(parseInt(v.id) === parseInt(pathArr[1])) {
              $scope.init.childid = v;
            }
          })
        }
        if($scope.init.childid) {
          $scope.productCategory = $scope.secondChild[$scope.init.childid.id];
          angular.forEach($scope.productCategory, function(v) {
            if(parseInt(v.id) === parseInt(pathArr[2])) {
              $scope.init.productid = v;
            }
          })
        }
      }
    }
    // 获取属性规格
    var getProperty = function(category_id, condition) {
      req.getdata('property/cate_prop_list', 'POST', function(data) {
        if(parseInt(data.status) === 0) {
          $scope.properties = data.list;
        } else {
          if(condition == 'secondChild') {
            getProperty($scope.init.category_id.id, 'seconds');
          } else if(condition == 'thirdChild') {
            getProperty($scope.init.childid.id, 'secondChild');
          }
        }
      },{category_id:category_id});
    }

    // 获取分类列表
  var cateList = function(pathArr) {
      var cateData = daChuLocal.get('cateList');
      var cateDataTime = daChuLocal.get('cateListTime');
      if(!cateData || !daChuTimer.compare(cateDataTime)) {
        req.getdata('category/lists', 'GET', function(data){
          if(parseInt(data.status) != -1 && data.list.top.length > 0) {
            daChuLocal.set('cateList', data.list);
            daChuLocal.set('cateListTime', daChuTimer.getNow());
            setDefault(data.list, pathArr);
          }
        });
      } else {
        setDefault(cateData, pathArr);
      }
    }
    cateList();
    // 获取子集，都是一次性获取的数据
    $scope.getChilds = function(id, name, value) {
      if(value == 'seconds') {
        $scope.properties = [];
        $scope.productCategory = [];
        $scope.secondCategory = [];
        $scope.productsChild = [];
      } else if(value == 'secondChild') {
        $scope.properties = [];
        $scope.productsChild = [];
      }

      if(id !== undefined) {
        getProperty(id, value);
        var index = parseInt(id);
        $scope[name] = $scope[value][index];
      }
    };
    // 返回
    $scope.reback = function() {
      history.go(-1);
    };
    $scope.prop = [];
    $scope.disabled = false;
    // 添加确认
    $scope.add = function(id) {
      $scope.disabled = true;
      var item = $scope.init.productid;
      if($scope.init.productidChild && $scope.init.productidChild.id) {
        item = $scope.init.productidChild;
      }

      $scope.product.category_id = item.id;
      var spec = [];
      angular.forEach($scope.init.spec, function(v, k) {
        angular.forEach($scope.properties, function(sv, sk) {
          if(parseInt(sv.id) == parseInt(k)) {
            sv.val = v;
            spec.push(sv);
          }
        })
      })

      $scope.product.spec = spec;
      // 新增单位id
      if($scope.unit) {
        $scope.product.unit_id = $scope.unit.id;
      } else {
        daChuDialog.tips({bodyText: '请填写计量单位信息'});
        $scope.disabled = false;
        return false;
      }
      if($scope.product.title.length == 0) {
        daChuDialog.tips({bodyText: '请填写带*的信息'});
        $scope.disabled = false;
        return false;
      }
      $scope.product.imgs = $scope.imgUploads;
      // 货物保存
      req.getdata('/sku/save', 'POST', function(data) {
        $scope.disabled = false;
        if(parseInt(data.status) === 0) {
          daChuDialog.tips({bodyText:"保存成功！", close: function() {
          }});
          daChuLocal.set('imgUpload', []);
          req.redirect('/goods');
        } else {
          daChuDialog.tips({bodyText: data.msg, close: function() {
          }});
        }
      }, $scope.product);
    };
    // 上传图片
    $scope.$watch('files', function () {
      if($scope.files != undefined) {
        $scope.upload();
      }
    });

    $scope.upload = function () {
      var imgUpload = [];
      angular.forEach($scope.files, function(v) {
        $upload.upload({
          url: 'http://img.dachuwang.com/upload?bucket=sku',
          file: v,
          fileFormDataName: 'files[]'
        }).progress(function (evt) {
          var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
          v['progressPercentage'] = progressPercentage;
        }).success(function (data, status, headers, config) {
            // 成功后预览
            v['dataUrl'] = data['files'][0]['url'];
            v['size'] += 'bytes';
            imgUpload.push(v);
            $scope.imgUploads = imgUpload;
            daChuLocal.set('imgUpload', $scope.imgUploads);
        });
      });
    };
    // 取消上传文件
    // 清楚localStorage
    $scope.picCancel = function($index) {
      $scope.imgUploads.splice($index, 1);
      if($scope.imgUploads) {
        daChuLocal.set('imgUpload', $scope.imgUploads);
      }
    }
}]);
