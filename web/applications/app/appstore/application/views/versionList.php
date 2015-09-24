<?php include('header.php')?>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th class="text-info">版本名称</th>
            <th class="text-info">客户端类型</th>
            <th class="text-info">版本号</th>
            <th class="text-info">更新类型</th>
            <th class="text-info">更新内容</th>
            <th class="text-info">状态</th>
            <th class="text-success">操作</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach($data as $item) {
            $txt_class = $item['status'] == 0 ? 'text-success' : 'text-danger';
            $action_class = $item['status'] == 0 ? 'btn btn-danger' : 'btn btn-info';
            echo '<tr>';
            echo "<td> {$item['version_name']} </td>";
            echo '<td>'. ($item['client_type'] == 0 ? '安卓' : 'IOS') .'</td>';
            echo "<td class='text-success'> {$item['version_num']}</td>";
            //0:强制更新, 1:建议更新
            echo '<td class="text-success">' . C('apk.update_type')[$item['update_type']]. '</td>';
            echo "<td> {$item['update_txt']} </td>";
            //0:可用, 1:禁用
            echo "<td class='{$txt_class}'>" . C('apk.list_status')[$item['status']] . '</td>';
            echo '<td>';
            echo '<a href="'.($url.'version_edit/'. $item['id']) . '#list" class="btn btn-success">修改</a>&nbsp;';
            echo '<a href="'.($url.'del_version/'.$item['id'].'/'.$item['status'].'#list') . ' " class="' .$action_class.'">'.C('apk.action')[$item['status']].'</a>';
            echo '</td>';
            echo '</tr>';
        }
    ?>
    </tbody>
</table>
<?php include('footer.php')?>
