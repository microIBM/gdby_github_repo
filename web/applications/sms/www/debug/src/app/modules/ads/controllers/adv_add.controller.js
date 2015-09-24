'use strict';
angular
.module('hop')
.controller('AdvCreateCtrl', ['$rootScope' , '$scope','$filter', 'req', '$upload', 'daChuLocal','dialog' , function($rootScope ,$scope,$filter, req, $upload, daChuLocal , dialog) {
  // 增加广告
  $scope.title = '添加广告';
  //初始化状态
  //
  $scope.loginStatus = [{

    status : 0 ,
    name : '全部用户'
  },{
    status : 1 ,
    name : '登陆用户'
  },{
    status : 1 ,
    show_line : true ,
    name : '指定线路用户'
  }];

  //线路可见控制器
  $scope.getLoginStatus = function(model){
    if(model.show_line){
      $scope.show_line = true;
    }else{
      $scope.show_line = false ;
    }
  }

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
  var setDefault = function() {
    req.getdata('ads/input_options', 'GET', function(data) {
      if(parseInt(data.status) === 0) {
        $scope.positions = data.list.positions;
        $scope.locationInfo = data.list.locations;
        $scope.siteSrcs = data.list.sites;
        $scope.site = $scope.siteSrcs[0];
        $scope.location = $scope.locationInfo[0];
        $scope.catemap = data.list.catemaps;
        $scope.cateMaps = $scope.catemap[$scope.site.id][$scope.location.id];
        $scope.advStatus = data.list.adv_status;
        $scope.advsStatus = $scope.advStatus[0];
        $scope.line = data.list.line_options;
        $scope.line_default = $scope.line['804'];
      }
    });
  }
  $scope.link_url = '';
  setDefault();
  // 选择分类
  $scope.getMaps = function() {
    $scope.cateMaps = $scope.catemap[$scope.site.id][$scope.location.id];
    $scope.line_default = $scope.line[$scope.location.id];
    console.log($scope.location.id)
    // 获取分类映射
    // 站点id，地理位置id
  }
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
        v['pic_size'] = v['size'] + 'bytes';
        imgUpload.push(v);
        $scope[key] = imgUpload;
        daChuLocal.set(key, $scope[key]);
      });
    });
  };
  // 保存
  $scope.add = function(form) {

    //必填项 并且 图片全部填写 才发请求
    if(form.$invalid || !$scope.imgUploads ){
      dialog.tips({
        bodyText : '请填写完整信息！'
      })
      return ;
    }

    var postData = {
      pic_url : '',
      detail_img : '',
      site_id : 1,
      location_id : 1,
      title : '',
      pos_id : '',
      link_url : '',
      line_id : []
    };
    postData.pic_url = $scope.imgUploads[0].dataUrl;
    if($scope.detailUploads) {
      postData.detail_img = $scope.detailUploads[0].dataUrl;
    }

    postData.site_id = $scope.site.id;
    postData.location_id = $scope.location.id;
    postData.title = $scope.name;

    angular.forEach($scope.line_default , function(v){
      if(v.checked){
        postData.line_id.push(v.id);
      }
    })
    postData.needLogin = $scope.m.status ;
    postData.onlineTime = Date.parse($scope.startTime)/1000;
    postData.offlineTime = Date.parse($scope.endTime)/1000;
    if($scope.catemap_s) {
      postData.link_url = 'page.list({cateId:' + $scope.catemap_s.origin_id + '})';
    } else {
      postData.link_url = $scope.link_url;
    }
    angular.forEach($scope.line_default , function(v){

    })
    angular.forEach($scope.positions, function(v) {
      if(v.checked) {
        postData.pos_id += v.id + ',';
      }
    });
    $rootScope.is_loading = true ;
    req.getdata('ads/save', 'POST', function(data) {

      $rootScope.is_loading = false ;
      alert(data.msg);

      //只有成功才返回列表
      if(data.status == 0){

         req.redirect('/ads');
      }
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
