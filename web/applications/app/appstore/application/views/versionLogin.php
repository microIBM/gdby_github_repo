<?php include 'header.php'; ?>
<form class="form-horizontal" method="post" action="<?php echo $url . 'login'; ?>">
    <div class="form-group">
        <label for="mobile" class="col-sm-2 control-label text-info">用户名:</label>
        <div class="col-sm-3">
            <input type="text" class="form-control" id="mobile" name="mobile" placeholder="用户名">
        </div>
    </div>
    <div class="form-group">
        <label for="password" class="col-sm-2 control-label text-info">密码:</label>
        <div class="col-sm-3">
            <input type="password" class="form-control" id="password" name="password" placeholder="密码">
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-3">
            <button type="submit" class="btn btn-success">登录</button>
        </div>
    </div>
</form>
<?php include 'footer.php'; ?>
