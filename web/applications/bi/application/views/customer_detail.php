<!-- 大厨、大果货物销量统计模板-->
<?php include APPPATH."views/shared/header.php"?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main ">
    <h1 class="page-header hidden-print">
    大厨网 · 客户分析
    </h1>

<!--客户信息统计展示 -->
<div class="col-md-12 second hidden-print">
  <div class="panel panel-default">
    <div class="table-responsive">
      <table id="customer_detail" class="table table-condensed">
      <tbody>
        <tr>
          <td class="table_font_style">店铺名称：</td>
          <td><?php echo $cus_info['base_info']['shop_name']?></td>
          <td></td>
          <td></td>
          <td>订单数(已合并)：</td>
          <td><?php echo isset($cus_info['order_info']['distinct_cnt']) ? $cus_info['order_info']['distinct_cnt'] : 0?></td>
          <td>店铺规模：</td>
          <td><span id="customer_dimension"><?php echo ($cus_info['base_info']['dimensions'])?></span></td>
        </tr>
        <tr>
          <td>客户姓名：</td>
          <td><?php echo $cus_info['base_info']['name']?></td>
          <td></td>
          <td></td>
          <td>订单金额(￥)：</td>
          <td><?php echo isset($cus_info['order_info']['order_amount']) ? format_money($cus_info['order_info']['order_amount'], 2) : 0?></td>
          <td>客单价(￥)：</td>
          <td><?php echo isset($cus_info['order_info']['average_price']) ? format_money($cus_info['order_info']['average_price'], 2) : 0?></td>
        </tr>
        <tr>
        <td>客户电话：</td>
        <td><?php echo $cus_info['base_info']['mobile']?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>下单频率：</td>
        <td><?php echo $cus_info['order_info']['orderate']?></td>
      </tr>
      <tr>
        <td>录入时间：</td>
        <td><?php echo $cus_info['base_info']['record_time']?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>注册时间：</td>
        <td><?php echo $cus_info['base_info']['register_time']?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
    </tbody>
    </table>
    </div>
  </div>
</div>

<!--月份选择-->
<div class="col-md-11 second hidden-print">
  <form class="form-inline">
      <div class="form-group">
          <div class="input-daterange input-group pull-left" id="datepicker_customer">
  	        <span class="input-group-addon">选择年月</span>
            <input name="month" type="text" id="date_select" class="form-control from"  value=""/>
          </div>
      </div><!-- form-group-->
      <a class="btn btn-warning reset" id="customer_reset">重置</a>
  </form>
</div>
<!--返回按钮-->
<div class="col-md-1 second second_left_padding hidden-print">
  <a type="button" href="javascript:history.go(-1);" class="btn btn-success btn-block">返回</a>
</div>

<!--条形图图表展示 -->
<div class="col-md-12 second hidden-print">
    <div id="customer" style="height: 400px; border: 1px solid #ccc; padding: 10px; margin:10px 0;"></div>
</div>

<!--饼状图图标展示 -->
<div class="m-t-10">
    <div class="btn-group J-cus-cate-pie-time" role="group">
        <button class="btn btn-default J-history" type="group">历史总计</button>
        <button class="btn btn-default J-today" type="group">今天</button>
        <button class="btn btn-default J-yesterday" type="group">昨天</button>
        <button class="btn btn-default J-thisweek" type="group">本周</button>
        <button class="btn btn-default J-lastweek" type="group">上周</button>
        <button class="btn btn-default J-thismonth" type="group">本月</button>
        <button class="btn btn-default J-lastmonth" type="group">上月</button>
    </div>
</div>
<!--饼图图表展示 -->
<div class="col-md-12 second hidden-print" style="height: 430px; border: 1px solid #ccc; padding: 10px 0px; margin:10px 0;">
    <div id="J-cus-detail-pie" class="col-lg-6" style="height: 410px;"></div>
    <div style="height: 380px; padding: 10px 50px;" class="col-lg-offset-1 col-lg-5">
        <table class="table table-striped table-bordered">
            <thead>
             <tr>
                <th>品类</th>
                <th>下单金额</th>
                <th>占比</th>
             <tr>
            </thead>
            <tbody class="J-cus-cate-table">
            </tbody>
        </table>
    </div>
</div>

<!--静态框-->
<!-- Modal -->
<div class="modal fade modal_print_wrap" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg ">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h3 class="text-center" id="customer_detail_modal_tital">客户下单详情</h3>
        <p class="text-right"><span style="margin-right:10px;">姓名:<?php echo $cus_info['base_info']['name']?></span ><span style="margin-right:10px;">电话:<?php echo $cus_info['base_info']['mobile']?></span><span style="margin-right:10px;">店名:<?php echo $cus_info['base_info']['shop_name']?></span></p>
        <div class="table-responsive">
          <table class="table table-condensed table-bordered modal_print" id="customer_detail_modal">
            <tbody>
              <tr>
                <th>订单号</th>
                <th>商品名称</th>
                <th>单价(￥)</th>
                <th>数量</th>
                <th>小计</th>
              </tr>
              <tr>
                <td colspan="3">总计</td>
                <td>10</td>
                <td>1560元</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

</div>

<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
