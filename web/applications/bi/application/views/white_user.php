<!-- 大厨、大果统计模板-->
<?php include APPPATH . "views/shared/header.php" ?>
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
    <h1 class="page-header">大厨网 · 白名单管理</h1>
    <!-- 大厨大果切换按钮 -->
    <div>
        <ul class="list-unstyled list-inline clearfix">
            <li>
                <button type="button" class="btn btn-primary <?= $btn_class ?>" data-toggle="modal" data-target="#addWhiteUser" data-whatever="添加白名单">添加白名单</button>
            </li>
            <li class="desc pull-right">
                <button type="button" class="btn btn-primary active" data-toggle="modal" data-target="#addModule" data-whatever="添加白名单管理模块">添加白名单管理模块</button>
            </li>
            <?php if(isset($white_info['status']) && $white_info['status'] == 0) :?>
            <li class="desc pull-right">
                <button type="button" class="btn btn-primary active" data-toggle="modal" data-target="#manageModule" data-whatever="查看白名单信息">查看白名单信息</button>
            </li>
            <?php endif;?>
        </ul>
        
        <?php if(isset($white_info['status']) && $white_info['status'] == 0) :?>
        <!--查看白名单管理信息-->
        <div class="modal fade" id="manageModule" tabindex="-1" role="dialog" aria-labelledby="manageModuleLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="manageModuleLabel">查看白名单信息</h4>
                    </div>
                    <table class="table table-condensed table-bordered table-striped table-hover">
                        <!-- 统计表格表头 -->
                        <thead class="content-indicator nav-table">
                            <tr>
                                <th>模块ID</th>
                                <th>模块名称</th>
                                <th>创建人</th>
                                <th>用户人数</th>
                            </tr>
                        </thead>
                        <!-- 统计表格表内容 -->
                        <tbody>
<?php if (!empty($white_info['data'])) :
    foreach (($white_info['data']) as $key => $value):
        ?>
                            <tr>
                                <td><?= $value['module_id']; ?></td>
                                <td><?= $value['module_name']; ?></td>
                                <td><?= $value['name']; ?></td>
                                <td><?= $value['user_num']; ?></td>
                            </tr>
    <?php endforeach;
endif; ?>
                        </tbody>
                    </table>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                    </div>		      
                </div>
            </div>
        </div>
        <?php endif;?>

        <!--添加白名单用户-->
        <div class="modal fade" id="addWhiteUser" tabindex="-1" role="dialog" aria-labelledby="addWhiteUserLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="addWhiteUserLabel">添加白名单</h4>
                    </div>
                    <form>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">用户姓名:<p style="color:red" class="J-user-tip"></p></label>
                                <input type="text" name="user_name" class="form-control" id="recipient-name" placeholder="必填">
                            </div>
                            <div class="form-group">
                                <label for="message-text" class="control-label">用户手机号:</label>
                                <input type="text" name="user_mobile" class="form-control" id="message-text" placeholder="必填，请填写正确的手机号">
                            </div>
                            <div class="form-group"> 
                                <!-- <div class="input-group-addon">白名单模块</div> -->
                                <label for="message-module" class="control-label">白名单模块:</label>
                                <div id="message-module">
                                    <?php foreach ($manage_module as $key => $value) :
                                        if (isset($value['module_name'])) :
                                            ?>
                                            <input type="checkbox" name="select_module[]" value="<?= $value['id'] ?>"><?= $value['module_name'] ?>
    <?php endif;
endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                            <button type="button" class="btn btn-primary J-user-submit">发送</button>
                        </div>		      
                    </form>
                </div>
            </div>
        </div>

        <!--添加白名单模块-->
        <div class="modal fade" id="addModule" tabindex="-1" role="dialog" aria-labelledby="addModuleLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="addModuleLabel">添加白名单管理模块</h4>
                    </div>

                    <form>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">模块名称:<p style="color:red" class="J-module-tip"></p></label>
                                <input type="text" name="module" class="form-control" id="recipient-name" placeholder="必填，如BI决策系统，微信支付接口">
                            </div>
                            <div class="form-group">
                                <label for="recipient-controller" class="control-label">模块对应的控制器:</label>
                                <input name="controller" class="form-control" id="recipient-controller" placeholder="选填"></input>
                            </div>
                            <div class="form-group">
                                <label for="recipient-action" class="control-label">模块对应方法:</label>
                                <input name="action" class="form-control" id="recipient-action" placeholder="选填"></input>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                            <button type="button" class="btn btn-primary J-module-submit">发送</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ul class="list-unstyled list-inline clearfix">
            <li>
                <form class="form-inline" action="<?= $base_url ?>/white_user/index?city_id=<?= $city_id ?>&menue_id=<?= $menue_id ?>&tab_id=<?= $tab_id ?>"  method="get">
                    <div class="form-group">
                        <div class="input-group key-words myselect">
                            <div class="input-group-addon">白名单模块</div>
                            <select name="searchModule" class="form-control">
                                <option value="0">请选择</option>
                                <?php foreach ($manage_module as $key => $value) :
                                    if (isset($value['module_name'])) :
                                        ?>
                                        <option  value="<?= $value['id'] ?>" <?= $value['id'] == $searchModule ? 'selected' : ''; ?>><?= $value['module_name'] ?></option>
    <?php endif;
endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <select class="form-control width-5p" name="searchKey">
                                <option value="1" <?= 1 == $searchKey ? 'selected' : ''; ?>>姓名</option>
                                <option value="2" <?= 2 == $searchKey ? 'selected' : ''; ?>>手机号</option>
                            </select>
                            <input type="text" class="form-control width-5p" name="searchValue" value="<?= $searchValue ?>" placeholder="请输入">
                        </div><!-- search-key search-value  id="search-Value"-->
                    </div>
                    <input name="city_id" type="hidden" value="<?= $city_id ?>">
                    <input name="menue_id" type="hidden" value="<?= $menue_id ?>">
                    <input name="tab_id" type="hidden" value="<?= $tab_id ?>">
                    <button type="submit" class="btn btn-primary">筛选</button>
                    <a class="btn btn-warning reset" href="<?= $base_url ?>/white_user/index?city_id=<?= $city_id ?>&menue_id=<?= $menue_id ?>&tab_id=<?= $tab_id ?>">重置</a>
                </form>
            </li>
        </ul>
    </div>

    <!-- 统计表格 -->
    <div class="table-responsive table-show">
        <table class="table table-condensed table-bordered table-striped table-hover">
            <!-- 统计表格表头 -->
            <thead class="content-indicator nav-table">
                <tr>
                    <th>编号</th>
                    <th>模块ID</th>
                    <th>模块名称</th>
                    <th>姓名</th>
                    <th>手机号</th>
                    <th>操作</th>
                </tr>
            </thead>
            <!-- 统计表格表内容 -->
            <tbody>
<?php if (!empty($white_users)) :
    foreach (($white_users) as $key => $value):
        ?>
                        <tr>
                            <td><?= ($page - 1) * $offset + $key + 1; ?></td>
                            <td><?= $value['module_id']; ?></td>
                            <td><?= $value['module_name']; ?></td>
                            <td><?= $value['name']; ?></td>
                            <td><?= $value['mobile']; ?></td>
                            <td>
                                <a content="<?= $value['user_id'] ?>" class="btn btn-default J-edit-white-user" href="#" role="button" data-toggle="modal" data-target="#editWhiteUser" >编辑</a>
                                <a href="<?= $base_url; ?>/white_module/set_status?module_id=<?= $value['id'] ?>&city_id=<?= $city_id ?>&menue_id=<?= $menue_id ?>&tab_id=<?= $tab_id ?>" onclick="if(confirm('确定要删除,如果该用户为这个模块下的最后一个用户，模块也将被删除；如果只是取消用户在这个模块的白名单权限，推荐使用编辑功能') == false) return false;">删除</a>
                            </td>
                        </tr>
    <?php endforeach;
endif; ?>
            </tbody>
        </table>

        <!--编辑白名单用户-->
        <div class="modal fade" id="editWhiteUser" tabindex="-1" role="dialog" aria-labelledby="editWhiteUserLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="editWhiteUserLabel">编辑白名单</h4>
                    </div>
                    <form action="<?= $base_url ?>/white_user/edit?city_id=<?= $city_id ?>&menue_id=<?= $menue_id ?>&tab_id=<?= $tab_id ?>" method="post">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-edit-name" class="control-label">用户姓名:</label>
                                <input type="text" name="edit_user_name" value="" class="form-control" id="recipient-edit-name" readonly />
                            </div>
                            <div class="form-group">
                                <label for="message-edit-text" class="control-label">用户手机号:</label>
                                <input type="text" name="edit_user_mobile" value="" class="form-control" id="message-edit-text" readonly />
                            </div>
                            <div class="form-group"> 
                                <label for="edit-module" class="control-label">白名单模块:</label>
                                <div id="edit-module">
<?php foreach ($manage_module as $key => $value) :
    if (isset($value['module_name'])) :
        ?>
                                            <input type="checkbox" name="edit_select_module[]" value="<?= $value['id'] ?>"><?= $value['module_name'] ?>
    <?php endif;
endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group J-admin-radio">
                                <label for="message-edit-user-role" class="control-label">用户所属角色: </label>
                                <input type="radio" name="edit_user_role" value="1" class="J－edit-user-role" />超级管理员
                                <input type="radio" name="edit_user_role" value="2" class="J－edit-user-role" />管理员
                                <input type="radio" name="edit_user_role" value="3" class="J－edit-user-role" />普通用户
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" name="edit_user_id" value=""/>
                            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                            <button type="submit" class="btn btn-primary">发送</button>
                        </div>          
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- 分页 -->
    <div class="control-pages clearfix">
        <nav class="pagination" ><?= $pagination ?></nav>
                <?php if ($total_records >= 10): ?>
            <div class="btn-group dropup page-size">
                <button type="button" class="btn btn-default">
    <?php if ($offset == 10): ?>每页10条
    <?php elseif ($offset == 15 && $total_records >= 15): ?>每页15条
    <?php elseif ($offset == 20 && $total_records >= 20): ?>每页20条
    <?php elseif ($offset == 30 && $total_records >= 30): ?>每页30条
    <?php elseif ($offset == 50 && $total_records >= 50): ?>每页50条
                    <?php else: ?>选择每页条目数
                    <?php endif; ?>
                </button>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="<?= $base_url ?>/white_user/index?tab_id=<?= $tab_id ?>&menue_id=<?= $menue_id ?>&offset=10&searchKey=<?= $searchKey ?>&searchValue=<?= $searchValue ?>&city_id=<?= $city_id ?>">每页10条</a></li>
                    <?php if ($total_records > 10): ?>
                        <li><a href="<?= $base_url ?>/white_user/index?tab_id=<?= $tab_id ?>&menue_id=<?= $menue_id ?>&offset=15&searchKey=<?= $searchKey ?>&searchValue=<?= $searchValue ?>&city_id=<?= $city_id ?>">每页15条</a></li>
                    <?php endif; ?>
                    <?php if ($total_records > 15): ?>
                        <li><a href="<?= $base_url ?>/white_user/index?tab_id=<?= $tab_id ?>&menue_id=<?= $menue_id ?>&offset=20&searchKey=<?= $searchKey ?>&searchValue=<?= $searchValue ?>&city_id=<?= $city_id ?>">每页20条</a></li>
            <?php endif; ?>
            <?php if ($total_records > 20): ?>
                        <li><a href="<?= $base_url ?>/white_user/index?tab_id=<?= $tab_id ?>&menue_id=<?= $menue_id ?>&offset=30&searchKey=<?= $searchKey ?>&searchValue=<?= $searchValue ?>&city_id=<?= $city_id ?>">每页30条</a></li>
    <?php endif; ?>
    <?php if ($total_records > 30): ?>
                        <li><a href="<?= $base_url ?>/white_user/index?tab_id=<?= $tab_id ?>&menue_id=<?= $menue_id ?>&offset=50&searchKey=<?= $searchKey ?>&searchValue=<?= $searchValue ?>&city_id=<?= $city_id ?>">每页50条</a></li>
    <?php endif; ?>
                </ul>
            </div>
<?php endif; ?>
        <div class="label label-info total-records">共 <?= $total_records ?> 条记录</div>
    </div>
</div><!-- div main -->

<?php include APPPATH . "views/shared/footer.php" ?>
