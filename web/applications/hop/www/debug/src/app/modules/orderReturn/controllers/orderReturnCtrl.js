
angular.module('hop').controller('orderReturnCtrl', ['$location', 'dialog', 'req', '$scope', '$cookieStore', '$state', 'appConfigure','daChuLocal', function($location, dialog, req, $scope, $cookieStore, $state, appConfigure, $localStorage) {
  $scope.site_url = appConfigure.url;
  $scope.status = '-1';

  // 查询参数
  $scope.vm = {
      searchValue:"",
      otype:"",
      refundMethod:"",
      operator:"",
      startTime:null,
      endTime:null
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
  
  // 初始化数据
  (function() {
    req.getdata('rejected/list_condition', 'POST', function(data) {
      console.log(data);
      if (data.status == 0) {
        $scope.rejected_status = data.rejected_status;
        $scope.areas = data.area;
        $scope.operators = data.operators;
        $scope.refundMethods= data.refund_methods;
      }
    });
  })();

  // 重新获取分页数据
  var getList = function(isCache) {
     var localCache = $localStorage.get("orderReturnListCache"),postData={};

      postData = {
          status: $scope.status,
          keyword: $scope.vm.searchValue,
          refund_method: $scope.vm.refundMethod==null?"":$scope.vm.refundMethod.id,
          area_id: $scope.vm.otype==null?"":$scope.vm.otype.id,
          operator_id: $scope.vm.operator==null?"":$scope.vm.operator.id,
          startTime: Date.parse($scope.vm.startTime),
          endTime: Date.parse($scope.vm.endTime),
          currentPage: $scope.paginationConf.currentPage,
          itemsPerPage: $scope.paginationConf.itemsPerPage
      };

      // 是否获取缓存
      if(isCache>0 && localCache!=null){
          postData=localCache;
          $scope.status=postData.status;
      } else {
        $localStorage.set("orderReturnListCache",postData);
      }

      req.getdata('/rejected/lists', 'POST', function(data) {
        if (data.status == 0) {
          // 变更分页的总数
          $scope.paginationConf.totalItems = data.total;

          // 变更数据条目
          $scope.list = data.list;
        }
      }, postData,true);
  };

  //分页cookie纪录
  var startPage = $cookieStore.get('paginationReturnCookie')||1;

  $scope.getpage = function() {
    $cookieStore.put('paginationReturnCookie', $scope.paginationConf.currentPage);
  }

  // 分页参数初始化
  $scope.paginationConf = {
    currentPage: startPage,
    itemsPerPage: 10
  };

  // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
  $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);

  // 切换城市或系统
  $scope.switchCity = function() {
    if (!$scope.site && !$scope.city) {
      $scope.lineList = $scope.lineListAll;
      return;
    }
    $scope.lineList = [];
    angular.forEach($scope.lineListAll, function(value, key) {
      var selected = true;
      if ($scope.site && value.site_src != $scope.site.id) {
        selected = false;
      }
      if ($scope.city && value.location_id != $scope.city.id) {
        selected = false;
      }

      if (selected) {
        $scope.lineList.push(value);
      }
    })
  };

  // 按照日期筛选
  $scope.search = function() {
    getList(0);
  };

  $scope.filterByStatus = function(status) {
    $scope.status = status;
    $scope.searchValue = '';
    $scope.paginationConf.currentPage = 1;
    getList(0);
  }

  // 重置搜索条件
  $scope.reset = function() {
     $scope.vm = {
        searchValue:"",
        otype:"",
        operator:"",
        startTime:null,
        endTime:null
    }
    getList(0);
  };

  // 导出退货退款单
  $scope.export = function() {
    var id_arr = [];
    angular.forEach($scope.list, function(value, key) {
      if (value.checked) {
        id_arr.push(value.id);
      }
    });
    if (id_arr && id_arr.length <= 0) {
      dialog.tips({
        bodyText: '请选择要导出的退货退款单！'
      });
      return;
    }

    dialog.tips({
      actionText: '确定',
      bodyText: '确定导出选中退货退款单吗?',
      ok: function() {
        window.location.href = $scope.site_url + "/rejected/export?ids=" + id_arr.join(',');
      }
    });
  };


  // 全选或取消全选
  $scope.checkAll = function() {
    angular.forEach($scope.list, function(value, key) {
      value.checked = $scope.check_all;
    });
  };

}]);