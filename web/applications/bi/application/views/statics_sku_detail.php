<!-- SKU统计模板-->
<?php include APPPATH."views/shared/header.php" ?>

<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
  <form>
    <div>
      <!-- 面包屑分类导航 -->
        <h4><?= $sku_details['name'];?></h4>
        <ol class="breadcrumb">
          <?php if(!empty($sku_details['cats_info'])):?>
            <?php foreach ($sku_details['cats_info'] as $value):?>
                <li><?= $value['name'];?></li>
            <?php endforeach;?>
          <?php endif;?>
        </ol>
    </div>

    <div class="new-line">
      <!-- 时间快捷选择方式 -->
      <div class="btn-group" role="group">
<!--        <a href="#" date-mode =1 tab-id="1" date-start="--><?//= $quik_date['today'];?><!--" date-end="--><?//= $quik_date['today'];?><!--" class="date-tab btn btn-default --><?php //if($tab_id ==1):?><!--active--><?php //endif;?><!--">今日</a>-->
        <a href="#" date-mode =1 tab-id="2" date-start="<?= $quik_date['yesterday'];?>" date-end="<?= $quik_date['yesterday'];?>" class="date-tab btn btn-default <?php if($tab_id ==2):?>active<?php endif;?>">昨日</a>
        <a href="#" date-mode =3 tab-id="3" date-start="<?= $quik_date['prev_week_start'];?>" date-end="<?= $quik_date['prev_week_end'];?>" class="date-tab btn btn-default <?php if($tab_id ==3):?>active<?php endif;?>">上周</a>
        <a href="#" date-mode =2 tab-id="4" date-start="<?= $quik_date['prev_month_start'];?>" date-end="<?= $quik_date['prev_month_end'];?>" class="date-tab btn btn-default <?php if($tab_id ==4):?>active<?php endif;?>">上月</a>
      </div>
      <!-- 时间选择，按天或周或月 -->
      <div class="input-group key-words myselect">
        <select class="form-control width-4p J-date-select" name="date_mode">
          <option value="1" <?php if($date_mode==1):?>selected="selected"<?php endif;?> >按天</option>
          <option value="3" <?php if($date_mode==3):?>selected="selected"<?php endif;?> >按周</option>
          <option value="2" <?php if($date_mode==2):?>selected="selected"<?php endif;?> >按月</option>
        </select>
        <span class="J-sku-day-sdate"><input name="sdate" class="form-control width-6p J-datepicker-sku-day hidden"></span>
        <span class="J-sku-week-sdate"><input name="sdate" class="form-control width-6p J-datepicker-sku-week hidden"></span>
        <span class="J-sku-month-sdate"><input name="sdate" class="form-control width-6p J-datepicker-sku-month hidden"></span>
        <input class="J-sdate-hidden hidden" value="<?php if(isset($sdate)):?><?php echo $sdate;?><?php endif;?>">
      </div>
      <span class="J-sku-edate hidden">到
        <input name="edate" class="week-edate J-date-end" readonly value="<?php if(isset($edate)):?><?php echo $edate;?><?php endif;?>">
      </span>

      <!-- 仓库选择 -->
      <div class="input-group key-words myselect">
        <div class="input-group-addon">仓库</div>
        <select name="warehouse_id" class="form-control search-key J-warehouse">
            <?php foreach ($warehouse_lists as $value):?>
                <?php if($value['warehouse_id'] == $warehouse_id):?>
                    <option selected="selected" value="<?= $value['warehouse_id']?>"><?= $value['warehouse_name']?></option>
                <?php else :?>
                    <option value="<?= $value['warehouse_id']?>"><?= $value['warehouse_name']?></option>
                <?php endif;?>
            <?php endforeach;?>
        </select>
      </div>
      <input type="hidden" name="menue_id" value="<?= $menue_id ?>">
      <input type="hidden" name="sku_number" value="<?= $sku_number ?>">
      <input type="hidden" name="tab_id" value="<?= $tab_id ?>">
      <input type="hidden" name="city_id" value="<?= $city_id ?>">
      <input type="hidden" name="is_search" value="0">
      <a href="#" id="J_submit" class="btn btn-primary ">筛选</a>
      <a class="btn btn-warning reset" href="">重置</a>
    </div>
  </form>
    <div class="row" style="width:90%;margin-top:20px;">
      <div class="col-md-2"><img alt="" width="150"  onerror="this.src='http://mall.dachuwang.com/assets/images/no_image.jpg'" src="<?php if(!empty($sku_details['pics']['raw_image'][0]['pic_url'])):?><?php echo $sku_details['pics']['raw_image'][0]['pic_url'];?><?php endif;?>" class="img-rounded"></div>
      <div class="col-md-10">
        <table class="table table-bordered">
            <tr>
                <td>计量单位:<?= $sku_details['unit_name']?></td>
                <td>保 质 期:<?= $sku_details['guarantee_period']?></td>
                <td>近 效 期:<?= $sku_details['effect_stage']?></td>
            </tr>
            <tr>
                <td>录入条码：<?= $sku_details['code']?></td>
                <td>最小安全库存：<?= $sku_details['min_safe_storage']?></td>
                <td>最大安全库存：<?= $sku_details['max_safe_storage']?></td>
            </tr>
            <tr>
                
                <td>实时在库量：<?= $sku_details['inwarehouse']?></td>
                <td>订单锁定量：<?= $sku_details['stock_locked']?></td>
                <td>实时可售量：<?= $sku_details['salable']?></td>
            </tr>
            <tr>
                <?php if(!empty($sku_details['spec'])):?>
                    <?php
                    foreach ($sku_details['spec'] as $value):?>
                        <td ><?= $value;?></td>
                    <?php endforeach;?>
                <?php endif;?>
            </tr>
            <?php if(!empty($sku_details['sale_price'])):?>
                <tr>
                    <td>在线销售单价：
                    <?php foreach ($sku_details['sale_price'] as $value):?>
                        <?= round($value[0]/100, 2) ?>元
                    <?php endforeach;?>
                    </td>
                    <td>采集类型：
                        <?php foreach ($sku_details['sale_price'] as $value):?>
                            <?= $value[1] ?>
                        <?php endforeach;?>
                    </td>
                </tr>
            <?php endif;?>
        </table>
      </div>
  </div>
  <!-- 数据表格 -->
  <div class="table-responsive table-show new-line">
    <table class="table table-condensed table-bordered table-striped table-hover">
      <thead class="content-indicator nav-table">
        <tr>
          <th>日期</th>
          <th>下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的金额总和"></span></th>
          <th>下单数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的数量总和"></span></th>
          <th>签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的金额总和"></span></th>
          <th>签收数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的数量总和"></span></th>
          <th>滞销天数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,该SKU没有被下单的天数总和"></span></th>
          <th>实时在库量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总库存件数"></span></th>
          <th>实时可售量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总可售件数"></span></th>
          <th>平均销售价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件销售价"></span></th>
          <th>平均采购价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件采购价"></span></th>
          <th>毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="(签收金额 - 采购总成本)/签收金额"></span></th>
          <th>客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该SKU的客户总数/时间周期内下单的客户总数"></span></th>
          <th>质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，投诉类型为质量问题的该SKU的订单数"></span></th>
<!--          <th>退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款单数"></span></th>-->
          <th>退货件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款件数"></span></th>
          <th>拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的订单拒收总件数(数据来源于TMS)"></span></th>
          <th>出库件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该SKU的销售出库总件数"></span></th>
          <th>货品状态</th>
        </tr>
      </thead>
      <tbody>
      
        <!-- 阶段统计（周，月） -->
        <?php if(!empty($sku_lists['period_lists'])):?>
            <?php $period = $sku_lists['period_lists'][0];?>
            <tr>
              <!-- 日期 -->
              <td>总计</td>
              <!-- 流水 -->
              <td><?= number_format($period['sale_amount'],2);?></td>
              <!--下单数量-->
              <td><?= $period['order_sku_counts'];?></td>
                <!-- 实际销售额 -->
              <td><?= number_format($period['actual_sale_amount'],2);?></td>
              <!-- 实际销售数量 -->
              <td><?= $period['sale_quantity'];?></td>
              <!-- 滞销天数 -->
              <td><?= $period['unsalable_day_counts'];?></td>
              <!-- 实时在库量 -->
              <td><?= $period['quantity_inwarehouse'];?></td>
              <!-- 实时可售量 -->
              <td><?= $period['quantity_salable'];?></td>
              <!-- 平均销售价 -->
              <td><?= number_format($period['average_sale_price'],2);?></td>
              <!-- 平均采购价 -->
              <td><?= number_format($period['average_buy_price'],2);?></td>
              <!-- 毛利率 -->
              <td><?= $period['margin_rate'] * 100;?>%</td>
              <!-- 客户覆盖率 -->
              <td><?= $period['cover_rate'] * 100;?>%</td>
              <!-- 质量问题投诉率 -->
              <td><?= $period['complaint_order_counts'];?></td>
              <!-- 退货单数 -->
<!--              <td>--><?//= $period['return_order_counts'];?><!--</td>-->
              <!-- 退货件数 -->
              <td><?= $period['return_sku_counts'];?></td>
              <!-- 拒收件数 -->
              <td><?= $period['reject_sku_counts'] ?></td>
              <!-- 出库件数 -->
              <td><?= $period['out_warehouse_sku_counts'] ?></td>
              <td>--</td>
            </tr>
        <?php endif;?>
        
        <!-- 每日sku统计lits -->
        <?php if(!empty($sku_lists['day_lists'])):?>
            <?php foreach ($sku_lists['day_lists'] as $value) :?>
                <tr>
                  <td><?= $value['data_date'] ? $value['data_date'] : date('Y-m-d');?></td>
                  <td><?= number_format($value['sale_amount'],2);?></td>
                  <td><?= $value['order_sku_counts'] ?></td>
                  <td><?= number_format($value['actual_sale_amount'],2);?></td>
                  <td><?= $value['sale_quantity'];?></td>
                  <td><?= $value['unsalable_day_counts'];?></td>
                  <td><?= $value['quantity_inwarehouse'];?></td>
                  <td><?= $value['quantity_salable'];?></td>
                  <td><?= number_format($value['average_sale_price'],2);?></td>
                  <td><?= number_format($value['average_buy_price'],2);?></td>
                  <!-- 毛利率 -->
                  <td><?= $value['margin_rate'] * 100;?>%</td>
                  <!-- 客户覆盖率 -->
                  <td><?= $value['cover_rate'] * 100;?>%</td>
                  <!-- 质量问题投诉率 -->
                  <td><?= $value['complaint_order_counts'] ?></td>
                  <!-- 退货单数 -->
<!--                  <td>--><?//= $value['return_order_counts'];?><!--</td>-->
                  <!-- 退货件数 -->
                  <td><?= $value['return_sku_counts'];?></td>
                  <!-- 拒收件数 -->
                  <td><?= $value['reject_sku_counts'] ?></td>
                  <!-- 出库件数 -->
                  <td><?= $value['out_warehouse_sku_counts'] ?></td>
                  <td><?= $sku_details['status'] == 1 ? "<span class='btn btn-xs btn-success'>已上架</span>" : "<span class='btn btn-xs btn-danger'>已下架</span>"; ?></td>
                </tr>
            <?php endforeach;?>
        <?php endif;?>
        
      </tbody>
    </table>
  </div>

  <!-- 隐藏表头 -->
  <div class="table-responsive table-hide new-line">
    <table class="table table-condensed table-bordered table-striped table-hover">
      <thead class="content-indicator nav-table">
        <tr>
          <th>日期</th>
          <th>下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的金额总和"></span></th>
          <th>下单数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="除了已取消订单,该SKU的数量总和"></span></th>
          <th>签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的金额总和"></span></th>
          <th>签收数量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,所有已签收订单中该SKU的数量总和"></span></th>
          <th>滞销天数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内,该SKU没有被下单的天数总和"></span></th>
          <th>实时在库量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总库存件数"></span></th>
          <th>实时可售量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在所选仓库内的总可售件数"></span></th>
          <th>平均销售价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件销售价"></span></th>
          <th>平均采购价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该SKU在时间周期内，所有已签收订单的加权平均单件采购价"></span></th>
          <th>毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="(签收金额 - 采购总成本)/签收金额"></span></th>
          <th>客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该SKU的客户总数/时间周期内下单的客户总数"></span></th>
          <th>质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，投诉类型为质量问题的该SKU的订单数"></span></th>
<!--          <th>退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款单数"></span></th>-->
          <th>退货件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的已处理退货退款件数"></span></th>
          <th>拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内，包含该SKU的订单拒收总件数(数据来源于TMS)"></span></th>
          <th>出库件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该SKU的销售出库总件数"></span></th>
          <th>货品状态</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<?php include APPPATH."views/shared/footer.php" ?>
