<?php include APPPATH."views/shared/header.php"?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <h1 class="page-header">大厨网-竞品分析</h1>
<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">大图</h4>
      </div>
      <div class="modal-body anti-modal">
      <img class="anti-product-img-modal" src="<?php echo $base_url.'/resource/img/no_image.jpg' ?>">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      </div>
    </div>
  </div>
</div>
    <?php
    $current_url = $base_url.'/spider_anti/index';
    ?>
    <div class="anti-line">
        <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span>站点: </span><?= $friend_list[$friend_id] ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php
                foreach ($friend_list as $key => $value){
                    if($key == 22){
                        continue;
                    }
                    $url = $current_url . '?search_key='.$search_key.'&menue_id=8&friend_id=' . $key.'&city_id='.$city_id.'&search_value='.$search_value;
                    echo "<li><a href='{$url}'>$value</a></li>";
                }
                ?>
            </ul>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span>城市: </span><?= $city_list[$city_id] ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php
                foreach ($city_list as $key => $value){
                    $url = $current_url . '?search_key='.$search_key.'&menue_id=8&city_id=' . $key . '&friend_id=' . $friend_id . '&search_value=' . $search_value;
                    echo "<li><a href='{$url}'>$value</a></li>";
                }
                ?>
            </ul>
        </div>
            <form class="form-inline myform inline" action="<?php echo $current_url ?>"  method="get">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-addon">关键字</div>
                            <select name="search_key"  class="form-control">
                                <option  value="product_name" <?php if($search_key == 'product_name' || $search_key == "") {echo "selected"; } ?> >商品名称</option>
                                <option  value="product_id" <?php if($search_key == 'product_id') {echo "selected"; }?>>商品ID</option>
                                <option  value="sku_number" <?php if($search_key == 'sku_number') {echo "selected"; }?>>SKUID</option>
                            </select>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control search-value" id="searchValue" name="search_value" value="<?php echo $search_value?>" placeholder="请输入搜索关键字">
                        <input type="hidden" name="menue_id" value="8">
                        <input type="hidden" name="city_id" value="<?= $city_id ?>">
                        <input type="hidden" name="friend_id" value="<?= $friend_id ?>">
                    </div><!--input-group -->
                    </div><!-- form-group-->
                <button type="submit" class="btn btn-primary">查询</button>
            </form>
    </div>
    <div class="col-sm-12 pl-0">
            <?php if($friend_id != 0 && $city_id != 0): ?>
            <div class="panel panel-default">
                <div class="panel-heading">分类选择</div>
                <div class="panel-body pb-10">
                    <?php
                    foreach($cate_list AS $val){
                        $url = $current_url .'?menue_id=8&cate_name=' . $val . '&city_id=' . $city_id . '&friend_id=' . $friend_id;
                    ?>
                        <a href="<?= $url ?>" class="btn btn-sm <?= ($cate_name == $val) ? 'btn-danger' : 'btn-info' ?>"><?= $val ?></a>
                    <?php } ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <div class="form-inline fr m-r-15">
        <div class="form-group">
            <label>将选定的竞品信息关联到SKU: </label>
            <input type="text" class="form-control J-sku-num" placeholder="请输入SKU货号">
            <a href="javascript:;" class="btn btn-primary J-map-anti">关联</a>
        </div>
    </div>
    <!-- 统计表格 -->
    <div class="table-responsive col-sm-12 pl-0 table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
            <tr>
                <th colspan="1" rowspan="1"></th>
                <th colspan="1" rowspan="1">ID</th>
                <th colspan="1" rowspan="1">图片</th>
                <th colspan="1" rowspan="1">站点名称</th>
                <th colspan="1" rowspan="1">城市</th>
                <th colspan="1" rowspan="1">商品名称</th>
                <th colspan="1" rowspan="1">商品规格</th>
                <th colspan="1" rowspan="1">商品描述</th>
                <th colspan="1" rowspan="1">单价</th>
                <th colspan="1" rowspan="1">总价</th>
                <th colspan="1" rowspan="1">价格同比<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="和前次数据抓取的单价之差"></th>
                <th colspan="1" rowspan="1">更新时间</th>
                <th colspan="1" rowspan="1">已关联SKU</th>
            </tr>
            </thead>

            <!-- 统计表格表内容 -->
            <tbody>
            <?php foreach($data_list AS $info): ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td name="auto_id" value="<?= $info['auto_id'] ?>" class="hidden"></td>
                    <td name="product_id" value="<?= $info['product_id'] ?>"><?= $info['product_id'] ?></td>
                    <td><img <?php if($info['images'] != '-' && $info['images']) echo "data-toggle='modal' data-target='#myModal'" ?> class="anti-product-image" src="<?php if($info['images'] != '-' && $info['images']) echo $info['images']; else echo $base_url.'/resource/img/no_image.jpg'; ?>"></td>
                    <td name="site_id" value="<?= $info['site_id'] ?>"><?= $info['site_name'] ?></td>
                    <td name="city_id" value="<?= $info['city_id'] ?>"><?= $city_list[$info['city_id']] ?></td>
                    <td><?= $info['title'] ?><?php if(strpos($info['title'], '*') !== FALSE) :?><span class="badge">改</span><?php endif; ?></td>
                    <td><?= $info['prop'] ?></td>
                    <td><?= $info['feature'] ?></td>
                    <td><?= ($info['single_price']/100) ?>元</td>
                    <td><?= ($info['price']/100) ?>元</td>
                    <td>
                    <?php if($info['price_change'] == 0): ?><span class="glyphicon glyphicon-minus price-same"></span><?php elseif($info['price_change'] > 0): ?>
                    <span class="glyphicon glyphicon-arrow-up price-up"><?= $info['price_change']/100 ?>元</span><?php elseif($info['price_change'] < 0): ?>
                    <span class="glyphicon glyphicon-arrow-down price-down"><?= $info['price_change']/100 ?>元</span>
                    <?php endif; ?>
                    </td>
                    <td><?= $info['updated_time'] ?></td>
                    <td>
                        <?php foreach($info['sku_numbers'] as $value): ?>
                        <div class="sku-num-label-container J-sku-num-del">
                        <span class="label label-primary center-block sku-num-label"><?= $value ?></span>
                        <span class="glyphicon glyphicon-remove-circle sku-num-close"></span>
                        </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 隐藏表头 -->
    <div class="table-responsive table-hide">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
            <tr>
                <th colspan="1" rowspan="1"></th>
                <th colspan="1" rowspan="1">ID</th>
                <th colspan="1" rowspan="1">图片</th>
                <th colspan="1" rowspan="1">站点名称</th>
                <th colspan="1" rowspan="1">城市</th>
                <th colspan="1" rowspan="1">商品名称</th>
                <th colspan="1" rowspan="1">商品规格</th>
                <th colspan="1" rowspan="1">商品描述</th>
                <th colspan="1" rowspan="1">单价</th>
                <th colspan="1" rowspan="1">总价</th>
                <th colspan="1" rowspan="1">价格同比</th>
                <th colspan="1" rowspan="1">更新时间</th>
                <th colspan="1" rowspan="1">已关联SKU</th>
            </tr>
            </thead>
        </table>
    </div>
    <!-- 分页 -->
    <div class="control-pages clearfix">
        <nav class="pagination" ><?= $pagination ?></nav>
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
                    <li><a href="<?=$base_url?>/spider_anti/index?friend_id=<?= $friend_id?>&menue_id=8&offset=10&search_key=<?=$search_key?>&search_value=<?=$search_value ?>&cate_name=<?=$cate_name ?>&city_id=<?=$city_id ?>">每页10条</a></li>
                    <?php if($total_records > 10): ?>
                        <li><a href="<?=$base_url?>/spider_anti/index?friend_id=<?= $friend_id?>&menue_id=8&offset=15&search_key=<?=$search_key?>&search_value=<?=$search_value ?>&cate_name=<?=$cate_name ?>&city_id=<?=$city_id ?>">每页15条</a></li>
                    <?php endif; ?>
                    <?php if($total_records > 15): ?>
                        <li><a href="<?=$base_url?>/spider_anti/index?friend_id=<?= $friend_id?>&menue_id=8&offset=20&search_key=<?=$search_key?>&search_value=<?=$search_value ?>&cate_name=<?=$cate_name ?>&city_id=<?=$city_id ?>">每页20条</a></li>
                    <?php endif; ?>
                    <?php if($total_records > 20): ?>
                        <li><a href="<?=$base_url?>/spider_anti/index?friend_id=<?= $friend_id?>&menue_id=8&offset=30&search_key=<?=$search_key?>&search_value=<?=$search_value ?>&cate_name=<?=$cate_name ?>&city_id=<?=$city_id ?>">每页30条</a></li>
                    <?php endif; ?>
                    <?php if($total_records > 30): ?>
                        <li><a href="<?=$base_url?>/spider_anti/index?friend_id=<?= $friend_id?>&menue_id=8&offset=50&search_key=<?=$search_key?>&search_value=<?=$search_value ?>&cate_name=<?=$cate_name ?>&city_id=<?=$city_id ?>">每页50条</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="label label-info total-records">共 <?=$total_records ?> 条记录</div>
    </div>
</div>
<?php include APPPATH."views/shared/footer.php" ?>
