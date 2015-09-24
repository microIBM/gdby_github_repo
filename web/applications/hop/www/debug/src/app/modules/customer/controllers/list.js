'use strict'
angular
  .module('hop')
  .controller('CustomerListCtrl', ['$location', 'dialog', 'req', '$scope',"daChuLocal", function($location, dialog, req, $scope, $localStorage) {
    $scope.status = 'all';
    $scope.disableLogs=[];
    
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

    // 请求操作
    var request = {
      // 获取参数
      initOptions:function(){
        req.getdata('customer/lists_options', 'POST', function(data) {
          if (data.status == 0) {
            $scope.line_list = data.list;
            $scope.customerTypes = data.types;
          }
        });
      },
      // 获取禁用或启用的日志
      disableLogList:function(id,callback){
        $scope.disableLogs = [];
        req.getdata('customer/get_disable_reason', 'POST', function(data) {
          if (data.status == 0) {
             $scope.disableLogs = data.reasons;
             if(typeof callback == "function")
                callback.apply();
          }
        },{uid:id});
      }
    }
    
    request.initOptions();

    // 初始化搜索类别
    $scope.keyList = [{
      'name': '手机号',
      'val': 'mobile'
    }, {
      'name': '姓名',
      'val': 'name'
    }, {
      'name': '店铺名称',
      'val': 'shop_name'
    }, ];
    $scope.searchKey = $scope.keyList[0];

    // 重新获取分页数据
    var getList = function(isCache) {
        var lineId = $scope.line ? $scope.line.id : 0;
        var localCache = $localStorage.get("listPostParamCache"),postData={};

        postData = {
          status: $scope.status,
          lineId: lineId,
          searchKey: $scope.searchKey.val,
          searchValue: $scope.searchValue,
          startTime: Date.parse($scope.startTime),
          endTime: Date.parse($scope.endTime),
          currentPage: $scope.paginationConf.currentPage,
          itemsPerPage: $scope.paginationConf.itemsPerPage,
        };

        if($scope.customerType!=undefined)
            postData.customer_type=$scope.customerType.value;
        
        // 是否获取缓存
        if(isCache==16 && localCache!=null){
          postData=localCache;
        } else {
          $localStorage.set("listPostParamCache",postData);
        }
          
        req.getdata('customer/lists', 'POST', function(data) {
            if (data.status == 0) {
              // 变更分页的总数
              $scope.paginationConf.totalItems = data.total;
              // 变更数据条目
              $scope.list = data.list;
            }
        }, postData);
    };
    // 分页参数初始化
    $scope.paginationConf = {
      currentPage: 1,
      itemsPerPage: 15
    };
    // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
    $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', getList);

    // 按照日期筛选
    $scope.search = function() {
      getList(0);
    };

  // 按照日期筛选
  $scope.search = function(){
    getList();
  };
  // 按照状态筛选
  $scope.filterByStatus = function($status) {
    $scope.status = $status;
    getList();
  };
  // 设置状态值
  $scope.setStatus = function($index, status) {
    var text = '禁用';
    var url = 'customer/disable';
    // （禁用 或 启用） 弹框操作
    var modalFunc =function(){
        console.log($scope.disableLogs);
        dialog.tips({
        actionText: '确定' ,
        list:$scope.disableLogs,
        code:status,
        textMsg:"",
        bodyText: '确定'+text+'['+$scope.list[$index].name+']账号吗?',
        ok: function(_this) {
          var modal = _this;

          var postParam = {
              uid:$scope.list[$index].id,
              remark:'确认启用'
          }
          
          // 禁用理由
          if(this.code==-1){
             postParam.remark = this.textMsg;
          }

          req.getdata(url, 'POST', function(data) {
            if(data.status == 0) {
              alert('操作成功！')
              $scope.list[$index].status = status;
            }else{
              alert('操作失败！' + data.msg);
            }

            modal.cancel();

          }, postParam);
        }
       },{templateUrl:'diablle.html'});
    }
    
    // 启用操作
    if(status == 1) {
      text = '启用';
      url = 'customer/enable';
     
      // 获取禁用日志列表
      request.disableLogList($scope.list[$index].id,function(){
          modalFunc();
      });

    //禁用操作
    }else if(status == -1){
      modalFunc();
    }

    
  };
  // 重置密码
  $scope.resetPassword = function($index) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定重置密码吗?',
      ok: function() {
        req.getdata('customer/reset_password', 'POST', function(data) {
          if(data.status == 0) {
            dialog.tips({bodyText:'密码重置成功！'});
          }else{
            dialog.tips({bodyText:'密码重置失败！'});
          }
        }, {uid:$scope.list[$index].id});
      }
    });
  };
  // 重置搜索条件
  $scope.reset = function() {
    $scope.startTime = '';
    $scope.endTime = '';
    $scope.searchKey = $scope.keyList[0];
    $scope.searchValue = '';
    getList();
  };
  // 批量修改线路
  $scope.batchUpdateLine = function() {
    var id_arr = [];
    angular.forEach($scope.list, function(value, key) {
      if(value.checked) {
        id_arr.push(value.id);
      }

      dialog.tips({
        actionText: '确定',
        bodyText: '确定' + text + '[' + $scope.list[$index].name + ']账号吗?',
        ok: function() {
          req.getdata(url, 'POST', function(data) {
            if (data.status == 0) {
              alert('操作成功！')
              $scope.list[$index].status = status;
            } else {
              alert('操作失败！');
            }
          }, {
            uid: $scope.list[$index].id
          });
        }
      })
     })
   }

    // 重置密码
    $scope.resetPassword = function($index) {
      dialog.tips({
        actionText: '确定',
        bodyText: '确定重置密码吗?',
        ok: function() {
          req.getdata('customer/reset_password', 'POST', function(data) {
            if (data.status == 0) {
              dialog.tips({
                bodyText: '密码重置成功！'
              });
            } else {
              dialog.tips({
                bodyText: '密码重置失败！'
              });
            }
          }, {
            uid: $scope.list[$index].id
          })
        }
      })
    }

    // 重置搜索条件
    $scope.reset = function() {
      $scope.startTime = '';
      $scope.endTime = '';
      $scope.searchKey = $scope.keyList[0];
      $scope.searchValue = '';
      getList();
    };

    // 批量修改线路
    $scope.batchUpdateLine = function() {
      var id_arr = [];
      angular.forEach($scope.list, function(value, key) {
        if (value.checked) {
          id_arr.push(value.id);
        }
      });
      if (id_arr && id_arr.length <= 0) {
        dialog.tips({
          bodyText: '请选择要操作的客户！'
        });
        return;
      }
      dialog.tips({
        actionText: '确定修改',
        bodyText: '修改选中客户线路：',
        line_list: $scope.line_list,
        ok: function(line_change) {
          if (!line_change) {
            dialog.tips({
              bodyText: '请选择线路!'
            });
            return;
          }
          req.getdata('customer/batch_edit_line', 'POST', function(data) {
            if (data.status == 0) {
              dialog.tips({
                bodyText: '批量修改线路成功！'
              });
              getList();
            } else {
              dialog.tips({
                bodyText: '批量修改线路失败！' + data.msg
              });
            }
          }, {
            cid: id_arr,
            line_id: line_change.id
          });
        },
      }, {
        templateUrl: 'line.html'
      });

    };
    // 全选或取消全选
    $scope.checkAll = function() {
      angular.forEach($scope.list, function(value, key) {
        value.checked = $scope.check_all;
      });
    }
  }]);
