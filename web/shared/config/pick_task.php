<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//分拣任务的配置项
$config = array(
    'status' => array(
        'not_created' => array(
            'code' => 0,
            'msg'  => '未创建'
        ),
        'started' => array(
            'code' => 1,
            'msg' => '已开始'
        ),
        'finished' => array(
            'code' => 2,
            'msg'  => '已完成'
        )
    ),
);
