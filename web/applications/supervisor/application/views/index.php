<html>
<head>
    <title>大厨网监控平台</title>
    <link href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">
</link>
</head>
<body style="width: 95%;margin: 0 auto;">
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="#">大厨网supervisor server监控</a>
    </div>

</div>
</nav>

<?foreach($data as $serverno => $server):?>
<h3><?=$serverno?></h3>
<div style="margin: -35px 0 20px 200px">
<button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/startall')?>/<?=$serverno?>">Start All</a></button>
<button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/stopall')?>/<?=$serverno?>">Stop All </a></button>
<button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/logclearall')?>/<?=$serverno?>">Clear All Log </a></button>
</div>
<table class="table table-hover">
<tr>
    <th>进程名称</th>
    <th>status</th>
    <th>description</th>
    <th>log</th>
    <th>Process Logging</th>
    <th>Process Control</th>
</tr>
<tbody>
    <?foreach($server as $process):?>
<tr>
    <td><?=$process['name']?></td>
    <td>
        <?if($process['statename'] == 'RUNNING'):?>
            <span class="label label-success"><?=$process['statename']?></span>
        <?elseif($process['statename'] == 'FATAL'):?>
            <span class="label label-danger"><?=$process['statename']?></span>
        <?else:?>
            <span class="label label-warning"><?=$process['statename']?></span>
        <?endif;?>
    </td>
    <td><?=$process['description']?></td>
    <td><?=$process['stdout_logfile']?></td>
    <td>
    <button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/logtail')?>/<?=$serverno?>/<?=$process['name']?>" target="_blank">Tail -f </a></button>
    <button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/logclear')?>/<?=$serverno?>/<?=$process['name']?>">清空日志 </a></button>
    </td>
    <td>
    <button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/start')?>/<?=$serverno?>/<?=$process['name']?>">Start</a></button>
        <button type="button" class="btn btn-default"><a href="<?=base_url('/monitor/stop/')?>/<?=$serverno?>/<?=$process['name']?>">Stop</a></button>

    </td>
    </tr>
        <?endforeach;?>
    </tbody>
</table>
<?endforeach;?>

<script src="http://libs.baidu.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>

</body>
</head>

