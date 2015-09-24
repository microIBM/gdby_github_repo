<?php
    $url = 'http://'.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'apk'.DIRECTORY_SEPARATOR;
    $app_url = 'http://'.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'resource'.DIRECTORY_SEPARATOR;
?>
<html>
  <head>
    <meta charset="utf-8"/>
    <meta name="description" content="apk版本更新" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <title>app版本库</title>
    <link rel="stylesheet" href="<?php echo ($app_url.'css/bootstrap.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo ($app_url.'css/dashboard.css'); ?>" />
    <script type="text/javascript" src="<?php echo $app_url.'js/jquery-1.11.3.min.js'; ?>"></script>
  </head>
  <body>
    <nav class="navbar navbar-inverse  navbar-fixed-top">
        <div class="container-fluid">
            <label class="navbar-brand">apk版本库管理</label> 
            <ul class="nav navbar-nav navbar-right">
                <li><a href="<?php echo $url . 'version_login#list'; ?>">登录</a></li>
            </ul>
        </div>
    </nav>
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
            <ul class="nav nav-sidebar">
                <li class="app-sub">
                    <a href="<?php echo $url.'version_sub#sub';?>">上传版本</a>
                </li>
                <li class="app-list">
                    <a href="<?php echo $url . 'version_list#list'; ?>">版本列表</a>
                </li>

            </ul>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            var activeClass = 'app' + location.hash.replace('#', '-');
            $('.'+activeClass).addClass('active');
        });
    </script>

    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
