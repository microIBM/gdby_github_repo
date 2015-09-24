<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 规格属性type
$config= array(
    'type'  => array(
        'select'    => array(
            'name'  => '下拉列表',
            'val'   => 3
        ),
        'radio'     => array(
            'name'  => '单选',
            'val'   => 2
        ),
        'checkbox'  => array(
            'name'  => '多选',
            'val'   => 1
        ),
        'input'     => array(
            'name'  => '自定义输入框',
            'val'   => 0
        ),
    ),
    'status'    => array(
        'new'       => 2,
        'success'   => 1,
        'del'       => 0
    )
);
