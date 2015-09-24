// 路径配置
require.config({
  paths: {
    echarts: 'http://echarts.baidu.com/build/dist'
  }
});
// 使用
require([
    'echarts',
    'echarts/chart/line' // 使用柱状图就加载bar模块，按需加载
  ],
  DrawEChart            //异步加载的回调函数绘制图表
);

var myChart;
function DrawEChart (ec) {
  myChart = ec.init(document.getElementById('order_td')); 
  myChart.showLoading({
    text: "图表数据正在努力加载..."
  });

  //定义图标options
  var options = {
    title : {
      text : "每日订单分时统计",
    },
    tooltip : {
      trigger: 'axis'
    },
    legend : {
      data: []
    },
    toolbox: {
      show: true,
      feature: {
        mark: false
      }
    },
    calculable: true,
    xAxis: [
        {
          type: 'category',
          data: []
        }
    ],
    yAxis: [
        {
          type: 'value',
          splitArea: { show: true }
        }
    ],
    series: [
        {
          "name": "",
          "type": "",
          "data": []
        },
        {
          "name": "",
          "type": "",
          "data": []
        },
        {
          "name": "",
          "type": "",
          "data": []
        }
    ]
  };//options

  function timetodate(time) {
    var date_time  = new Date(time);
    var time_year  = date_time.getFullYear(date_time);
    var time_date  = date_time.getDate(date_time);
    var time_month = date_time.getMonth(date_time);

    if (time_month < 9) {
        time_month = "0" + (time_month + 1);
    }
    if (time_date < 10) {
        time_date  = "0" + time_date;
    }
    
    return return_date = time_year + '-' + time_month + '-' + time_date;

  }
  
  //获取今天日期
  var date_select = $("#date_select").attr("value");
  var date = new Date(date_select);
  //获取昨天日期
  var date_yesterday_time = new Date((date.getTime() - 86400000));
  var date_yesterday = timetodate(date.getTime() - 86400000);
  //获取7天前日期
  var date_seven_days_before_time = new Date((date.getTime() - 7*86400000));
  var date_seven_days_before = timetodate(date.getTime() - 7*86400000);
  //获取URL搜索部分
  var search = window.location.search;
  var site_id = search.substr((search.indexOf('site_id')),9);
  //增加城市筛选 zhangxiao@dachuwang.com
  var city_id = $("input[name='city_id']").val();

  //通过Ajax获取数据 --今天
  $.ajax({
      type: "GET",
      async: false, //同步执行
      url: "order_td_ajax?date=" + date_select + '&' + site_id + '&city_id=' + city_id,
      dataType: "json", //返回数据为json
      success: function (result) {
        if(result) {
          //将返回的category和series对象赋值给options对象内的category和series
          //因为xAxis是一个数组 这里需要是xAxis[i]的形式
          options.xAxis[0].data = result.category;
          options.series[0].data = result.series;
          options.series[0].name = "本日("+ date_select+")";
          options.series[0].type = "line";
          options.legend.data[0] = "本日("+ date_select+")";
        }
      },
      error: function (errorMsg) {
        alert("图表请求数据失败啦!刷新再来一次吧");
      }
  });
  
  //通过Ajax获取数据 --昨天
  $.ajax({
      type: "GET",
      async: false, //同步执行
      url: "order_td_ajax?date=" + date_yesterday + '&' + site_id + '&city_id=' + city_id,
      dataType: "json", //返回数据为json
      success: function (result) {
        if(result) {
          //将返回的category和series对象赋值给options对象内的category和series 
          //因为xAxis是一个数组 这里需要是xAxis[i]的形式
          options.xAxis[0].data = result.category;
          options.series[1].data = result.series;
          options.series[1].name = "昨日("+ date_yesterday+")";
          options.series[1].type = "line";
          options.legend.data[1] = "昨日("+ date_yesterday+")";
        }
      },
      error: function (errorMsg) {
        alert("图表请求数据失败啦!刷新再来一次吧");
      }
  });
  
  //通过Ajax获取数据 --7天前
  $.ajax({
      type: "GET",
      async: false, //同步执行
      url: "order_td_ajax?date=" + date_seven_days_before + '&' + site_id + '&city_id=' + city_id,
      dataType: "json", //返回数据为json
      success: function (result) {
        if(result) {
          //将返回的category和series对象赋值给options对象内的category和series 
          //因为xAxis是一个数组 这里需要是xAxis[i]的形式
          options.xAxis[0].data = result.category;
          options.series[2].data = result.series;
          options.series[2].name = "7天前("+ date_seven_days_before+")";
          options.series[2].type = "line";
          options.legend.data[2] = "7天前("+ date_seven_days_before+")";
        }
      },
      error: function (errorMsg) {
        alert("图表请求数据失败啦!刷新再来一次吧");
      }
  });

  myChart.hideLoading();
  myChart.setOption(options);

}//DrawEChart
