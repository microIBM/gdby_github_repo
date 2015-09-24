<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 订单分时统计 TD--time division
 * @author wangyang@dachuwang.com
 */
class Order_td extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
                'MOrder'
        ));
    }

    /**
     * 获取给定时间的订单状态信息 
     * @author:wangyang@dachuwang.com
     */
    public function get_order_status($stime = 0, $etime = 0, $city_id = 0) {
        $where = [];
        //按时间来筛选
        if (! empty($stime)) {
            $where['created_time >='] = $stime;
        }
        if (! empty($etime)) {
            $where['created_time <='] = $etime;
        }
        if (! empty($city_id)) {
            $where['city_id'] = $city_id;
        }

        $order_lists =  $this->MOrder->get_lists('id, status, created_time' , $where);

        $res = ! empty($order_lists) ? $order_lists : array ();
        return $this->_assemble_res(0, 'success', $res);
    }


    private function _assemble_res ($status, $msg, $res) {
        $arr = array (
                'status' => $status,
                'msg' => $msg,
                'res' => $res 
        );
        $this->_return_json($arr);
    }

    private function _assemble_err ($status, $msg) {
        $arr = array (
                'status' => $status,
                'msg' => $msg 
        );
        $this->_return_json($arr);
    }



}// class Order_td
