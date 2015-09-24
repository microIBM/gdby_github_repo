<!-- 大厨、大果统计模板-->
 <?php include APPPATH."views/shared/header.php" ?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
  <h1 class="page-header">大厨网 · 统计</h1>
    <!-- 大厨大果切换按钮 -->
    <div>
    <ul class="list-unstyled list-inline clearfix">
        <li>
        <form class="form-inline" action="<?php echo $base_url.'/statics'?>"  method="get">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">选择月份</span>
                    <input name="date" class="form-control to J-datepicker-statics" value="<?php echo $date;?>" >
                </div>
                <div class="input-group key-words myselect">
                     <div class="input-group-addon">选择客户</div>
                     <select name="customer_type" class="form-control search-key">
                         <option value="0" <?php if($customer_type == 0) echo 'selected';?>>所有客户</option>
                         <option value="1" <?php if($customer_type == 1) echo 'selected';?>>普通客户</option>
                         <option value="2" <?php if($customer_type == 2) echo 'selected';?>>KA客户</option>
                     </select>
                </div>
            </div>
            <input name="city_id" type="hidden" value="<?php echo $city_id ?>">
            <button type="submit" class="btn btn-primary">筛选</button>
            <a class="btn btn-warning reset" href="<?php echo $base_url.'/statics?city_id='.$city_id ?>">重置</a>
        </form>
        </li>
        <li class="desc pull-right">
            <ul class="list-unstyled">
                <li style="font-size:14px;">※所有数据不包括取消的订单</li>
                <li style="font-size:14px;">※本页的数据，每24:00，4:00，8:00，12:00，16:00，20:00更新一次</li>
            </ul>
        </li>
    </ul>
    </div>

    <!-- 统计表格 -->
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th rowspan="2">日期</th>
                    <th rowspan="2">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-viewport={"selector":".table-responsive","padding":0} data-content="除取消订单外的总订单数" aria-hidden="true"></span></th>
                    <th rowspan="2" valign="middle">流水(￥)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="下单金额，取消的订单不计入" aria-hidden="true"></th>
                    <!-- <th rowspan="2">消耗流水(￥)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将KA客户的大单流水平摊到每一天后，得到的流水情况，更真实稳定" aria-hidden="true"></th> -->
                    <th colspan="5" rowspan="1">客户</th>
                    <th colspan="3">客单价</th>
                    <th rowspan="2" valign="middle">详情</th>
                </tr>
                <tr>
                    <th>潜在客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="BD录入的潜在客户数" aria-hidden="true"></th>
                    <th>注册客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="在系统中注册了账号的客户数" aria-hidden="true"></th>
                    <th>下单客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="注册客户中下过单的客户数，取消的订单不算" aria-hidden="true"></th>
                    <th>首单客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①以前没下单；②本时间段下了单；③取消的订单不算" aria-hidden="true"></th>
                    <th>复购客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①以前也下过单；②本时间段也下了单；③取消的订单不算" aria-hidden="true"></th>
                    <th>客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="流水/这些订单所属客户数" aria-hidden="true"></th>
                    <th>首单客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="首单客户的流水/这些订单所属客户数" aria-hidden="true"></th>
                    <th>复购客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-viewport={"selector":".table-responsive","padding":0} data-content="复购客户的流水/这些订单所属客户数" data-placement="bottom" aria-hidden="true"></th>
                </tr>
            </thead>
            <!-- 统计表格表内容 -->
            <tbody>
                <tr>
                    <td>历史总计</td>
                    <!--历史总计订单总数 -->
                    <td><?= $history_data['period_valid_order_cnt'] ?></td>

                    <!--历史总计交易总额 -->
                    <td><?= format_money($history_data['period_valid_order_amount'],2) ?></td>

                    <!--历史消耗流水 -->
                    <!-- <td><?= format_money($history_data['period_valid_order_amount'],2) ?></td> -->

                    <!-- 历史总计新增潜在用户数 -->
                    <td><?= $history_data['period_potential_cus'] ?></td>

                    <!-- 历史总计新增注册用户数 -->
                    <td><?= $history_data['period_resign_cus'] ?></td>

                    <!-- 历史下单客户数 -->
                    <td><?= $history_data['period_ordered_customers_total'] ?></td>

                    <!-- 历史首单客户数 暂时和历史下单顾客数相等-->
                    <td><?= $history_data['period_ordered_customers_total'] ?></td>

                    <!-- 历史复购客户数 -->
                    <td><?= $history_data['period_again_customers_total'] ?></td>
                    <td>--</td>
                    <td>--</td>
                    <td>--</td>
                    <td>--</td>
                </tr>
                <tr>
                    <td>本月总计</td>
                    <!--本月总计订单总数 -->
                    <td><?= $period_data['period_valid_order_cnt'] ?></td>

                    <!--本月总计交易总额 -->
                    <td><?= format_money($period_data['period_valid_order_amount'],2) ?></td>

                    <!--本月消耗流水 -->
                    <!-- <td><?= format_money($period_data['period_valid_order_amount'],2) ?></td> -->

                    <!-- 本月总计新增潜在用户数 -->
                    <td><?= $period_data['period_potential_cus'] ?></td>

                    <!-- 本月总计新增注册用户数 -->
                    <td><?= $period_data['period_resign_cus'] ?></td>

                    <!-- 本月下单客户数 -->
                    <td><?= $period_data['period_ordered_customers_total'] ?></td>

                    <!-- 本月首单客户数 -->
                    <td><?= $period_data['period_first_customer_total']?></td>

                    <!-- 本月复购客户数 -->
                    <td><?= $period_data['period_again_customers_total']?></td>
                    <td>--</td>
                    <td>--</td>
                    <td>--</td>
                    <td>--</td>
                </tr>
                <?php foreach(($days_list) as $key => $value):?>
                    <tr>
                      <td><?= substr($key,5).'('.$value['week'].')'?></td>

                      <!-- 每日订单总数 -->
                      <td><?= $value['valid_order_cnt']?></td>

                      <!-- 每日交易流水 -->
                      <td><?= format_money($value['valid_order_amount'],2)?></td>

                      <!-- 每日交易消耗流水 -->
                      <!--  <td><?= format_money($value['consumed_amount'],2)?></td> -->

                      <!-- 每日新增潜在用户数 -->
                      <td><?= $value['potential_cus_cnt']?></td>

                      <!-- 每日新增注册用户数 -->
                      <td><?= $value['resign_cus_cnt']?></td>

                      <!-- 每日下单客户数 -->
                      <td><?= $value['order_cus_cnt']?></td>

                      <!-- 每日首单客户数 -->
                      <td><?= $value['first_ordered_count']?></td>

                      <!-- 每日复购客户数 -->
                      <td><?= $value['again_ordered_count']?></td>

                      <!-- 每日客单价 -->
                      <?php if($customer_type ==2):?>
                          <td><?= $value['order_cus_cnt'] ? format_money($value['valid_order_amount']/$value['order_cus_cnt'],2) : 0?></td>
                      <?php else:?>
                            <td><?= $value['order_cus_cnt'] ? format_money($value['valid_order_amount']/$value['order_cus_cnt'],2) : 0?></td>
                      <?php endif;?>
                      <!-- 每日首单客单价 -->
                      <td><?= $value['first_ordered_count'] ? format_money($value['first_amount']/$value['first_ordered_count'],2) : 0?></td>

                      <!-- 每日复购客单价 -->
                      <td><?= $value['again_ordered_count'] ? format_money($value['again_amount']/$value['again_ordered_count'],2) : 0?></td>

                      <!-- 查看详情 -->
                      <td><a href="<?=$base_url?>/cus_top?city_id=<?= $city_id?>&customer_type=<?= $customer_type?>&sdate_picker=<?=$key?>&edate_picker=<?=$key?>">查看</a></td>
                    </tr>
                <?php endforeach;?>
              </tbody>
            </table>
          </div>

          <!-- 统计表格隐藏表头 -->
          <div class="table-responsive table-hide">
            <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th rowspan="2">日期</th>
                    <th rowspan="2">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-viewport={"selector":".table-responsive","padding":0} data-content="除取消订单外的总订单数" aria-hidden="true"></span></th>
                    <th rowspan="2" valign="middle">流水(￥)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="下单金额，取消的订单不计入" aria-hidden="true"></th>
                    <!-- <th rowspan="2">消耗流水(￥)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将KA客户的大单流水平摊到每一天后，得到的流水情况，更真实稳定" aria-hidden="true"></th> -->
                    <th colspan="5" rowspan="1">客户</th>
                    <th colspan="3">客单价</th>
                    <th rowspan="2" valign="middle">详情</th>
                </tr>
                <tr>
                    <th>潜在客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="BD录入的潜在客户数" aria-hidden="true"></th>
                    <th>注册客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="在系统中注册了账号的客户数" aria-hidden="true"></th>
                    <th>下单客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="注册客户中下过单的客户数，取消的订单不算" aria-hidden="true"></th>
                    <th>首单客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①以前没下单；②本时间段下了单；③取消的订单不算" aria-hidden="true"></th>
                    <th>复购客户数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①以前也下过单；②本时间段也下了单；③取消的订单不算" aria-hidden="true"></th>
                    <th>客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="流水/这些订单所属客户数" aria-hidden="true"></th>
                    <th>首单客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="首单客户的流水/这些订单所属客户数" aria-hidden="true"></th>
                    <th>复购客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-viewport={"selector":".table-responsive","padding":0} data-content="复购客户的流水/这些订单所属客户数" data-placement="bottom" aria-hidden="true"></th>
                </tr>
            </thead>
        </table>
    </div>
        </div><!-- div main -->

<?php include APPPATH."views/shared/footer.php" ?>
