'use strict';

angular
.module('hop')
.directive('map', ['req', 'dialog',  function(req, dialog){
  return {
    restrict : 'A' ,
    link : function(scope, ele, attrs){
      var mapObj, marker, markers = [];
      // 地图初始化
      function mapInit(){
        mapObj = new AMap.Map("mapContainer",{
          //center:new AMap.LngLat(116.397428,39.90923), //地图中心点
          level:11  //地图显示的比例尺级别
          //zoomEnable:false
        });
        // AMap.event.addListener(mapObj,'click',getLnglat); //点击事件
      }
      mapInit();
      var toolBar = null;
      var orderQuantity = [];
      var orderDetail = [] ;
      mapObj.plugin(["AMap.ToolBar"],function(){
        toolBar = new AMap.ToolBar();
        mapObj.addControl(toolBar);
      });
      mapObj.plugin(["AMap.MapType"], function() {
        var type = new AMap.MapType({defaultType:0});//初始状态使用2D地图
        mapObj.addControl(type);
      });

      // 创建信息窗体
      function createInfoWindow(title,content){
        var info = document.createElement("div");
        info.className = "info";
        //可以通过下面的方式修改自定义窗体的宽高
        // 定义顶部标题
        var top = document.createElement("div");
        var titleD = document.createElement("div");
        var closeX = document.createElement("img");
        top.className = "info-top";
        titleD.innerHTML = title;
        closeX.src = "http://webapi.amap.com/images/close2.gif";
        closeX.onclick = closeInfoWindow;
        top.appendChild(titleD);
        top.appendChild(closeX);
        info.appendChild(top);

        // 定义中部内容
        var middle = document.createElement("div");
        middle.className = "info-middle";
        middle.style.backgroundColor='white';
        middle.innerHTML = content;
        info.appendChild(middle);

        // 定义底部内容
        var bottom = document.createElement("div");
        bottom.className = "info-bottom";
        bottom.style.position = 'relative';
        bottom.style.top = '0px';
        bottom.style.margin = '0 auto';
        var sharp = document.createElement("img");
        sharp.src = "http://webapi.amap.com/images/sharp.png";
        bottom.appendChild(sharp);
        info.appendChild(bottom);
        return info;
      }

      //关闭信息窗体
      function closeInfoWindow(){
        mapObj.clearInfoWindow();
      }

      scope.markList = [];
      scope.geolist = [];
      scope._initmark = function(orderType , data){
        scope.geolist = [];
        clearMap();

        function isType(){
          if(type && orderType !== 'success'){ //是否被选中,true - 选中
            marker = new AMap.Marker({
              map:mapObj,
              title : data[i].shop_name,
              position:markerPosition, //基点位置
              content:'<div class="mapIcon"  style="background:url(assets/images/will.png); width:45px; height:38px;text-align: right; padding-right: 5px;">'+(data[i].id)+'</div>',
              offset:{x:-8,y:-34} //相对于基点的位置
            });
            marker.hide();
          }else if(!type && orderType !== 'waiting'){
            marker = new AMap.Marker({
              map:mapObj,
              title : data[i].shop_name,
              clickable : false,
              position:markerPosition, //基点位置
              content:'<div class="mapIcon"  style="background:url(assets/images/ed.png); width:45px; height:38px;text-align: right; padding-right: 5px;">'+(data[i].id)+'</div>',
              offset:{x:-8,y:-34} //相对于基点的位置
            });
            marker.hide();
          }
          if(status == 'hideMarker'){
            marker.show();
          }
          scope.markList.push(marker);
        }
        data.orderlist ? (scope.orderdata.data =  data = data.orderlist)  : data = data ;
        if(!data.length){
          dialog.tips({bodyText:'您选择的线路目前没有订单!'});
          return;
        }
        scope.orderdata.orderlen = data.length;
        //mapObj.setCenter( new AMap.LngLat(JSON.parse(data[0].geo).lng || 'null',JSON.parse(data[0].geo).lat || 'null' ))

        mapObj.setFitView(scope.markList)
        var geo = null;
        var successObj =  scope.orderdata.successObj;
        // 设置地图上显示的点为所有已选中订单
        data = scope.orderList;
        // 循环添加地图上标注点
        for(var i = 0, len = data.length; i < len; i++) {
          var type = true;
          var newgeo = '';
          var status = null;
          if(data[i].geo == ''){
            scope.geolist.push(data[i].username);
          }
          if(data[i].geo){
            geo = JSON.parse(data[i].geo);
            var markerPosition = new AMap.LngLat(geo.lng*1, geo.lat*1);
            for (var j = 0; j <successObj.length; j ++) {
              // 路线订单重复
              if(successObj.length && data[i].id == successObj[j]){
                var type = false;
              }
            }
            for(var k=0; k<scope.orderList.length ; k++){
              if(scope.orderList[k].id == data[i].id){
                status  = 'hideMarker';
              }
            }
            isType();

            (function(i){
              AMap.event.addListener(marker, 'click', function(e){
                var infoWindow = new AMap.InfoWindow({
                  isCustom:true,  //使用自定义窗体
                  content:createInfoWindow('&nbsp;'+data[i].shop_name+'&nbsp;<span style="font-size:11px;">客户:'+data[i].realname+'</span>',"地址："+data[i].deliver_addr+"<br/>电话："+data[i].mobile+"<br/>"),
                  offset:new AMap.Pixel(16, -45)//-113, -140
                });
                infoWindow.open(mapObj,this.getPosition());
                scope.orderdata.enOrder.push(data[i].id);  //存储待分配id
                var con =  e.target.getContent().toString().replace(/will/,'will') ;
                e.target.setContent(con);
                //TODO 去掉选中效果
                /*
                   e.target.setClickable(false);
                   e.target.hide();
                   scope.quantitys = 0;
                   for(var k=0; k<data[i].detail.length; k++){
                   var num  = data[i].detail[k].quantity *1;
                   scope.quantity += num;
                   scope.quantitys += num ;
                   }
                   scope.orderList.push({detail:data[i],name:data[i].realname,quantity:scope.quantitys ,sum:data[i].detail.length ,  id:data[i].id ,userId:data[i].user_id ,tel:data[i].mobile,address:data[i].deliver_addr,store:data[i].shop_name,model:e.target})
                   scope.sum += data[i].detail.length;
                   scope.$apply();
                   */
              });
            })(i);
            markers.push(marker);
          }
        }
        mapObj.setFitView(scope.markList)
      }

      //初始化标注
      scope.initMarker = function(url, method , opt, orderType , router){
        clearMap();
        // 如果存在缓存订单就不去请求
        if(router){
          scope._initmark(orderType, router)
          return;
        }
        // 如果不存在缓存订单就重新请求
        req.getdata(url, method, function(data){
          scope.ordersuccess = [];
          scope.alldata= [];
          angular.forEach(scope.orderdata.success , function(c){
            scope.ordersuccess.push(c.id);
          })
          scope.alldata = scope.ordersuccess.toString().split(',');

          // 切换线路时设置已加入待配送列表的订单为选中不可操作状态
          if(scope.alldata){
            angular.forEach(scope.alldata, function(v){
              angular.forEach(data.orderlist, function(k){
                if(v == k.id){
                  k.ngDisabled = true;
                  k.isChecked = true;
                }
              })
            })
          }
          // 切换线路时设置已选订单为选中状态
          if(scope.orderList) {
            angular.forEach(scope.orderList, function(v){
              angular.forEach(data.orderlist, function(k){
                if(v.id == k.id){
                  k.isChecked = true;
                }
              })
            })
          }
          // 添加地图标注点
          scope._initmark(orderType ,data);
        }, opt);
        if(!opt.city_id || !opt.chosedate || !opt.chosetime) {
          return;
        }
      }

      //清空地图
      function clearMap() {
        mapObj.clearMap();
      }
      // scope.initMarker('order/lists_assign', 'post', {itemsPerPage : 300}, 'waiting');
    }
  }
}]);
