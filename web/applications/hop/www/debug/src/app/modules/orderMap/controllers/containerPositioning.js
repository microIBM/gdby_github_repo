'use strict';

angular.module('hop')
.controller('mapCtrl', ['$scope',  '$modal','$filter', 'req', 'dialog',  'lineRouter',function ($scope,$modal,$filter, req, dialog ,lineRouter ) {
  var vm =  $scope.orderdata = {
    successorder :{
      list : [],
      id : '',
    },
    iconModel : [],
    iconObj : [],
    success : [],//待生成的配送单列表
    okData : [],
    successObj : [],
    iconLigt: [],
    enOrder : [],
    orderlen : 0,
    successlen : 0,
    waitinglen : 0,
    orderslen : 0
  };

  var line_route;

  $scope.viewShow = 'list'; // 列表切换
  $scope.orderList = []; // 已选订单
  $scope.checklist = [];
  $scope.idlist = [];

  /*生成订单列表*/
  $scope.sum = 0;  // 初始化行数
  $scope.quantity = 0;   //初始化件数
  $scope.customerSum = 0;   //初始化客户数
  $scope.quantitys = 0;
  $scope.pagesize = 300;

  //生成配送单
  $scope.listOk = function() {
    if(!$scope.orderList.length) {
      dialog.tips({bodyText:'请先选择订单！'})
      return ;
    };
    // 修改已分配订单数量
    $scope.orderdata.successlen += $scope.orderList.length;
    vm.orderslen ++;
    angular.forEach($scope.orderList, function(i) {
      vm.successorder.id += i.id + ',';
      vm.successorder.line = i.line;
      vm.iconObj.push(i.model);
      /*if($scope.viewShow == 'list') {
        var con = i.model.getContent().toString().replace(/ing/,'ed');
        i.model.setContent(con);
      }
      */
      vm.successObj.push(i.id);
      vm.iconLigt.push(i.model);
    });
    angular.forEach($scope.orderdata.data, function(v){
      if(v.isChecked == true){
        v.ngDisabled = true;
      }
    });
    vm.iconModel.push({icon : vm.iconObj});
    vm.successorder.id = vm.successorder.id.substring(0,vm.successorder.id.length - 1);
    var sum = $scope.sum;
    var quantity = $scope.quantity;
    vm.success.push({id:vm.successorder.id, line:vm.successorder.line, customersum:$scope.customerSum, line_id : vm.line_id ,len:$scope.orderList.length , sum : sum , quantity : quantity,date:new Date()});
    vm.successorder.id = '';
    $scope.quantity =  $scope.sum = 0;
    $scope.customerSum  = 0;
    vm.iconObj = [];
    $scope.orderList = [];
  };

  /*列表拖拽排序*/
  $scope.sortableOptions = {
    containment: '#sortable-container',
    accept: function (sourceItemHandleScope, destSortableScope) {
      return sourceItemHandleScope.itemScope.sortableScope.$id === destSortableScope.$id;
    }
  };

  /*删除单个客户列表*/
  $scope.removelist = function(id,model,sum,quantity) {
    angular.forEach($scope.orderdata.data, function(v) {
      if(v.id == id) {
        v.isChecked = false ;
      }
    });
    $scope.sum = $scope.sum - sum ;
    $scope.quantity = $scope.quantity - quantity;
    for(var i in $scope.orderList) {
      if($scope.orderList[i].id == id) {
        $scope.orderList.splice(i,1);
        /*if($scope.viewShow == 'list') {
        var con =  model.getContent().toString().replace(/ing/,'will');
        model.setContent(con);
        }*/
       if($scope.viewShow == 'map'){
        model.setClickable(true);
        model.show();
       }
        //$scope.orderdata.successlen --;
        // $scope.orderdata.enOrder.splice(i,1);  //删除待分配id
      }
    }

    $scope.recalc();
  }

  /*删除配送单*/
  $scope.removeorder = function(index, model, date, id, sum) {
    angular.forEach($scope.orderdata.data, function(v) {
      if(v.isChecked == true) {
        $scope.orderidlist = [];
        $scope.orderidlists= [];
        $scope.orderidlist.push(id);
        $scope.orderidlists = $scope.orderidlist.toString().split(',');

        for(var i in $scope.orderidlists) {
          if(v.id == $scope.orderidlists[i]) {
            v.ngDisabled = false;
            v.isChecked = false;
          }
        }
      }
    });

    for(var i in $scope.orderdata.success) {
      if(date == $scope.orderdata.success[i].date) {
        angular.forEach(vm.iconModel[i].icon,function(j) {
          /*if($scope.viewShow == 'list') {
          var con =  j.getContent().toString().replace(/ed/,'will');
          j.setClickable(true);
          j.setContent(con);
          j.show();
          }
          */
        });

        vm.orderslen -- ;
        vm.successlen = vm.successlen - sum ;
        $scope.orderdata.success.splice(i,1);
        vm.iconModel.splice(i,1);
      }
    };
    var scId = vm.scId  = id.split(',');

    //删除已选id ，解决路线重复
    for(var i =0; i<vm.successObj.length ; i ++) {
      for(var j=0; j<scId.length ; j ++) {
        if(vm.successObj[i] == scId[j]) {
          vm.successObj.splice(i,1)
        }
      }
    }
    if(vm.line_id) {
      $scope.switchRoute(vm.line_id)
    }
  }

  /*配送单数据列表*/
  $scope.maporderOk = function(data) {
    dialog.tips({
      actionText: '确定' ,
      bodyText: '确定生成配送单吗?',
      ok: function(deliver_time) {
        $scope.orderdata.successlen = 0;
        angular.forEach(data,function(i) {
          vm.okData.push({order_ids:i.id,order_count:i.len, line_count:i.sum, sku_count: i.quantity})
        });
        if(!deliver_time) {
          alert('请选择配送时间');
          vm.okData = [];
          return;
        }
        req.getdata('/distribution/create','post',function(data) {
          if(data.status == 0) {
            vm.success = [];
            vm.okData = [];
            dialog.tips({bodyText:data.msg+',配送单号为：'+data.dist_numbers});
            init();
          }
        },{ distributions : vm.okData, deliver_time : deliver_time});
      }
    }, {templateUrl: 'dist.html'});
  }

  //切换线路
  $scope.switchRoute = function(id) {
    $scope.switchrouteid = id;
    return;
    /*
    $scope.orderList = [];
    //重置相关信息
    vm.orderlen = $scope.sum = $scope.quantity = vm.successlen = 0;
    angular.forEach(vm.success , function(i) {
      if( id && i.line_id == id) {
        vm.successlen += i.len ;
      }
      //是否为全部路线   undefined为全部
      if(id == undefined) {
        vm.successlen += i.len ;
      }
    })
    vm.line_id = id;
    $scope.initMarker('order/lists_assign','post',{line_id:id, itemsPerPage : $scope.pagesize} , 'waiting');  //切换线路默认显示未分配订单
    */
  }

  //订单详情
  $scope.orderdetail = function(data) {
    dialog.tips({detail:data},{templateUrl:'orderdetail.html'});
  }

  /*
  //已分配订单
  $scope.successOrder = function() {
    $scope.view('success')
  };
  //未分配订单
  $scope.waitingOrder = function() {
    $scope.view('waiting');
  };
  //全部订单
  $scope.allOrder = function() {
    $scope.view();
  };
  */
  $scope.view = function(type) {
    /*
    if(vm.line_id) { //是否在某一条线路上
      $scope.initMarker('order/lists_assign','post',{line_id: vm.line_id, itemsPerPage : $scope.pagesize} , type);
    }
    $scope.initMarker('order/lists_assign','post',{itemsPerPage : $scope.pagesize}, type);
    */
  }
  // -------------------------------列表展示
  $scope.orderdata.data = null ;
  // 分页配置
  $scope.paginationConf = {
    currentPage: 1,
    itemsPerPage: 15
  };
  $scope.recalc = function() {
      var userArr = [];
    angular.forEach($scope.orderList, function(v){
         userArr.push(v.userId);
    });
    var newArr = [];
    angular.forEach(userArr, function(v) {
      if(newArr.indexOf(v) == -1) {
        newArr.push(v);
      }
    });
    $scope.customerSum = newArr.length;
  };
  // input增加 减少
  $scope.checked = function(item) {
    $scope.quantitys = 0;
    if(item.isChecked == false) {
      angular.forEach($scope.orderList, function(v) {
        if(item.id == v.id) {
          item.model = v.model;
          item.sum = v.sum;
          item.quantity = v.quantity;
          $scope.removelist(item.id, item.model, item.sum, item.quantity)
        }
      })
    }
    if(item.isChecked == true) {
      $scope.sum += item.detail.length;
       angular.forEach(item.detail,function(k,idx) {
        var num = k.quantity * 1;
        $scope.quantity += num;
        $scope.quantitys += num;
      })
      $scope.orderList.push({
        detail:item,
        name:item.realname,
        quantity:$scope.quantitys,
        sum:item.detail.length,
        id:item.id,
        userId:item.user_id,
        line:item.line,
        tel:item.mobile,
        geo:item.geo,
        address:item.deliver_addr,
        store:item.shop_name
      });
    }
    $scope.recalc();
  }

  // 批量添加 - 取消
  $scope.selectAll = function() {
    $scope.isAll = !$scope.isAll;
    if($scope.isAll) {
      angular.forEach($scope.orderdata.data, function(i,k) {
        if(!i.isChecked ){
          i.isChecked = $scope.isAll;
          $scope.quantitys = 0; //每次重置quantitys  ;
          angular.forEach(i.detail , function( k, idx) {
            var num = k.quantity * 1;
            $scope.quantity += num;
            $scope.quantitys += num;
          })
          //添加到已选择列表
          $scope.orderList.push({detail:i,name:i.realname,quantity:$scope.quantitys, sum:i.detail.length, id:i.id ,userId:i.user_id ,tel:i.mobile,address:i.deliver_addr,store:i.shop_name, geo:i.geo})
          $scope.sum += i.detail.length;
        }
      });
    }else{
      angular.forEach($scope.orderdata.data, function(i) {
        if(!i.ngDisabled){
          i.isChecked = $scope.isAll;
          angular.forEach($scope.orderList , function(j , idx) {
            if(i.id == j.id) {
              $scope.sum -= j.sum ;
              $scope.quantity -= j.quantity ;
              $scope.orderList.splice(idx , 1);
            }
          })
        }
      });
    }
    $scope.recalc();
  }
  //订单同步input选中,地图列表切换
  $scope.viewFn = function(viewType) {
    if(viewType == "list") {
      angular.forEach($scope.orderdata.data, function(c) {
        for(var i in $scope.orderList) {
          if(c.id == $scope.orderList[i].id) {
            c.isChecked = true;
          }
        }
      });
      angular.forEach($scope.orderdata.data, function(v) {
        for(var i in $scope.orderdata.success) {
          $scope.idlist = [];
          $scope.idlists= [];
          $scope.idlist.push($scope.orderdata.success[i].id)
          $scope.idlists = $scope.idlist.toString().split(',');
          for(var j in $scope.idlists) {
            if($scope.idlists[j] == v.id) {
              v.isChecked = true;
              v.ngDisabled = true;
            }
          }
        }
      })
    } else if(viewType == "map") {
      $scope.initMarker('','','','',$scope.orderdata.data);

      //$scope.initMarker('order/lists_assign', 'get', '', 'waiting');
    }
    $scope.viewShow = viewType;
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
  $scope.timeList = [
    {id: 'today', val: '全天'},
    {id: 1, val: '上午'},
    {id: 2, val: '下午'},
  ];

  // 初始化数据
  var init = function() {
    //根据城市，日期，时间段筛选
    req.getdata('distribution/line_list', 'POST', function(data) {
      if(data.status == 0) {
        $scope.cityList = data.cities;
        $scope.lineListAll = data.list;
        // $scope.lineListCur = data.list;
        $scope.orderTypeList = data.order_type;
        //$scope.orderType = $scope.orderTypeList[0];
        $scope.switchCity();
      }
    });
  }

  init();

  //城市切换或系统切换
  $scope.switchCity = function(){
    if(!$scope.city) {
      $scope.lineListCur = $scope.lineListAll;
      return;
    }
    $scope.lineListCur = [];
    angular.forEach($scope.lineListAll, function(value, key) {
      var selected = true;
      if($scope.city && value.location_id != $scope.city.id){
        selected = false;
      }

      if(selected) {
        $scope.lineListCur.push(value);
      }
    })
  };
  //筛选
  $scope.mapfilter = function(){
    var city      = $scope.city || {id: 0},
        time      = $scope.time || {id: 0},
        dateValue = $scope.dateValue || "",
        line   = $scope.router || {id: 0},
        orderType = $scope.orderType || {code: 0};
    if(!city.id) {
      alert("请选择配送的城市，再继续操作");
      return;
    }
    if(!orderType.code) {
      //alert('请先选择订单类型');
      //return;
    }
    if(!dateValue) {
      alert("请选择配送的日期，再继续操作");
      return;
    }
    if(!time.id) {
      alert("请选择配送的时段，再继续操作");
      return;
    }
    // 切换线路时不清空已选中订单列表，支持混合多个线路的订单生成一个配送单
    $scope.initMarker('suborder/lists_assign', 'post', {
      line_id: line.id,
      cityId: city.id,
      orderType: orderType.code,
      chosedate: Date.parse(dateValue),
      chosetime: time.id,
      itemsPerPage: $scope.pagesize
    }, 'waiting');
  }
}]);
