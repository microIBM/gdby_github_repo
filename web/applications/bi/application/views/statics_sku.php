<!-- SKU统计模板-->
<?php include APPPATH."views/shared/header.php" ?>

<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <form action="<?=$base_url?>/statics_sku/sku_export_excel?offset=0" method="post">
    <div>
      <!-- 面包屑分类导航 -->
      <h3>
        <ol class="breadcrumb">
          <li class="J-cate-top-name"></li>
          <li class="J-cate-second-name hide"></li>
          <li class="J-cate-third-name hide"></li>
        </ol>
      </h3>
      <!-- 一级分类选择 -->
      <div class="input-group key-words myselect">
        <div class="input-group-addon">一级分类</div>
        <select class="form-control search-key J-cate-top" name="id1">
        </select>
      </div>
      <!-- 二级分类选择 -->
      <div class="input-group key-words myselect">
        <div class="input-group-addon">二级分类</div>
        <select class="form-control search-key J-cate-second" name="id2">
        </select>
      </div>
      <!-- 三级分类选择 -->
      <div class="input-group key-words myselect">
        <div class="input-group-addon">三级分类</div>
        <select class="form-control search-key J-cate-third" name="id3">
        </select>
      </div>

      <!-- 仓库选择 -->
      <div class="input-group key-words myselect">
        <div class="input-group-addon">仓库</div>
        <select class="form-control search-key J-warehouse" name="warehouse_id">
        </select>
      </div>
    </div>

    <div class="new-line">
      <!-- 时间快捷选择方式 -->
      <div class="btn-group J-sku-quik-search" role="group">
<!--        <button type="button" class="btn btn-default" value="1">今日</button>-->
        <button type="button" class="btn btn-default active" value="2">昨日</button>
        <button type="button" class="btn btn-default" value="3">上周</button>
        <button type="button" class="btn btn-default" value="4">上月</button>
      </div>
      <!-- 时间选择，按天或周或月 -->
      <div class="input-group key-words myselect">
        <select class="form-control width-4p" name="date_mode">
          <option value="1" selected="selected">按天</option>
          <option value="3">按周</option>
          <option value="2">按月</option>
        </select>
        <input name="sdate" class="form-control width-6p to J-datepicker-sku" value="">
      </div>
      <span class="J-week-edate hidden">到 <input name="edate" class="week-edate" readonly></span>

      <!-- SKU关键字筛选 -->
      <div class="input-group key-words myselect">
        <select class="form-control width-5p" name="search_key">
          <option value="0" selected="selected">SKU货号</option>
          <option value="1">SKU名称</option>
        </select>
        <input type="text" class="form-control width-5p" name="search_value"></div>

      <input type="hidden" name="menue_id" value="<?php echo $menue_id ?>" />
      <input type="hidden" name="city_id" value="<?php echo $city_id ?>" />
      <input type="hidden" name="tab_id" value="2" />
      <button type="button" id="searchSubmit" class="btn btn-primary J-sku-search">筛选</button>
      <a class="btn btn-warning reset" href="">重置</a>
      <input type="submit" class="btn btn-primary" value="Excel导出"/>
    </div>
  </form>

  <!-- 数据表格 -->
  <div class="table-responsive table-show new-line">
    <table class="table table-condensed table-bordered table-striped table-hover">
      <thead class="content-indicator nav-table">
        <tr class="J-sku-sort sku-sort">
          <th>货号</th>
          <th>名称</th>
          <th><div class="sku-sort-up"><span value="sale_amount" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="sale_amount" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的金额总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="order_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="order_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">下单数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的数量总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="actual_sale_amount" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="actual_sale_amount" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的金额总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="sale_quantity" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="sale_quantity" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">签收数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的数量总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="unsalable_day_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="unsalable_day_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">滞销天数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,该SKU没有被下单的天数总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="quantity_inwarehouse" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="quantity_inwarehouse" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">实时在库量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总库存件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="quantity_salable" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="quantity_salable" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">实时可售量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总可售件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="average_sale_price" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="average_sale_price" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">平均销售价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件销售价"></span></div></th>

          <th><div class="sku-sort-up"><span value="average_buy_price" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="average_buy_price" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">平均采购价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件采购价"></span></div></th>

          <th><div class="sku-sort-up"><span value="margin_rate" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="margin_rate" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="(签收金额 - 采购总成本)/签收金额"></span></div></th>

          <th><div class="sku-sort-up"><span value="cover_rate" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="cover_rate" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该SKU的客户总数/时间周期内下单的客户总数"></span></div></th>

          <th><div class="sku-sort-up"><span value="complaint_order_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="complaint_order_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的投诉单数（类型为质量问题)"></span></div></th>

<!--          <th><div class="sku-sort-up"><span value="return_order_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="return_order_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款单数"></span></div></th>-->

          <th><div class="sku-sort-up"><span value="return_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="return_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">退货件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="reject_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="reject_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的订单拒收总件数（数据来源于TMS）"></span></div></th>

          <th><div class="sku-sort-up"><span value="out_warehouse_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="out_warehouse_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">出库件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该SKU的销售出库总件数"></span></div></th>
          <th>货品状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <!-- 隐藏表头  -->
  <div class="table-responsive table-hide new-line">
    <table class="table table-condensed table-bordered table-striped table-hover">
      <thead class="content-indicator nav-table">
        <tr class="J-sku-sort sku-sort">
          <th>货号</th>
          <th>名称</th>
          <th><div class="sku-sort-up"><span value="sale_amount" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="sale_amount" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的金额总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="order_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="order_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">下单数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的数量总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="actual_sale_amount" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="actual_sale_amount" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的金额总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="sale_quantity" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="sale_quantity" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">签收数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的数量总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="unsalable_day_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="unsalable_day_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">滞销天数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,该SKU没有被下单的天数总和"></span></div></th>

          <th><div class="sku-sort-up"><span value="quantity_inwarehouse" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="quantity_inwarehouse" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">实时在库量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总库存件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="quantity_salable" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="quantity_salable" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">实时可售量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总可售件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="average_sale_price" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="average_sale_price" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">平均销售价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件销售价"></span></div></th>

          <th><div class="sku-sort-up"><span value="average_buy_price" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="average_buy_price" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">平均采购价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件采购价"></span></div></th>

          <th><div class="sku-sort-up"><span value="margin_rate" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="margin_rate" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="(签收金额 - 采购总成本)/签收金额"></span></div></th>

          <th><div class="sku-sort-up"><span value="cover_rate" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="cover_rate" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该SKU的客户总数/时间周期内下单的客户总数"></span></div></th>

          <th><div class="sku-sort-up"><span value="complaint_order_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="complaint_order_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的投诉单数（类型为质量问题)"></span></div></th>

<!--          <th><div class="sku-sort-up"><span value="return_order_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="return_order_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款单数"></span></div></th>-->

          <th><div class="sku-sort-up"><span value="return_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="return_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">退货件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款件数"></span></div></th>

          <th><div class="sku-sort-up"><span value="reject_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="reject_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的订单拒收总件数（数据来源于TMS）"></span></div></th>

          <th><div class="sku-sort-up"><span value="out_warehouse_sku_counts" sort="asc" class="glyphicon glyphicon-triangle-top"></span></div><div class="sku-sort-down"><span value="out_warehouse_sku_counts" sort="desc" class="glyphicon glyphicon-triangle-bottom"></span></div><div class="title-desc">出库件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该SKU的销售出库总件数"></span></div></th>
          <th>货品状态</th>
          <th>操作</th>
        </tr>
      </thead>
    </table>
  </div>

  <div id="J-pagination-box"><ul id="J-pagination-sku"></ul></div>
  <div class="btn-group dropup page-size m-t-20 m-l-5">
      <button type="button" class="btn btn-default">每页<span class="J-pagesize-num">10</span>条</button>
      <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></button>
      <ul class="dropdown-menu J-sku-pagesize" role="menu">
        <li><a value="10">每页10条</a></li>
        <li><a value="15">每页15条</a></li>
        <li><a value="20">每页20条</a></li>
        <li><a value="30">每页30条</a></li>
        <li><a value="50">每页50条</a></li>
      </ul>
  </div>
  <div class="label label-info total-records m-t-20 m-l-5">共 <span class="J-total-num">0</span> 条记录</div>
</div>


<?php include APPPATH."views/shared/footer.php" ?>
