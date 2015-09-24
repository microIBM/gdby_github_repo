<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//波次相关的配置内容
$config = array(
    'wave_type' => array(
        'auto'   => array(
            'code' => 1,
        ),
        'manual' => array(
            'code' => 2,
        )
    ),
    'task_created' => array(
        'created' => array(
            'code' => 1,
            'msg' => '已释放'
        ),
        'pending' => array(
            'code' => 0,
            'msg' => '未释放'
        )
    ),
    'status' => array(
        'valid'   => 1,
        'deleted' => 0
    )
);
