<!-- SKU统计模板-->
<?php include APPPATH . "views/shared/header.php" ?>
<script type="text/javascript">
var php_category_id = <?php echo isset($category_id) ? $category_id : 1; ?>;
</script>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <form>
        <div>
            <!-- 面包屑分类导航 -->
            <h3>
                <ol class="breadcrumb">
                    <?php if (isset($crumbs)) :
                    foreach($crumbs as $key => $val) :?>
                    <li><?php echo $val?></li>
                    <?php endforeach;endif;?>
                </ol>
            </h3>
        </div>

        <div class="new-line">
            <!-- 时间快捷选择方式 -->
            <div class="btn-group J-sku-quik-search" role="group">
                <button type="button" class="btn btn-default" value="2">昨日</button>
                <button type="button" class="btn btn-default" value="3">上周</button>
                <button type="button" class="btn btn-default" value="4">上月</button>
            </div>
            <!-- 时间选择，按天或周或月 -->
            <div class="input-group key-words myselect">
                <select class="form-control width-4p" name="date-mode">
                    <option value="1" selected="selected">按天</option>
                    <option value="3">按周</option>
                    <option value="2">按月</option>
                </select>
                <input name="sdate" class="form-control width-6p to J-datepicker-sku" value="">
            </div>
            <span class="J-week-edate hidden">到 <input name="edate" class="week-edate" readonly></span>

            <!-- 仓库选择 -->
            <div class="input-group key-words myselect">
                <div class="input-group-addon">仓库</div>
                <select class="form-control search-key J-warehouse" name="warehouse-id">
                </select>
            </div>

            <button type="button" id="searchSubmit" class="btn btn-primary J-sku-search">筛选</button>
            <a class="btn btn-warning reset" href="">重置</a>
        </div>
    </form>
    <!-- 数据表格 -->
    <div class="table-responsive table-show new-line">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th>日期</th>
                    <th>下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内除取消订单外的总订单金额"></span></th>
                    <th>签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内所有已签收订单金额总和"></span></th>
                    <th>总采购成本<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下所有sku最近五个批次的加权平均采购成本总和"></span></th>
                    <th>签收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内签收sku件数总和"></span></th>
                    <th>在线sku种数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下在时间周期内有销量的SKU数"></span></th>
                    <th>整体动销率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下在时间周期内有签收的SKU数/该分类下所有已上架SKU种数"></span></th>
                    <th>毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="（签收金额 - 总采购成本）/签收金额"></span></th>
                    <th>客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该品类商品的客户总数/时间周期内下单的客户总数"></span></th>
                    <th>质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下投诉类型为质量问题的订单总数/时间周期内该品类下已签收的订单总数"></span></th>
                    <th>退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下已处理退货退款订单总数"></span></th>
                    <th>拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下的订单拒收总件数"></span></th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <!-- 隐藏表头 -->
    <div class="table-responsive table-hide new-line">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <thead class="content-indicator nav-table">
                <tr>
                    <th>日期</th>
                    <th>下单金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内除取消订单外的总订单金额"></span></th>
                    <th>签收金额<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内所有已签收订单金额总和"></span></th>
                    <th>总采购成本<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下所有sku最近五个批次的加权平均采购成本总和"></span></th>
                    <th>签收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内签收sku件数总和"></span></th>
                    <th>在线sku种数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下在时间周期内有销量的SKU数"></span></th>
                    <th>整体动销率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="该品类下在时间周期内有签收的SKU数/该分类下所有已上架SKU种数"></span></th>
                    <th>毛利率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="（签收金额 - 总采购成本）/签收金额"></span></th>
                    <th>客户覆盖率<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内购买过该品类商品的客户总数/时间周期内下单的客户总数"></span></th>
                    <th>质量问题投诉单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下投诉类型为质量问题的订单总数/时间周期内该品类下已签收的订单总数"></span></th>
                    <th>退货单数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下已处理退货退款订单总数"></span></th>
                    <th>拒收件数<span class="glyphicon glyphicon-question-sign pop" data-placement="bottom" data-content="时间周期内该品类下的订单拒收总件数"></span></th>
                    <th>操作</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?php include APPPATH . "views/shared/footer.php" ?>
