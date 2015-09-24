<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//微信相关的配置内容
$config = array(
    'appid'      => 'wxf4ef1de9b0e82da7',
    'secret'     => 'a5921def4dbaf476697031f138706aa0',

    'grant_type' => 'client_credential',
    'token_url'  => 'https://api.weixin.qq.com/cgi-bin/token?',

    'ticket_url' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?',
    'ticket_type' => 'jsapi',
    'file_token' => 'access_token.json',
    'file_ticket' => 'jsapi_ticket.json',

    'chu' => array(
        'appid' => 'wxae05f7ff06e51301',
        'secret' => '8c39c5a1d007d461760f7922ba3c7750'
    )
);
