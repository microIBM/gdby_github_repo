<script type="text/javascript">
    var base_url = "<?php echo $base_url;?>";
</script>
<!-- 大厨大果、城市切换导航内容 -->
<div class="collapse text-center" id="x-navbar-collapse">

    <!-- 切换站点 -->
    <div class="panel panel-default">
        <div class="panel-heading">请选择客户类型</div>
        <div class="panel-body">
            <label class="radio-inline">
              <input type="radio" name="custypes" value="0"> 所有客户
            </label>
            <label class="radio-inline">
              <input type="radio" name="custypes" value="1"> 普通客户
            </label>
            <label class="radio-inline">
              <input type="radio" name="custypes" value="2"> KA客户
            </label>
        </div>
    </div>
    <!-- 切换城市 -->
    <div class="panel panel-default">
        <div class="panel-heading">请选择城市</div>
        <div class="panel-body">
            <div>
                <label class="radio-inline ">
                  <input type="radio" name="cities" value=""> 全国
                </label>
                <label class="radio-inline ">
                  <input type="radio" name="cities" value="804"> 北京
                </label>
                <label class="radio-inline ">
                  <input type="radio" name="cities" value="1206"> 天津
                </label>
            </div>
            <div>
                <label class="radio-inline ">
                  <input type="radio" name="cities" value="993"> 上海
                </label>
            </div>
        </div>
    </div>
    <button type="button" class="diy-blk btn btn-default J-nav-ok">选好了</button>
    <hr>

    <button type="button" class="diy-blk btn btn-default J-go-bi">进入数据后台(原BI系统)</button>
</div>