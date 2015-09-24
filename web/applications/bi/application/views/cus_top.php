<!-- 客户下单详情 -->
<?php include APPPATH."views/shared/header.php"?>
<?php
$current_url = $base_url.'/cus_top';
if($tab_id) {
    $cus_date_url = $current_url.'?tab_id='.$tab_id.'&sdate='.$sdate.'&edate='.$edate.'&pagesize='.$pagesize;
} else {
    $cus_date_url = $current_url.'?sdate_picker='.$sdate_picker.'&edate_picker='.$edate_picker.'&pagesize='.$pagesize;
}?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main customer-info">
    <h1 class="page-header">
    大厨网 · 客户下单品类详情
    </h1>
    <!-- 筛选区域 -->
    <div class="row">
        <div class="col-sm-12">
            <div>
            <div class="btn-group" role="group" style="vertical-align: baseline;">
            <a href="<?php echo $current_url.'?tab_id=0&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id === 0) echo 'active' ?>" type="group">历史总计</a>
                <a href="<?php echo $current_url.'?tab_id=1&sdate='.$date['today']['sdate'].'&edate='.$date['today']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize ?>" class="btn btn-default <?php if($tab_id == 1) echo 'active' ?>" type="group">今天</a>
                <a href="<?php echo $current_url.'?tab_id=2&sdate='.$date['yesterday']['sdate'].'&edate='.$date['yesterday']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id == 2) echo 'active' ?>" type="group">昨天</a>
                <a href="<?php echo $current_url.'?tab_id=3&sdate='.$date['this_week']['sdate'].'&edate='.$date['this_week']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id == 3) echo 'active' ?>" type="group">本周</a>
                <a href="<?php echo $current_url.'?tab_id=4&sdate='.$date['last_week']['sdate'].'&edate='.$date['last_week']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id == 4) echo 'active' ?>" type="group">上周</a>
                <a href="<?php echo $current_url.'?tab_id=5&sdate='.$date['this_month']['sdate'].'&edate='.$date['this_month']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id == 5) echo 'active' ?>" type="group">本月</a>
                <a href="<?php echo $current_url.'?tab_id=6&sdate='.$date['last_month']['sdate'].'&edate='.$date['last_month']['edate'].'&customer_type='.$customer_type.'&pagesize='.$pagesize?>" class="btn btn-default <?php if($tab_id == 6) echo 'active' ?>" type="group">上月</a>
            </div>
            <div class="inline">
            <div class="input-daterange input-group" id="datepicker">
                <span class="input-group-addon">起始日期</span>
                <input name="from" type="text" class="form-control from" value="<?php if($sdate_picker !== 0) echo $sdate_picker ?>"/>
                <span class="input-group-addon">截止日期</span>
                <input name="to" type="text" class="form-control to" value="<?php if($edate_picker !== 0) echo $edate_picker ?>"/>
            </div>
            </div>
            </div>
            <div class="m-t-10">
            <div class="btn-group" role="group">
                <a href="<?php echo $cus_date_url.'&customer_type=0'?>" class="btn btn-default <?php if($customer_type == 0) echo 'active' ?>" type="group">全部类型</a>
                <a href="<?php echo $cus_date_url.'&customer_type=1'?>" class="btn btn-default <?php if($customer_type == 1) echo 'active' ?>" type="group">普通客户</a>
                <a href="<?php echo $cus_date_url.'&customer_type=2'?>" class="btn btn-default <?php if($customer_type == 2) echo 'active' ?>" type="group">KA客户</a>
            </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 myform">
            <form class="form-inline" action="<?php echo $current_url?>"  method="get">
                <div class="form-group">
                    <div class="input-group key-words myselect">
                        <div class="input-group-addon">关键字</div>
                        <select name="search_key" class="form-control search-key">
                            <?php $search_key = isset($_GET['search_key']) ? $_GET['search_key'] : "" ?>
                            <option  value="c_name" <?php if($search_key == 'c_name' || $search_key == "") {echo "selected"; } ?> >客户姓名</option>
                            <option  value="c_tel" <?php if($search_key == 'c_tel') {echo "selected"; }?>>客户电话</option>
                            <option  value="c_shop" <?php if($search_key == 'c_shop') {echo "selected"; }?>>客户店铺名称</option>
                            <option  value="c_id" <?php if($search_key == 'c_id') {echo "selected"; }?>>客户ID</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control search-value" name="search_value" value="<?php echo $search_value?>" placeholder="请输入关键字">
                    </div>
                    <!-- 隐藏域 -->
                    <input name="pagesize" type="hidden" value="<?php echo $pagesize ?>">
                    <input name="tab_id" type="hidden" value="<?php echo $tab_id ?>">
                    <input name="city_id" type="hidden" value="<?php echo $city_id?>">
                    <input name="customer_type" type="hidden" value="<?php echo $customer_type?>">
                    <input name="sdate" type="hidden" value="<?php echo $sdate ?>">
                    <input name="edate" type="hidden" value="<?php echo $edate ?>">
                    <input name="sdate_picker" type="hidden" value="">
                    <input name="edate_picker" type="hidden" value="">
                    <input name="order_key" type="hidden" value="<?php echo $order_key?>">
                    <input name="order_value" type="hidden" value="<?php echo $order_value?>">
                    <div class="control_btn">
                        <button type="submit" class="btn btn-primary">筛选</button>
                        <a class="btn btn-warning reset" href="<?php echo $current_url ?>">重置</a>
                    </div>
                    </div>
            </form>
        </div>
        <!-- <div>
            <ul class="list-unstyled pull-right">
                <li>※</li>
            </ul>
        </div> -->
    </div>

    <!-- 统计表格 -->
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr class="sort">
                    <th>客户ID</th>
                    <th>客户店铺</th>
                    <th>客户姓名</th>
                    <th>客户电话</th>
                    <th>城市</th>
                    <?php foreach($cate_top as $value): ?>
                    <th><div class="sort-up"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key='.$value['category_id'] ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == $value['category_id']) echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key='.$value['category_id'] ?>" class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == $value['category_id']) echo 'blue' ?>"></a></div><div class="title-desc"><?php echo $value['category_name'] ?>(￥)</div></th>
                    <?php endforeach; ?>
                    <th><div class="sort-up"><a  href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key=vm' ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == 'vm') echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key=vm' ?>"  class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == 'vm') echo 'blue' ?>"></a></div><div class="title-desc">蔬菜+肉(￥)</div></th>
                    <th><div class="sort-up"><a  href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key=total' ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == 'total') echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key=total' ?>"  class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == 'total') echo 'blue' ?>"></a></div><div class="title-desc">总计(￥)</div></th>
                    <th style="width: 40px">操作</th>
                </tr>
            </thead>

            <!-- 统计表格表内容 -->
            <tbody>
                <?php foreach($cus_cate_info as $value): ?>
                <tr>
                    <td><?php echo $value['customer_id'] ?></td>
                    <td><?php echo $value['shop_name'] ?></td>
                    <td><?php echo $value['name']?></td>
                    <td><?php echo $value['mobile']?></td>
                    <td><?php echo $value['city_name']?></td>
                    <?php foreach($cate_top as $cate): ?>
                    <td><?php echo $value[$cate['category_id']] ?></td>
                    <?php endforeach; ?>
                    <td><?php echo $value['vm']?></td>
                    <td><?php echo $value['total']?></td>
                    <td><a href="<?=$base_url?>/customer_statics/show_cus_detail?site_id=1&cus_id=<?= $value['customer_id']?>&city_id=<?=$city_id?>&menue_id=4">查看</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 统计表格隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr class="sort">
                    <th>客户ID</th>
                    <th>客户店铺</th>
                    <th>客户姓名</th>
                    <th>客户电话</th>
                    <th>城市</th>
                    <?php foreach($cate_top as $value): ?>
                    <th><div class="sort-up"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key='.$value['category_id'] ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == $value['category_id']) echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key='.$value['category_id'] ?>" class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == $value['category_id']) echo 'blue' ?>"></a></div><div class="title-desc"><?php echo $value['category_name'] ?>(￥)</div></th>
                    <?php endforeach; ?>
                    <th><div class="sort-up"><a  href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key=vm' ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == 'vm') echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key=vm' ?>"  class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == 'vm') echo 'blue' ?>"></a></div><div class="title-desc">蔬菜+肉(￥)</div></th>
                    <th><div class="sort-up"><a  href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=asc&order_key=total' ?>" class="glyphicon glyphicon-triangle-top <?php if($order_value == 'asc' && $order_key == 'total') echo 'blue' ?>"></a></div><div class="sort-down"><a href="<?php echo $cus_date_url.'&customer_type='.$customer_type.'&order_value=desc&order_key=total' ?>"  class="glyphicon glyphicon-triangle-bottom <?php if($order_value == 'desc' && $order_key == 'total') echo 'blue' ?>"></a></div><div class="title-desc">总计(￥)</div></th>
                    <th>操作</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- 分页 -->
    <?php echo $pagination['links'];?>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
