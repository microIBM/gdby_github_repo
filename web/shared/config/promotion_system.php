<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 新版促销系统活动类型配置
 * 活动类型：满减，满赠，立减,打折
 * @version: 1.0.0
 * @since: datetime
 */
$config = array(
    'full_minus' => array(
        'id' => 1,
        'name' => '满减'
    ),
    'full_gift'  => array(
        'id' => 2,
        'name' => '满赠'
    ),
    'immediate_minus' => array(
        'id' => 3,
        'name' => '立减'
    ),
    'discount'   => array(
        'id' => 4,
        'name' => '打折'
    ),
    //为了方便生存下拉列表，所有新添加的都要添加到这里
    'all_types' => array(
        array(
            'id' => 1,
            'name' => '满减'
        ),
        array(
            'id' => 2,
            'name' => '满赠'
        ),
        array(
            'id' => 3,
            'name' => '立减'
        ),
        array(
            'id' => 4,
            'name' => '打折'
        ),
    )
);
