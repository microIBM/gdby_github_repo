'use strict';
angular
.module('hop')
.controller('SubjectAddCtrl', ['$rootScope' , '$scope', 'req', '$upload', 'daChuLocal', 'dialog' ,function($rootScope , $scope, req, $upload, daChuLocal , dialog) {
  // 增加广告
  $scope.title = '新建专题';
  //-----
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endOpened = $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
  };
  $scope.endOpen = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.endOpened = true;
  };

  // 默认的规格选项值输入框
  $scope.initValues =   {
    name: '添加',
    value: '',
    id: '',
    icon: 'glyphicon-plus',
    cls: 'btn-info',
    clk: 'addProduct'
  };
  // 规格值输入框数组初始化
  $scope.products = [$scope.initValues];

  // 添加
  $scope.addProduct = function($index, v) {
    if(v == undefined) {
      v = '';
    }
    var next = {
      name: '删除',
      value: v,
      id: '',
      icon: 'glyphicon-minus',
      cls: 'btn-danger',
      clk: 'remove'
    };
    $scope.products.push(next);
  };

  // 删除
  $scope.remove = function(item) {
    var index = $scope.products.indexOf(item);
    $scope.products.splice(index, 1);
  };
  //-------
  $scope.showProduct = function(item) {
    var index = $scope.products.indexOf(item);
    var postData = {
      locationId : $scope.location.id,
      searchVal : item.value
    };
    req.getdata('product/manage', 'POST', function(data) {
      item.products = data.list;
    }, postData);
  }
  $scope.selectProduct = function(product, item) {
    var index = $scope.products.indexOf(item);
    $scope.products[index].id = product.id;
    $scope.products[index].value = product.title + '|' + product.sku_number + '|' + product.price +'/' + product.unit;
    item.products = '';
  }
  //--------
  var setDefault = function() {
    req.getdata('subject/input_options', 'GET', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.locationInfo = data.list.locations;
        $scope.siteSrcs = data.list.sites;
        $scope.site = $scope.siteSrcs[0];
        $scope.location = $scope.locationInfo[0];
        $scope.subjectTypes = data.list.subject_type;
        $scope.subjectType = $scope.subjectTypes[0];
      }
    });
  }
  $scope.link_url = '';
  setDefault();
  // 上传图片详情图片
  $scope.$watch('detailImg', function () {
    if($scope.files != undefined) {
      $scope.upload('detailUploads', 'detailImg');
    }
  });

  // 上传图片
  $scope.$watch('files', function () {
    if($scope.files != undefined) {
      $scope.upload('imgUploads', 'files');
    }
  });
  // 上传文件
  $scope.upload = function (key, name) {
    var imgUpload = [];
    angular.forEach($scope[name], function(v) {
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
        $scope[key] = imgUpload;
        daChuLocal.set(key, $scope[key]);
      });
    });
  };
  // 保存
  $scope.add = function(addForm) {


    //必填项 并且 图片全部填写 才发请求
    if(addForm.$invalid || !$scope.imgUploads ){
      dialog.tips({
        bodyText : '请填写完整信息！'
      })
      return ;
    }
    var postData = {
      pic_url : '',
      banner_url : '',
      detail_img : '',
      site_id : 1,
      location_id : 1,
      products : [],
      startTime : '',
      endTime : '',
      subjectType : '',
      title : ''
    };
    postData.pic_url = $scope.imgUploads[0].dataUrl;
    if($scope.detailUploads) {
      postData.detail_img = $scope.detailUploads[0].dataUrl;
    }
    postData.site_id = $scope.site.id;
    postData.location_id = $scope.location.id;
    postData.title = $scope.name;
    postData.startTime = Date.parse($scope.startTime)/1000;
    postData.endTime = Date.parse($scope.endTime)/1000;
    postData.subjectType = $scope.subjectType.id;
    angular.forEach($scope.products, function(v) {
      if(parseInt(v.id) > 0) {
        postData.products.push(v.id);
      }
    });
    if(postData.subjectType == 2) {
      postData.banner_url = postData.pic_url;
      postData.pic_url = '';
    }
    $rootScope.is_loading = true ;
    req.getdata('subject/save', 'POST', function(data) {
      $rootScope.is_loading = false;
      alert(data.msg);
      req.redirect('/subject');
    }, postData);
  }
  // 取消上传文件
  // 清楚localStorage
  $scope.picCancel = function(item, type) {

    var name = 'detailUploads';
    if(parseInt(type) === 0) {
      name = 'imgUploads';
    }
    var index = $scope[name].indexOf(item);
    $scope[name].splice(index, 1);
    if($scope[name]) {
      daChuLocal.set(name, $scope[name]);
    }
  }

}]);
