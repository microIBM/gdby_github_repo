<!-- 大厨、大果货物销量统计模板-->
<?php include APPPATH."views/shared/header.php"?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <?php
    // echo $site_id.'<br>'.$tab_id.'<br><pre>';
    // var_dump($rank);
    ?>
    <h1 class="page-header">
    <?php
        if ($site_id == 1) {
            echo '大厨网';
        } elseif ($site_id == 2) {
            echo '大果网';
        }
    ?> · BD业绩统计
    </h1>
    <!-- 大厨大果切换按钮 -->
    <div>
        <a href="<?php echo $base_url?>/statics/bd_statics?site_id=<?=$site_id?>&tab_id=4" class="btn btn-default <?php if($tab_id == 4) { echo 'active'; }?>" role="button">今日新增</a> 
        <a href="<?php echo $base_url?>/statics/bd_statics?site_id=<?=$site_id?>&tab_id=6" class="btn btn-default <?php if($tab_id == 6) { echo 'active'; }?>" role="button">每月新增</a> 
        <a href="<?php echo $base_url?>/statics/bd_statics?site_id=<?=$site_id?>&tab_id=5" class="btn btn-default <?php if($tab_id == 5) { echo 'active'; }?>" role="button">历史总计</a>     
        <span class="data-explain" style="font-size:14px;">※若一个BD没有成功推过大果或大厨，则数据为零。</span><br>
        <span class="data-explain" style="font-size:14px;">※所有数据不包括交易关闭的订单。今日数据为实时数据。</span>
    </div>

    <!-- 搜索输入框 -->
    <p></p>
    <div>
        <form class="form-inline" action="<?php echo $base_url?>/statics/bd_statics?site_id=<?=$site_id?>&tab_id=<?php echo $tab_id?>"  method="get">
            <div class="form-group">
                <div class="input-group key-words">
                    <div class="input-group-addon">关键字</div>
                        <select name="searchKey"  class="form-control">
                            <?php $search_type = isset($_GET['searchKey']) ? $_GET['searchKey'] : "" ?>
                            <option  value="name" <?php if($search_type == 'name' || $search_type == "") {echo "searched"; } ?> >BD姓名</option>
                            <!--<option  value="sku_number" <?php if($search_type == 'sku_number') {echo "selected"; }?>>货号</option>-->
                        </select>
                </div>
                <label class="sr-only" for="searchValue">关键字</label>
                <div class="input-group">
                    <input type="text" class="form-control search-value" id="searchValue" name="searchValue" value="<?php echo $search_value?>" placeholder="请输入BD姓名">
                </div><!--input-group -->
                </div><!-- form-group-->
                <input name="site_id" type="hidden" value="<?php echo $site_id ?>">
                <input name="tab_id" type="hidden" value="<?php echo $tab_id ?>">
                <input name="offset" type="hidden" value="<?php echo $offset ?>">
                <?php if ($tab_id == 6) {?>
                <div class="input-group key-words">
                    <div class="input-group-addon">月份</div>
                    <select  name="month" class="form-control">
                    <?php for($i=date('n'); $i>0; $i--) :?>
                        <option name="month" <?php if(isset($_GET['month']) && $_GET['month'] == $i) echo 'selected'?> value="<?php echo $i; ?>"><?php echo $i.'月'; ?></option>
                    <?php endfor;?>
                    </select>
                </div>
                <?php } else if($tab_id == 4) {?>
                    <input name="month" type="hidden" value=0>
                <?php }?>
            <button type="submit" class="btn btn-primary">筛选</button>
            <!--<button class="btn btn-warning reset" type="reset">重置</button> --> 
            <a class="btn btn-warning reset" href="<?php echo $base_url?>/statics/bd_statics?site_id=<?=$site_id?>&tab_id=<?php echo $tab_id?>">重置</a>
        </form>
    </div>

    <!-- 统计表格 -->
    <?php
    //表头变量统计
    switch ($tab_id) {
        //今日新增
    case 4:
            $customer_num = "今天新拓展的顾客数";
            $order_customer_num = "今日下了单的顾客数";
            $first_customer_num = "①以前从未下单②今日下了单③今日下的单可以是一单或多单";
            $again_customer_num = "①今日下过单②以前也下过单③同一用户、同一配送时间的单合并成一单计算 的顾客数";
            break;
        //按月统计
    case 6:
            $customer_num = "该月新拓展的顾客数";
            $order_customer_num = "该月下了单的顾客数";
            $first_customer_num = "①以前从未下单②此月下了单③此月下的单可以是一单或多单";
            $again_customer_num = "①该月下过单②以前也下过单③同一用户、同一配送时间的单合并成一单计算 的顾客数";
            break;
        //历史统计
    case 5:
            $customer_num = "所有顾客数";
            $order_customer_num = "所有下过单顾客数";
            $first_customer_num = "--";
            $again_customer_num = "①下过两单或以上②同一用户、同一配送时间的单合并成一单计算 的顾客数";
            break;
    }
    ?>
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="2" rowspan="1">BD</th>
                    <th colspan="5" rowspan="1">顾客</th>
                    <th colspan="3" rowspan="1">订单</th>
                    <th colspan="3" rowspan="1">金额</th>
                    <th colspan="2" rowspan="1">小组</th>
                </tr>
                <tr>
                    <th colspan="1" rowspan="1">BD姓名</th>
                    <th colspan="1" rowspan="1">电话</th>
                    <th colspan="1" rowspan="1">顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">下单顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $order_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">交易转化率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="下单顾客数/顾客数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">首购顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $first_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">复购顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $again_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单数，仅不包括已关闭订单" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">合并后订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将同一顾客、同一配送时间的订单合并成一单后 的订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交单数(已合并)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①成交的订单数②同一用户、同一配送时间的单合并成一单计算" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单金额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单的金额之和" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交金额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单成交的金额之和" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单金额/订单数，均指有效订单" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">组长名</th>
                    <th colspan="1" rowspan="1">组长电话</th>
                </tr>
            </thead>
            <!-- 统计表格表内容 -->
            <tbody>
                <?php foreach ($bd_info as $value) {?>
                <tr>
                    <!--BD姓名-->
                    <td><?php echo $value['name']?></td>
                    <!--BD电话-->
                    <td><?php echo $value['mobile']?></td>
                    <!--新增顾客数-->
                    <td><?php echo isset($customer_statics[$value['id']]['bd_customers_total_count']) ? $customer_statics[$value['id']]['bd_customers_total_count'] : "0"?></td>
                    <!--下单顾客数-->
                    <td><?php echo isset($customer_statics[$value['id']]['bd_customers_order_count']) ? $customer_statics[$value['id']]['bd_customers_order_count'] : "0"?></td>
                    <!--交易转化-->
                    <?php if($tab_id == 6): ?><td>--</td><?php else: ?>
                    <td><?php echo (isset($customer_statics[$value['id']]['bd_customers_total_history_count']) && $customer_statics[$value['id']]['bd_customers_total_history_count'] !=0 ) ? number_format($customer_statics[$value['id']]['bd_customers_order_count'] / $customer_statics[$value['id']]['bd_customers_total_history_count'] * 100, 2) : "0.00"?>%</td>
                    <?php endif; ?>
                    <!--首购顾客数-->
                    <?php if ($tab_id == 5) {  ?>
                    <td>--</td>
                    <?php } else { ?> 
                    <td><?php echo isset($customer_statics[$value['id']]['bd_customers_first_count']) ? $customer_statics[$value['id']]['bd_customers_first_count'] : "0"?></td>
                    <?php }?>
                    <!--复购顾客数-->
                    <td><?php echo isset($customer_statics[$value['id']]['bd_customers_again_count']) ? $customer_statics[$value['id']]['bd_customers_again_count'] : "0"?></td>
                    <!--订单数-->
                    <td><?php echo isset($order_statics[$value['id']]['order_num']) ? $order_statics[$value['id']]['order_num'] : "0"?></td>
                    <!--去重后订单数-->
                    <td><?php echo isset($order_statics[$value['id']]['order_num_distinct']) ? $order_statics[$value['id']]['order_num_distinct'] : "0"?></td>

                    <!--成交订单数（已经去重）-->
                    <?php if ($tab_id == 4) {  ?>
                    <td>--</td>
                    <?php } else { ?> 
                    <td><?php echo isset($order_statics_success[$value['id']]['order_num_distinct']) ? $order_statics_success[$value['id']]['order_num_distinct'] : "0"?></td>
                    <?php }?>
                    <!--订单金额-->
                    <td><?php echo isset($order_statics[$value['id']]['order_amout']) ? format_money($order_statics[$value['id']]['order_amout'], 2): "0"?></td>

                    <!--成交金额-->
                    <?php if ($tab_id == 4) {  ?>
                    <td>--</td>
                    <?php } else { ?> 
                    <td><?php echo isset($order_statics_success[$value['id']]['order_amout']) ? format_money($order_statics_success[$value['id']]['order_amout'],2) : "0"?></td>
                    <?php }?>

                    <!--客单价-->
                    <td><?php echo (isset($order_statics[$value['id']]['order_num']) && ($order_statics[$value['id']]['order_num'] !=0) ) ?  format_money($order_statics[$value['id']]['order_amout']/$order_statics[$value['id']]['order_num'], 2) : "0"  ?></td>
                    <!--BD组长姓名-->
                    <td><?php echo isset($value['bdm_nam']) ? $value['bdm_nam'] : "--" ?></td>
                    <!--BD组长电话-->
                    <td><?php echo isset($value['bdm_mobile']) ? $value['bdm_mobile'] : '--'?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <!-- 统计表格隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="2" rowspan="1">BD</th>
                    <th colspan="5" rowspan="1">顾客</th>
                    <th colspan="3" rowspan="1">订单</th>
                    <th colspan="3" rowspan="1">金额</th>
                    <th colspan="2" rowspan="1">小组</th>
                </tr>
                <tr>
                    <th colspan="1" rowspan="1">BD姓名</th>
                    <th colspan="1" rowspan="1">电话</th>
                    <th colspan="1" rowspan="1">顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">下单顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $order_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">交易转化率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="下单顾客数/顾客数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">首购顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $first_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">复购顾客数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="<?php echo $again_customer_num?>" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单数，仅不包括已关闭订单" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">合并后订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将同一顾客、同一配送时间的订单合并成一单后 的订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交单数(已合并)<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="①成交的订单数②同一用户、同一配送时间的单合并成一单计算" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单金额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单的金额之和" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交金额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单成交的金额之和" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单金额/订单数，均指有效订单" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">组长名</th>
                    <th colspan="1" rowspan="1">组长电话</th>
                </tr>                
            </thead>
        </table>
    </div>
    <!-- 分页 -->
    <?php echo $pagination['links'];?>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
