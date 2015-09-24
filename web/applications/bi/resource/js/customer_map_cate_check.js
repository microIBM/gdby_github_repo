$(document).ready(function(){
    var meat = null;
    var vegetables = null;
    var sku_cate = '';
    $('#all_select').on('click', function(){
        $('input[name="sku_cate_ids[]"]').each(function(){
            $(this).prop('checked', $('#all_select').prop('checked'));
        });
    });
    
    $('input[name="sku_cate_ids[]"]:checked').each(function(){
        sku_cate = sku_cate + $(this).attr('content') + ' ';
    });
    $('.J-span-sku-content').html(sku_cate);
    $('.J-button-ok').on('click', function(){
        sku_cate = '';
        $('.J-input-sku-content').val('');
        $('.J-input-sku-content').val('已选择品类');
        $('input[name="sku_cate_ids[]"]:checked').each(function(){
            sku_cate = sku_cate + $(this).attr('content') + ' ';
        });
        $('.J-span-sku-content').html('');
        $('.J-span-sku-content').html(sku_cate);
    });
    
    // 百度地图API功能
    var map = new BMap.Map("allmap");
    map.enableScrollWheelZoom(true);
    var opts = {
        width : 235,     // 信息窗口宽度
        height: 120,     // 信息窗口高度
    }
    
    var eventListenerHandle = function(content, marker) {
        marker.addEventListener("mouseover", function(e){          
            openInfo(content, e); //开启信息窗口
        });
    }
    
    function openInfo(content,e){
            var p = e.target;
            var point = new BMap.Point(p.getPosition().lng, p.getPosition().lat);
            var infoWindow = new BMap.InfoWindow(content,opts);  // 创建信息窗口对象 
            map.openInfoWindow(infoWindow,point); //开启信息窗口
    }
    
    // 用经纬度设置地图中心点
    var theLocation = function(data) {
        console.log(data);
        if (php_city_id == 804) {
            map.centerAndZoom('北京',13);
        } else if (php_city_id == 993) {
            map.centerAndZoom('上海',13);
        } else if (php_city_id == 1206) {
            map.centerAndZoom('天津',13);
        }
        map.clearOverlays();
        var new_point = [];
        var marker = [];
        var infoWindow   = [];
        var customerInfo = [];
        var icon_blue = new BMap.Icon('http://cache.amap.com/lbs/static/jsdemo003.png', new BMap.Size(19,33));
        var icon_red = new BMap.Icon(base_url+'/resource/img/icon_red.png', new BMap.Size(19,33));
        if(data.status == 0) {
            for(var key in data.customer_info) {
                new_point[key] = new BMap.Point(data.customer_info[key].lng, data.customer_info[key].lat);
                if (data.customer_info[key].customer_unit_price > php_price_border) {
                    marker[key] = new BMap.Marker(new_point[key], {icon:icon_red});
                }else {
                    marker[key] = new BMap.Marker(new_point[key], {icon:icon_blue});
                }
                map.addOverlay(marker[key]);             
                map.panTo(new_point[key]);

                customerInfo[key] = "商户名: "+data.customer_info[key].shop_name+"<br/>电话: "+data.customer_info[key].mobile+"<br/>路线: "+data.customer_info[key].line+"<br/>销售: "+data.customer_info[key].salesman+"<br/>日客单价: "+data.customer_info[key].customer_unit_price+"<br/>下单总金额: "+data.customer_info[key].amounts+"<a style='float:right;' href='"+base_url+"/customer_statics/show_cus_detail?cus_id="+data.customer_info[key].customer_id+"&city_id="+php_city_id+"&menue_id=4'>查看</a>";
                eventListenerHandle(customerInfo[key], marker[key]);
            }
        }
        //添加地图缩放控件
        var top_left_control = new BMap.ScaleControl({anchor: BMAP_ANCHOR_TOP_LEFT});// 左上角，添加比例尺
	var top_left_navigation = new BMap.NavigationControl();  //左上角，添加默认缩放平移控件
	var top_right_navigation = new BMap.NavigationControl({anchor: BMAP_ANCHOR_TOP_RIGHT, type: BMAP_NAVIGATION_CONTROL_SMALL}); //右上角，仅包含平移和缩放按钮
        map.addControl(top_left_control);        
        map.addControl(top_left_navigation);     
        map.addControl(top_right_navigation);
	
    }
    $(function(){
        theLocation(php_map_data);
    });
});