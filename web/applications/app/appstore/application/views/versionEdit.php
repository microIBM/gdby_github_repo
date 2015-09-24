<?php include('header.php'); ?>
<form class="form-horizontal" action="<?php echo $url . 'update_version#list'; ?>" method="post">
    <input type="hidden" name="id" value="<?php echo $data['id']; ?>"></input>
    <div class="form-group">
        <label for="" class ="col-sm-2 control-label">版本名称:</label>
        <div class="col-sm-3">
            <input type="text" class="form-control" name="ver_name" value="<?php echo $data['version_name']; ?>"/>
        </div>
    </div>

    <div class="form-group">
        <label for="" class ="col-sm-2 control-label">客户端类型:</label>
        <div class="col-sm-3">
            <select class="form-control col-sm-5" name="client_type">
                <option value="0" <?php echo $data['client_type'] == 0 ? 'selected' : ''; ?> >安卓</option>;
                <option value="1" <?php echo $data['client_type'] == 1 ? 'selected' : ''; ?> >IOS</option>;
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">版本号：</label>
        <div class="col-sm-3">
            <input type="text" class="form-control" name="ver_num" value="<?php echo $data['version_num']; ?>"/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">更新类型</label>
        <div class="col-sm-3">
            <select class="form-control col-sm-5" name="update_type">
                <option value="0" <?php echo $data['update_type'] == 0 ? 'selected' : ''; ?> >强制更新</option>;
                <option value="1" <?php echo $data['update_type'] == 1 ? 'selected' : ''; ?> >建议更新</option>;
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">更新内容：</label>
        <div class="col-sm-3">
            <textarea class="form-control" name="update_content"><?php echo $data['update_txt']; ?></textarea> 
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">下载地址:</label>
        <div class="col-sm-3">
            <input type="text" class="form-control" name="down_url" value="<?php echo $data['down_url']; ?>"/>
        </div>
    </div>

    <!-- 
    <div class="form-group">
        <label class="col-sm-2 control-label">上传文件：</label>
        <div class="col-sm-3">
            <input class="form-control" type="file" name="userfile" />
        </div>
    </div>
    -->
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-3">
            <button type="submit" class="btn btn-success">确认修改</button>
        </div>
    </div>
</form>
<?php include('footer.php'); ?>
