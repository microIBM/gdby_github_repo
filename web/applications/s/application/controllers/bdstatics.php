<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * BD数据统计
 * 
 * @author wangyang@dachuwang.com
 *         @date 2015-03-24
 */
class Bdstatics extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
                'MCustomer',
                'MOrder',
                'MUser' 
        ));
    }
    
    /**
     * @描述：数据库中获取bd信息以及bd组长信息，原始数据（二维数组）
     * 
     * @author : wangyang@dachuwang.com
     */
    public function get_bd_info ($search = FALSE) {
        $page = $this->get_page();
        $fields = array (
                'id',
                'name',
                'mobile',
                'role_id',
                'dept_id' 
        );
        $where = [ 
                'in' => array (
                        'role_id' => array (
                                C('role.BD.code'),
                                C('role.BDM.code') 
                        ) 
                ) 
        ];
        if ($search == TRUE) {
            if (isset($_POST['search_key']) && isset($_POST['search_value']) && ! empty($_POST['search_key']) && ! empty($_POST['search_key'])) {
                $where['like'] = array (
                        $_POST['search_key'] => $_POST['search_value'] 
                );
            }
            $bd_info = $this->MUser->get_lists($fields, $where, array (), array (), $page['offset'], $page['page_size']);
        } else {
            $bd_info = $this->MUser->get_lists($fields, $where);
        }
        
        return empty($bd_info) ? array () : $bd_info;
    
    }
    
    /**
     * @描述：获取bd信息以及bd组长信息
     * 
     * @author : wangyang@dachuwang.com
     */
    public function get_bd_bdm_info () {
        $bd_info = $this->get_bd_info();
        $bdm_info = $this->_get_bdm_info($bd_info);
        
        $bd_info = $this->get_bd_info(TRUE);
        // 把bd与bdm信息拼装起来；
        $bd_info_with_bdm = array ();
        foreach ( $bd_info as $value ) {
            if (array_key_exists($value['dept_id'], $bdm_info)) {
                $data['bdm_nam'] = $bdm_info[$value['dept_id']]['name'];
                $data['bdm_mobile'] = $bdm_info[$value['dept_id']]['mobile'];
                $bd_info_with_bdm[] = array_merge($value, $data);
            } else {
                $bd_info_with_bdm[] = $value;
            }
        }
        return $this->_assemble_res(0, 'success', $bd_info_with_bdm);
    }
    
    /**
     * @描述: 通过customer_ids来进行 获取订单详情
     * 
     * @author :wangyang@dachuwang.com
     */
    public function order_info ($site_id = 1, $stime = 0, $etime = 0) {
        $where = [ 
                'status !=' => C('order.status.closed.code') 
        ];
        if (! empty($stime)) {
            $where['created_time >='] = $stime;
        }
        if (! empty($etime)) {
            $where['created_time <='] = $etime;
        }
        
        if (isset($_POST['bd_customer_ids'])) {
            $where['in'] = array (
                    'user_id' => $_POST['bd_customer_ids'] 
            );
        }
        $where['site_src'] = $site_id;
        $field = array (
                'id',
                'user_id',
                'status',
                'created_time',
                'total_price',
                'site_src',
                'deliver_time',
                'deliver_date' 
        );
        $order_lists = $this->MOrder->get_lists($field, $where);
        
        $res = ! empty($order_lists) ? $order_lists : array ();
        return $this->_assemble_res(0, 'success', $res);
    }
    
    /**
     * @描述 : 查找总的bd数
     * 
     * @author : wangyang@dachuwang.com
     */
    public function bd_count () {
        $where['in'] = array (
                'role_id' => array (
                        C('role.BD.code'),
                        C('role.BDM.code') 
                ) 
        );
        if (isset($_POST['search_key']) && isset($_POST['search_value']) && ! empty($_POST['search_key']) && ! empty($_POST['search_key'])) {
            $where['like'] = array (
                    $_POST['search_key'] => $_POST['search_value'] 
            );
        }
        $res = $this->MUser->count($where);
        return $this->_assemble_res(0, 'success', $res);
    }

    /**
     * @描述: 历史总计 中订单统计(包括 订单数、合并后订单数、成交订单数、订单金额、成交金额)
     * @author:wangyang@dachuwang.com
     */
    public function history_order_statics_by_customer_ids() {
        $order_status = isset($_POST['order_status']) ? $_POST['order_status'] : C('order.status.closed.code');
        $customer_ids = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : array();
        $bd_id        = isset($_POST['bd_id'])        ? $_POST['bd_id'] : 0;
        $site_id      = isset($_POST['site_id'])      ? $_POST['site_id'] : C('site.dachu');
        $customer_ids = explode('-',$customer_ids);
        if(empty($customer_ids)) {
            return $this->_assemble_res(0, 'success', $res);
        }

        $res = [];
        //区分order_status；
        if(isset($order_status)) {
            if($order_status == C('order.status.success.code')) {
                $where = [
                    'status =' => C('order.status.success.code')
                ];
            }elseif($order_status == C('order.status.closed.code')) {
                $where = [
                    'status !=' => C('order.status.closed.code')
                ];
            }
        }else {
            $where = [
                'status !=' => C('order.status.closed.code')
            ];
        }

        if(isset($customer_ids) && !empty($customer_ids)) {
            $where['in'] = array(
                'user_id' => $customer_ids
            );
        }
        $where['site_src'] = $site_id;

        //订单数，未合并
        $cnt = $this->MOrder->get_one(
            'count(*) cnt',
            $where
        );
        //订单数，合并后
        $cnt_distinct = $this->MOrder->get_one(
            'count(distinct(concat(`user_id`, "_", `deliver_date`, "_", `deliver_time`))) cnt',
            $where
        );
        //总的金额
        $amount = $this->MOrder->get_one(
            'sum(total_price) amount',
            $where
        );

        $res[$bd_id]['order_num'] = $cnt['cnt'];
        $res[$bd_id]['order_num_distinct'] = $cnt_distinct['cnt'];
        $res[$bd_id]['order_amout'] = $amount['amount'] ? $amount['amount'] : 0 ;


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
    
    /**
     * ription: 开始、结束时间
     * 
     * @author :wangyang@dachuwang.com
     */
    private function _get_start_and_end () {
        $request_time = $this->input->server('REQUEST_TIME');
        $stime = ! empty($_POST['stime']) ? $_POST['stime'] : strtotime(date('Y-m-d', $request_time));
        $etime = ! empty($_POST['etime']) ? $_POST['etime'] : ($stime + 86400);
        $res = [ ];
        
        $time_type = isset($_POST['time_type']) ? $_POST['time_type'] : '';
        switch ($time_type) {
            case 'period' :
                $res['start'] = $stime;
                $res['end'] = $etime;
                break;
            case 'all' :
                break;
            default :
                break;
        }
        return $res;
    }
    
    /**
     * @描述：通过给出bd的信息，找到bd对应的组长信息，组长对应自己；
     * 
     * @author : wangyang@dachuwang.com
     */
    private function _get_bdm_info ($bd_info = array()) {
        $bdm_info = array ();
        if (! empty($bd_info)) {
            foreach ( $bd_info as $value ) {
                if ($value['role_id'] == C('role.BDM.code')) {
                    $bdm_info[$value['dept_id']] = $value;
                }
            }
        }
        return $bdm_info;
    }

}
