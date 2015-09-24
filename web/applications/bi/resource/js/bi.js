/**
 * BI系统JS控制程序
 * @author zhangxiao@dachuwang.com
 */

$(document).ready(function(){

    //监听鼠标滑动popover事件
    $(".pop").on('mouseenter',function(){
        $(this).popover({
            html: true,
            container: 'body'
        }).popover('show');
    });

    $(".pop").on('mouseleave',function(){
        $(this).popover('hide');
    });

    //文档load完毕监听表头并固定
    setTimeout(function() {
        fixTable();
    },1000);

    //当用户resize窗口，重新初始化监听
    $(window).on("resize", function(){
        fixTable();
    });

    //重置button监听
    $('.reset').on('click', function(){
        $('.search-value').attr("value", "");
    });

    //初始化日期
    $('#datepicker').datepicker({
        language: 'zh-CN',
        format: 'yyyy-mm-dd',
        todayBtn: 'linked',
        todayHighlight: true
    });
    
    $('.J-datepicker-statics').datepicker({
        language: 'zh-CN',
        format: "yyyy-mm",
        startView: "months", 
        minViewMode: "months",
        defaultViewDate: "year",
        autoclose: true,
        orientation : "auto top"
    });

    //监听日期变化
    $('#datepicker').datepicker().on('changeDate', function(e){
        $('#past-7-days').removeClass('active');
        $('#today').removeClass('active');
        $('#yesterday').removeClass('active');
        $("[name='is_tab_id']").attr('value','false');
        if($(e.target).attr('name') == 'from'){
            $("[name='sdate']").attr('value', e.format([0],"yyyy-mm-dd"));
            $("[name='sdate_picker']").attr('value', e.format([0],"yyyy-mm-dd"));
        }else if($(e.target).attr('name') == 'to'){
            $("[name='edate']").attr('value', e.format([0],"yyyy-mm-dd"));
            $("[name='edate_picker']").attr('value', e.format([0],"yyyy-mm-dd"));
        }
    });
    var from = $('.from').attr('value');
    var to = $('.to').attr('value');
    if (from && to) {
      $("[name='sdate']").attr('value', from);
      $("[name='edate']").attr('value', to);
      $("[name='sdate_picker']").attr('value', from);
      $("[name='edate_picker']").attr('value', to);
    }

    //监听选择项
    $('#search-Value').attr('placeholder', '请输入'+$(".search-key > option[selected]").text());
    $(".search-key").on('change', function(e) {
        $(".search-key > option[selected]").attr('selected', false);
        if (this.value == 'c_name') {
            $('#search-Value').attr('placeholder', '请输入客户姓名');
        } else if (this.value == 'c_tel') {
            $('#search-Value').attr('placeholder', '请输入客户电话');
        } else if (this.value == 'c_shop') {
            $('#search-Value').attr('placeholder', '请输入客户店铺名称');
        } else if (this.value == 'c_id') {
            $('#search-Value').attr('placeholder', '请输入客户ID');
        } else {
            $('#search-Value').attr('placeholder', '请输入客户姓名');
        }
    });

    //每个客户订单详情页面，table样式
    $('#customer_detail tbody tr td:even').addClass('table_font_style_item');
    $('#customer_detail tbody tr td:odd').addClass('table_font_style_value');

    //监听表头到top并固定表头
    window.fixTable = function(){
        if($('.table-show').length <= 0) {
            return false;
        }
        //测量表头宽度并复制给隐藏表头
        $(".table-show thead>tr>th").each(function(index,element){
            var width = $(element).outerWidth();
            $(".table-hide thead>tr>th").eq(index).attr("width",width);
        });
        var theadHeight = $('.nav-table').offset().top;
        $(window).scroll(function(){
            var scroHeight = $(this).scrollTop();
            if((scroHeight+50) >= theadHeight && $(window).width() > 916){
                $(".table-hide").css({"display":"table","position":"fixed","top":40,"z-index":1000,"margin":0});
            }else{
                $(".table-hide").css({"display":"none"});
            }
        });
    }

    $('#J-loading').click(function() {
        $(this).addClass('hidden');
    });
});


//全局方法   解析URL
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? false : sParameterName[1];
        }
    }
};

var biGlobal = {
     quickTimer : function (){
         function getCountDays(flag) {
            var curDate = new Date();
            /* 获取当前月份 */
            var curMonth = curDate.getMonth();
           /*  生成实际的月份: 由于curMonth会比实际月份小1, 故需加1 */
           if(flag) {
                curDate.setMonth(curMonth + 1); //获取本月的天数
           } else {
               curDate.setMonth(curMonth); //获取上月的天数
           }
           /* 将日期设置为0, 这里为什么要这样设置, 我不知道原因, 这是从网上学来的 */
           curDate.setDate(0);
           /* 返回当月的天数 */
           return curDate.getDate();
        }
        var date = new Date();
        var curDay = date.getDate();
        var curMonth = date.getMonth();
        var curMonth = date.getMonth();
        var daySum = getCountDays(1);
        var dateBak = new Date();
        var getMonthDate = new Date();
        
        this.today = {
            sdate : date.format('yyyy-mm-dd'),
            edate : date.format('yyyy-mm-dd'),
        };
        date.setDate(date.getDate() - curDay +1);
        dateBak.setDate(daySum);
        this.month = {
            sdate : date.format('yyyy-mm-dd'),
            edate : dateBak.format('yyyy-mm-dd')
        };
        date.setDate(curDay);
        dateBak.setDate(curDay);
        
        date.setDate(date.getDate() - 1);
        this.yesterday = {
            sdate : date.format('yyyy-mm-dd'),
            edate : date.format('yyyy-mm-dd')
        };
        date.setDate(date.getDate() + 1); // 还原
        
        date.setMonth(date.getMonth() - 1);
        dateBak.setMonth(dateBak.getMonth() - 1);
        date.setDate(date.getDate() - curDay +1);
        daySum = getCountDays(0);
        dateBak.setDate(daySum);
        this.lastMonth = {
            sdate : date.format('yyyy-mm-dd'),
            edate : dateBak.format('yyyy-mm-dd')
        };
        date = new Date();
        dateBak = new Date();
        
        date.setDate(date.getDate() - date.getDay() + 1);
        dateBak.setDate(dateBak.getDate() - dateBak.getDay() + 7);
        this.week = {
            sdate : date.format('yyyy-mm-dd'),
            edate : dateBak.format('yyyy-mm-dd')
        };
        date.setDate(date.getDate() + date.getDay() - 1); // 还原
        dateBak.setDate(dateBak.getDate() + dateBak.getDay() - 7); // 还原
        
        date.setDate(date.getDate() - date.getDay() - 6);
        dateBak.setDate(dateBak.getDate() - dateBak.getDay());
        this.lastWeek = {
            sdate : date.format('yyyy-mm-dd'),
            edate : dateBak.format('yyyy-mm-dd')
        };
         
     },
};


