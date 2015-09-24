$(document).ready(function(){

    var cur_page  = 1;    //默认当前页码
    var total     = 0;    //默认总页码数
    var pageSize  = 10;
    var value     = '';   //默认排序名称 eg : sale_amount
    var sort      = '';   //默认排序方式 desc/asc
    var flag      = 0;    //默认监听降序升序时ajax请求方法标志
    var tabObj    = $('.J-sku-quik-search > button:eq(0)'); //默认点击对象  eg : 今日、昨天、上周、上月
    var edate     = '';   //起始日期
    var sdate     = '';   //终止日期
    var tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
    var date_mode = $("select[name=date_mode]").val();
    var urlCommon = base_url+'/statics_cate/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&tab_id='+tabObjVal+'&date_mode='+date_mode;
	var getCrumbs = function() {
        var catenames = '';
        var cateids = '';
        var cate1name = $('.J-cate-top-name').text();
        var cate2name = $('.J-cate-second-name').text();
        var cate3name = $('.J-cate-third-name').text();
        if(cate1name) {
            catenames += cate1name;
            cateids   += $('.J-cate-top-name').attr('value');
        }
        if(cate2name) {
            catenames += '-' + cate2name;
            cateids   += '-'+$('.J-cate-second-name').attr('value');
        }
        if(cate3name) {
            catenames += '-' + cate3name;
            cateids   += '-'+$('.J-cate-third-name').attr('value');
        }
        return [catenames, cateids];
    }

    var get_search_sku_lists = function() {
        flag = 'search';
        $('#J-loading').removeClass('hidden');
        $.ajax({
            'url' : base_url+'/statics_cate/get_lists',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'id1' : $("select[name=id1]").val(),
                'id2' : $("select[name=id2]").val(),
                'id3' : $("select[name=id3]").val(),
                'warehouse_id' : $("select[name=warehouse_id]").val(),
                'search_key' : $("select[name=search_key]").val(),
                'search_value' : $("input[name=search_value]").val(),
                'date_mode' : $("select[name=date_mode]").val(),
                'sdate' : $("input[name=sdate]").val(),
                'edate' : $("input[name=edate]").val(),
                'page'  : cur_page,
                'offset' : $(".J-pagesize-num").html(),
                'sort_name' : value,
                'sort_value' : sort,
                'tab_id' : $(tabObj).val(),
                'city_id' : php_city_id
            },
            'success' : function(data){
                assembleData(data);
                $('#J-loading').addClass('hidden');
               //分页控制
                if (data.total == 0) {
                    return;
                }
                pageSize = parseInt($(".J-pagesize-num").html()) ? parseInt($(".J-pagesize-num").html()) : 10;
                total = Math.ceil(data.total / pageSize);
                $('#J-pagination-sku').twbsPagination({
                    totalPages: total,
                    visiblePages: (total > 7 ? 7 : total),
                    first: '首页',
                    last: '末页',
                    prev: '上一页',
                    next: '下一页',
                    onPageClick: function (event, page) {
                        cur_page = page;
                        get_search_sku_lists();
                    }
                });
                fixTable();
            },
            'fail' : function(){
              $('#J-loading').addClass('hidden');
              alert('数据请求失败，请刷新页面试试吧亲~');
            }
        });
    };
	

    $('.J-sku-search').on('click', function(){
        cur_page = 1;
        total    = 0;
        tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(1)').val();
        if ($("input[name=sdate]").val()) {
            if ($("select[name=date_mode]").val() == 3) {
                sdate = $("input[name=sdate]").val();
                edate = $("input[name=edate]").val();
            } else {
                sdate = $("input[name=sdate]").val();
                edate = sdate;
            }
            urlCommon = base_url+'/statics_cate/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&date_mode='+$("select[name=date_mode]").val()+'&sdate='+sdate+'&edate='+edate;
        }
        if($("input[name=sdate]").val()) {
          $('.J-sku-quik-search').find('button').each(function(){
            $(this).removeClass('active');
          });
          tabObj = null;
        }
        if ($(tabObj).val() == 1) {
            return;
        }
        $('#J-pagination-sku').remove();
        $("#J-pagination-box").append('<ul id="J-pagination-sku"></ul>');
        $(".page-size").removeClass('hidden');
        $(".total-records").removeClass('hidden');
        get_search_sku_lists();
    });

    var get_sku_by_tab_id = function(obj){
        flag = 'tab_id';
        $('#J-loading').removeClass('hidden');
        $.ajax({
            'url' : base_url+'/statics_cate/get_lists',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'id1' : $("select[name=id1]").val(),
                'id2' : $("select[name=id2]").val(),
                'id3' : $("select[name=id3]").val(),
                'warehouse_id' : $("select[name=warehouse_id]").val(),
                'tab_id' : $(obj).val(),
                'page'  : cur_page,
                'offset' : $(".J-pagesize-num").html(),
                'sort_name' : value,
                'sort_value' : sort,
                'city_id' : php_city_id
            },
            'success' : function(data){
                assembleData(data);
                $('#J-loading').addClass('hidden');
                if (parseInt($(obj).val()) === 1) {
                    $(".page-size").addClass('hidden');
                    $(".total-records").addClass('hidden');
                    return;
                } else {
                    $(".page-size").removeClass('hidden');
                    $(".total-records").removeClass('hidden');
                }
                if (data.total == 0) {
                    return;
                }
                //分页控制
                pageSize = parseInt($(".J-pagesize-num").html()) ? parseInt($(".J-pagesize-num").html()) : 10;
                total = Math.ceil(data.total / pageSize);
                $('#J-pagination-sku').twbsPagination({
                    totalPages: total,
                    visiblePages: total > 7 ? 7 : total,
                    first: '首页',
                    last: '末页',
                    prev: '上一页',
                    next: '下一页',
                    onPageClick: function (event, page) {
                        cur_page = page;
                        get_sku_by_tab_id(obj);
                    }

                });
                fixTable();
            },
            'fail' : function(){
              $('#J-loading').addClass('hidden');
              alert('数据请求失败，请刷新页面试试吧亲~');
            }
        });
    };
    $('.J-sku-quik-search').on('click', function(e) {
        cur_page = 1;
        total    = 0;
        tabObj   = e.target;
        tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
        $("input[name=tab_id]").val(tabObjVal);
        if (tabObjVal == 4) {
            date_mode = 2;
        } else if (tabObjVal == 3) {
            date_mode = 3;
        } else {
            date_mode = 1;
        }
        urlCommon = base_url+'/statics_cate/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&tab_id='+tabObjVal+'&date_mode='+date_mode;
        $('span[sort]').each(function() {
          $(this).removeClass('hidden');
        });
        $('input[name=sdate]').val('');
        $('input[name=edate]').val('');
        $('.J-week-edate').addClass('hidden');
        $('.J-sku-quik-search > button').removeClass(function(index, className) {
            return "active";
        });
        if($(e.target).attr('value') == 1) {
          $('span[sort]').each(function() {
            $(this).addClass('hidden');
          });
          if(!$('.J-warehouse').val()) {
            $('.J-warehouse').val($('.J-warehouse > option').eq(1).attr('value'));
          }
        }
        $(e.target).addClass('active');
        $('#J-pagination-sku').remove();
        $("#J-pagination-box").append('<ul id="J-pagination-sku"></ul>');
        get_sku_by_tab_id(e.target);
    });


    var assembleData = function(data) {
      $(".table > tbody").html('');
      var tbody = '';
      if (!data) {
        return;
      } else if (!data.data) {
        return;
      }
      var my_thead = ['category_name', 'sale_amount', 'actual_sale_amount', 'buy_amount', 'sale_quantity', 'online_sku_counts', 'sale_sku_kinds_margin', 'gross_margin', 'order_cus_margin', 'complaint_order_margin', 'return_goods_orders', 'rejection_orders'];
      tbody = create_table(data.data, my_thead);
      $(".table > tbody").html(tbody);
      $(".J-total-num").html(data.total);
    }

    //监听降序升序
    $('.J-sku-sort ').on('click', function(e) {
        if ($(tabObj).val() == 1) {
            return;
        }
        value = $(e.target).attr('value');
        sort  = $(e.target).attr('sort');
        $('#J-pagination-sku').remove();
        $("#J-pagination-box").append('<ul id="J-pagination-sku"></ul>');
        if(value && sort) {
            $('.sku-sort-up span').each(function(){
              $(this).removeClass('blue');
            });
            $('.sku-sort-down span').each(function(){
              $(this).removeClass('blue');
            });
            $(e.target).addClass('blue');
            if (flag === 'tab_id') {
                get_sku_by_tab_id(tabObj);
            } else if (flag === 'search') {
                get_search_sku_lists();
            }
            return;
        }
    });
    
    var create_table = function (data, my_thead) {
        var url = '';
        var items = [];
        $.each ( data , function(index, row) {
            url = urlCommon+'&category_id='+row.category_id+'&warehouse_id='+row.warehouse_id+'&catenames='+getCrumbs()[0]+'-'+row.category_name+'&cateids='+getCrumbs()[1]+'-'+row.category_id;
            items.push('<tr>');
            $.each( my_thead, function( index, val ) {
                items.push( "<td>" + row[val] + "</td>" );
            });
            items.push('<td><a target="_blank" href="'+url+'" class="btn btn-info">查看</a></td>');
            items.push('</tr>');
        });
        return items.join('');
    }
    
    //监听pagesize
    $('.J-sku-pagesize').on('click', function(e){
        $('.J-pagesize-num').text($(e.target).attr('value'));
        $('#J-pagination-sku').remove();
        $("#J-pagination-box").append('<ul id="J-pagination-sku"></ul>');
        cur_page = 1;
        if (flag === 'tab_id') {
            get_sku_by_tab_id(tabObj);
        } else if (flag === 'search') {
            get_search_sku_lists();
        }
        return;
    });

    $(function(){
        var yesterday = $('.J-sku-quik-search > button:eq(0)');
        get_sku_by_tab_id(yesterday);
    });
});
