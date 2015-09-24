$(document).ready(function(){

    var cur_page  = 1;    //默认当前页码
    var total     = 0;    //默认总页码数
    var pageSize  = 10;
    var status    = '';   //今日商品状态
    var value     = '';   //默认排序名称 eg : sale_amount
    var sort      = '';   //默认排序方式 desc/asc
    var flag      = 0;    //默认监听降序升序时ajax请求方法标志
    var tabObj    = null; //默认点击对象  eg : 今日、昨天、上周、上月
    var edate     = '';   //起始日期
    var sdate     = '';   //终止日期
    var tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
    var date_mode = $("select[name=date_mode]").val();
    var urlCommon = base_url+'/statics_sku/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&tab_id='+tabObjVal+'&date_mode='+date_mode+'&from_list=1';
    var id1 = 1;  //默认一级分类ID
    var id2 = 0;  //默认二级分类ID
    var id3 = 0;  //默认三级分类ID
    var warehouse_id = 0;
    
    var get_search_sku_lists = function() {
        flag = 'search';
        $('#J-loading').removeClass('hidden');
        $.ajax({
            'url' : base_url+'/statics_sku/get_search_sku_lists',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'id1' : id1,
                'id2' : id2,
                'id3' : id3,
                'warehouse_id' : warehouse_id,
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
                assembleData(data, 0);
                $('#J-loading').addClass('hidden');
               //分页控制
                if (data.sku_lists.total === 0) {
                    return;
                }
                pageSize = parseInt($(".J-pagesize-num").html()) ? parseInt($(".J-pagesize-num").html()) : 10;
                total = Math.ceil(data.sku_lists.total / pageSize);
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
        id1 = $("select[name=id1]").val();
        id2 = $("select[name=id2]").val();
        id3 = $("select[name=id3]").val();
        warehouse_id = $("select[name=warehouse_id]").val();
        tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
        if ($("input[name=sdate]").val()) {
            if ($("select[name=date_mode]").val() == 3) {
                sdate = $("input[name=sdate]").val();
                edate = $("input[name=edate]").val();
            } else {
                sdate = $("input[name=sdate]").val();
                edate = sdate;
            }
            urlCommon = base_url+'/statics_sku/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&date_mode='+$("select[name=date_mode]").val()+'&sdate='+sdate+'&edate='+edate+'&is_search=1';
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
            'url' : base_url+'/statics_sku/get_sku_by_tab_id',
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
//                console.log(data);
                var today = parseInt($(obj).val());
                assembleData(data, today);
                $('#J-loading').addClass('hidden');
                if (parseInt($(obj).val()) === 1) {
                    $(".page-size").addClass('hidden');
                    $(".total-records").addClass('hidden');
                    return;
                } else {
                    $(".page-size").removeClass('hidden');
                    $(".total-records").removeClass('hidden');
                }
                if (data.sku_lists.total === 0) {
                    return;
                }
                //分页控制
                pageSize = parseInt($(".J-pagesize-num").html()) ? parseInt($(".J-pagesize-num").html()) : 10;
                total = Math.ceil(data.sku_lists.total / pageSize);
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
        urlCommon = base_url+'/statics_sku/detail?menue_id='+php_menue_id+'&city_id='+php_city_id+'&tab_id='+tabObjVal+'&date_mode='+date_mode+'&from_list=1';
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


    var assembleData = function(data, today) {
      var url = '';
      $(".table > tbody").html('');
      var tbody = '';
      if (!data) {
      	return;
      } else if (!data.sku_lists) {
        return;
      } else if (!data.sku_lists.data) {
        return;
      }

      for (var key in data.sku_lists.data) { 
        url = urlCommon+'&sku_number='+data.sku_lists.data[key].sku_number+'&warehouse_id='+data.sku_lists.data[key].warehouse_id;
        tbody += "<tr>";
        tbody += "<td>";
        tbody += data.sku_lists.data[key].sku_number;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].sku_name;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].sale_amount;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].order_sku_counts;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].actual_sale_amount;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].sale_quantity;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].unsalable_day_counts; // 12
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].quantity_inwarehouse;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].quantity_salable;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].average_sale_price;
        tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].average_buy_price;
        tbody += "</td>";

        tbody += "<td>";
        tbody += (data.sku_lists.data[key].margin_rate * 100).toFixed(2);
        tbody += "%</td>";

        tbody += "<td>";
        tbody += (data.sku_lists.data[key].cover_rate * 100).toFixed(2);
        tbody += "%</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].complaint_order_counts;
        tbody += "</td>";

        //tbody += "<td>";
        //tbody += data.sku_lists.data[key].return_order_counts;
        //tbody += "</td>";

        tbody += "<td>";
        tbody += data.sku_lists.data[key].return_sku_counts;
        tbody += "</td>";

        tbody += "<td>";
        tbody += (data.sku_lists.data[key].reject_sku_counts);
        tbody += "</td>";

        tbody += "<td>";
        tbody += (data.sku_lists.data[key].out_warehouse_sku_counts);
        tbody += "</td>";

        status = data.sku_lists.data[key].status == 1 ? "<span class='btn btn-xs btn-success'>已上架</span>" : "<span class='btn btn-xs btn-danger'>已下架</span>";
        tbody += "<td>";
        tbody += status;
        tbody += "</td>";

        tbody += "<td>";
        tbody += '<a target="_blank" href="'+url+'" class="btn btn-info">查看</a>';
        tbody += "</td>";

        tbody += "</tr>";
      }
      $(".table > tbody").html(tbody);
      $(".J-total-num").html(data.sku_lists.total);
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

    //监听pagesize
    $('.J-sku-pagesize').on('click', function(e){
        $('.J-pagesize-num').text($(e.target).attr('value'));
        $('#J-pagination-sku').remove();
        $("#J-pagination-box").append('<ul id="J-pagination-sku"></ul>');
        if (flag === 'tab_id') {
            get_sku_by_tab_id(tabObj);
        } else if (flag === 'search') {
            get_search_sku_lists();
        }
        return;
    });

    $(function(){
        if (getUrlParameter('id1')) {
            //时间控件初始化
            if (tabObjVal = getUrlParameter('tab_id')) {
                $('.J-sku-quik-search > button[value='+tabObjVal+']').addClass('active');
            }
            $("select[name=date_mode]").val(getUrlParameter('date_mode'));
            $("select[name=date_mode]").trigger('change');
            if (sdate = getUrlParameter('sdate')) {
                $('input[name=sdate]').val(sdate);
                $('input[name=edate]').val(getUrlParameter('edate'));
                if (getUrlParameter('date_mode') == 3) {
                    $('.J-week-edate').removeClass('hidden');
                }
            }
            
            if ($("input[name=sdate]").val()) {
                if ($("select[name=date_mode]").val() == 3) {
                    sdate = $("input[name=sdate]").val();
                    edate = $("input[name=edate]").val();
                } else {
                    sdate = $("input[name=sdate]").val();
                    edate = sdate;
                }
                urlCommon = base_url+'/statics_sku/detail?menue_id='+getUrlParameter('menue_id')+'&city_id='+getUrlParameter('city_id')+'&date_mode='+getUrlParameter('date_mode')+'&sdate='+getUrlParameter('sdate')+'&edate='+getUrlParameter('edate')+'&is_search=1';
            }
            id1 = getUrlParameter('id1');
            id2 = getUrlParameter('id2');
            id3 = getUrlParameter('id3');
            warehouse_id = getUrlParameter('warehouse_id');
            get_search_sku_lists();
        } else {
            var yesterday = $('.J-sku-quik-search > button:eq(0)');
            get_sku_by_tab_id(yesterday);
        }
    });
});




