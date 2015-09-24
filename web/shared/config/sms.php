<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 短信发送服务接口
 * @author caiyilong@ymt360.com
 * @since 2014-10-21
 * @version 1.0.0
 */
$config = array(
    'dachu' => array(
        'normal' => array(
            'NOTICE'             => 333334,
            'CAPTCHA'            => 676767,
            'USERNAME'           => 'dachuwang',
            'PASSWORD'           => 'dachuwang123',
            'SMS_MULTI_SERVICE'  => "http://www.ztsms.cn:8800/sendManySms.do", // 单条短信
            'SMS_SINGLE_SERVICE' => "http://www.ztsms.cn:8800/sendXSms.do", // 多条短信
        ),
        'marketing' => array(
            //'NOTICE'             => 333334,
            'NOTICE'             => 435227,
            'CAPTCHA'            => 95533,
            'USERNAME'           => 'dachuedm',
            'PASSWORD'           => 'dachu!@#',
            'SMS_MULTI_SERVICE'  => "http://www.ztsms.cn:8800/sendManySms.do", // 单条短信
            'SMS_SINGLE_SERVICE' => "http://www.ztsms.cn:8800/sendXSms.do", // 多条短信
        )
    ),
    'daguo' => array(
        'normal' => array(
            'NOTICE'             => 333334,
            'CAPTCHA'            => 676767,
            'USERNAME'           => 'daguowang',
            'PASSWORD'           => 'daguowang123',
            'SMS_MULTI_SERVICE'  => "http://www.ztsms.cn:8800/sendManySms.do", // 单条短信
            'SMS_SINGLE_SERVICE' => "http://www.ztsms.cn:8800/sendXSms.do", // 多条短信
        ),
        'marketing' => array(
            //'NOTICE'             => 333334,
            'NOTICE'             => 435227,
            'CAPTCHA'            => 95533,
            'USERNAME'           => 'daguoedm',
            'PASSWORD'           => 'daguo!@#',
            'SMS_MULTI_SERVICE'  => "http://www.ztsms.cn:8800/sendManySms.do", // 单条短信
            'SMS_SINGLE_SERVICE' => "http://www.ztsms.cn:8800/sendXSms.do", // 多条短信
        )
    )
);

