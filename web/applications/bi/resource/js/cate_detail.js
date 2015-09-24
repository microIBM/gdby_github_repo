/**
 * cate统计页面
 * @author zhangxiao@dachuwang.com
 */

$(function(){
  var datepicker = $.fn.datepicker.noConflict();
  $.fn.bootstrapDP = datepicker;
});

$(document).ready(function(){
  
  var mydatepicker = $('.J-datepicker-sku');
  var weekEndDate = $('.J-week-edate');
  // 1：日 2：月 3：周
  var whichMode = 1;
  //初始化仓库ID
  var warehouse_id = 0;
  //初始化日期控件
  mydatepicker.bootstrapDP({
    language: 'zh-CN',
    format: "yyyy-mm-dd",
    minViewMode: "days",
    multidate: false,
    autoclose: true,
    orientation : "auto top",
  });
  //日期控件按日周月切换监控
  $('select[name="date-mode"]').on('change', function(){
    whichMode = $(this).val();
    if(whichMode == 1) {
      format = "yyyy-mm-dd";
      minViewMode = "days";
    } else if (whichMode == 2) {
      format = "yyyy-mm";
      minViewMode = "months";
    }
    $('input[name="sdate"]').val('');
    mydatepicker.bootstrapDP('remove');
    weekEndDate.addClass('hidden');

    if(whichMode != 3) {
      mydatepicker.bootstrapDP({
        language: 'zh-CN',
        format: format,
        minViewMode: minViewMode,
        multidate: false,
        autoclose: true,
        orientation : "auto top",
      }).on('show', function(e) {
        $('.datepicker-days tbody tr').off('mouseenter mouseleave');
      });
    } else {
      var startDate;
      var endDate;

      mydatepicker.bootstrapDP({
        language: 'zh-CN',
        format: "yyyy-mm-dd",
        orientation : "auto top",
        autoclose: true
      }).on('changeDate', function(e) {
        if(whichMode == 3) {
          var date = mydatepicker.bootstrapDP('getDate');
          startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 1);
          endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 7);
          mydatepicker.bootstrapDP('update', startDate);
          weekEndDate.removeClass('hidden').find('input').val(endDate.format("yyyy-mm-dd"));
        }
      }).on('show', function(e) {
        $('.datepicker-days tbody tr').hover(function() {
          $(this).css('background-color', '#eee');
        }, function() {
          $(this).css('background-color', '#fff');
        });
      });
    }
  });

  //初始化化仓库信息
  $.post( base_url+"/statics_cate/get_warehouse_info",
    function( data ) {
      if( data['status'] != 0 ) {
        alert(data['msg']);
        return;
      }
      var items = [];
      $.each( data['result'], function( key, val ) {
        if(val['location_id'] == php_city_id ) {
          var name = val['warehouse_name'];
          var patt = new RegExp('测试');
          if (!patt.test(name)) {
            items.push( "<option value='" + val['warehouse_id'] + "'>" + val['warehouse_name'] + "</option>" );
          }
        }
      });
      items.unshift("<option value=''>全部</option>");
      $('.J-warehouse').html(items.join(''));
      
      if (getUrlParameter('warehouse_id')) {
        $('.J-warehouse').val(getUrlParameter('warehouse_id'));
      }
    },
    'json'
  );
    
    //ajax请求数据
    var tabObj       = null; //默认点击对象  eg : 昨天、上周、上月
    var sdate        = '';
    var tabObjVal    = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();

    var get_cate_lists = function() {
        $('#J-loading').removeClass('hidden');
        $.ajax({
            'url' : base_url+'/statics_cate/get_cate_day_lists',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'category_id' : php_category_id,
                'warehouse_id' : warehouse_id,
                'tab_id' : tabObjVal,
                'date_mode' : $("select[name=date-mode]").val(),
                'sdate' : $("input[name=sdate]").val(),
                'edate' : $("input[name=edate]").val(),
                'city_id' : php_city_id,
                'cateids' : getUrlParameter('cateids')
            },
            'success' : function(data){
                if (!data) {
                  $(".table > tbody").html('');
                  $('#J-loading').addClass('hidden');
                  return;
                }
                console.log(data);
                $(".table > tbody").html('');
                $(".table > tbody").html(data);
                $('#J-loading').addClass('hidden');
            },
            'fail' : function(){
              $('#J-loading').addClass('hidden');
              alert('数据请求失败，请刷新页面试试吧亲~');
            }
        });
    };

    $('.J-sku-search').on('click', function(){
        if($("input[name=sdate]").val()) {
          $('.J-sku-quik-search').find('button').each(function(){
            $(this).removeClass('active');
          });
          tabObj = null;
        }
        tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
        warehouse_id = $("select[name=warehouse-id]").val();
        get_cate_lists();
    });
    
    $('.J-sku-quik-search').on('click', function(e) {
        tabObj   = e.target;
        tabObjVal = $(tabObj).val() ? $(tabObj).val() : $('.J-sku-quik-search > button:eq(0)').val();
        warehouse_id = $("select[name=warehouse-id]").val();
        $('input[name=sdate]').val('');
        $('input[name=edate]').val('');
        $('.J-week-edate').addClass('hidden');
        $('.J-sku-quik-search > button').removeClass(function(index, className) {
            return "active";
        });
        if (tabObjVal == 2) {
            $("select[name=date-mode]").val(1);
            $("select[name=date-mode]").trigger('change');
        } else if (tabObjVal == 3) {
            $("select[name=date-mode]").val(3);
            $("select[name=date-mode]").trigger('change');
        } else if (tabObjVal == 4) {
            $("select[name=date-mode]").val(2);
            $("select[name=date-mode]").trigger('change');
        }
        $(e.target).addClass('active');
        get_cate_lists();
    });

    $(function(){
        if (tabObjVal = getUrlParameter('tab_id')) {
            $('.J-sku-quik-search > button[value='+tabObjVal+']').addClass('active');
        }
        $("select[name=date-mode]").val(getUrlParameter('date_mode'));
        $("select[name=date-mode]").trigger('change');
        if (sdate = getUrlParameter('sdate')) {
            $('input[name=sdate]').val(sdate);
            $('input[name=edate]').val(getUrlParameter('edate'));
            if (getUrlParameter('date_mode') == 3) {
                $('.J-week-edate').removeClass('hidden');
            }
        }
        warehouse_id = getUrlParameter('warehouse_id');
        get_cate_lists();
    });
});
