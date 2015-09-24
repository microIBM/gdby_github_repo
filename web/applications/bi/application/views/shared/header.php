<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
<link rel="icon" href="../../favicon.ico">

<title>BI决策支持系统</title>

<!-- Bootstrap core CSS -->
<link href="http://cdn.bootcss.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo $base_url ?>/resource/css/bi.css?v=<?php echo $css_version;?>" rel="stylesheet">
<link href="http://cdn.bootcss.com/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker3.min.css" rel="stylesheet">
<script type="text/javascript">var base_url ="<?php echo $base_url;?>"</script>
<script type="text/javascript">var php_city_id ="<?= isset($city_id) ? $city_id : 804;?>"</script>
<script type="text/javascript">var php_menue_id ="<?= isset($menue_id) ? $menue_id : 1; ?>"</script>
</head>
<body>
    <div id="J-loading" class="hidden">
        <div class="progress">
          <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar"  style="width: 100%">
          数据加载中，请稍等亲~~
          </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="J-bi-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="myModalLabel">Modal title</h4>
        </div>
        <div class="modal-body">

        </div>
        <div class="modal-footer hidden">
            <button type="button" class="btn btn-default hidden" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary hidden">Save changes</button>
        </div>
        </div>
    </div>
    </div>
    <nav class="navbar navbar-inverse navbar-fixed-top hidden-print">
        <div class="container-fluid">
            <div class="navbar-header">
                <a style="padding-right: 0;" href="javascript:history.back()" class="glyphicon navbar-brand glyphicon-chevron-left"></a>
                <a class="navbar-brand" href="<?= $base_url?>/statics">BI决策支持系统</a>
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <!-- 城市切换按钮 -->
            <div class="city-btn">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class='glyphicon glyphicon-map-marker'></span><?php echo $current_city['name'];?><span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <?php foreach ($city_map as $key => $value):?>
                        <li><a href="<?php echo $value['url'];?>"><?php echo $value['name'];?></a></li>
                    <?php endforeach;?>
                </ul>
            </div>
            <!-- 导航栏右侧显示：用户姓名+登陆登出+小屏幕下拉菜单 -->
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <?php if(isset($user_info['name'])) :?>
                    <li><a href="#"><?php echo $user_info['name']?></a></li>
                    <?php else :?>
                    <li><a href="<?php echo $base_url.'/user/login'?>">登陆</a></li>
                    <?php endif;?>
                    <li><a href="<?php echo $base_url.'/user/logout'?>">登出</a></li>
                </ul>
                <nav class="mobile-nav clearfix">
                        <hr class="col-xs-12">
                        <ul class="list-unstyled">
                            <li class="col-xs-12"><h4 class="nav-li">大厨网</h4></li>
                            <?php foreach ($left_nav as $key => $value) :?>
                                <li class="col-xs-2"><a class="nav-li-child" href="<?php echo $value['base_url']?>?city_id=<?php echo $city_id?>"><?php echo $value['title'];?></a></li>
                            <?php endforeach;?>
                        </ul>
                        <hr class="col-xs-12">
                </nav>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-3 col-md-2 sidebar hidden-print">
                <ul class="nav system-sidebar">
                    <li><h4 class="nav-li">大厨网</h4></li>
                    <?php foreach ($left_nav as $key => $value) :?>
                        <li name="dachu-statics" class="<?php echo $value['class'];?>"><a class="nav-li-child <?php echo $value['class'];?>-color'" href="<?php echo $value['url']; ?>"><?php echo $value['title'];?></a></li>
                    <?php endforeach;?>
                </ul>
            </div>
            <!-- div sidebar -->
