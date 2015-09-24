/**
 * BI系统MOBILE端 DASHBOARD
 * @author zhangxiao@dachuwang.com
 */

$(document).ready(function(){

    var dashboard = {
        baseurl : base_url,
        req_url : window.location.href,
        bardate : [],
        data : {},
        custype : 0,
        city : '',
        bar_data_amount : [],
        bar_data_order : [],
        bar_data_cus : [],
        bar_data_cus_price : [],
        order_cus_cnt : [],
        first_order_cus_cnt : [],
        again_order_cus_cnt : [],
        p_cus_cnt : [],
        first_order_cus_price : [],
        again_order_cus_price : [],
        flag : 0,
        //后台获取流水数据
        get_home_data : function() {
            this.bar_data_amount = [];
            this.bar_data_order = [];
            this.bar_data_cus = [];
            this.bar_data_cus_price = [];
            for(var date in this.data) {
                this.bar_data_amount.push((this.data[date].valid_order_amount/100).toFixed(2));
                this.bar_data_order.push(this.data[date].valid_order_cnt);
                this.bar_data_cus.push(this.data[date].resign_cus_cnt);
                var bar_data_cus_price = (this.data[date].valid_order_amount/this.data[date].order_cus_cnt/100).toFixed(2);
                if (!isNaN(bar_data_cus_price)) {
                    this.bar_data_cus_price.push(bar_data_cus_price);
                }else {
                    this.bar_data_cus_price.push(0);
                }
            }
            this.show_bar_data(this.bar_data_amount);
            this.show_home_data();
        },
        //后台获取订单数据
        get_order_data : function() {
            this.bar_data_order = [];
            for(var date in this.data) {
                this.bar_data_order.push(this.data[date].valid_order_cnt);
            }
            this.show_bar_data(this.bar_data_order);
            this.show_order_data(this.bar_data_order);
        },
        //后台获取客户数据
        get_cus_data : function() {
            this.bar_data_cus = [];
            this.order_cus_cnt = [];
            this.first_order_cus_cnt = [];
            this.again_order_cus_cnt = [];
            this.p_cus_cnt = [];
            for(var date in this.data) {
                this.bar_data_cus.push(this.data[date].resign_cus_cnt);
                this.order_cus_cnt.push(this.data[date].order_cus_cnt);
                this.first_order_cus_cnt.push(this.data[date].first_ordered_count);
                this.again_order_cus_cnt.push(this.data[date].again_ordered_count);
                this.p_cus_cnt.push(this.data[date].potential_cus_cnt);
            }
            this.show_bar_data(this.bar_data_cus);
            this.show_cus_data();
        },
        //后台获取客单价数据
        get_cus_price_data : function() {
            this.bar_data_cus_price = [];
            this.first_order_cus_price = [];
            this.again_order_cus_price = [];
            for(var date in this.data) {
                var bar_data_cus_price = (this.data[date].valid_order_amount/this.data[date].order_cus_cnt/100).toFixed(2);
                var first_order_cus_price = (this.data[date].first_amount/this.data[date].first_ordered_count/100).toFixed(2);
                var again_order_cus_price = (this.data[date].again_amount/this.data[date].again_ordered_count/100).toFixed(2);
                if(!isNaN(bar_data_cus_price)) {
                    this.bar_data_cus_price.push(bar_data_cus_price);
                } else {
                    this.bar_data_cus_price.push(0);
                }
                if(!isNaN(first_order_cus_price)) {
                    this.first_order_cus_price.push(first_order_cus_price);
                } else {
                    this.first_order_cus_price.push(0);
                }
                if(!isNaN(again_order_cus_price)) {
                    this.again_order_cus_price.push(again_order_cus_price);
                } else {
                    this.again_order_cus_price.push(0);
                }
            }
            this.show_bar_data(this.bar_data_cus_price);
            this.show_cus_price_data();
        },
        //展示柱状图数据
        show_bar_data : function(data) {
            var max = Math.max.apply(null, data);
            var percentage = [];
            for(var i in data) {
                var p = (data[i]/max*100).toFixed(2);
                if(!isNaN(p)){
                    percentage.push(p);
                }else {
                    percentage.push(0);
                }
            }
            $('.J-simple-bar ul li .bar .inner-bar').each(function(index, element){
                $(this).css('height', percentage[percentage.length - 1 - index]+'%');
            });
        },
        //渲染首页数据
        show_home_data : function() {
            $('.J-amount-data').text(this.bar_data_amount[0]);
            $('.J-order-cnt-data').text(this.bar_data_order[0]);
            $('.J-cus-cnt-data').text(this.bar_data_cus[0]);
            $('.J-cus-price-data').text(this.bar_data_cus_price[0]);
        },
        //渲染订单页数据
        show_order_data : function() {
            $('.J-order-cnt-data').text(this.bar_data_order[0]);
        },
        //渲染客户页数据
        show_cus_data : function() {
            $('.J-cus-cnt-data').text(this.bar_data_cus[0]);
            $('.J-order-cus-cnt-data').text(this.order_cus_cnt[0]);
            $('.J-first-order-cus-cnt-data').text(this.first_order_cus_cnt[0]);
            $('.J-p-cus-cnt-data').text(this.p_cus_cnt[0]);
            $('.J-again-order-cus-cnt-data').text(this.again_order_cus_cnt[0]);
        },
        //渲染客单价数据
        show_cus_price_data : function() {
            $('.J-cus-price-data').text(this.bar_data_cus_price[0]);
            $('.J-first-cus-price-data').text(this.first_order_cus_price[0]);
            $('.J-again-cus-price-data').text(this.again_order_cus_price[0]);
        },
        //渲染、更新总标题
        update_title : function() {
            if($.cookie('custype') == '0') {
                $('.J-cus-name').text('所有客户');
            }else if($.cookie('custype') == '1'){
                $('.J-cus-name').text('普通客户');
            }else if($.cookie('custype') == '2'){
                $('.J-cus-name').text('KA客户');
            }
            switch($.cookie('city')) {
                case '' :
                    $('.J-city-name').text('全国');
                    break;
                case '804' :
                    $('.J-city-name').text('北京');
                    break;
                case '993' :
                    $('.J-city-name').text('上海');
                    break;
                case '1206' :
                    $('.J-city-name').text('天津');
                    break;
            }
        },
        //根据不同的页面得到相应的数据集
        get_data : function(){
            var url_arr = this.req_url.split('/');
            var lastword = url_arr[url_arr.length-1];
            switch (lastword) {
                case 'dashboard' :
                    this.flag = 0;
                    this.get_home_data();
                    break;
                case 'order' :
                    this.flag = 1;
                    this.get_order_data();
                    break;
                case 'customer' :
                    this.flag = 2;
                    this.get_cus_data();
                    break;
                case 'cus_price' :
                    this.flag = 3;
                    this.get_cus_price_data();
                    break;
            }
         },
         //请求数据、更新数据
         update_data : function() {
            $.post(
                dashboard.baseurl+"/statics/get_seven_days_statics",
                {
                    customer_type : $.cookie('custype'),
                    city_id       : $.cookie('city')
                },
                function(data) {
                    if(data.status == 0){
                        dashboard.data = data.data;
                        dashboard.get_data();
                    }else {
                        alert('接口请求错误');
                    }
                },
                "json"
            );
         }
    };

    //页面数据初始化
    dashboard.update_data();

    //初始化筛用户类型、城市
    if($.cookie('custype') != null) {
        $("input[name='custypes'][value='"+$.cookie('custype')+"']").attr('checked', 'true');
    } else {
        $.cookie('custype', '0', {path: "/"});
        $("input[name='custypes'][value='0']").attr('checked', 'true');
    }
    if($.cookie('city') != null) {
        $("input[name='cities'][value='"+$.cookie('city')+"']").attr('checked', 'true');
    } else {
        $.cookie('city', '', {path: "/"});
        $("input[name='cities'][value='']").attr('checked', 'true');
    }
    //初始化标题
    dashboard.update_title();

    //计算最近几天内的日期并插入dom
    (function(){
        var myDate  = new Date();
        var num     = $('.J-simple-bar ul li').length;

        for (var i = 0 ; i < num; i++) {
            dashboard.bardate.push(myDate.getDate());
            myDate.setDate(myDate.getDate() - 1);
        }
        $('.J-bardate').each(function(index, element) {
            var revindex = num - 1 - index;
            var value = dashboard.bardate[revindex];
            $(this).text(value);
            $(this).attr('value', value);
        });

    })();

    //监听日期切换按钮
    $('.J-date').on('click', function() {
        var flag = 0;
        if($(this).attr('name') == 'left'){
            flag = -1;
        }else if($(this).attr('name') == 'right') {
            flag = 1;
        }

        $('.J-simple-bar ul li').each(function(index, element) {
            if(!$(this).find('.triangle').hasClass('hidden')) {

                //flag : -1 表示向左移动一位index的增加值
                //flag : +1 表示向右移动一位index的增加值

                if(flag == -1 && index != 0 || flag == 1 && index != dashboard.bardate.length - 1) {
                    var newindex = index + flag;
                    //箭头移动
                    $(this).find('.triangle').addClass('hidden');
                    $('.J-simple-bar ul li').eq(newindex).find('.triangle').removeClass('hidden');
                    //文字更新
                    var wordindex = dashboard.bardate.length - 1 - newindex;
                    //wordindex为0表示数组第一位，表示今日的日期
                    if(wordindex == 0) {
                        $('.J-dateflag').text('今日');
                    }else if(wordindex > 0) {
                        $('.J-dateflag').text(dashboard.bardate[wordindex] + '号');
                    }

                    switch(dashboard.flag) {
                        // 0 dashboard首页
                        // 1 订单页
                        // 2 客户页
                        // 3 客单价页
                        case 0 :
                            //流水更新
                            $('.J-amount-data').text(dashboard.bar_data_amount[wordindex]);
                            //订单更新
                            $('.J-order-cnt-data').text(dashboard.bar_data_order[wordindex]);
                            //客户更新
                            $('.J-cus-cnt-data').text(dashboard.bar_data_cus[wordindex]);
                            //客单价更新
                            $('.J-cus-price-data').text(dashboard.bar_data_cus_price[wordindex]);
                            break;
                        case 1 :
                            //订单更新
                            $('.J-order-cnt-data').text(dashboard.bar_data_order[wordindex]);
                            break;
                        case 2 :
                            //顾客数更新
                            $('.J-cus-cnt-data').text(dashboard.bar_data_cus[wordindex]);
                            //下单顾客数更新
                            $('.J-order-cus-cnt-data').text(dashboard.order_cus_cnt[wordindex]);
                            //首购客户数更新
                            $('.J-first-order-cus-cnt-data').text(dashboard.first_order_cus_cnt[wordindex]);
                            //潜在客户数更新
                            $('.J-p-cus-cnt-data').text(dashboard.p_cus_cnt[wordindex]);
                            //复购客户数更新
                            $('.J-again-order-cus-cnt-data').text(dashboard.again_order_cus_cnt[wordindex]);
                            break;
                        case 3 :
                            $('.J-cus-price-data').text(dashboard.bar_data_cus_price[wordindex]);
                            $('.J-first-cus-price-data').text(dashboard.first_order_cus_price[wordindex]);
                            $('.J-again-cus-price-data').text(dashboard.again_order_cus_price[wordindex]);
                            break;
                    }

                    return false;
                }
            }
        });

    });

    //监听二级页面导航
    $('.J-order').on('click', function() {
        $(window.location).attr('href', dashboard.baseurl+'/dashboard/order');
    });

    $('.J-cus').on('click', function() {
        $(window.location).attr('href', dashboard.baseurl+'/dashboard/customer');
    });

    $('.J-cus-price').on('click', function() {
        $(window.location).attr('href', dashboard.baseurl+'/dashboard/cus_price');
    });


    //监听站点、城市切换页
    $('.J-nav-ok').on('click', function(){
        $('#x-navbar-collapse').collapse('hide');
    });

    $('#x-navbar-collapse').on('hide.bs.collapse', function () {
        $('body').css('overflow', 'visible');

        dashboard.update_data();
        //初始化箭头指向
        $('.J-simple-bar ul li .triangle').each(function(index, element) {
            if(!$(this).hasClass('hidden')) {
                $(this).addClass('hidden');
            }
        });
        $('.J-simple-bar ul li .triangle').eq(dashboard.bardate.length -1).removeClass('hidden');
    });

    $('#x-navbar-collapse').on('show.bs.collapse', function () {
        $('body').css('overflow', 'hidden');
    });

    $("input[name='custypes']").on('click', function(e){
        $.cookie('custype', e.target.value, {path: "/"});
        dashboard.update_title();
    });

    $("input[name='cities']").on('click', function(e){
        $.cookie('city', e.target.value, {path: "/"});
        dashboard.update_title();
    });

    //监听筛选页
    $('.J-go-bi').on('click', function() {
        $(window.location).attr('href', dashboard.baseurl + "/statics");
    });

});