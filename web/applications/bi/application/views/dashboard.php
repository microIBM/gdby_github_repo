<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title>BI系统</title>
        <link rel="shortcut icon"  type="image/x-icon" href="<?php echo $base_url ?>/resource/img/icon.png" />
        <link rel="apple-touch-icon" href="<?php echo $base_url ?>/resource/img/icon.png" />
        <link rel="icon" href="<?php echo $base_url ?>/resource/img/icon.png" />
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resource/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resource/css/dashboard.css">
    </head>
    <body>
    <?php include APPPATH."views/shared/mobile_nav.php" ?>
        <div class="container-fluid">
            <!-- 头导航 -->
            <nav class="navbar navbar-default text-center">
                <span>大厨网 · <span class="J-cus-name"></span> · <span class="J-city-name"></span></span>
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#x-navbar-collapse">
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                </button>
            </nav>
            <!-- 柱状图 -->
            <div class="col-xs-12 x-simple-bar J-simple-bar">
                <ul class="list-unstyled">
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div class="J-bardate"></div><div class="triangle hidden"></div></li>
                    <li><div class="bar"><div class="inner-bar"></div></div><div>今</div><div class="triangle"></div></li>
                </ul>
            </div>
            <!-- 柱状图解释文字 -->
            <div class="col-xs-12 text-center pt-5 pb-5 font-white font-16"><span class="J-dateflag">今天</span>&nbsp;&nbsp;&nbsp;<span class="font-gray">实时更新</span></div>
            <!-- 数据展示导航 -->
            <nav class="col-xs-12 nav-list">
                <!-- 控制柱状图数据指向 -->
                <div class="current-data">
                    <div class="text-center">流水（￥）</div>
                    <div class="text-center font-30">
                    <span class="glyphicon glyphicon-menu-left pull-left J-date" name="left"></span><span class="J-amount-data"></span>&nbsp;<span class="font-16">元</span>
                        <span class="glyphicon glyphicon-menu-right pull-right J-date" name="right"></span>
                    </div>
                </div>
                <!-- 其他数据信息 -->
                <ul class="list-unstyled nav-redirect">
                    <li class="J-order">
                        <div class="clearfix mt-5">
                            <span class="glyphicon glyphicon-list-alt font-30 pull-left"></span>
                            <span class="pull-left">&nbsp;订单&nbsp;<span class="font-26 J-order-cnt-data"></span>&nbsp;个</span>
                            <span class="pull-right glyphicon glyphicon-menu-right pt-5"></span>
                        </div>
                    </li>
                    <li class="J-cus">
                        <div class="clearfix mt-5">
                            <span class="glyphicon glyphicon-user font-30 pull-left"></span>
                            <span class="pull-left">&nbsp;客户&nbsp;<span class="font-26 J-cus-cnt-data"></span>&nbsp;个</span>
                            <span class="pull-right glyphicon glyphicon-menu-right pt-5"></span>
                        </div>
                    </li>
                    <li class="J-cus-price">
                        <div class="clearfix mt-5">
                            <span class="glyphicon glyphicon-yen font-30 pull-left"></span>
                            <span class="pull-left">&nbsp;客单价&nbsp;<span class="font-26 J-cus-price-data"></span>&nbsp;元</span>
                            <span class="pull-right glyphicon glyphicon-menu-right pt-5"></span>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="<?php echo $base_url ?>/resource/js/dashboard.js"></script>
    </body>
</html>