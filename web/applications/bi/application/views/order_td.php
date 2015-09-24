<!-- 大厨、大果货物销量统计模板-->
<?php include APPPATH."views/shared/header.php"?>

<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <?php
    // echo $site_id.'<br>'.$tab_id.'<br><pre>';
    // var_dump($rank);
    ?>
    <h1 class="page-header">大厨网 · 订单分时</h1>
    <!-- 搜索输入框 -->
    <p></p>
    <div>
    <ul class="list-unstyled list-inline clearfix">
        <li>
        <form class="form-inline" action="<?php echo $base_url?>/statics/order_td"  method="get">
            <div class="form-group">
                <div class="input-daterange input-group pull-left" id="datepicker">
                    <span class="input-group-addon">选择日期</span>
                    <input name="date" type="text" id="date_select" class="form-control from" value="<?php echo $date;?>"/>
                </div>
            </div><!-- form-group-->
            <input name="city_id" type="hidden" value="<?php echo $city_id ?>">
            <input name="menue_id" type="hidden" value="<?php echo $menue_id ?>">
            <button type="submit" class="btn btn-primary">筛选</button>
            <a class="btn btn-warning reset" href="<?php echo $base_url?>/statics/order_td?menue_id=<?=$menue_id?>&city_id=<?=$city_id?>">重置</a>
        </form>
        </li>

        <li class="desc pull-right">
            <ul class="list-unstyled">
                <li style="font-size:14px;">※所有数据不包括交易关闭的订单。今日数据为实时数据。</li>
                <li style="font-size:14px;">※每日的划分按截单时间23:00计算（每天的23:01算入第二天中）。</li>
            </ul>
        </li>
    </ul>
    </div>

    <!--折线图展示 -->
    <div id="order_td" style="height: 400px; border: 1px solid #ccc; padding: 10px;"></div>

    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="1">小时区间</th>
                    <th colspan="1" rowspan="1">下单数</th>
                </tr>
            </thead>
            <!-- 统计表格表内容 -->
            <tbody>
                <?php
                 foreach($order_td as $value) {?>
                <tr>
                    <td><?php echo $value['sdate'] .'--'.$value['edate']?></td>
                    <td><?php echo $value['all']?></td>
                </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
    <!-- 统计表格隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="1">小时区间</th>
                    <th colspan="1" rowspan="1">下单数</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
