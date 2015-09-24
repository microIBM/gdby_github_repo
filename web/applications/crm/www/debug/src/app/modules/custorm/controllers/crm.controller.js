'use strict';

angular
.module('dachuwang')
.controller('crmCtrl',['$scope', '$state', '$stateParams', '$log', '$window', '$modal', 'rpc', 'pagination', 'daChuDialog', 'geo', 'urlHistoryService','daChuLocal','Analysis', function($scope, $state,  $stateParams, $log, $window, $modal, rpc, pagination, dialog, geo, urlHistoryService, daChuLocal, Analysis) {
  $scope.loadingGeo = false;
  var req_lists = ['potential_customer/lists','customer/register_lists','customer/undone_lists', 'customer/after_sale_lists', 'shared_customer/potential_lists', 'shared_customer/new_register_lists'];
  $scope.oSea = [
    {name:'私海潜在客户',value:0},
    {name:'私海注册客户',value:1},
    {name:'公海潜在客户',value:4},
    {name:'公海注册客户',value:5}
  ];
  $scope.order_fields = [
    {name:'按下单时间排序',value:'latest_ordered_time'},
    {name:'按创建时间排序',value:'created_time'}
  ];
  $scope.order_field = $scope.order_fields[0];
  $scope.show_order_field = function() {
    $scope.list = [];
    $scope.pagination = pagination;
    $scope.pagination.init(getlists);
    $scope.pagination.nextPage();
  }
  
  $scope.seachange = function(item){
    daChuLocal.remove('search_key');
    daChuLocal.remove('time_type');
    daChuLocal.remove('filter_sift');
    $scope.changeStatus(item.value);
  }
  $scope.role_id = true;
  $scope.role_id13 = true;
  var role_id = parseInt(daChuLocal.get('role_id'));
  if(role_id == 12){
     $scope.role_id = false;
  }
  if($scope.role_id13 == 13){
    $scope.role_id13 =false;
  }

  $scope.list = [];
  $scope.func = {
    shopInfosea : function(item) {
      urlHistoryService.push($scope.showType);
      $state.go('page.seainfodetail',{uid:parseInt(item.id)});
    },
    shopInfo : function(item) {
      urlHistoryService.push($scope.showType);
      $state.go('page.customerdetail',{uid:parseInt(item.id)});
    },
    potential_change_shared : function(item) {
      dialog.tips(
        {
          bodyText: '是否确定要把客户放到公海?',
          actionText: "确定",
          ok: function() {
            $scope.change_button = true;
            var cid = parseInt(item.id);
            rpc.load('shared_customer/potential_change_shared', 'POST', {cid : cid})
            .then(function(res) {
              for(var i=0; i<$scope.list.length; i++) {
                if(parseInt($scope.list[i].id) === cid) {
                  $scope.list.splice(i, 1);
                }
              }
              dialog.alert('操作成功');
              $scope.change_button = false;
            }, function(res) {
              dialog.alert(res);
              $scope.change_button = false;
            });
          }
        }
      );
    },
    potential_change_private : function(item) {
      var cid = parseInt(item.id);
      $scope.change_button = true;
      rpc.load('shared_customer/potential_change_private', 'POST', {cid : cid})
        .then(function(res) {
          for(var i=0; i<$scope.list.length; i++) {
            if(parseInt($scope.list[i].id) === cid) {
              $scope.list.splice(i, 1);
            }
          }
          dialog.alert('操作成功');
          $scope.change_button = false;
        }, function(res) {
          dialog.alert(res);
          $scope.change_button = false;
        });
    },
    new_register_change_private : function(item) {
      var cid = parseInt(item.id);
      $scope.change_button = true;
      rpc.load('shared_customer/new_register_change_private', 'POST', {cid : cid})
        .then(function(res) {
          for(var i=0; i<$scope.list.length; i++) {
            if(parseInt($scope.list[i].id) === cid) {
              $scope.list.splice(i, 1);
            }
          }
          dialog.alert('操作成功');
          $scope.change_button = false;
        }, function(res) {
          dialog.alert(res);
          $scope.change_button = false;
        });
    }
  }
  var role_id = parseInt(daChuLocal.get('role_id'));
  $scope.showTabs = (function(role_id){
    //删除之前的需求，所有角色均显示tab
    return true;
  })(parseInt(daChuLocal.get('role_id')));
  $scope.showType = (function(){
    //删除之前的需求，均默认显示私海注册客户
    var last_status = urlHistoryService.pop() ;
    if(last_status && last_status >= 0) {
      return last_status;
    }
    return 1;
  })();
  $scope.defaultsea = (function(showtype) {
    for(var i=0; i<$scope.oSea.length; i++) {
      if(showtype === $scope.oSea[i].value) {
        return $scope.oSea[i];
      }
    }
    return $scope.oSea[1];
  })($scope.showType);
  $scope.filter = null;
  function getlists(callback) {
    var itemsPerPage = 10;
    function getSearchKey() {
      var local;
      if($scope.key) {
        return $scope.key;
      }
      local = daChuLocal.get('search_key');
      if(local!==null) {
        return local;
      }
      return null;
    }
    var postData = {itemsPerPage : itemsPerPage, currentPage : pagination.page, key: getSearchKey(), order_field: $scope.order_field.value};
    if($stateParams.invite_id) {
      postData.invite_id = $stateParams.invite_id;
    }
    if(!$scope.filter){
      var sift = daChuLocal.get('filter_sift');
      if(sift) {
        $scope.filter = {sift: sift};
      }
    }
    //筛选条件
    (function(t) {
      if(t === null) {
        return;
      }
      var obj = {};
      //筛选条件
      if(t.sift && !isEmptyObject(t.sift)) {
        obj.sift = {};
        (function() {
          var i,len,arr;
          arr = ['line','site','province'];
          len = arr.length;
          for(i=0; i<len; i++) {
            if(t.sift[arr[i]]) {
              obj.sift[arr[i]] = parseInt(t.sift[arr[i]].id);
            }
          }
          if(t.sift.dimensions) {
            obj.sift.dimensions = t.sift.dimensions.value;
          }
          if(t.sift.shop_type) {
            obj.sift.shop_type = t.sift.shop_type.id;
          }
          if(t.sift.customer_type) {
            obj.sift.customer_type = parseInt(t.sift.customer_type.value);
          }
          if(t.sift.order_type) {
            obj.sift.order_type = parseInt(t.sift.order_type.value);
          }
        })();
      }
      if(!isEmptyObject(obj)) {
        postData.conditions = obj;
      }
    })($scope.filter);
    //筛选条件时间的处理
    (function() {
      var property,sift = daChuLocal.get('time_type');
      if(!sift) {
        return;
      }
      for(property in sift) {
        postData[property] = sift[property];
      }
    })();
    rpc.load(req_lists[$scope.showType], 'POST', postData).then(function(data) {
      angular.forEach(data.list, function(value) {
        if($scope.showType === 0) {
          try {
            var obj = JSON.parse(value.geo);
            value.geo = obj;
          } catch(e) {
            value.geo = null;
          }
        }
        $scope.list.push(value);
      });
      if(data.list.length<itemsPerPage) {
        callback(true);
      } else {
        callback(false);
      }
      $scope.siftNumber = data.total;
      $scope.totalNumber = data.total_number;
    });
  }
  $scope.pagination = pagination;
  $scope.pagination.init(getlists);
  $scope.pagination.nextPage();

  // 切换显示客户列表
  $scope.changeStatus = function(num) {
    if($scope.showType === num) {
      return;
    }
    daChuLocal.remove('filter_sift');
    $scope.filter = null;
    $scope.showType = num;
    $scope.key = '';
    $scope.list = [];
    $scope.pagination.init(getlists);
    if($scope.list.length<1) {
      $scope.pagination.nextPage();
    }
  };

  // 搜索
  $scope.search = function() {
    daChuLocal.set('search_key', $scope.key);
    $scope.list = [];
    $scope.pagination.init(getlists);
    $scope.pagination.nextPage();
  }

  //  重置密码
  $scope.resetPass = function(uid) {
    $scope.resetClk = true;
    dialog.tips(
      {
      bodyText: '是否要重置该用户密码',
      actionText: "确定",
      ok: function() {
        var postData = {uid: uid};
        rpc.load('customer/update_password', 'POST', postData).then(function(data) {
          $scope.resetClk = false;
          dialog.alert(data.content);
        }, function(msg) {
          dialog.alert('重置密码失败，请联系技术人员');
        });
      }
    }
    );
  }
  // 禁用或者启用
  $scope.setStatus = function(index, status) {
    $scope.list[index].status = status;
    var uid = $scope.list[index].id;
    var postData = {uid: uid, status: status};
    rpc.load('customer/set_status', 'POST', postData)
    .then(function(data) {
      if(status == 0) {
        dialog.alert('禁用成功');
      } else {
        dialog.alert('启用成功');
      }
    }, function(msg) {
      dialog.alert(msg);
    });
  }
  var selected_item = null;

    $window.geoData.callback = function(data) {
      if(selected_item === null) {
        dialog.alert('你必须选择一个用户');
        return false;
      }
      var postData = {
        id : selected_item.id,
        geo : {
          lat : data.latitude,
          lng : data.longitude
        }
      };
      rpc.load('customer/edit','POST',postData).
        then(function(res) {
          if(res.status == 0) {
            selected_item.geo.lat = data.latitude;
            selected_item.geo.lng = data.longitude;
            selected_item.is_located = 1;
            dialog.alert('更新成功');
          } else {
            dialog.alert('更新失败');
          }
      },  function(err) {
        dialog.alert('更新失败 '+err);
      });
    };
    $scope.analysisPhone = function() {
      Analysis.send('客户列表电话');
    }

    $scope.viewMap = function(item) {
      Analysis.send('客户列表路线');
      if(!item.lat || !item.lng) {
        dialog.alert('位置信息不全无法查看,请更新位置信息');
      } else {
        if($window.jsInterface && $window.jsInterface.searchLine) {
          //行为分析统计
          $window.jsInterface.searchLine(JSON.stringify({latitude:item.lat,longitude:item.lng}));
        } else {
          dialog.alert('lat:'+item.lat+', lng:'+item.lng+' 暂不支持查看地图');
        }
      }
    }
    $scope.updateGeo = function(item) {
      var uid = item.id;
      if(!uid) {
        dialog.alert('出错了');
        return false;
      }
      if($window.jsInterface && $window.jsInterface.findRestaurantOnMap) {
        selected_item = item;
        $window.jsInterface.findRestaurantOnMap();
      } else {
        $scope.loadingGeo = true;
        var phone_geo = null;
        geo.info().
          then(function(geoinfo) {
            var pdata = {
              id : uid,
              geo : {
                lat : geoinfo.lat,
                lng : geoinfo.lng
              }
            };
            phone_geo = geoinfo;
            return rpc.load('customer/edit','POST',pdata);
            $log.log(item);
        }).
          then(function(res) {
            $scope.loadingGeo = false;
            if(res.status == 0) {
              item.geo = phone_geo;
              item.is_located = 1;
              dialog.alert('更新成功');
            } else {
              dialog.alert('更新失败');
            }
        }, function(err) {
          $scope.loadingGeo = false;
          dialog.alert('更新失败 '+err);
        });
      }
    }

  $scope.openUser = function(id) {
    $state.go('page.openpot', {potid:id});
  }
  $scope.editUser = function(id) {
    $state.go('page.editpot', {potid:id});
  }
  $scope.deleteUser = function(id) {
    dialog.tips(
      {
        bodyText: '确定要删除客户?',
        actionText: "确定",
        ok: function() {
          rpc.load('potential_customer/delete','POST',{id:id})
          .then(function(res) {
            if(res.status == 0) {
              dialog.alert('删除成功');
              $window.location.reload(true);
            } else {
              dialog.alert('删除失败 '+res.msg)
            }
          }, function(err) {
            dialog.alert('删除失败 '+err);
          });
        }
      }
    );
  }
  $scope.editCustomer = function(id) {
    $state.go('page.editcustomer',{customer_id:id});
  }
  $scope.filterfunc = function() {
    var startTime = new Date().getTime();
    var animation = true;
    var modalInstance = $modal.open({
      animation : animation,
      templateUrl : 'app/modules/custorm/filter.html',
      controller : 'FilterController',
    });
    function getSiftAttr(sift) {
      if(!sift) {
        return null;
      }
      var obj = {};
      var arr = [];
      angular.forEach(sift, function(val, key) {
        arr.push(key);
      });
      if(arr.length > 0) {
        obj.sift = arr;
        return obj;
      }
      return null;
    }
    modalInstance.result.then(function(conditions) {
      $scope.filter = conditions;
      var attr = null;
      var endTime = new Date().getTime();
      if($scope.filter) {
        attr = getSiftAttr($scope.filter.sift);
      }
      Analysis.send('客户列表筛选',attr,endTime-startTime);
      $scope.list = [];
      $scope.pagination.init(getlists);
      if($scope.list.length<1) {
        $scope.pagination.nextPage();
      }
    }, function(err) {
      console.log(err);
    });
   }
   function isEmptyObject(obj) {
     var i;
     for(i in obj) {
       return false;
     }
     return true;
   }
   function initSearchKey() {
    $scope.key = daChuLocal.get('search_key');
   }
   initSearchKey();

   $window.callback_function.sellerRequest = function(types) {
      // dialog.alert('window.crm.sellerRequest' + types);
      rpc.load('customer/nearby_lists', 'POST', types).then(function(data) {
        // dialog.alert('result2:' + JSON.stringify(data.list));
        $window.jsInterface.onReceiveSellers(JSON.stringify(data.list));
      });
   };
   $window.callback_function.cdetail = function(uid) {
     $state.go('page.customerdetail', {uid:parseInt(uid)});
   };
 }]);
