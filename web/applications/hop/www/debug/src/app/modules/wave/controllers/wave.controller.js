'use strict';
angular
.module('hop')
.controller('WaveCtrl', ['$scope', '$state', 'req', 'dialog', function($scope, $state, req, dialog) {
  // 获取创建波次的条件
  req.getdata('wave/wave_list_page_options', 'GET', function(data) {
     $scope.sites = data.sites;
     $scope.cities = data.cities;
     $scope.orderTypeList = data.order_type;
     $scope.orderType = $scope.orderTypeList[0];
     $scope.deliverDate = data.deliver_date;
     $scope.maxDate = $scope.deliverDate[1].name;
     $scope.deliverTime = data.deliver_time;
     $scope.waveType = data.wave_type;
  });
  // 查询条件
  $scope.init = {
    wave : {},
    searchValue : '',
    released : false,
    release : false
  };
  var getList = function() {
    var postData = {
      wave_type: ($scope.init.wave == null) ? '' : $scope.init.wave.val ,
      wave_id : $scope.init.searchValue,
      city_id : $scope.listCity ? $scope.listCity.id : 0,
      order_type : $scope.orderType ? $scope.orderType : 0,
      pick_task_created : $scope.init.released ? 1: 0,
      currentPage: $scope.paginationConf.currentPage,
      itemsPerPage: $scope.paginationConf.itemsPerPage,
    };
    req.getdata('wave/wave_list', 'POST', function(data) {
      $scope.list = data.list;
      $scope.paginationConf.totalItems = data.total;
    }, postData);
  }
  // 日期选择控件初始化
  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };
  $scope.opened = false;
  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.opened = true;
  };

  // 创建波次
  $scope.createWave = function() {
    /*if(typeof $scope.site.id == 'undefined') {
      alert('请选择波次所属系统，再继续操作');
      return false;
    }*/
    if(typeof $scope.city.id == 'undefined') {
      alert('请选择波次所属城市，再继续操作');
      return false;
    }
    if(!$scope.orderType) {
      alert('请先选择订单类型');
      return;
    }
    if(!$scope.startTime) {
      alert('请选择波次的配送日期，再继续操作');
      return false;
    }
    if(!$scope.deliverT) {
      alert('请选择波次的配送时段，再继续操作');
      return false;
    }
    var createData = {
      //site_id : $scope.site.id,
      city_id : $scope.city.id,
      order_type : $scope.orderType.code,
      deliver_date : Date.parse($scope.startTime)/1000,
      deliver_time : $scope.deliverT.val
    };
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定创建波次？',
      ok: function() {
        req.getdata('wave/create_wave', 'POST', function(data) {
          if(parseInt(data.status) === 0) {
            alert('波次创建成功,波次号为' + data.wave_id);
            getList();
          } else {
            alert(data.msg);
          }
        }, createData);
      }
    });
  }
  // 释放波次
  $scope.setReleased = function(item) {
    var $index = $scope.list.indexOf(item);
    dialog.tips({
      actionText: '确定',
      bodyText: '确定开始分拣？',
      ok: function() {
        req.getdata('wave/create_pick_task', 'POST', function(data) {
          if(parseInt(data.status) === 0) {
            $scope.list[$index].pick_task_created = 1;
            $scope.list[$index].task_created = data.msg;
            alert(data.msg);
          } else {
            alert(data.msg);
          }
        }, {wave_id : $scope.list[$index].id});
      }
    });
    }
  // 分页配置
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);
  // search
  $scope.search = function() {
    if(typeof $scope.init != 'undefined') {
      getList();
      $scope.init.release = $scope.init.released;
    }
    $scope.isAll = false;
  }
  // 删除波次
  $scope.rollWave = function() {
    var wave_ids = [];
    angular.forEach($scope.list, function(v) {
      if(v.isChecked) {
        wave_ids.push(v.id);
      }
    });
    if(wave_ids.length == 0) {
      dialog.tips({bodyText: '没有要删除的波次'});
    } else {
      dialog.tips({
        actionText: '确定',
        bodyText: '确定删除波次？',
        ok: function() {
          if(wave_ids.length > 0) {
            req.getdata('wave/delete_wave', 'POST', function(data) {
              if(parseInt(data.status) === 0) {
                alert('删除波次成功');
                getList()
                $scope.isAll = false;
              } else {
                alert('任务创建失败');
              }
            }, {wave_ids : wave_ids});
          } else {
            alert('任务创建失败');
          }
        }
      });
    }
  }
  // 批量删除
  $scope.selectAll = function() {
    $scope.isAll = !$scope.isAll;
    angular.forEach($scope.list, function(v) {
      v['isChecked'] = $scope.isAll;
    });
  }
  // reset
  $scope.reset = function() {
    $scope.init.wave = {};
    $scope.init.searchValue = '';
    $scope.init.released = false;
    $scope.isAll = false;
    getList();
    $scope.init.release = $scope.init.released;
  }
}]);
