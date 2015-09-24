<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * bi订单统计 合并大厨大果版
 * @author zhangxiao@dachuwang.com
 * @version 2015-07-01
 */
class Statics_customer_day extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
            'MStatics_customer_day'
        ));
        $this->cities = C("open_cities");
    }

    /**
     *  统计页面中一段时间内统计数据
     *  @author zhangxiao@dachuwang.com
     */
    public function crm_cc_statics() {
        try {
            $yesterday          = date("Y-m-d", strtotime("-1 day"));
            $where              = array();
            $where['in']        = array('province_id' => array($this->cities['beijing']['id'], $this->cities['shanghai']['id'], $this->cities['tianjing']['id']));
            $where['data_date'] = $yesterday;
            
            $data = $this->MStatics_customer_day->get_lists(array(), $where);
            return $this->_return_json($data);
        }catch(Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }

    private function _assemble_res($msg, $data) {
        $arr = array (
            'status' => C('status.req.success'),
            'msg' => $msg,
            'data' => $data
        );
        $this->_return_json($arr);
    }

    private function _assemble_err($msg) {
        $arr = array (
            'status' => C('status.req.failed'),
            'msg' => $msg 
        );
        $this->_return_json($arr);
    }

}
