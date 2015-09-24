<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'prefix' => array(
        'picking' => 'F',//分拣单条码前缀
    	'dispatch' => 'DR',//配送单条码前缀
    ),
    'url'             => 'http://api.pda.dachuwang.com/barcode/get',
);
