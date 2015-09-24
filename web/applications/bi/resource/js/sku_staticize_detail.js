/**
 * sku统计页面
 * @author zhangxiao@dachuwang.com
 */

$(function(){
  var datepicker = $.fn.datepicker.noConflict();
  $.fn.bootstrapDP = datepicker;
});

$(document).ready(function(){
  var edate = $('.J-sku-edate');
  // 1：日 2：月 3：周
  var whichMode = $('.J-date-select').find(":selected").val();
  if(whichMode == 3) {
    edate.removeClass('hidden');
  }

  var initDatePicker = function(whichMode) {
    $('input[name="sdate"]').val('');
    var dpWeek = $('.J-datepicker-sku-week');
    var dpDay = $('.J-datepicker-sku-day');
    var dpMonth = $('.J-datepicker-sku-month');

    if(whichMode == 1) {
      edate.addClass('hidden');
      dpDay.removeClass('hidden');
      dpMonth.addClass('hidden');
      dpWeek.addClass('hidden');
      dpDay.bootstrapDP({
        language: 'zh-CN',
        format: "yyyy-mm-dd",
        minViewMode: "days",
        autoclose: true,
        orientation : "auto top"
      }).on('show', function(e) {
        $('.datepicker-days tbody tr').off('mouseenter mouseleave');
      }).on('changeDate', function(e) {
          var endDate = dpDay.bootstrapDP('getDate');
          edate.find('input').val(endDate.format("yyyy-mm-dd"));
      });
      dpDay.bootstrapDP('update', $('.J-sdate-hidden').attr('value'));
      $('.J-date-end').val($('.J-sdate-hidden').attr('value'));
    } else if (whichMode == 2) {
      edate.addClass('hidden');
      dpMonth.removeClass('hidden');
      dpDay.addClass('hidden');
      dpWeek.addClass('hidden');
      dpMonth.bootstrapDP({
        language: 'zh-CN',
        format: "yyyy-mm",
        minViewMode: "months",
        autoclose: true,
        orientation : "auto top"
      }).on('show', function(e) {
        $('.datepicker-days tbody tr').off('mouseenter mouseleave');
      }).on('changeDate', function(e) {
          var endDate = dpMonth.bootstrapDP('getDate');
          edate.find('input').val(endDate.format("yyyy-mm"));
      });
      dpMonth.bootstrapDP('update', $('.J-sdate-hidden').attr('value'));
    } else if (whichMode == 3) {
      //edate.addClass('hidden');
      dpWeek.removeClass('hidden');
      dpDay.addClass('hidden');
      dpMonth.addClass('hidden');

      var startDate;
      var endDate;
      dpWeek.bootstrapDP({
        language: 'zh-CN',
        orientation : "auto top",
        format: "yyyy-mm-dd",
        autoclose: true
      }).on('changeDate', function(e) {
          var date = dpWeek.bootstrapDP('getDate');
          startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 1);
          endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 7);
          dpWeek.bootstrapDP('update', startDate);
          edate.removeClass('hidden').find('input').val(endDate.format("yyyy-mm-dd"));
      }).on('show', function(e) {
        $('.datepicker-days tbody tr').hover(function() {
          $(this).css('background-color', '#eee');
        }, function() {
          $(this).css('background-color', '#fff');
        });
      });
      dpWeek.bootstrapDP('update', $('.J-sdate-hidden').attr('value'));
    }
  };
  //初始化日期控件
  initDatePicker(whichMode);

  var removeNoValueSdate = function() {
    if($('.J-sku-day-sdate').find('input[name=sdate]').hasClass('hidden')) {
      $('.J-sku-day-sdate').empty();
    }
    if($('.J-sku-week-sdate').find('input[name=sdate]').hasClass('hidden')) {
      $('.J-sku-week-sdate').empty();
    }
    if($('.J-sku-month-sdate').find('input[name=sdate]').hasClass('hidden')) {
      $('.J-sku-month-sdate').empty();
    }
  }

  //日期控件按日周月切换监控
  $('select[name="date_mode"]').on('change', function(){
    whichMode = $(this).val();
    initDatePicker(whichMode);
  });

  $(".date-tab").click(function(){
      $date_mode = $(this).attr('date-mode');
      $obj=$('select[name=date_mode]').find('option[value='+$date_mode+']');
      $obj.attr('selected',true);
      //$('input[name=date_mode]').val($(this).attr('tab_id'));
      $('input[name=tab_id]').val($(this).attr('tab-id'));
      $('input[name=is_tab_id]').val($(this).attr('tab-id'));
      $('input[name=sdate]').val($(this).attr('date-start'));
      $('input[name=edate]').val($(this).attr('date-end'));
      removeNoValueSdate();
      $('form').submit();
  });
  $("#J_submit").click(function(){
    $('input[name=is_search]').val(1);
    // if($('select[name=date_mode]').val() ==2){
    //   $('input[name=edate]').val($('input[name=sdate]').val());
    // }

    removeNoValueSdate();
    $('form').submit();
  });

});
