<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CRM客户详情API
 * @author liudeen@dachuwang.com
 * @date 2015-03-24
 * 让供外部调用的方法需要写到_action_lists中
 * 内部方法需要加前缀_pv或_get
 */
class Cdetail extends MY_Controller {

    private $_customer_info_lists = ['created_time','shop_name','name','mobile','shop_type','dimensions','site_id','address','line_id','remark','lng', 'lat', 'id', 'status','account_type','customer_type','greens_meat_estimated','rice_grain_estimated','recieve_name','recieve_mobile','parent_mobile'];
    private $_check_lists = ['action'];
    private $_action_lists = ['customer_info','history_data','latest_sms','history_belong_detail','order_detail'];
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MOrder',
                'MSms_log',
                'Mline',
                'MCustomer_image',
                'MCustomer_transfer_log',
                'MOrder_detail',
                'MUser',
                'MCustomer_transfer_log',
                'MSuborder'
            )
        );
    }
    private function _pv_assemble_res($status, $msg, $res) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
            'res'    => $res
        );
        $this->_return_json($arr);
    }

    private function _pv_assemble_err($status, $msg) {
        $arr = array(
            'status' => $status,
            'msg'    => $msg,
        );
        $this->_return_json($arr);
    }

    private function _pv_checkNecessary() {
        foreach($this->_check_lists as $v) {
            if(empty($_POST[$v])) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function _pv_checkValid() {
        if(in_array($_POST['action'], $this->_action_lists)) {
            return TRUE;
        }
        return FALSE;
    }

    //传入当前日期,格式为date('Y-m-d',xyz)，返回本月第一天的时间戳
    private function _get_month_firstday($date) {
        return strtotime(date('Y-m-01',strtotime($date)));
    }

    //获取用户最新短信
    private function _latest_sms() {
        $uid = $_POST['uid'];
        $where = ['id' => $uid];
        try {
            $mobile = $this->MCustomer->get_one(['mobile'],$where);
        } catch(Exception $e) {
            throw new Exception('query mobile error in '.__FUNCTION__);
        }
        $where = ['mobile'=>$mobile['mobile']];
        try {
            $sms = $this->MSms_log->get_lists(['content'], $where, ['updated_time'=>'desc'], [], 0,1);
        } catch(Exception $e) {
            throw new Exception('query content error in '.__FUNCTION__);
        }
        return empty($sms) ? '' : $sms[0]['content'];
    }

    private function _history_data() {
        $uid = intval($_POST['uid']);
        $target = $_POST['target'];
        $arr = [];
        foreach($target as $item) {
            try {
                $method = '_get_customer_'.$item;
                $arr[$item] = $this->$method($uid);
            } catch(Exception $e) {
                $arr[$item] = '未知';
            }
        }
        return $arr;
    }

    private function _get_customer_history_belong($uid) {
        $uid = intval($uid);
        $where = ['cid' => $uid];
        try {
            $count = $this->MCustomer_transfer_log->count($where);
        } catch(Exception $e) {
            throw new Exception('error get history belong num in '.__FUNCTION__);
        }
        return intval($count)+1;
    }

    private function _get_customer_order_num($uid) {
        $uid = intval($uid);
        $where = [
            'user_id' => $uid
        ];
        try {
            $num = $this->MOrder->count($where);
        } catch(Exception $e) {
            throw new Exception('error get customer order num in '.__FUNCTION__);
        }
        return $num;
    }

    //单位是分
    private function _get_customer_order_amount($uid) {
        $uid = intval($uid);
        $where = [
            'status' => C('status.order.success'),
            'user_id' => $uid
        ];
        try {
            $amount = $this->MOrder->get_one('SUM(total_price) as total', $where);
        } catch(Exception $e) {
            throw new Exception('error get customer order amount in '.__FUNCTION__);
        }
        return isset($amount['total']) ? $amount['total'] : 0;
    }

    private function _get_line_name($lineid) {
        $where = ['id' => $lineid];
        $ans =  $this->Mline->get_one(['name'], $where);
        return $ans['name'];
    }

    private function _get_dimensions_value($dimen) {
        try {
            $dimensions = array_values(C('customer.dimension'));
            $dimension_dict = [];
            foreach ($dimensions as $dimension) {
                $dimension_dict[$dimension['value']] = $dimension['name'];
            }
            return isset($dimension_dict[$dimen]) ? $dimension_dict[$dimen] : '未填写';
        } catch(Exception $e) {
            return '未填写';
        }
    }

    private function _get_shoptype_name($id) {
        $id = intval($id);
        $arr = C('customer_type.top');
        foreach($arr as $v) {
            if($v['id'] === $id) {
               return $v['name'];
            }
        }
        return '暂无类别';
    }

    private function _get_site_name($id) {
        $id = intval($id);
        $arr = C('site.code');
        foreach($arr as $v) {
            if($v['id'] === $id) {
                return $v['name'];
            }
        }
        return '未知系统';
    }

    private function _get_customer_urls($id) {
        $where['owner_id'] = $id;
        $where['owner_type'] = C('customer_image.owner_type.customer');
        $order_by = ['updated_time' => 'desc'];
        $res = $this->MCustomer_image->get_lists(['url'], $where, $order_by, [], 0,2);
        if(!$res) {
            return "";
        }
        $ans = [];
        foreach($res as $v) {
            $ans[] = $v;
        }
        while(count($ans)<2) {
            $ans[] = $ans[0];
        }
        return $ans;
    }

    private function _get_ka_date($cycle, $date) {
        if($cycle == 'month') {
            if($date >= 1 && $date <= 28) {
                return $date.'号';
            } else {
                return '未知';
            }
        }
        $config = 'customer.ka_date.'.$cycle;
        $times = C($config);
        foreach($times as $time) {
            if($time['value'] == $date) {
                return $time['name'];
            }
        }
        return '未知';
    }

    private function _assemble_ka_info_arr($name, $value) {
        return [
            'name' => $name,
            'value' => $value
        ];
    }

    private function _get_ka_info($account_type, $uid) {
        if(!$account_type) {
            return null;
        }
        $account_type = intval($account_type);
        if($account_type === C('customer.account_type.parent.value')) {
            $type = C('customer.account_type.parent.name');
        } else {
            $type = C('customer.account_type.child.name');
        }
        $where = ['id' => $uid];
        //母账号
        if($type == C('customer.account_type.parent.name')) {
            $fields = ['bank','sub_bank','bank_account','billing_cycle','check_date','invoice_date','pay_date'];
            $info = $this->MCustomer->get_one($fields, $where);
            //bank
            $banks = C('customer.bank');
            foreach($banks as $bank) {
                if($bank['value'] == $info['bank']) {
                    $info['bank'] = $this->_assemble_ka_info_arr('开户银行',$bank['name']);
                    break;
                }
            }
            $info['sub_bank'] = $this->_assemble_ka_info_arr('开户银行支行', $info['sub_bank']);
            $info['bank_account'] = $this->_assemble_ka_info_arr('银行账号', $info['bank_account']);
            $billing_cycle = $info['billing_cycle'];
            //billing_cycle
            $cycles = C('customer.billing_cycle');
            foreach($cycles as $cycle) {
                if($cycle['value'] == $info['billing_cycle']) {
                    $info['billing_cycle'] = $this->_assemble_ka_info_arr('支付周期', $cycle['name']);
                    break;
                }
            }
            //date
            $info['check_date'] = $this->_assemble_ka_info_arr('对账日期', $this->_get_ka_date($billing_cycle, $info['check_date']));
            $info['invoice_date'] = $this->_assemble_ka_info_arr('开票日期', $this->_get_ka_date($billing_cycle, $info['invoice_date']));
            $info['pay_date'] = $this->_assemble_ka_info_arr('支付日期', $this->_get_ka_date($billing_cycle, $info['pay_date']));
        } else {
            $fields = ['parent_mobile'];
            $info = $this->MCustomer->get_one($fields, $where);
            $info['parent_mobile'] = $this->_assemble_ka_info_arr('关联母账号手机号', $info['parent_mobile']);
            $fields = ['shop_name'];
            $where = ['mobile' => $info['parent_mobile']['value']];
            $parent = $this->MCustomer->get_one($fields, $where);
            $info['parent_shop'] = $this->_assemble_ka_info_arr('母账号店铺名', $parent['shop_name']);
        }
        return [
            'type' => $type,
            'info' => $info
        ];
    }

    private function _customer_info() {
        $uid = $_POST['uid'];
        $where = ['id'=>$uid];
        try {
            $info = $this->MCustomer->get_one($this->_customer_info_lists, $where);
            $info['line'] = $this->_get_line_name($info['line_id']);
            unset($info['line_id']);
            $info['shop_type'] = $this->_get_shoptype_name($info['shop_type']);
            $info['site'] = $this->_get_site_name($info['site_id']);
            unset($info['site_id']);
            $info['urls'] = $this->_get_customer_urls($uid);
            $info['is_new'] = $info['status'] == C('customer.status.new.code') ? TRUE:FALSE;
            $dimensions = array_values(C('customer.dimension'));
            $dimension_dict = array_combine(array_column($dimensions, 'value'), array_column($dimensions, 'name'));
            $info['dimension_name'] = isset($dimension_dict[$info['dimensions']]) ? $dimension_dict[$info['dimensions']] : '';
            unset($info['status']);
            $info['customer_type'] = $this->_get_customer_type_name($info['customer_type']);
            $info['account_type'] = $this->_get_account_type_name($info['account_type']);
            if($info['account_type'] != C('customer.account_type.child.name')) {
                unset($info['parent_mobile']);
            }
            $info['greens_meat_estimated'] = $this->_get_estimate_type_name($info['greens_meat_estimated']);
            $info['rice_grain_estimated'] = $this->_get_estimate_type_name($info['rice_grain_estimated']);
            $info['recieve_mobile'] = empty($info['recieve_mobile']) ? $info['mobile'] : $info['recieve_mobile'];
            $info['recieve_name'] = empty($info['recieve_name']) ? $info['name'] : $info['recieve_name'];
        } catch (Exception $e) {
            throw new Exception('query error in '.__FUNCTION__);
        }
        return $info;
    }

    private function _get_customer_type_name($customer_type) {
        $arr = C('customer.type');
        foreach($arr as $v) {
            if($v['value'] == $customer_type) {
                return $v['name'];
            }
        }
        return '数据缺失';
    }

    private function _get_account_type_name($account_type) {
        $arr = C('customer.account_type');
        foreach($arr as $v) {
            if($v['value'] == $account_type) {
                return $v['name'];
            }
        }
        return '数据缺失';
    }

    private function _get_estimate_type_name($estimate_type) {
        $arr = C('customer.estimated');
        foreach($arr as $v) {
            if($v['value'] == $estimate_type) {
                return $v['name'];
            }
        }
        return '数据缺失';
    }

    private function _get_time_arr() {
        if(!isset($_POST['time_arr'])) {
            return FALSE;
        }
        return $_POST['time_arr'];
    }

    private function _hook_order_status($status) {
        $arr = C('order.status');
        foreach($arr as $val) {
            if($val['code'] == $status) {
                return $val['msg'];
            }
        }
        return '未知状态';
    }

    //获取某一天的最早时间
    private function _get_time_early($time) {
        return strtotime(date('Y-m-d',$time));
    }
    //获取某一天的最晚时间
    private function _get_time_late($time) {
        return strtotime('+1 day',strtotime(date('Y-m-d',$time)))-1;
    }

    private function _customer_orders() {
        $uid = intval($_POST['uid']);
        $where = ['user_id'=>$uid];
        $time_arr = $this->_get_time_arr();
        if($time_arr !== FALSE) {
            if(isset($time_arr['begin_time'])) {
                $begin_time = $time_arr['begin_time'];
                $where['created_time >='] = $this->_get_time_early($begin_time);
            }
            if(isset($time_arr['end_time'])) {
                $end_time = $time_arr['end_time'];
                $where['created_time <'] = $this->_get_time_late($end_time);
            }
        }
        $where['status >'] = C('order.status.closed.code');
        $page = $this->get_page();
        $orders = $this->MSuborder->get_lists(['created_time','order_number','status'], $where, ['created_time' => 'desc'],NULL, $page['offset'], $page['page_size']);
        foreach($orders as &$item) {
            $item['status'] = $this->_hook_order_status($item['status']);
        }
        return $orders;
    }

    //根据订单编号查询订单详情
    private function _order_detail() {
        $order_number = isset($_POST['order_number']) ? $_POST['order_number'] : NULL;
        if(!$order_number) {
            throw new Exception('lack of order number');
        }
        $fields = ['id','sale_id','order_number','total_price','deal_price','created_time','deliver_date','deliver_time','final_price','status', 'driver_name', 'driver_mobile'];
        $where = ['order_number' => $order_number];
        try {
            //查订单的主要信息
            $order_info = $this->MSuborder->get_one($fields, $where);
            $order_info['status'] = $this->_hook_order_status($order_info['status']);
            $where = ['suborder_id' => $order_info['id']];
            //查询订单的订单内容
            $fields = ['name','quantity','sum_price','spec'];
            $order_content = $this->MOrder_detail->get_lists($fields, $where);
            foreach($order_content as &$v) {
                $v['spec'] = json_decode($v['spec']);
            }
            unset($order_info['id']);
            $order_info['content'] = $order_content;
            //查询下单时客户所属销售的名字
            $sale_name = $this->MUser->get_one(['name'],['id'=>$order_info['sale_id']]);
            $order_info['sale_name'] = $sale_name['name'];
            unset($order_info['sale_id']);
            $order_info['deliver_time'] = $this->_get_extract_deliver_time($order_info['deliver_time']);
            return $order_info;
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _get_extract_deliver_time($time_number) {
        foreach(C('order.deliver_time') as $alias_time) {
            if($alias_time['code'] == $time_number) {
                return $alias_time['msg'];
            }
        }
        return '未知送货时间';
    }

    private function _history_belong_detail() {
        $uid = isset($_POST['uid']) ? $_POST['uid'] : NULL;
        if(!$uid) {
            throw new Exception('lack of uid');
        }
        $fields = ['dest_id','dest_role','created_time'];
        $where = ['cid'=>$uid];
        $arr = $this->MCustomer_transfer_log->get_lists($fields, $where, ['created_time'=>'asc']);
        $ids = [];
        $sales = [];
        foreach($arr as $log) {
            if(!in_array($log['dest_id'], $ids)) {
                $ids[] = $log['dest_id'];
                $sales[] = [
                    'time' => $log['created_time'],
                    'name' => $this->_get_sale_name($log['dest_id']),
                    'role' => $this->_get_extract_role($log['dest_role'])
                ];
            }
        }
        $where = ['id' => $uid];
        $customer_info = $this->MCustomer->get_one(['invite_bd', 'created_time'], $where);
        $where = ['id' => $customer_info['invite_bd']];
        $invite_bd = $this->MUser->get_one(['name','role_id'], $where);
        array_unshift($sales, [
            'time' => $customer_info['created_time'],
            'name' => $invite_bd['name'],
            'role' => $this->_get_extract_role($invite_bd['role_id'])
        ]);
        return $sales;
    }

    private function _get_extract_role($role_id) {
        $role_list = [
            12 => 'BD',
            13 => 'BDM',
            14 => 'AM',
            15 => 'SAM',
            16 => 'CM'
        ];
        $role_id = intval($role_id);
        if(array_key_exists($role_id, $role_list)) {
            return $role_list[$role_id];
        }
        return '无';
    }

    /*
     * @description 获取销售姓名
     * @param $uid integer
     * @return string
     */
    private function _get_sale_name($uid) {
        if(!isset($uid)) {
            return '无名氏';
        }
        if(intval($uid) === -1) {
            return '公海';
        }
        $res = $this->MUser->get_one(['name'],['id'=>$uid]);
        return $res['name'];
    }

    private function _get_sub_accounts() {
        if(empty($_POST['id'])) {
            return [];
        }
        $parent_id = $_POST['id'];
        //判断是否为母账号
        $parent_info = $this->MCustomer->get_one(['customer_type','account_type','mobile'], ['id' => $parent_id]);
        $parent_type = C('customer.account_type.parent.value');
        if(!$parent_info || intval($parent_info['account_type'])!==$parent_type) {
            return [];
        }
        $sub_lists = $this->MCustomer->get_lists(['id','shop_name'], ['parent_mobile' => $parent_info['mobile'], 'customer_type' => $parent_info['customer_type'], 'account_type'=>C('customer.account_type.child.value')]);
        return $sub_lists;
    }

    public function index() {
        try {
            $this->_pv_checkNecessary();
            $this->_pv_checkValid();
            $action = '_'.$_POST['action'];
            $ans = $this->$action();
            $this->_pv_assemble_res(C('status.req.success'), '请求成功', $ans);
        } catch (Exception $e) {
            $this->_pv_assemble_err(C('status.req.failed'), $e->getMessage());
        }
    }
}
