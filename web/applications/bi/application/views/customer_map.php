
<?php include APPPATH."views/shared/header.php"?>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=47947c203833c809bc33fee2245790ab"></script>
<script type="text/javascript">
    var php_map_data = <?= json_encode($customer)?>;
    var php_price_border = <?= $price_border?>;
</script>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <form class="form-inline" action="<?= $base_url?>/customer_map"  method="get">
        <div class="form-group">
            <div class="input-daterange input-group key-words" id="datepicker">
                <span class="input-group-addon">起始日期</span>
                <input name="sdate" type="text" class="form-control from" value="<?= $sdate;?>"/>
                <span class="input-group-addon">截止日期</span>
                <input name="edate" type="text" class="form-control from" value="<?= $edate;?>"/>
            </div>
            <div class="input-group key-words">
                <span class="input-group-addon">品类</span>
                <input type="text" class="form-control from J-input-sku-content" data-toggle="modal" data-target="#skuModal" value="<?= isset($sku_modal_tips) ? $sku_modal_tips : '请选择品类'; ?>"/>
                <div class="modal fade" id="skuModal" tabindex="-1" role="dialog" aria-labelledby="skuModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="skuModalLabel">请选择品类</h4>
                            </div>
                            <table class="table table-condensed table-bordered table-striped table-hover">
                                <!-- 统计表格表头 -->
                                <thead class="content-indicator nav-table">
                                    <tr>
                                        <th><input type="checkbox" id="all_select"/></th>
                                        <th>品类</th>
                                    </tr>
                                </thead>
                                <!-- 统计表格表内容 -->
                                <tbody>
                                    <?php if ($sku_cate['status'] == 0) :
                                        foreach (($sku_cate['data']) as $key => $value):?>
                                    <tr>
                                        <td><input type="checkbox" name="sku_cate_ids[]" content="<?= $value['category_name']; ?>" value="<?= $value['category_id']; ?>" <?= in_array($value['category_id'], $sku_cate_ids) ? 'checked' : ''; ?>/></td>
                                        <td><?= $value['category_name']; ?></td>
                                    </tr>
                                <?php endforeach;endif; ?>
                                </tbody>
                            </table>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default J-button-ok" data-dismiss="modal">确定</button>
                            </div>		      
                        </div>
                    </div>
                </div>
            </div>
            <div class="input-group key-words">
                <span class="input-group-addon">日客单价分界点</span>
                <input name="price_border" type="text" class="form-control from" value="<?= $price_border?>"/>
            </div>
        </div><!-- form-group-->
        <input name="city_id" type="hidden" value="<?=$city_id ?>">
        <input name="menue_id" type="hidden" value="<?=$menue_id ?>">
        <button type="submit" class="btn btn-primary">确认</button>
        <a class="btn btn-warning reset" href="<?= $base_url?>/customer_map?menue_id=<?=$menue_id?>&city_id=<?=$city_id?>">重置</a>
    </form>
    <div style="margin-top:10px;">
        <img src="http://cache.amap.com/lbs/static/jsdemo003.png">日客单价 <= <?= $price_border?>&emsp;&emsp;&emsp;
        <img src="<?= $base_url ?>/resource/img/icon_red.png">日客单价 > <?= $price_border?>&emsp;&emsp;&emsp;
        <span>品类为:&emsp;<span class="J-span-sku-content"></span></span>
    </div>
    <div id="allmap" style="height:650px;width:100%;margin-top:10px;margin-bottom:10px;"></div>
    <p>日客单价 = 所选时间内选定品类的货品总额/该时间段内有下单的天数</p>
</div>
<!-- div main -->
<?php include APPPATH."views/shared/footer.php" ?>

