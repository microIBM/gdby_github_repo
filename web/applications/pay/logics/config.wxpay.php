<?php

class Config {
    
    const API_SUCCESS_CODE = 0; //内部接口成功状态码
    const API_FAILED_CODE = -1; //内部接口失败状态码
    
    const ORDER_PAY_SUCCESS_CODE = 1; //订单支付成功状态
    const ORDER_PAY_WAITTINT_CODE = 0; //订单未支付状态
    const ORDER_PAY_FAILED_CODE = -1;  //订单支付失败状态
    const ORDER_PAY_PROCESS_STATUS = 3;//订单处理状态－待生产
    const ORDER_PAY_DISCOUNT = 100;     //订单支付折扣，整数存储，100无折扣，98九八折
    const ORDER_PAY_TYPE = 1;             //订单支付类型，1为微信支付
    
    const PAY_LOG_PATH = '/data/paylogs/'; //支付流程日志记录
    const PAY_LOG_LEVER = 8;                    //支付流程日志级别
    //========［接口超时设置］=======================//
    const API_TIMEOUT = 60;
    //=======[大厨网首页地址]========================//
    const BASE_URL ='http://chu.dachuwang.com';
    //=======[微信支付base目录]========================//
    const BASE_PAY_URL ='http://pay.dachuwang.com/weixin/wxpay';
    //=======[获取支付订单详情接口]===================================//
    const API_ORDER_INFO = 'http://s.dachuwang.com/order/get_order';
    //=======[更新订单状态接口]===================================//
    const API_ORDER_UPDATE = 'http://s.dachuwang.com/order/update';
    //=======[更新子订单状态接口]===================================//
    const API_SUBORDER_UPDATE = 'http://s.dachuwang.com/suborder/update_by_orderid';
    //=======[添加支付流水接口]===================================//
    const API_ADD_PAY_BILLS = 'http://s.dachuwang.com/pay_bills/add_bill';
    
    //=======[支付成功通知回调接口(支付成功后微信调用)]==================//
    const API_NOTIFY_URL = 'http://pay.dachuwang.com/weixin/wxpay/notify.php';
    //=======[扫码支付回调接口(用户扫码后微信调用)]==================//
    const API_NATIVE_NOTIFY_URL = 'http://pay.dachuwang.com/weixin/wxpay/native_notify.php';
    
    //=======[支付成功成功后跳转地址]==================//
    const PAY_SUCCESS_URL = 'http://chu.dachuwang.com/order/list/2';
    
    
    const WX_ORDER_TITLE = "大厨网订单";  //订单支付标题
    const WX_ORDER_TIMEOUT = 600;       //订单实效时间，默认10分钟
    const WX_ORDER_TAG ='dachuwang';    //订单标记
    const WX_VERSION = '20150619v1.0';
}