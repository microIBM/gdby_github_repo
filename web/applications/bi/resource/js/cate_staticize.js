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
  $('select[name="date_mode"]').on('change', function(){
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

  //组装select数据
  var assemble_cate_tags = function(data) {
    if( !data || data['status'] != 0 ) {
      alert(data['msg']);
      return false;
    }
    var items = [];
    $.each( data['result'], function( key, val ) {
      items.push( "<option value='" + val['category_id'] + "'>" + val['category_name'] + "</option>" );
    });
    return items;
  }

  // 初始化一级分类列表
  $.post( base_url+"/statics_cate/get_category_info",
    function( data ) {
      var items = assemble_cate_tags( data );
      if (items) {
        items.unshift("<option value=''>全部</option>");
        $('.J-cate-top').html(items.join(''));
//        $('.J-cate-top-name').text(data['result'][0]['category_name']);
//        $('.J-cate-top-name').attr('value', data['result'][0]['category_id']);
      }
    },
    'json'
  );
  // 初始化二级分类列表
  $.post( base_url+"/statics_cate/get_category_info",
    {
      //默认米面粮油
      category_id: 1
    },
    function( data ) {
      var items = assemble_cate_tags( data );
      if(items) {
        items.unshift("<option value=''>全部</option>");
        $('.J-cate-second').html(items.join(''));
      }
    },
    'json'
  );

  // 初始化三级分类列表
  $('.J-cate-third').html("<option value=''>全部</option>");

  //监听一级分类点击
  $('.J-cate-top').on('change', function() {
    var categoryID = $(this).val();
    if(categoryID == ''){
      $('.J-cate-second').html("<option value=''>全部</option>");
      $('.J-cate-second-name').empty().addClass('hide').removeAttr('value');
      $('.J-cate-top-name').empty().addClass('hide').removeAttr('value');
      $('.J-cate-third').html("<option value=''>全部</option>");
      $('.J-cate-third-name').empty().addClass('hide').removeAttr('value');
      $('.J-cate-second-name').empty().addClass('hide').removeAttr('value');
      return;
    }
    $('.J-cate-top-name').empty().text($(this).find(":selected").text()).removeClass('hide');
    $('.J-cate-top-name').empty().text($(this).find(":selected").text());
    $('.J-cate-top-name').attr('value', categoryID);
    $('.J-cate-second-name').empty().addClass('hide').removeAttr('value');
    $('.J-cate-third-name').empty().addClass('hide').removeAttr('value');

    $.post( base_url+"/statics_cate/get_category_info",
      {
        category_id: categoryID
      },
      function( data ) {
        var items = assemble_cate_tags( data );
        if(items) {
          items.unshift("<option value=''>全部</option>");
          $('.J-cate-second').html(items.join(''));
          $('.J-cate-third').html("<option value=''>全部</option>");
        } else {
          $('.J-cate-second').html("<option value=''>全部</option>");
          $('.J-cate-third').html("<option value=''>全部</option>");
        }
      },
      'json'
    );
  });

  //监听二级分类点击
  $('.J-cate-second').on('change', function() {
    var categoryID = $(this).val();
    if(categoryID == ''){
      $('.J-cate-third').html("<option value=''>全部</option>");
      $('.J-cate-third-name').empty().addClass('hide').removeAttr('value');
      $('.J-cate-second-name').empty().addClass('hide').removeAttr('value');
      return;
    }
    $('.J-cate-second-name').empty().text($(this).find(":selected").text()).removeClass('hide');
    $('.J-cate-second-name').attr('value', $(this).find(":selected").attr('value'));
    $('.J-cate-third-name').empty().addClass('hide').removeAttr('value');

    $.post( base_url+"/statics_cate/get_category_info",
      {
        category_id: categoryID
      },
      function( data ) {
        var items = assemble_cate_tags( data );
        if(items) {
          items.unshift("<option value=''>全部</option>");
          $('.J-cate-third').html(items.join(''));
        } else {
          $('.J-cate-third').html("<option value=''>全部</option>");
        }
      },
      'json'
    );
  });

  //监听三级分类点击
  $('.J-cate-third').on('change', function() {
    $('.J-cate-third-name').empty().text($(this).find(":selected").text()).removeClass('hide');
    $('.J-cate-third-name').attr('value', $(this).find(":selected").attr('value'));
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
    },
    'json'
  );

});
