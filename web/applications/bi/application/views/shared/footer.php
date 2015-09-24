
        </div><!-- div rows -->
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="http://cdn.bootcss.com/jquery/1.11.2/jquery.min.js"></script>
    <script type="text/javascript" src="http://code.jquery.com/ui/1.11.3/jquery-ui.min.js"></script>
    <script src="http://cdn.bootcss.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script src="http://cdn.bootcss.com/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js" charset="UTF-8"></script>
    <script src="http://cdn.bootcss.com/bootstrap-datepicker/1.4.0/locales/bootstrap-datepicker.zh-CN.min.js" charset="UTF-8"></script>
    <script type="text/javascript" src="http://echarts.baidu.com/build/dist/echarts.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/date.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/jquery.twbsPagination.min.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/bi.js?v=<?php echo $js_version;?>"></script>
    <?php if(!empty($load_js)):?>
        <?php foreach ($load_js as $filname):?>
            <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/<?php echo $filname;?>?v=<?php echo $js_version;?>"></script>
        <?php endforeach;?>
    <?php endif;?>
    </body>
</html>
