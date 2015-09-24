<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'order_end_time' => array(
        'beijing' => array(
            'city_id' => 1,
            'order_end_time' => '23:00:00', //截单时间
            'diff_timestamp' => 3600       //截单时间和24:00:00的差值
        )
    )
);