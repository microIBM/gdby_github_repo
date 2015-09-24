<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CRM数据统计
 * @author liudeen@dachuwang.com
 * @date 2015-03-24
 */
class Performance extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MOrder',
                'MCustomer_potential',
                'MUser'
            )
        );
    }
    private $_bd_list = [12,13];
    private $_am_list = [14,15];

    private function _bd_or_am() {
        $role_id = $_POST['role_id'];
        if(!$role_id) {
            return FALSE;
        }
        if(in_array($role_id, $this->_bd_list)) {
            return 'bd';
        } else if(in_array($role_id, $this->_am_list)) {
            return 'am';
        } else {
            return 'none';
        }
    }

    private function _assemble_res($status, $msg, $res) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
            'res'    => $res
        );
        $this->_return_json($arr);
    }

    private function _assemble_err($status, $msg) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
        );
        $this->_return_json($arr);
    }

    //传入当前日期,格式为date('Y-m-d',xxx)，返回本月第一天的时间戳
    private function _get_month_firstday($date) {
        return strtotime(date('Y-m-01',strtotime($date)));
    }

    private function _get_start_and_end() {
        $res = [];
        $time_type = isset($_POST['time_type']) ? $_POST['time_type'] : '';
        switch($time_type) {
        case 'by_day':
            $res['start'] = strtotime('today');
            $res['end']   = strtotime('now');
            break;
        case 'by_week':
            $res['start'] = strtotime('this Monday')>strtotime('now') ? strtotime('last Monday'):strtotime('this Monday');
            $res['end'] = strtotime('now');
            break;
        case 'by_month':
            $res['start'] = $this->_get_month_firstday(date('Y-m-d',time()));
            $res['end'] = strtotime('now');
            break;
        case 'all':
            $res['start'] = strtotime('2015-05-06');
            $res['end'] = strtotime('now');
            break;
        case 'optional':
            $res['start'] = isset($_POST['begin_time']) ? $_POST['begin_time'] : NULL;
            $res['end'] = isset($_POST['end_time']) ? $_POST['end_time'] + 86400 : NULL;
            if($res['start'] === NULL || $res['end'] === NULL) {
                return FALSE;
            }
            break;
        default:
            return FALSE;
            break;
        }
        //统计的最早时间从5月6号开始
        $latest = strtotime('2015-05-06');
        $res['start'] = $res['start']<$latest ? $latest:$res['start'];
        return $res;
    }

    private function _parse_bd_id() {
        $res = [];
        if(!empty($_POST['bd_ids'])) {
            if(is_array($_POST['bd_ids'])) {
                foreach($_POST['bd_ids'] as $item) {
                    $res[] = intval($item);
                }
            } else {
                $res = array(
                    intval($_POST['bd_ids'])
                );
            }
        }
        return $res;
    }

    private function _get_customer_ids_by_bd($bd_ids = array()) {
        $res = [];
        if(!empty($bd_ids)) {
            $where = [
                'status !=' => C('customer.status.invalid.code'),
                'in' => array(
                    'invite_id' => $bd_ids
                )
            ];
            $invited_customers = $this->MCustomer->get_lists(
                'id',
                $where
            );
            if(!empty($invited_customers)) {
                $res = array_column($invited_customers, 'id');
            }
        }
        return $res;
    }

    //用户数统计
    public function customer_num() {
        $answer = $this->_private_customer_num();
        if(is_string($answer)) {
            $this->_assemble_err(-1, $answer);
        } else {
            $this->_assemble_res(0, 'success', $answer);
        }
    }

    private function _private_customer_num() {
        $where = [
            'status !=' => C('customer.status.invalid.code')
        ];
        $time_arr = $this->_get_start_and_end();
        if($time_arr!== FALSE) {
            $where['created_time >='] = $time_arr['start'];
            $where['created_time <']  = $time_arr['end'];
        } else {
            return 'error time param';
        }

        $bd_ids = $this->_parse_bd_id();
        $where['in'] = [
            'invite_id' => $bd_ids
        ];
        $cnt = $this->MCustomer->count($where);
        return intval($cnt);
    }

    //下单用户数,不管订单状态
    public function order_customer_num() {
        $answer = $this->_private_order_customer_num();
        if(is_string($answer)) {
            $this->_assemble_err(-1, $answer);
        } else {
            $this->_assemble_res(0, 'success', $answer);
        }
    }

    private function _private_order_customer_num() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        $this->db->select('distinct user_id',false);
        $this->db->from('t_order');
        $this->db->join('t_customer', 't_order.sale_id=t_customer.invite_id and t_order.user_id=t_customer.id', 'inner');
        $this->db->where('t_order.status !=', C('order.status.closed.code'));
        $this->db->where('t_order.created_time >', $time_arr['start']);
        $this->db->where('t_order.created_time <=', $time_arr['end']);
        $this->db->where_in('t_order.sale_id', $bd_ids);
        $result = $this->db->get()->result_array();
        return count($result);
    }

    //从数组中提取出user_id
    private function _parse_user_ids($temp, $key = 'user_id') {
        $user_ids = [];
        foreach($temp as $v) {
            if($v[$key]) {
                $user_ids[] = $v[$key];
            }
        }
        return $user_ids;
    }

    //总客户数与时间无关
    private function _private_all_customer_num() {
        $bd_ids = $this->_parse_bd_id();
        if(count($bd_ids)<=0) {
            return 'lack of bdids';
        }
        $where = ['in' => [
                'invite_id' => $bd_ids
            ]
        ];
        $cnt = $this->MCustomer->count($where);
        return $cnt;
    }

    //未下单客户数,不管起始时间
    public function not_order_customer_num() {
        //获取下单客户数
        $order_customer_num = $this->_private_order_customer_num();
        if(is_string($order_customer_num)) {
            $this->_assemble_err(-1, $order_customer_num);
        }
        //获取总客户数
        $customer_num = $this->_private_all_customer_num();
        if(is_string($customer_num)) {
            $this->_assemble_err(-1, $customer_num);
        }
        $this->_assemble_res(0, 'success', $customer_num - $order_customer_num);
    }

    public function finish_order_num() {
        $answer = $this->_private_finish_order_num();
        if(is_string($answer)) {
            $this->_assemble_err(-1, $answer);
        } else {
            $this->_assemble_res(0, 'success', $answer);
        }
    }

    private function _private_finish_order_num() {
        $bd_ids = $this->_parse_bd_id();
        if(count($bd_ids)<=0) {
            return 'lack of bdids';
        }
        //找出属于bd_ids的客户
        $where = ['in'=>[]];
        $where['in']['am_id'] = $bd_ids;
        $user_ids = $this->_parse_user_ids($this->MCustomer->get_lists(['id'], $where), 'id');
        if(!is_array($user_ids) || count($user_ids)<=0) {
            return 0;
        }
        $time_arr = $this->_get_start_and_end();
        if($time_arr === FALSE) {
            return 'time param error';
        }
        //找出客户下的状态为完成的订单数
        $where = [];
        $where['status'] = C('order.status.success.code');
        $where['updated_time >='] = $time_arr['start'];
        $where['updated_time <'] = $time_arr['end'];
        $where['in'] = ['user_id' => $user_ids];
        $cnt = $this->MOrder->count($where);
        return $cnt;
    }

    /*
     * 已完成首单数
     * 订单是完成状态，完成时间最早的一单算首单
     */
    public function first_finish_order_num() {
        $answer = $this->_private_first_finish_order_num();
        if(is_string($answer)) {
            $this->_assemble_err(-1, $answer);
        } else {
            $this->_assemble_res(0, 'success', $answer);
        }
    }

    /*
     * 全部首单数
     * 订单是有效状态（不一定完成了）
     */
    public function first_order_num() {
        $answer = $this->_private_first_order_num();
        if(is_string($answer)) {
            $this->_assemble_err(-1, $answer);
        } else {
            $this->_assemble_res(0, 'success', $answer);
        }
    }

    private function _private_first_finish_order_num() {
        $bd_ids = $_POST['bd_ids'];
        if(!is_array($bd_ids)) {
            return 'lack of bdids';
        }
        //找出首单发生在某一时间段内的客户
        $time_arr = $this->_get_start_and_end();
        if(empty($time_arr)) {
            return 'lack of time param';
        }
        $where = [
            'status' => C('order.status.success.code'),
            'created_time >=' => strtotime('2015-05-06'),
        ];
        //所有用户的首单
        $results = $this->MOrder->get_lists(['user_id','min(updated_time) as min_time'], $where, [], ['user_id']);
        if(!is_array($results) || count($results)<=0) {
            return 0;
        }
        //去除首单时间不在查询时间段的用户
        $len = count($results);
        for($i=0; $i<$len; $i++) {
            if(intval($results[$i]['min_time'])<intval($time_arr['start']) || intval($results[$i]['min_time'])>intval($time_arr['end'])) {
                unset($results[$i]);
            }
        }
        //获取user_ids
        $user_ids = $this->_parse_user_ids($results);
        if(count($user_ids)<=0) {
            return 0;
        }
        $where = [
            'in' => ['id' => $user_ids, 'invite_id' => $bd_ids]
        ];
        $cnt = $this->MCustomer->count($where);
        return $cnt;
    }


    private function _private_first_order_num() {
        $bd_ids = $_POST['bd_ids'];
        if(!is_array($bd_ids)) {
            return 'lack of bdids';
        }
        //找出首单发生在某一时间段内的客户
        $time_arr = $this->_get_start_and_end();
        if(empty($time_arr)) {
            return 'lack of time param';
        }
        $where = array(
            'status !=' => C('order.status.closed.code'),
            'in' => array(
                'sale_id' => $bd_ids,
            ),
            'created_time >=' => strtotime('2015-05-06'),
        );
        //所有用户的首单
        $results = $this->MOrder->get_lists(['user_id','min(created_time) as min_time'], $where, [], ['user_id']);
        if(!is_array($results) || count($results)<=0) {
            return 0;
        }
        //去除首单时间不在查询时间段的用户
        $len = count($results);
        for($i=0; $i<$len; $i++) {
            if(intval($results[$i]['min_time'])<intval($time_arr['start']) || intval($results[$i]['min_time'])>intval($time_arr['end'])) {
                unset($results[$i]);
            }
        }
        //获取user_ids
        $user_ids = $this->_parse_user_ids($results);
        if(count($user_ids)<=0) {
            return 0;
        }
        //属于某些am或者bd的客户
        $where = [
            'in' => ['id' => $user_ids, 'invite_id' => $bd_ids]
        ];
        $cnt = $this->MCustomer->count($where);
        return $cnt;
    }

    public function order_num() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        $where = [
            'status >' => C('order.status.closed.code'),
            'created_time >' => $time_arr['start'],
            'created_time <=' => $time_arr['end'],
            'in' => [
                'sale_id' => $bd_ids
            ]
        ];
        $cnt = $this->MOrder->count($where);
        $this->_assemble_res(0, 'success', $cnt);
    }

    public function potential_customer_num() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        if(!$bd_ids || !$time_arr) {
            $this->_assemble_res(0, 'lack of params', 0);
        }
        $where = [
            'status !=' => C('customer.status.invalid.code'),
            'created_time >' => $time_arr['start'],
            'created_time <=' => $time_arr['end'],
            'in' => [
                'invite_id' => $bd_ids
            ]
        ];
        $cnt = $this->MCustomer_potential->count($where);
        $this->_assemble_res(0, 'success', $cnt);
    }

    //新增流水
    public function flow_water() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        if(!$bd_ids || !$time_arr) {
            $this->_assemble_res(0, 'lack of params', 0);
        }
        $where = [
            'status' => C('order.status.success.code'),
            'created_time >' => $time_arr['start'],
            'created_time <=' => $time_arr['end'],
            'in' => [
                'sale_id' => $bd_ids
            ]
        ];
        $fields = ['sum(deal_price) summ'];
        $result = $this->MOrder->get_one($fields, $where);
        if(!$result['summ']) {
            $result['summ'] = 0;
        }
        $this->_assemble_res(0, 'success', $result['summ']);
    }

    private function _private_customer_num_till_end_time(){
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        $where = [
            'status !=' => C('customer.status.invalid.code'),
            'in' => [
                'invite_id' => $bd_ids
            ],
            'created_time <=' => $time_arr['end']
        ];
        return $this->MCustomer->count($where);
    }

    //未下单客户数，与时间相关
    public function not_order_customer_num_care_time() {
        $customer_num = $this->_private_customer_num_till_end_time();
        $order_customer_num = $this->_private_order_customer_num();
        $this->_assemble_res(0, 'success', $customer_num-$order_customer_num);
    }

    private function _get_private_potential_customer_num($bd_id) {
        if(!$bd_id) {
            return 0;
        }
        $where = ['invite_id' => $bd_id, 'status !=' => C('customer.status.invalid.code')];
        return $this->MCustomer_potential->count($where);
    }

    private function _get_private_register_customer_num($bd_id) {
        if(!$bd_id) {
            return 0;
        }
        $where = ['invite_id' => $bd_id, 'status !=' => C('customer.status.invalid.code')];
        return $this->MCustomer->count($where);
    }

    private function _get_private_potential_capability($bd_id) {
        if(!$bd_id) {
            return 0;
        }
        $where = ['id' => $bd_id];
        $capability = $this->MUser->get_one(['max_potential_customer'], $where);
        return intval($capability['max_potential_customer']);
    }

    private function _get_private_register_capability($bd_id) {
        if(!$bd_id) {
            return 0;
        }
        $where = ['id' => $bd_id];
        $capability = $this->MUser->get_one(['max_customer'], $where);
        return intval($capability['max_customer']);
    }

    public function get_capability() {
        if(!isset($_POST['bd_id'])) {
            $this->_assemble_err(C('status.req.failed'), 'lack of bdid');
        }
        $bd_id = $_POST['bd_id'];
        $arr = [
            ['key' => '私海潜在客户数量', 'val' => ['current' => $this->_get_private_potential_customer_num($bd_id), 'upper' => $this->_get_private_potential_capability($bd_id)]],
            ['key' => '私海注册客户数量', 'val' => ['current' => $this->_get_private_register_customer_num($bd_id), 'upper' => $this->_get_private_register_capability($bd_id)]],
        ];
        $this->_assemble_res(C('status.req.success'), '请求成功', $arr);
    }

    private function _get_pagination() {
        $page = [];
        if(!empty($_POST['itemsPerPage']) && !empty($_POST['currentPage'])) {
            $page['itemsPerPage'] = $_POST['itemsPerPage'];
            $page['currentPage'] = $_POST['currentPage'];
        } else {
            $page = false;
        }
        return $page;
    }

    public function get_new_add_orders() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        $page = $this->get_page();
        if(empty($bd_ids) || empty($time_arr)) {
            $this->_assemble_res(C('status.req.failed'), 'lack params');
        }
        //查询时间段内，BD旗下客户的订单
        $fields = ['user_id','final_price'];
        $where = [
            'status >' => C('order.status.closed.code'),
            'in' => [
                'sale_id' => $bd_ids
            ],
            'created_time >' => $time_arr['start'],
            'created_time <=' => $time_arr['end']
        ];
        $total = $this->MOrder->count($where);
        if($page) {
            $order_info = $this->MOrder->get_lists($fields, $where, null, null, $page['offset'], $page['page_size']);
        } else {
            $order_info = $this->MOrder->get_lists($fields, $where);
        }
        if(count($order_info) <= 0) {
            $this->_assemble_res(C('status.req.success'), 'success', []);
        }
        //查询下单客户的信息
        $fields = ['id','shop_name'];
        $ids = [];
        foreach($order_info as $v) {
            $ids[] = $v['user_id'];
        }
        $where = [
            'in' => [
                'id' => $ids
            ],
            'status >' => C('customer.status.invalid.code')
        ];
        $customer_info = $this->MCustomer->get_lists($fields, $where);
        if(count($customer_info) <= 0) {
            $this->_assemble_res(C('status.req.success'), 'success', []);
        }
        $result = [
            'total'=>$total,
            'list' => []
        ];
        foreach($order_info as $order) {
            foreach($customer_info as $customer) {
                if($order['user_id'] == $customer['id']) {
                    $result['list'][] = [
                        'shop_name' => $customer['shop_name'],
                        'flow_water' => $order['final_price']
                    ];
                }
            }
        }
        $this->_assemble_res(C('status.req.success'), 'success', $result);
    }

    public function get_back_flow_water() {
        $bd_ids = $this->_parse_bd_id();
        $time_arr = $this->_get_start_and_end();
        $page = $this->get_page();
        if(!$bd_ids || !$time_arr) {
            $this->_assemble_err(C('status.req.failed'), 'lack params');
        }
        $fields = ['user_id', 'deal_price'];
        $where = [
            'status' => C('order.status.success.code'),
            'in' => [
                'sale_id' => $bd_ids
            ],
            'created_time >' => $time_arr['start'],
            'created_time <=' => $time_arr['end']
        ];
        $total = $this->MOrder->count($where);
        if($page) {
            $order_info = $this->MOrder->get_lists($fields, $where, ['created_time' => 'desc'], null, $page['offset'], $page['page_size']);
        } else {
            $order_info = $this->MOrder->get_lists($fields, $where);
        }
        if(empty($order_info)) {
            $this->_assemble_res(C('status.req.success'), 'success', []);
        }
        $fields = ['id', 'shop_name'];
        $ids = [];
        foreach($order_info as $v) {
            $ids[] = $v['user_id'];
        }
        $where = [
            'status !=' => C('customer.status.invalid.code'),
            'in' => [
                'id' => $ids
            ]
        ];
        $customer_info = $this->MCustomer->get_lists($fields, $where);
        $result = [
            'total' => $total,
            'list' => []
        ];
        foreach($order_info as $order) {
            foreach($customer_info as $customer) {
                if($order['user_id'] == $customer['id']) {
                    $result['list'][] = [
                        'shop_name' => $customer['shop_name'],
                        'flow_water' => $order['deal_price']
                    ];
                }
            }
        }
        $this->_assemble_res(C('status.req.success'), 'success', $result);
    }
}
