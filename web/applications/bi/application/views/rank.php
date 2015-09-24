<!-- 大厨、大果货物销量统计模板-->
<?php include APPPATH."views/shared/header.php"?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <h1 class="page-header">大厨网 · 货物销量 </h1>
    <!-- 日期选择 -->
    <div class="clearfix">
        <?php
            $today = date("Y-m-d");
            $yesterday = date("Y-m-d", strtotime("-1 day"));
            $past_seven_day = date("Y-m-d", strtotime("-6 day"));
        ?>
        <div class="btn-group pull-left fast-btn">
            <a href="<?php echo $base_url?>/statics/sku_rank?tab_id=1&city_id=<?php echo $city_id?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php if($tab_id == 1) { echo 'active'; }?>" role="button" id="today">今天</a>
            <a href="<?php echo $base_url?>/statics/sku_rank?tab_id=2&city_id=<?php echo $city_id?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php if($tab_id == 2) { echo 'active'; }?>" role="button" id="yesterday">昨天</a> 
            <a href="<?php echo $base_url?>/statics/sku_rank?tab_id=3&city_id=<?php echo $city_id?>&menue_id=<?php echo $menue_id;?>" class="btn btn-default <?php if($tab_id == 3) { echo 'active'; }?>" role="button" id="past-7-days">最近7天</a>
        </div>
        <div class="input-daterange input-group pull-left" id="datepicker">
            <span class="input-group-addon">起始日期</span>
            <input name="from" type="text" class="form-control from" value="<?php if($tab_id == 1) echo $today; elseif ($tab_id == 2) echo $yesterday; elseif ($tab_id == 3) echo $past_seven_day; elseif ($tab_id == 0) echo $sdate;?>"/>
            <span class="input-group-addon">截止日期</span>
            <input name="to" type="text" class="form-control to" value="<?php if($tab_id == 1 || $tab_id == 3) echo $today; elseif ($tab_id == 2) echo $yesterday; elseif ($tab_id == 0) echo $edate;?>"/>
        </div>
    </div>
    <!-- 搜索输入框:名称、分类、货号 -->
    <p></p>
    <ul class="list-unstyled list-inline clearfix">
        <li>
            <form class="form-inline" action="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab_id?>&city_id=<?php echo $city_id?>" method="get">
                <div class="form-group">
                    <div class="input-group key-words">
                        <div class="input-group-addon">关键字</div>
                            <select name="searchKey"  class="form-control">
                                <?php $search_type = isset($_GET['searchKey']) ? $_GET['searchKey'] : "" ?> 
                                <option  value="name" <?php if($search_type == 'name' || $search_type == "") {echo "searched"; } ?> >名称</option>
                                <option  value="sku_number" <?php if($search_type == 'sku_number') {echo "selected"; }?>>货号</option>
                            </select>
                    </div>
                    <label class="sr-only" for="searchValue">关键字</label>
                    <div class="input-group">
                        <input type="text" class="form-control search-value" id="searchValue" name="searchValue" value="<?php echo $search_value?>" placeholder="请输入名称或货号">
                    </div><!--input-group -->
                    </div><!-- form-group-->
                    <input name="is_tab_id" type="hidden" value="<?php echo $is_tab_id ?>">
                    <input name="tab_id" type="hidden" value="<?php echo $tab_id ?>">
                    <input name="offset" type="hidden" value="<?php echo $offset ?>">
                    <input name="city_id" type="hidden" value="<?php echo $city_id?>">
                    <input name="menue_id" type="hidden" value="<?php echo $menue_id?>">
                    <input name="sdate" type="hidden" value="">
                    <input name="edate" type="hidden" value="">
                <button type="submit" class="btn btn-primary">筛选</button>
                <!--<button class="btn btn-warning reset" type="reset">重置</button> --> 
                <a class="btn btn-warning reset" href="<?php echo $base_url?>/statics/sku_rank?menue_id=<?=$menue_id?>&tab_id=<?php echo $tab_id?>&city_id=<?php echo $city_id?>">重置</a>
            </form>
        </li>
        <li class="desc pull-right">
            <ul class="list-unstyled"> 
                <li style="font-size:14px;">※所有数据不包括交易关闭的订单。今日数据为实时数据。</li>
                <li style="font-size:14px;">※每日的划分按截单时间23:00计算（每天的23:01算入第二天中）。</li>
            </ul>
        </li>
    </ul>

    <!-- 统计表格 -->
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="2">货号</th>
                    <th colspan="3" rowspan="1">货物</th>
                    <th colspan="1" rowspan="2">交易额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="已完成和已签收订单中该货物的总金额" aria-hidden="true"></span></th>
                    <th colspan="2" rowspan="1">销量(SKU)</th>
                </tr>
                <tr>
                    <th colspan="1" rowspan="1">名称</th>
                    <th colspan="1" rowspan="1">规格</th>
                    <th colspan="1" rowspan="1">分类</th>
                    <th colspan="1" rowspan="1">下单量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单中该货物数量" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="已完成和已签收订单中该货物数量" aria-hidden="true"></span></th>
                </tr>
            </thead>
            <!-- 统计表格表内容 -->
            <tbody>
            <?php if(!empty($rank)): ?>
            <?php foreach ($rank['sku_number'] as $key=>$value): ?>
                <tr>
                    <td><?php echo $value ?></td>
                    <td><?php echo $rank['sku_info'][$key]['name'] ?></td>
                    <td><?php echo $rank['sku_info'][$key]['spec'] ?></td>
                    <td><?php echo $rank['sku_info'][$key]['category'] ?></td>
                    <td><?php echo number_format($rank['transaction_amount'][$key],2);?></td>
                    <td><?php echo $rank['ordered_num'][$key]?></td>
                    <td><?php echo $rank['sucess_ordered_num'][$key]?></td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- 统计表格隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th colspan="1" rowspan="2">货号</th>
                    <th colspan="3" rowspan="1">货物</th>
                    <th colspan="1" rowspan="2">交易额（￥）<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="已完成订单中该货物的总金额" aria-hidden="true"></span></th>
                    <th colspan="2" rowspan="1">销量(SKU)</th>
                </tr>
                <tr>
                    <th colspan="1" rowspan="1">名称</th>
                    <th colspan="1" rowspan="1">规格</th>
                    <th colspan="1" rowspan="1">分类</th>
                    <th colspan="1" rowspan="1">下单量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="有效订单中该货物数量" aria-hidden="true"></span></th>
                    <th colspan="1" rowspan="1">成交量<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="成交订单中该货物数量" aria-hidden="true"></span></th>
                </tr>
            </thead>
        </table>
    </div>
    <!-- 分页 -->
    <div class="control-pages clearfix">
        <nav class="pagination" ><?php echo $pagination ?></nav>
        <?php if($total_records > $offset): ?>
             <!--<div class="input-group page-num">
                 <span class="input-group-addon">第</span>
                  <input id="page-num" type="number" min="1" value="<?php echo $page ?>" class="form-control">
                 <span class="input-group-addon">页</span>
             </div>-->
        <?php endif; ?>
        <?php if($total_records >= 10): ?>
            <div class="btn-group dropup page-size">
                  <button type="button" class="btn btn-default">
                  <?php if($offset == 10): ?>每页10条
                  <?php elseif($offset == 15 && $total_records >=15): ?>每页15条
                  <?php elseif($offset == 20 && $total_records >=20): ?>每页20条
                  <?php elseif($offset == 30 && $total_records >=30): ?>每页30条
                  <?php elseif($offset == 50 && $total_records >=50): ?>每页50条
                  <?php else: ?>选择每页条目数
                  <?php endif; ?>
                  </button>
                  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu" role="menu">
                        <?php if($is_tab_id == 'true') {
                            $tab = $tab_id;
                        } elseif ($is_tab_id == 'false') {
                            $tab = 0;
                        }?>
                      <li><a href="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab ?>&is_tab_id=<?php echo $is_tab_id ?>&offset=10&searchKey=<?php echo $search_type ?>&searchValue=<?php echo $search_value ?>&sdate=<?php echo $sdate ?>&edate=<?php echo $edate ?>&city_id=<?php echo $city_id ?>">每页10条</a></li>
                      <?php if($total_records > 10): ?>
                      <li><a href="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab ?>&is_tab_id=<?php echo $is_tab_id ?>&offset=15&searchKey=<?php echo $search_type ?>&searchValue=<?php echo $search_value ?>&sdate=<?php echo $sdate ?>&edate=<?php echo $edate ?>&city_id=<?php echo $city_id ?>">每页15条</a></li>
                      <?php endif; ?>
                      <?php if($total_records > 15): ?>
                      <li><a href="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab ?>&is_tab_id=<?php echo $is_tab_id ?>&offset=20&searchKey=<?php echo $search_type ?>&searchValue=<?php echo $search_value ?>&sdate=<?php echo $sdate ?>&edate=<?php echo $edate ?>&city_id=<?php echo $city_id ?>">每页20条</a></li>
                      <?php endif; ?>
                      <?php if($total_records > 20): ?>
                      <li><a href="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab ?>&is_tab_id=<?php echo $is_tab_id ?>&offset=30&searchKey=<?php echo $search_type ?>&searchValue=<?php echo $search_value ?>&sdate=<?php echo $sdate ?>&edate=<?php echo $edate ?>&city_id=<?php echo $city_id ?>">每页30条</a></li>
                      <?php endif; ?>
                      <?php if($total_records > 30): ?>
                      <li><a href="<?php echo $base_url?>/statics/sku_rank?tab_id=<?php echo $tab ?>&is_tab_id=<?php echo $is_tab_id ?>&offset=50&searchKey=<?php echo $search_type ?>&searchValue=<?php echo $search_value ?>&sdate=<?php echo $sdate ?>&edate=<?php echo $edate ?>&city_id=<?php echo $city_id ?>">每页50条</a></li>
                      <?php endif; ?>
                  </ul>
            </div>
        <?php endif; ?>
        <div class="label label-info total-records">共 <?php echo $total_records ?> 条记录</div>
    </div>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>
