'use strict';
angular
  .module('hop')
  // 添加货物
  .controller('GoodsEditCtrl', ['$scope', '$upload', '$stateParams', 'req', '$location', '$cookieStore', 'dialog', 'daChuLocal', 'daChuTimer', function($scope, $upload, $stateParams, req, $location, $cookieStore, daChuDialog, daChuLocal, daChuTimer) {
   $scope.productsChild = [];
   var id = $stateParams.productId;
   $scope.title = '编辑货号';
    // 添加货物需要提交的数据model
    $scope.product = {
      title: '',
      id: id,
      category_id: '',
      imgs: [],
      originImgs: [],
      spec:[]
    };
    $scope.init = {
      category_id:'',
      childid: '',
      productid: '',
      selfProduct: '',
      spec:[]
    };
    $scope.buttons = [
      {
      name:'保存',
      type: 'submit',
      clk: 'add',
      cls: 'btn-success'
    },
    {
      name: '返回',
      type: 'reset',
      clk: 'reback',
      cls: 'btn-default'
    }
    ];
   //----------------------------------
    function setDefault(data, pathArr) {
      $scope.topCategory = data.top;
      $scope.seconds = data.second;
      $scope.thirdChild = data.third_child;
      $scope.units = data.units;
      $scope.secondChild  = data.second_child;
      // imgs
      $scope.imgUploads = daChuLocal.get('imgUpload') || [];

      if(pathArr) {
        angular.forEach($scope.topCategory, function(v) {
          if(parseInt(v.id) === parseInt(pathArr[0])) {
            $scope.init.category_id = v;
          }
        })
        if($scope.init.category_id) {
          // 获取规格
          $scope.getChilds($scope.init.category_id.id, 'secondCategory', 'seconds');
          $scope.secondCategory = $scope.seconds[$scope.init.category_id.id];
          angular.forEach($scope.secondCategory, function(v) {
            if(parseInt(v.id) === parseInt(pathArr[1])) {
              $scope.init.childid = v;
            }
          })
        }
        if($scope.init.childid) {
          $scope.getChilds($scope.init.childid.id, 'productCategory', 'secondChild');
          $scope.productCategory = $scope.secondChild[$scope.init.childid.id];
          angular.forEach($scope.productCategory, function(v) {
            if(parseInt(v.id) === parseInt(pathArr[2])) {
              $scope.init.productid = v;
            }
          })
        }

        if($scope.init.productid) {
          $scope.getChilds($scope.init.productid.id, 'productsChild', 'thirdChild');
          $scope.productsChild = $scope.thirdChild[$scope.init.productid.id];
          angular.forEach($scope.productsChild, function(v) {
            if(parseInt(v.id) === parseInt(pathArr[3])) {
              $scope.init.productidChild = v;
            }
          })
        }
      }
      if($scope.unitId) {
        angular.forEach($scope.units, function(v) {
          if(parseInt(v.id) === parseInt($scope.unitId)) {
            $scope.unit = v;
          }
        })
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
    // 获取信息
    var getInfo = function() {
      req.getdata('sku/info', 'POST', function(data) {
         cateList(data.info.path_arr);
         $scope.product.title = data.info.name;
         if(data.info.pictures) {
            $scope.imgs = data.info.pictures;
         }
         $scope.properties = [];
         angular.forEach(data.info.properties, function(p) {
          $scope.properties.push(p);
         });
         angular.forEach(data.info.spec, function(v) {
           $scope.init.spec[v.id] = v.val;
         });
         $scope.product.guarantee_period = data.info.guarantee_period;
         $scope.product.effect_stage     = data.info.effect_stage;
         $scope.product.code             = data.info.code;
         $scope.product.net_weight       = data.info.net_weight;
         if(data.info.unit_id) {
           $scope.unitId = data.info.unit_id;
         }
      }, {id: id});
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
    getInfo();
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
      } else if(value == 'thirdChild') {
        $scope.init.productidChild = [];
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
    // 添加确认
    $scope.add = function(id) {
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
      $scope.product.originImgs = $scope.imgs;
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
            imgUpload.push(v);
            $scope.imgUploads = imgUpload;
            daChuLocal.set('imgUpload', $scope.imgUploads);
        });
      });
    };
    // 取消上传文件
    // 清楚localStorage
    $scope.picCancel = function(file, type) {
      if(type == 1) {
        var index = $scope.imgs.indexOf(file);
        $scope.imgs.splice(index, 1);
      } else {
        var index = $scope.imgUploads.indexOf(file);
        $scope.imgUploads.splice(index, 1);
        if($scope.imgUploads) {
          daChuLocal.set('imgUpload', $scope.imgUploads);
        }
      }
    }

}]);
