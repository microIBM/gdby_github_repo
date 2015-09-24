<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 请求返回code
$config= array(
    'code' => array(
        'op_failed'     => -1,
        'op_success'    => 0
    ),
    'msg' => array(
        'login_success' => '登录成功',
        'login_fail'    => '登录失败',
        'add_success'   => '添加成功',
        'add_fail'      => '添加失败',
        'del_success'   => '删除成功',
        'del_fail'      => '删除失败',
        'reuse_success' => '启用成功',
        'reuse_fail'    => '启用失败',
        'op_fail'       => '操作失败',
        'op_success'    => '操作成功',
        'product'   => array(
            'suggest' => '该市场暂无批发商供货，为您找到周边供应',
        )
    )
);
