<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户授权相关的错误信息配置
 * @author: caiyilong@ymt360.com
 * @version: 1.0.0
 * @since: 2015-01-19
 */
$config = array(
    'success' => array(
        'id' => 0,
        'msg' => '登录成功'
    ),
    'default' => array(
        'id' => -1,
        'msg' => '未知错误'
    ),
    'not_found' => array(
        'id' => -2,
        'msg' => '账号不存在'
    ),
    'not_actived' => array(
        'id' => -3,
        'msg' => '此账号正在等待审核'
    ),
    'invalid_password' => array(
        'id' => -4,
        'msg' => '密码错误'
    ),
    'disabled' => array(
        'id' => -5,
        //'msg' => '此帐号已经被禁用'
        'msg' => '很抱歉，系统升级中，暂无法下单'
    ),
    'invalid_info' => array(
        'id' => -6,
        'msg' => '登录信息格式有误'
    ),
    'login_limit' => array(
        'id'  => -7,
        'msg' => '您没有登录该系统的权限，如有疑问，请与客服联系'
    ),
);
