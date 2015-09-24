<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'app_type' => array(
        'dachu' => 1,
        'daguo' => 2,
        'crm'   => 3,
    ),
    'key' => array(
        'dachu' => array(
            'appkey' => '0c595e4e9046b663f6770a00',
            'secret' => '76282d77e650102c974720cb',
        ),
        'daguo' => array(
            'appkey' => '',
            'secret' => '',
        ),
        'crm'   => array(
            'appkey' => '',
            'secret' => '',
        )
    ),
    'platform'    => array(
        'all'     => 0,
        'android' => 1,
        'ios'     => 2
    ),
    'message_type' => array(
        'message'      => 1,
        'notification' => 2,
    ),
    'push_type'  => array(
        'direct' => 1, //指定用户
        'all'    => 2, //所有用户
    ),
    'message_status' => array(
        'init' => 0,
        'sent' => 1,
        'read' => 2,
    ),
    'url_type' => array(
        '1' => 'SEARCH_ACTIVITY',
        '2' => 'CLASSIFY_ACTIVITY',
        '3' => 'SUBJECT_ACTIVITY',
        '4' => 'WEBVIEW_ACTIVITY',
        '5' => 'MY_ORDER_ACTIVITY_UNRECEIVE',
        '6' => 'MAIN_ACTIVITY'
    ),
    'apns' => array(
        'password' => "phppush@dachuwang"
    ),
);
