 <?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 配置
$config = array(
    'options' => array(
        // 广告位
        'position' => array(
            'status' => array(
                array(
                    'name' => '启用',
                    'value' => 1
                ),
                array(
                    'name' => '禁用',
                    'value' => 0
                )
            )
        ),
        // 广告
        'advs' => array(
            'status' => array(
                array(
                    'name' => '上线',
                    'value'=> 1
                ),
                array(
                    'name' => '下线',
                    'value' => 0
                )
            )
        ),
    ),
    'adv_by_time' => array(
        'exceed_time' => 3,
        'verify' => 2,
        'online' => 1,
        'offline' => 0,
    )
);
