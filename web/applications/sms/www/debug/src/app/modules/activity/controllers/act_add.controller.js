'use strict';
angular
.module('hop')
.controller('ActivityAddCtrl', ['$scope', 'req', 'rpc', '$upload', 'daChuLocal', function($scope, req, rpc, $upload, daChuLocal) {
  // 增加广告
  $scope.title = '发布运营活动';
  var setDefault = function() {
    req.getdata('promotion/input_options', 'GET', function(data) {
      if(parseInt(data.status) === 0) {
       $scope.locationInfo = data.list.locations;
       $scope.location = data.list.locations[0];
       $scope.type = data.list.types;
       $scope.firstOptions = data.list.first_options;
       $scope.isFirst = $scope.firstOptions[1];
       $scope.groups = data.list.order_groups;
       $scope.groups.push({id:-1, name:"新增分组..."});
       $scope.showNewGroup = false;
       $scope.orderGroup = $scope.groups[0];
       $scope.newCustomerOptions = data.list.new_customer_options;
       $scope.isNewCustomer = $scope.newCustomerOptions[0];
      }
    });
  }
  setDefault();
  // 选择分类
  $scope.changeGroup = function() {
    if($scope.orderGroup.id == -1) {
      $scope.showNewGroup = true;
    } else {
      $scope.showNewGroup = false;
    }
  }
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.endDateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.latestOptions = {
    formatYear: 'yy',
    startingDay: 1
  };

  $scope.endOpened = $scope.latestOpened = $scope.opened = false;
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

  $scope.latestOpen = function($event) {
    if($scope.endTime == undefined || $scope.startTime == undefined) {
      alert('请先选择活动时间范围');
      return false;
    }
    $event.preventDefault();
    $event.stopPropagation();
    $scope.latestOpened = true;
  };
  // 保存
  $scope.add = function() {
    var postData = {
      title : '',
      location_id : 1,
      rule : '',
      startTime : '',
      endTime : '',
      latestDeliverTime : '',
      categoryNames : '',
      categoryLimitNum: 0,
      group_id : ''
    };
    postData.location_id       = $scope.location.id;
    postData.rule              = $scope.rule;
    postData.title             = $scope.name;
    postData.startTime         = Date.parse($scope.startTime) / 1000;
    postData.endTime           = Date.parse($scope.endTime) / 1000;
    postData.latestDeliverTime = Date.parse($scope.latestDeliverTime) / 1000;
    postData.categoryNames     = $scope.categoryNames ? $scope.categoryNames : '';
    postData.categoryLimitNum  = $scope.categoryLimitNum ? $scope.categoryLimitNum : 0;
    postData.ruleType          = $scope.ruleType ? $scope.ruleType.id : 0;
    postData.isFirst           = $scope.isFirst ? $scope.isFirst.id : 1;
    postData.isNewCustomer     = $scope.isNewCustomer ? $scope.isNewCustomer.id : 0;
    postData.groupId           = $scope.orderGroup ? $scope.orderGroup.id : 1;

    // 如果选择了新建分组，则保留新名字
    var checkGroup = true;
    if(postData.groupId == -1) {
      postData.groupId = -1;
      postData.groupName = $scope.newGroupName;
      if($scope.newGroupName == "") {
        alert('请输入新分组名称');
        checkGroup = false;
      } else {
        angular.forEach($scope.groups, function(i){
          if(i.name == $scope.newGroupName) {
            alert('分组名称"' + $scope.newGroupName + '"已存在！请重新填写。');
            checkGroup = false;
          }
        });
      }
    }
    if(!checkGroup) {
      return false;
    }

    if(!postData.title) {
      alert('请输入活动标题');
      return false;
    }
    if(!$scope.ruleType) {
      alert('请选择活动类型');
      return false;
    }
    rpc.load('promotion/save', 'POST', postData).then(
      function(data) {
        alert(data.msg);
        req.redirect('/activity');
      },
      function(msg) {
        alert(msg);
      }
    );
  }
}]);
