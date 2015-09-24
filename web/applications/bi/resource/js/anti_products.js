/**
 * 竞品分析
 * @author zhangxiao@dachuwang.com
 */

$(document).ready(function() {
  // 展示大图
  $('#myModal').on('show.bs.modal', function (e) {
   var src = e.relatedTarget.src;
   if(src){
     $('.modal-body img').attr('src', src);
   }
  });

  //监听关联按钮
  $(".J-map-anti").on('click', function() {
   var validator = new RegExp(/^\d{7}$/);
   var isNum = validator.test($(".J-sku-num").val());
   if(!isNum){
     alert('商品货号必须是7位数字, 请重新输入哦~');
     return;
   }
   // product_id 和 sku_number 进行关联
   var checkedInfo = getCheckedInfo();
   if(checkedInfo.length !== 0) {
    // 发起添加SKU关联的请求
    var postData = {
      'prod_sku_lists' : checkedInfo
    }
    $.ajax({
      'url' : base_url + '/spider_anti/add_sku',
      'type' : 'post',
      'dataType' : 'json',
      'data'    : postData,
      'success' : function(data) {
        if(data.status == 0) {
          alert('关联操作成功');
          location.reload();
        } else {
          alert(data.msg);
        }
      },
      'fail'    : function() {
        alert('网络请求失败，请重试一下哦~');
      }
    });
   }
  });

  //监听删除和链接sku关联按钮
  $('.J-sku-num-del').on('click', function(e){
    var target = $(e.target);
    if(target.hasClass('sku-num-close')) {
      var confirm = window.confirm('确认要删除?');
      if(!confirm) {
        return;
      }
      var sku_number = target.prev().text();
      var auto_id = null;
      $(this).parent().siblings().each(function() {
        var name = $(this).attr('name');
        if(name == 'auto_id') {
          auto_id = $(this).attr('value');
        }
      });

      if(!sku_number || !auto_id) {
        return;
      }
      //初始化post参数
      var postContent = {
        'auto_id'    : auto_id,
        'sku_number' : sku_number
      }
      var postData = {
        'delete_sku_list' : postContent
      }
      $.ajax({
        'url' : base_url + '/spider_anti/delete_sku',
        'type' : 'post',
        'dataType' : 'json',
        'data'    : postData,
        'success' : function(data) {
          if(data.status == 0) {
            location.reload();
          } else {
            alert(data.msg);
          }
        },
        'fail'    : function() {
          alert('网络请求失败，请重试一下哦~');
        }
      });

    } else if (target.hasClass('sku-num-label')) {
      sku_number = target.text();
      //默认北京1号仓
      window.location.href = base_url + '/statics_sku/detail?menue_id=6&warehouse_id=7&tab_id=2&sku_number='+sku_number;
    }
  });

  function getCheckedInfo() {
    var data = [];
    $('tbody tr').each(function(event){
      var checked = $(this).find('input[type=checkbox]').prop('checked');
      if(checked) {
        var obj = {
          "auto_id" : $(this).find('td[name=auto_id]').attr('value'),
          "sku_number" : $(".J-sku-num").val()
        }
        data.push(obj);
      }
    });
    return data;
  }
});
