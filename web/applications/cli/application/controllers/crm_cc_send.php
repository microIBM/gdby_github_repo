<?php

/**
 * crm+cc 数据推送
 *
 * @author wangzejun@dachuwang.com
 */
class Crm_cc_send extends MY_Controller {
    const RESOURCE_IOS     = 1;  //订单来源ios
    const RESOURCE_ANDROID = 2;  //订单来源android
    const RESOURCE_CHU     = 3;  //订单来源chu
    const RESOURCE_MALL    = 4;  //订单来源mall

    private $cities = array();
    public function __construct() {
        parent::__construct();
        $this->cities = C("staticize_open_cities");
        $this->email_group = C('email_push_group');
    }
    
    /**
     * 
     * @author wangzejun@dachuwang.com
     */
    public function crm_cc() {
        $data = $this->cli_query("statics_customer_day/crm_cc_statics", array('timeout' => 1000));
        foreach($data as $key => &$val) {
            unset($val['id']);
            unset($val['inhive_date']);
            if($val['province_id'] == $this->cities['beijing']['code']) {
                $val['province_id'] = $this->cities['beijing']['name'];
            } else if($val['province_id'] == $this->cities['tianjin']['code']) {
                $val['province_id'] = $this->cities['tianjin']['name'];
            } else if($val['province_id'] == $this->cities['shanghai']['code']) {
                $val['province_id'] = $this->cities['shanghai']['name'];
            }
            
            if ($val['customer_type'] == 1) {
                $val['customer_type'] = '普通客户';
            } else if ($val['customer_type'] == 2) {
                $val['customer_type'] = 'KA客户';
            }
            $val['sale_amount'] /= 100;
            $val['customer_average_price'] /= 100;
        }
        $this->_send($data);
    }
    
    private function _send($content) {
        $header = array(
            '数据日期',
            '城市',
            '客户类型',
            '有效客户数',
            '总流水',
            '订单总数',
            '客单价',
            '复购率',
            '复购客户数',
            '客户流失率',
            '客户流失数',
            '客户下单频率',
            '投诉单数',
            
        );

        $result = $this->cli_query('email_report/send', array(
                'to'      => $this->email_group['crm_cc_email_group']['to'],
                'cc'      => $this->email_group['crm_cc_email_group']['cc'],
                'name'    => $this->email_group['crm_cc_email_group']['name'],
                'subject' => $this->email_group['crm_cc_email_group']['subject'],
        	'title'   => $this->email_group['crm_cc_email_group']['title'],
                'desc'    => $this->email_group['crm_cc_email_group']['desc'],
        	'header'  => $header,
        	'content' => $content,
        ));
    }
}

