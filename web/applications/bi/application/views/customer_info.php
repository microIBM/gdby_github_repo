<!-- 大厨、大果货物销量统计模板-->
<?php include APPPATH."views/shared/header.php"?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <h1 class="page-header">
    <?php
        if ($site_id == 1) {
            echo '大厨网';
        } elseif ($site_id == 2) {
            echo '大果网';
        }
    ?> · 客户分析
    </h1>
    <?php
        $current_url = $base_url.'/statics/customer_info?site_id='.$site_id;
        $customer_all = '';
        $customer_losting = '';
        $customer_new= '';
        $customer_save= '';
        $customer_loyal = '';
        $customer_valid = '';
        $customer_loss = '';
        switch ($tab_id) {
            case 1:
                $customer_all = 'active';
                break;
            case 2:
                $customer_losting = 'active';
                break;
            case 3:
                $customer_new = 'active';
                break;
            case 4:
                $customer_save = 'active';
                break;
            case 5:
                $customer_loyal = 'active';
                break;
            case 6:
                $customer_valid = 'active';
                break;
            case 7:
                $customer_loss = 'active';
                break;
            default:
                break;
        }
    ?>
    <!-- 筛选区域 -->
    <?php
        //print_r($customer_orderinfo);
    ?>
    <div class="row">
        <div class="col-sm-12">
            
            <div class="btn-group a-list" role="group">
                <a href="<?php echo $current_url.'&tab_id=1&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_all ?>" type="group">所有客户</a>
                <a href="<?php echo $current_url.'&tab_id=2&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_losting ?>" type="group">将流失客户</a>
                <a href="<?php echo $current_url.'&tab_id=3&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_new ?>" type="group">新客户</a>
                <a href="<?php echo $current_url.'&tab_id=4&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_save ?>" type="group">留存客户</a>
                <a href="<?php echo $current_url.'&tab_id=5&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_loyal ?>" type="group">忠实客户</a>
                <a href="<?php echo $current_url.'&tab_id=6&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_valid ?>" type="group">有效客户</a>
                <a href="<?php echo $current_url.'&tab_id=7&city_id='.$city_id ?>&customer_type=<?php echo $customer_type?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php echo $customer_loss ?>" type="group">流失客户</a>
            </div>
            <span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="
                <strong>将流失客户</strong>：7天前注册，7天内下单数≤1，且最近4天未下单<hr>
                <strong>新客户</strong>：7天内注册客户<hr>
                <strong>留存客户</strong>：7天前注册，7天内下单数≥2，且最近4天下单数≥1<hr>
                <strong>忠实客户</strong>：7天前注册，下单频率≥0.5<hr>
                <strong>有效客户</strong>: 注册后下过单的客户<hr>
                <strong>流失客户</strong>:连续30天未下单，且之前下过单的客户
                " aria-hidden="true">
            </span>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-7 myform">
            <form class="form-inline" action="<?php echo $base_url?>/statics/customer_info"  method="get">
                <div class="input-group key-words myselect">
                    <div class="input-group-addon">选择客户</div>
                    <select name="customer_type" class="form-control J-customer">
                        <option value="0" <?php if($customer_type == 0) echo 'selected';?>>全部客户</option>
                        <option value="1" <?php if($customer_type == 1) echo 'selected';?>>非KA客户</option>
                        <option value="2" <?php if($customer_type == 2) echo 'selected';?>>KA客户</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="input-group key-words myselect">
                        <div class="input-group-addon">关键字</div>
                        <select name="searchKey" class="form-control search-key">
                            <?php $search_type = isset($_GET['searchKey']) ? $_GET['searchKey'] : "" ?>
                            <option  value="c_name" <?php if($search_Type == 'c_name' || $search_Type == "") {echo "selected"; } ?> >客户姓名</option>
                            <option  value="c_tel" <?php if($search_Type == 'c_tel') {echo "selected"; }?>>客户电话</option>
                            <option  value="c_shop" <?php if($search_Type == 'c_shop') {echo "selected"; }?>>客户店铺名称</option>
                            <option  value="c_id" <?php if($search_Type == 'c_id') {echo "selected"; }?>>客户ID</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control search-value" id="search-Value" name="searchValue" value="<?php echo $search_value?>" placeholder="请输入客户姓名">
                    </div>
                </div>
                <!-- 隐藏域 -->
                <input name="site_id" type="hidden" value="<?php echo $site_id ?>">
                <input name="tab_id" type="hidden" value="<?php echo $tab_id ?>">
                <input name="offset" type="hidden" value="<?php echo $offset ?>">
                <input name="city_id" type="hidden" value="<?php echo $city_id?>">
                <input name="menue_id" type="hidden" value="<?php echo $menue_id?>">
                <div class="control_btn">
                    <button type="submit" class="btn btn-primary">筛选</button>
                    <a class="btn btn-warning reset" href="<?php echo $base_url?>/statics/customer_info?site_id=<?=$site_id?>&tab_id=<?php echo $tab_id?>&city_id=<?php echo $city_id?>">重置</a>
                </div>
            </form>
        </div>
        <div class="col-sm-5">
            <ul class="list-unstyled pull-right">
                <li>※所有数据不包括交易关闭的订单。今日数据为实时数据。</li>
                <li>※每日的划分按截单时间23:00计算（每天的23：01算入第二天中）。</li>
            </ul>
        </div>
    </div>

    <!-- 统计表格 -->
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="1">客户ID</th>
                    <th colspan="1" rowspan="1">店铺名称</th>
                    <th colspan="1" rowspan="1">客户姓名</th>
                    <th colspan="1" rowspan="1">客户电话</th>
                    <th colspan="1" rowspan="1">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="总有效订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">合并后订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将同一顾客、同一配送时间的订单合并成一单后的订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="总下单金额" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单金额/订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">下单频率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有下单的天数/注册天数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">详情</th>
                </tr>
            </thead>

            <!-- 统计表格表内容 -->
            <tbody>
            <?php foreach($ids as $value) :?>
                <tr>
                    <td><?php echo $value ?></td>
                    <td><?php echo $customer_baseinfo[$value]['shop_name'] ?></td>
                    <td><?php echo $customer_baseinfo[$value]['name'] ?></td>
                    <td><?php echo $customer_baseinfo[$value]['mobile'] ?></td>
                    <td><?php if(isset($customer_orderinfo[$value])) echo $customer_orderinfo[$value]['order_num']; else echo 0;?></td>
                    <td><?php if(isset($customer_orderinfo[$value])) echo $customer_orderinfo[$value]['distinct_cnt']; else echo 0;?></td>
                    <td><?php if(isset($customer_orderinfo[$value])) echo number_format($customer_orderinfo[$value]['order_amount']/100, 2); else echo 0;?></td>
                    <td><?php if(isset($customer_orderinfo[$value])) echo number_format($customer_orderinfo[$value]['average_price']/100, 2); else echo 0;?></td>
                    <td><?php if(isset($customer_orderate[$value])) echo $customer_orderate[$value]; else echo 0;?></td>
                    <td><a href="<?php echo $base_url?>/customer_statics/show_cus_detail?site_id=<?=$site_id?>&cus_id=<?php echo $value; ?>&city_id=<?php echo $city_id?>&menue_id=<?php echo $menue_id?>">查看</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 统计表格隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="1">客户ID</th>
                    <th colspan="1" rowspan="1">店铺名称</th>
                    <th colspan="1" rowspan="1">客户姓名</th>
                    <th colspan="1" rowspan="1">客户电话</th>
                    <th colspan="1" rowspan="1">订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="总有效订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">合并后订单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="将同一顾客、同一配送时间的订单合并成一单后 的订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">订单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="总下单金额" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">客单价<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="订单金额/订单数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">下单频率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有下单的天数/注册天数" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">详情</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- 分页 -->
    <?php echo $pagination['links'];?>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
