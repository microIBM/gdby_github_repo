<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @description 共海私海API
 * @author liudeen@dachuwang.com
 */

class Shared_customer extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model(['MUser', 'MCustomer', 'MCustomer_potential', 'MLine', 'MOrder']);
    }
    private function _assemble_res($status, $msg, $res, $total=NULL, $totalNumber=NULL) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
            'list'   => $res
        );
        if($total !== NULL) {
            $arr['total'] = $total;
        }
        if($totalNumber !== NULL) {
            $arr['total_number'] = $totalNumber;
        }
        return $arr;
    }

    private function _assemble_err($status, $msg) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
        );
        return $arr;
    }

    //获取BD的部分个人信息
    private function _bd_info() {
        if(!isset($_POST['bd_id'])) {
            return FALSE;
        }
        $bd_id = $_POST['bd_id'];
        $bd_info = $this->MUser->get_one(['province_id', 'site_id', 'id'], ['id' => $bd_id]);
        if(!$bd_info) {
            return FALSE;
        }
        return $bd_info;
    }

    private function _get_extract_sift() {
        if(!isset($_POST['conditions']) || !isset($_POST['conditions']['sift']) || !is_array($_POST['conditions']['sift'])) {
            return NULL;
        }
        $temp = $_POST['conditions']['sift'];
        $arr = [];
        if(isset($temp['line'])) {
            $arr['line_id'] = $temp['line'];
        }
        if(isset($temp['dimensions'])) {
            $arr['dimensions'] = $temp['dimensions'];
        }
        if(isset($temp['shop_type'])) {
            $arr['shop_type'] = $temp['shop_type'];
        }
        if(isset($temp['order_type'])) {
            $arr['order_type'] = $temp['order_type'];
        }
        return $arr;
    }

    private function _get_search_key() {
        if(!isset($_POST['key'])) {
            return NULL;
        }
        if(is_numeric($_POST['key'])) {
            return ['mobile' => intval($_POST['key'])];
        }
        return ['shop_name' => $_POST['key']];
    }

    //公海潜在客户列表
    public function potential_lists() {
        $fields = ['shop_name','address','id','remark','line_id','customer_type'];
        $bd_info = $this->_bd_info();
        $where = [
            'status >' => C('status.common.del'),
            'province_id' => $bd_info['province_id'],
            'invite_id' => -1
        ];
        $totalNumber = $this->MCustomer_potential->count($where);
        $sift = $this->_get_extract_sift();
        if($sift) {
            foreach($sift as $k => $v) {
                $where[$k] = $v;
            }
        }
        $key = $this->_get_search_key();
        if($key) {
            foreach($key as $k => $v) {
                $where['like'][$k] = $v;
            }
        }
        $total = $this->MCustomer_potential->count($where);
        $order_by = ['created_time' => 'desc'];
        $page = $this->get_page();
        $lists = $this->MCustomer_potential->get_lists($fields, $where, $order_by, NULL, $page['offset'], $page['page_size']);
        foreach($lists as &$item) {
           $tmp = $this->MLine->get_one(['name'], ['id' => $item['line_id']]);
           $item['line_name'] = $tmp['name'];
        }
        $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $lists, $total, $totalNumber));
    }

    //公海新注册客户列表
    public function new_register_lists() {
        $fields = ['shop_name', 'address', 'id', 'remark', 'line_id','customer_type','is_active'];
        $bd_info = $this->_bd_info();
        $where = [
            'status >' => C('status.common.del'),
            'province_id' => intval($bd_info['province_id']),
            'invite_id' => -1
        ];
        $totalNumber = $this->MCustomer->count($where);
        $sift = $this->_get_extract_sift();
        if($sift) {
            foreach($sift as $k => $v) {
                $where[$k] = $v;
            }
        }
        $key = $this->_get_search_key();
        if($key) {
            foreach($key as $k => $v) {
                if($v) {
                    $where['like'][$k] = $v;
                }
            }
        }
        if(isset($where['order_type'])) {
            $order_type = $where['order_type'];
            unset($where['order_type']);
            $customers = $this->MCustomer->get_lists(['id'], $where);
            $ids = [];
            foreach($customers as $v) {
                $ids[] = intval($v['id']);
            }
            //ids如果为空
            /*
             * ------
             */
            if(!$ids) {
                $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', [], 0));
            }
            $order_where = [
                'in' => [
                    'user_id' => $ids
                ],
                'status !=' => C('order.status.closed.code')
            ];
            $ordered_cus = $this->MOrder->get_lists(['distinct user_id'], $order_where);
            $ids = [];
            foreach($ordered_cus as $v) {
                $ids[] = intval($v['user_id']);
            }
            /*
             * ids为空需要处理
             */
            if($order_type == 1) {
                $key = 'in';
                //查询已下单但是下单客户ids为空
                if(!$ids) {
                    $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', [], 0));
                }
            } else {
                $key = 'not_in';
            }
            if(!empty($ids)) {
                $where[$key]['id'] = $ids;
            }
        }
        $total = $this->MCustomer->count($where);
        $page = $this->get_page();
        $order_by = ['created_time' => 'desc'];
        $lists = $this->MCustomer->get_lists($fields, $where, $order_by, NULL, $page['offset'], $page['page_size']);
        foreach($lists as &$item) {
            $tmp = $this->MLine->get_one(['name'], ['id'=>intval($item['line_id'])]);
            $item['line_name'] = ($tmp && $tmp['name']) ? $tmp['name'] : '锦秋国际大厦(公司内部专供)';
            if($item['customer_type'] == C('customer.type.KA.value') && $item['is_active'] == C('customer.status.invalid.code')) {
                $item['ka_status'] = '待审核';
            }
            unset($item['is_active']);
        }
        $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $lists, $total, $totalNumber));
    }

    //把私海潜在客户剔除到公海
    public function potential_change_shared() {
        try {
            $cid = $_POST['cid'];
            if(!$cid) {
                throw new Exception('lack of cid');
            }
            $update = ['invite_id' => -1];
            $bd_info = $this->_bd_info();
            $where = ['id' => $cid, 'invite_id !=' => -1];
            $result = $this->MCustomer_potential->update_info($update, $where);
            if(!$result) {
                throw new Exception('客户不存在或客户已转到私海');
            }
            $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $result));
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    //把私海新注册客户剔除到公海
    public function new_register_change_shared() {
        try {
            $cid = $_POST['cid'];
            if(!$cid) {
                throw new Exception('lack of cid');
            }
            $update = ['invite_id' => -1];
            $where = ['id' => $cid, 'invite_id !=' => -1];
            $result = $this->MCustomer->update_info($update, $where);
            if(!$result) {
                throw new Exception('客户不存在或客户已转到公海');
            }
            $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $result));
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    //BD私海容量是否达上限
    private function _can_get($customer_type) {
        $bd_info = $this->_bd_info();
        if($customer_type === 'potential') {
            $where = [
                'invite_id' => $bd_info['id'],
                'status !=' => C('customer.status.invalid.code')
            ];
            $already_have = $this->MCustomer_potential->count($where);
            $upper_bound = $this->MUser->get_one(['max_potential_customer'], ['id' => $bd_info['id']]);
            if($already_have >= $upper_bound['max_potential_customer']) {
                return FALSE;
            }
            return TRUE;
        } else if($customer_type === 'new_register') {
            $where = ['invite_id' => $bd_info['id'], 'status' => C('customer.status.new.code')];
            $already_have = $this->MCustomer->count($where);
            $upper_bound = $this->MUser->get_one(['max_customer'], ['id' => $bd_info['id']]);
            if($already_have >= $upper_bound['max_customer']) {
                return FALSE;
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

    //把公海中得潜在客户转为私海
    public function potential_change_private() {
        try {
            //客户id
            $cid = $_POST['cid'];
            if(!$cid) {
                throw new Exception('lack of cid');
            }
            //获取bd的信息
            $bd_info = $this->_bd_info();
            if(!$bd_info) {
                throw new Exception('cannot get bd info');
            }
            //判断bd是否容量超限
            if(!$this->_can_get('potential')) {
                throw new Exception('潜在客户数已达到上限');
            }
            $update = ['invite_id' => $bd_info['id']];
            $where = ['id' => $cid, 'invite_id' => -1];
            // 判断用户是否存在
            $user = $this->MCustomer_potential->get_one('*', $where);
            if(!$user) {
                throw new Exception('用户已被抢或不存在');
            }
            $result = $this->MCustomer_potential->update_info($update, $where);
            $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $result));
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    //把公海中的新注册客户转为私海
    public function new_register_change_private() {
        try {
            $cid = $_POST['cid'];
            if(!$cid) {
                throw new Exception('lack of cid');
            }
            $bd_info = $this->_bd_info();
            if(!$this->_can_get('new_register')) {
                throw new Exception('新注册客户数已达到上限');
            }
            $update = ['invite_id' => $bd_info['id']];
            $where = ['id' => $cid, 'invite_id' => -1];
            // 判断用户是否存在
            $user = $this->MCustomer->get_one('*', $where);
            if(!$user) {
                throw new Exception('用户已被抢或不存在');
            }
            $result = $this->MCustomer->update_info($update, $where);
            $this->_return_json($this->_assemble_res(C('status.req.success'), '请求成功', $result));
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }
}
/* End of file user.php */
/* Location: :./application/controllers/user.php */
