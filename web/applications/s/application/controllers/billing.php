<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 */
class Billing extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'MUser',
            'MBilling',
            'MOrder',
            'MCustomer',
            'MSuborder',
            'MOrder_detail',
            'MBilling_log',
            'MSku',
            'MCategory',
            'MRejected',
            'MRejected_content'
        ));
        $this->load->library(array(
            'redisclient'
        ));
    }

    /**
     * 获取查询条件
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_condition()
    {
        $billing_cycle = C('customer.billing_cycle');
        $area = C('open_cities');
        $final_area = array();
        $final_billing_cycle = array();
        $final_bd = array();
        $final_billing_status = array();
        while (list ($key, $val) = each($area)) {
            $final_area[$val['id']] = $val['name'];
        }
        
        while (list ($key, $val) = each($billing_cycle)) {
            $final_billing_cycle[$val['value']] = $val['name'];
        }
        
        $bd = $this->MUser->get_lists(array(
            'id',
            'name',
            'province_id'
        ), array(
            'role_id' => C('role.BD.code'),
            'status >' => C('status.common.del')
        ));
        while (list ($key, $val) = each($bd)) {
            $final_bd[$val['id']] = array(
                "name" => $val['name'],
                "city_id" => $val['province_id']
            );
        }
        
        $billing_status = C('billing.status');
        unset($billing_status['disabled']);
        unset($billing_status['valid']);
        unset($billing_status['invalid']);
        while (list ($key, $val) = each($billing_status)) {
            $final_billing_status[$val['code']] = $val['msg'];
        }
        
        $expire_status = array_values(C('billing.expire_status'));
        $expire_status = array_column($expire_status, 'msg', 'code');
        
        $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'billing_cycle' => $final_billing_cycle,
            'area' => $final_area,
            'bd' => $final_bd,
            'billing_status' => $final_billing_status,
            'expire_status' => $expire_status
        ));
    }

    /**
     * 获取账单列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function lists()
    {
        $page = $this->get_page();
        $where_arr = $this->_set_where_condition();
        $condition = $where_arr['condition'];
        $param = $where_arr['param'];
        $limit = " limit {$page['offset']}, {$page['page_size']}";
        $sql = "select b.id, b.id as billing_num, b.payment_evidence,b.start_time, b.end_time, b.theory_start, b.theory_end,b.expire_time, b.expire_status,b.billing_cycle, b.status, b.pay_date,b.total_price,c1.shop_name, c1.mobile, c2.name as bd_name, c2.mobile as bd_mobile  from t_billing b inner join t_customer c1 on b.customer_id = c1.id inner join t_user c2 on c1.invite_id = c2.id";
        // get total_count items
        $count_sql = "select  count(b.id) as total_count from t_billing b inner join t_customer c1 on b.customer_id = c1.id inner join t_user c2 on c1.invite_id = c2.id";
        $count_final_sql = $count_sql . $condition;
        $total_count = $this->db->query($count_final_sql, $param)->row();
        $total_count = $total_count->total_count;
        $billing_list = array();
        if ($total_count > 0) {
            $final_sql = $sql . $condition . $limit;
            $billing_list = $this->db->query($final_sql, $param)->result_array();
            if (count($billing_list) > 0) {
                $billing_cycles = C('customer.billing_cycle');
                $billing_status = C('billing.status');
                unset($billing_status['disabled']);
                unset($billing_status['valid']);
                unset($billing_status['invalid']);
                $billing_status_of_key = array();
                foreach ($billing_status as $k => $v) {
                    $billing_status_of_key[$v['code']] = $v['msg'];
                }
                foreach ($billing_list as &$billing) {
                    $billing_cycle = $billing['billing_cycle'];
                    $billing_cycle = $billing_cycles[$billing_cycle]['name'];
                    $billing['billing_cycle'] = $billing_cycle;
                    $billing['theory_start'] = Date('Y-m-d', $billing['theory_start']);
                    $billing['theory_end'] = Date('Y-m-d', $billing['theory_end']);
                    $billing['start_time'] = Date('Y-m-d', $billing['start_time']);
                    $billing['end_time'] = Date('Y-m-d', $billing['end_time']);
                    $remote_billing_status = $billing['status'];
                    $billing['status_code'] = $remote_billing_status;
                    $remote_billing_status = $billing_status_of_key[$remote_billing_status];
                    $billing['status'] = $remote_billing_status;
                    $billing['total_price'] = sprintf("%.2f", $billing['total_price'] / 100);
                    $billing['expire_time'] = Date('Y-m-d', $billing['expire_time']);
                }
            }
        }
        $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $billing_list,
            'total' => $total_count
        ));
    }

    /**
     * 检查账单和客户的关系
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function check_billing_customer()
    {
        $id = $_POST['id'];
        $customer_id = $_POST['customer_id'];
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'customer_id'
        ), array(
            'id' => $id,
            'status >' => C('billing.status.invalid.code')
        ));
        if (count($billing_info) == 0) {
            // 返回结果
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '账期不存在'
            ));
        }
        $customer_info = $this->MCustomer->get_one(array(
            'account_type',
            'parent_mobile',
            'mobile'
        ), array(
            'id' => $customer_id
        ));
        $customer_mobile = $customer_info['mobile'];
        $account_type = $customer_info['account_type'];
        if ($account_type == C('customer.account_type.child.value')) {
            $parent_mobile = $customer_info['parent_mobile'];
            $parent_customer_info = $this->MCustomer->get_one(array(
                'id',
                'mobile'
            ), array(
                'mobile' => $parent_mobile
            ));
            $customer_id = $parent_customer_info['id'];
            $customer_mobile = $parent_customer_info['mobile'];
        }
        if ($customer_id != $billing_info['customer_id']) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '该账户无当前账期'
            ));
        }
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '信息有效'
        ));
    }

    /**
     * 检查账单是否有效
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function check_billing_valid()
    {
        $id = $_POST['id'];
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'customer_id'
        ), array(
            'id' => $id,
            'status >' => C('billing.status.invalid.code')
        ));
        if (count($billing_info) == 0) {
            // 返回结果
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '账期不存在'
            ));
        }
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '信息有效'
        ));
    }

    /**
     * 获取某条账单的具体信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function view()
    {
        $id = $_POST['id'];
        $wanted_field = array(
            'id',
            'id as billing_num',
            'customer_id',
            'start_time',
            'end_time',
            'billing_cycle',
            'status',
            'pay_date',
            'total_price',
            'expire_status',
            'expire_time',
            'theory_start',
            'theory_end',
            'payment_evidence'
        );
        $billing_info = $this->MBilling->get_one($wanted_field, array(
            'id' => $id
        ));
        $billing_cycle = C('customer.billing_cycle');
        $billing_info['billing_cycle'] = $billing_cycle[$billing_info['billing_cycle']]['name'];
        
        // get customer_info
        
        $customer_id = $billing_info['customer_id'];
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime("+1 day", $end_time);
        $customer_info = $this->MCustomer->get_one(array(
            'id, shop_name, invite_id',
            'mobile'
        ), array(
            'id' => $customer_id
        ));
        $invite_id = $customer_info['invite_id'];
        $mobile = $customer_info['mobile'];
        // get child_ids
        $child_ids = $this->MCustomer->get_lists(array(
            'id'
        ), array(
            'parent_mobile' => $mobile
        ));
        $customer_ids = array_column($child_ids, 'id');
        array_push($customer_ids, $customer_id);
        $customer_ids_str = implode(',', $customer_ids);
        $bd_customer_info = $this->MUser->get_one(array(
            'name as bd_name, mobile as bd_mobile'
        ), array(
            'id' => $invite_id
        ));
        
        // get the orders which are at between start_time and end_time
        $closed_status = C('order.status.closed.code');
        $order_sql = "select FROM_UNIXTIME(deliver_date, '%Y-%m-%d') as deliver_date, SUM(total_price)/100 total_price,SUM(o.deliver_fee)/100 AS deliver_fee,SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100 AS minus_amount,SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100 AS reduction_price,SUM(o.final_price)/100 final_price,SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100 AS actual_deliver_fee,SUM(deal_price)/100 - SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100+ SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100+ SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100 sign_price,
                        SUM(total_price) / 100 -
                        (
                        SUM(deal_price)/100
                        - SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100
                        + SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100
                        + SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100
                        ) refuse_price,
                        SUM(deal_price)/100 deal_price,
                        SUM(deposit)/100 deposit
                        from t_suborder o
                        WHERE deliver_date>= $start_time
                        and deliver_date < $end_time
                        and status > $closed_status
                        and user_id in ($customer_ids_str)
                        GROUP BY FROM_UNIXTIME(deliver_date, '%Y-%m-%d')
                        ORDER BY deliver_date";
        
        $order_list = $this->db->query($order_sql)->result_array();
        
        foreach ($order_list as &$order) {
            $order['total_price'] = sprintf("%.2f", $order['total_price']);
            $order['deal_price'] = sprintf("%.2f", $order['deal_price']);
            $order['refuse_price'] = sprintf("%.2f", ($order['refuse_price']-$order['deposit']));
            $order['final_price'] = sprintf("%.2f", $order['final_price']);
            $order['deliver_fee'] = sprintf("%.2f", $order['deliver_fee']);
            $order['minus_amount'] = sprintf("%.2f", $order['minus_amount']);
            $order['reduction_price'] = sprintf("%.2f", $order['reduction_price']);
            $order['actual_deliver_fee'] = sprintf("%.2f", $order['actual_deliver_fee']);
            $order['sign_price'] = sprintf("%.2f", ($order['sign_price']+$order['deposit']));
            $order['deposit'] = sprintf("%.2f", $order['deposit']);
        }
        $billing_info['theory_start'] = date('Y-m-d', $billing_info['theory_start']);
        $billing_info['theory_end'] = date('Y-m-d', $billing_info['theory_end']);
        $billing_info['start_time'] = date('Y-m-d', $billing_info['start_time']);
        $billing_info['end_time'] = date('Y-m-d', $billing_info['end_time']);
        $billing_info['total_price'] = sprintf("%.2f", $billing_info['total_price'] / 100);
        $billing_info['expire_time'] = date('Y-m-d', $billing_info['expire_time']);
        $billing_info['status_code'] = $billing_info['status'];
        $billing_info['status'] = $this->_get_billing_status_msg($billing_info['status']);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'order_list' => $order_list,
            'customer_info' => $customer_info,
            'bd_customer_info' => $bd_customer_info,
            'billing_info' => $billing_info
        ));
    }

    /**
     * 增加备注
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function add_remark()
    {
        $id = $_POST['id'];
        $content = $_POST['content'];
        $author_id = $_POST['author_id'];
        $author_name = $_POST['author_name'];
        $role_name = $_POST['role_name'];
        $role_id = $_POST['role_id'];
        $this->_billing_status_logger($id, $content, $author_id, $author_name, $role_name, $role_id, C('billing.auto.is_not_auto.code'));
        // 返回结果
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '增加备注成功'
        ));
    }

    /**
     * 获取某一账单的备注信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_billing_dynamic()
    {
        $billing_id = $_POST['id'];
        $wanted_field = array(
            'id',
            'content',
            'author_name',
            'role_name',
            'created_time',
            'auto'
        );
        $billing_log_list = $this->MBilling_log->get_lists($wanted_field, array(
            'status >' => C('billing_log.status.invalid.code'),
            'billing_id' => $billing_id
        ), array(
            'created_time' => 'desc'
        ));
        foreach ($billing_log_list as &$billing_log) {
            $billing_log['created_time'] = date('Y-m-d H:i:s', $billing_log['created_time']);
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $billing_log_list
        ));
    }

    /**
     * 更改账单状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function one_key_pay()
    {
        $id = $_POST['id'];
        $author_id = $_POST['author_id'];
        $author_name = $_POST['author_name'];
        $role_name = $_POST['role_name'];
        $role_id = $_POST['role_id'];
        $payment = isset($_POST['payment']) ? $_POST['payment'] : 0;
        $billing_info = $this->MBilling->get_one(array(
            'status',
            'customer_id'
        ), array(
            'id' => $id,
            'status >' => C('billing.status.invalid.code')
        ));
        if (! isset($billing_info['status'])) {
            // 返回结果
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '账期不存在'
            ));
        }
        $customer_id = $billing_info['customer_id'];
        $time = time();
        $content = '';
        if ($payment == 0) {
            if ($billing_info['status'] != C('billing.status.unpay.code') && $billing_info['status'] != C('billing.status.prepay.code')) {
                return $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '无法执行一键支付'
                ));
            }
            $content = "一键支付";
        } else {
            if ($billing_info['status'] != C('billing.status.payed.code')) {
                return $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '无法执行收款操作'
                ));
            }
            $content = "收款操作";
        }
        // $this->db->trans_start();
        $this->MBilling->update($id, array(
            'status' => C('billing.status.finish.code'),
            'expire_status' => 0,
            'updated_time' => $time
        ));
        $this->_billing_status_logger($id, $content, $author_id, $author_name, $role_name, $role_id, C('billing.auto.is_auto.code'));
        // $this->db->trans_complete();
        // if ($this->db->trans_status() === FALSE) {
        // return $this->_return_json(array(
        // 'status' => C('status.req.fail'),
        // 'msg' => '事务异常'
        // ));
        // }
        // $this->_forbid($customer_id);
        // 返回结果
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '修改成功'
        ));
    }

    /**
     * 获取账单的订单列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_orders_of_billing()
    {
        $id = $_POST['id'];
        $date = $_POST['date'];
        $is_shop = isset($_POST['is_shop']) ? intval($_POST['is_shop']) : 0;
        $start_time = strtotime($date);
        $end_time = strtotime('+1 day', strtotime($date));
        // left is open and right is closed
        $customer_info = $this->MCustomer->get_one(array(
            'id',
            'mobile',
            'shop_name'
        ), array(
            'id' => $id
        ));
        $mobile = $customer_info['mobile'];
        // get child_ids
        $child_ids = $this->MCustomer->get_lists(array(
            'id',
            'shop_name'
        ), array(
            'parent_mobile' => $mobile
        ));
        $customer_ids = array_column($child_ids, 'id');
        array_push($customer_ids, $id);
        array_push($child_ids, $customer_info);
        $sub_order_list = $this->MSuborder->get_lists(array(
            'id',
            'user_id',
            'order_number',
            'status',
            'deliver_fee',
            'minus_amount',
            'total_price',
            'final_price',
            'deal_price',
            'sign_img_url',
            'deposit',
            'total_price -
            (
            deal_price
            - case  deal_price when 0 then 0 else deliver_fee end
            + case  deal_price when 0 then 0 else minus_amount end
            + case  deal_price when 0 then 0 else pay_reduce end
            ) refuse_price',
            '(case deal_price when 0 then 0 else minus_amount end) as  actual_minus_amount',
            '(case deal_price when 0 then 0 else pay_reduce end) as reduction_price',
            '(case deal_price when 0 then 0 else deliver_fee end) as actual_deliver_fee',
            'deal_price-(case deal_price when 0 then 0 else deliver_fee end)+(case deal_price when 0 then 0 else minus_amount end)+(case deal_price when 0 then 0 else pay_reduce  end) sign_price'
        ), array(
            'deliver_date >=' => $start_time,
            'deliver_date <' => $end_time,
            'in' => array(
                'user_id' => $customer_ids
            ),
            'status >' => C('order.status.closed.code')
        ));
        $customer_of_key = array();
        foreach ($child_ids as $customer) {
            $customer_of_key[$customer['id']] = $customer;
        }
        $sub_order_ids = array_column($sub_order_list, 'id');
        
        // get_order_detail
        $order_detail_list = $this->MOrder_detail->get_lists('*', array(
            'in' => array(
                'suborder_id' => $sub_order_ids
            )
        ));
        $order_detail_list_of_key = array();
        $sku_numbers = array_column($order_detail_list, 'sku_number');
        $sku_list = $this->MSku->get_lists(array(
            'sku_number, net_weight'
        ), array(
            'in' => array(
                'sku_number' => $sku_numbers
            )
        ));
        $sku_list_of_key = array();
        foreach ($sku_list as $sku) {
            $sku_list_of_key[$sku['sku_number']] = $sku['net_weight'];
        }
        while (list ($key, $val) = each($order_detail_list)) {
            $val['price'] = sprintf("%.2f", $val['price'] / 100);
            $val['sum_price'] = sprintf("%.2f", $val['sum_price'] / 100);
            $val['single_price'] = sprintf("%.2f", $val['single_price'] / 100);
            $val['actual_price'] = sprintf("%.2f", $val['actual_price'] / 100);
            $val['actual_sum_price'] = sprintf("%.2f", $val['actual_sum_price'] / 100);
            $val['net_weight'] = $sku_list_of_key[$val['sku_number']];
            $order_detail_list_of_key[$val['suborder_id']][] = $val;
        }
        
        foreach ($sub_order_list as &$sub_order) {
            $sub_order_id = $sub_order['id'];
            $user_id = $sub_order['user_id'];
            if (! isset($sub_order['detail'])) {
                $sub_order['detail'] = array();
            }
            if ($is_shop) {
                $sub_order['status_cn'] = $this->_shop_package_status($sub_order['status']);
            } else {
                $sub_order['status_cn'] = $this->_package_status($sub_order['status']);
            }
            $sub_order['detail'] = $order_detail_list_of_key[$sub_order_id];
            $sub_order['shop_name'] = $customer_of_key[$user_id]['shop_name'];
            $sub_order['deliver_fee'] = sprintf("%.2f", $sub_order['deliver_fee'] / 100);
            $sub_order['minus_amount'] = sprintf("%.2f", $sub_order['minus_amount'] / 100);
            $sub_order['total_price'] = sprintf("%.2f", $sub_order['total_price'] / 100);
            $sub_order['final_price'] = sprintf("%.2f", $sub_order['final_price'] / 100);
            $sub_order['deal_price'] = sprintf("%.2f", $sub_order['deal_price'] / 100);
            $sub_order['refuse_price'] = sprintf("%.2f", ($sub_order['refuse_price']-$sub_order['deposit']) / 100);
            $sub_order['actual_minus_amount'] = sprintf("%.2f", $sub_order['actual_minus_amount'] / 100);
            $sub_order['reduction_price'] = sprintf("%.2f", $sub_order['reduction_price'] / 100);
            $sub_order['actual_deliver_fee'] = sprintf("%.2f", $sub_order['actual_deliver_fee'] / 100);
            $sub_order['sign_price'] = sprintf("%.2f", ($sub_order['sign_price']+ $sub_order['deposit']) / 100);
            $sub_order['deposit'] = sprintf("%.2f", $sub_order['deposit'] / 100);
        }
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $sub_order_list
        ));
    }

    /**
     * 按照店铺的纬度获取订单信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_orders_of_store()
    {
        $billing_id = $_POST['id'];
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'start_time',
            'end_time',
            'customer_id'
        ), array(
            'id' => $billing_id
        ));
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime("+1 day", $end_time);
        $customer_id = $billing_info['customer_id'];
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'shop_name',
            'id'
        ), array(
            'id' => $customer_id
        ));
        $user_lists = $this->MCustomer->get_lists(array(
            'id',
            'shop_name'
        ), array(
            'parent_mobile' => $customer_info['mobile']
        ));
        $user_ids = array_column($user_lists, 'id');
        array_push($user_ids, $customer_id);
        array_push($user_lists, $customer_info);
        $user_ids_str = implode(',', $user_ids);
        $closed_status = C('order.status.closed.code');
        $order_sql = "select user_id, SUM(total_price)/100 total_price,SUM(o.deliver_fee)/100 AS deliver_fee,SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100 AS minus_amount,SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100 AS reduction_price,SUM(o.final_price)/100 final_price,SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100 AS actual_deliver_fee,SUM(deal_price)/100 - SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100+ SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100+ SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100 sign_price,
        SUM(total_price) / 100 -
        (
        SUM(deal_price)/100
        - SUM(case o.deal_price when 0 then 0 else o.deliver_fee end)/100
        + SUM(case o.deal_price when 0 then 0 else o.minus_amount end)/100
        + SUM(case o.deal_price when 0 then 0 else o.pay_reduce end)/100
        ) refuse_price,
        SUM(deal_price)/100 deal_price,
        SUM(deposit)/100 deposit
        from t_suborder o
        WHERE deliver_date>= $start_time
        and deliver_date < $end_time
        and user_id in ($user_ids_str)
        and status > $closed_status
        GROUP BY user_id";
        
        $orders = $this->db->query($order_sql)->result_array();
        
        $customer_info_of_key = array();
        foreach ($user_lists as $customer) {
            $customer_info_of_key[$customer['id']] = $customer;
        }
        foreach ($orders as &$order) {
            $order['shop_name'] = $customer_info_of_key[$order['user_id']]['shop_name'];
            $order['total_price'] = sprintf("%.2f", $order['total_price']);
            $order['deal_price'] = sprintf("%.2f", $order['deal_price']);
            $order['refuse_price'] = sprintf("%.2f", ($order['refuse_price']-$order['deposit']));
            $order['final_price'] = sprintf("%.2f", $order['final_price']);
            $order['deliver_fee'] = sprintf("%.2f", $order['deliver_fee']);
            $order['minus_amount'] = sprintf("%.2f", $order['minus_amount']);
            $order['reduction_price'] = sprintf("%.2f", $order['reduction_price']);
            $order['actual_deliver_fee'] = sprintf("%.2f", $order['actual_deliver_fee']);
            $order['sign_price'] = sprintf("%.2f", ($order['sign_price']+$order['deposit']));
            $order['deposit'] = sprintf("%.2f", $order['deposit']);
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $orders
        ));
    }

    /**
     * 按照店铺的纬度获取子订单详细信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_order_detail_of_store()
    {
        $billing_id = $_POST['id'];
        $user_id = $_POST['customer_id'];
        $is_shop = isset($_POST['is_shop']) ? intval($_POST['is_shop']) : 0;
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'start_time',
            'end_time'
        ), array(
            'id' => $billing_id
        ));
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime("+1 day", $end_time);
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'shop_name',
            'id'
        ), array(
            'id' => $user_id
        ));
        
        $suborders = $this->MSuborder->get_lists(array(
            'id',
            'user_id',
            'order_number',
            'status',
            'deliver_fee',
            'minus_amount',
            'total_price',
            'final_price',
            'deliver_date',
            'deal_price',
            'sign_img_url',
            'deposit',
            'total_price -
            (
            deal_price
            - case  deal_price when 0 then 0 else deliver_fee end
            + case  deal_price when 0 then 0 else minus_amount end
            + case  deal_price when 0 then 0 else pay_reduce end
            ) refuse_price',
            '(case deal_price when 0 then 0 else minus_amount end) as  actual_minus_amount',
            '(case deal_price when 0 then 0 else pay_reduce end) as reduction_price',
            '(case deal_price when 0 then 0 else deliver_fee end) as actual_deliver_fee',
            'deal_price-(case deal_price when 0 then 0 else deliver_fee end)+(case deal_price when 0 then 0 else minus_amount end)+(case deal_price when 0 then 0 else pay_reduce end) sign_price'
        ), array(
            'deliver_date >=' => $start_time,
            'deliver_date <' => $end_time,
            'user_id' => $user_id,
            'status >' => C('order.status.closed.code')
        ));
        
        $suborder_ids = array_column($suborders, 'id');
        $order_details = $this->MOrder_detail->get_lists(array(
            '*'
        ), array(
            'in' => array(
                'suborder_id' => $suborder_ids
            )
        ));
        
        $suborders_of_key = array();
        foreach ($suborders as $suborder) {
            $suborder['shop_name'] = $customer_info['shop_name'];
            $suborder['deliver_date'] = date('Y-m-d', $suborder['deliver_date']);
            
            $suborder['total_price'] = sprintf("%.2f", $suborder['total_price'] / 100);
            $suborder['deal_price'] = sprintf("%.2f", $suborder['deal_price'] / 100);
            $suborder['final_price'] = sprintf("%.2f", $suborder['final_price'] / 100);
            $suborder['deliver_fee'] = sprintf("%.2f", $suborder['deliver_fee'] / 100);
            $suborder['minus_amount'] = sprintf("%.2f", $suborder['actual_minus_amount'] / 100);
            $suborder['refuse_price'] = sprintf("%.2f", ($suborder['refuse_price']-$suborder['deposit']) / 100);
            $suborder['reduction_price'] = sprintf("%.2f", $suborder['reduction_price'] / 100);
            $suborder['actual_deliver_fee'] = sprintf("%.2f", $suborder['actual_deliver_fee'] / 100);
            $suborder['sign_price'] = sprintf("%.2f", ($suborder['sign_price']+ $suborder['deposit']) / 100);
            $suborder['deposit'] = sprintf("%.2f", $suborder['deposit'] / 100);
            if ($is_shop) {
                $suborder['status_cn'] = $this->_shop_package_status($suborder['status']);
            } else {
                $suborder['status_cn'] = $this->_package_status($suborder['status']);
            }
            $suborders_of_key[$suborder['id']] = $suborder;
        }
        
        foreach ($order_details as $order_detail) {
            $order_detail['price'] = sprintf("%.2f", $order_detail['price'] / 100);
            $order_detail['sum_price'] = sprintf("%.2f", $order_detail['sum_price'] / 100);
            $order_detail['actual_price'] = sprintf("%.2f", $order_detail['actual_price'] / 100);
            $order_detail['single_price'] = sprintf("%.2f", $order_detail['single_price'] / 100);
            $order_detail['actual_sum_price'] = sprintf("%.2f", $order_detail['actual_sum_price'] / 100);
            
            if (! isset($suborders_of_key[$order_detail['suborder_id']]['detail'])) {
                $suborders_of_key[$order_detail['suborder_id']]["detail"] = array();
            }
            // print_r(json_encode($order_detail['suborder_id']));
            $suborders_of_key[$order_detail['suborder_id']]["detail"][] = $order_detail;
        }
        // print_r(json_encode($suborders_of_key));
        // 按照日期归类订单
        // $orders_of_date = array();
        // foreach ($suborders_of_key as $v) {
        // $orders_of_date[$v['deliver_date']][] = $v;
        // }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => array_values($suborders_of_key)
        ));
    }

    /**
     * 根据订单修改账单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function change_billing_by_order()
    {
        $order_id = $_POST['suborder_id'];
        $order_info = $this->MSuborder->get_one('*', array(
            'id' => $order_id
        ));
        $customer_id = $order_info['user_id'];
        
        // 检查账户类型
        $customer_info = $this->MCustomer->get_one('*', array(
            'id' => $customer_id
        ));
        $account_type = $customer_info['account_type'];
        if ($account_type == C('customer.account_type.child.value')) {
            $parent_mobile = $customer_info['parent_mobile'];
            $parent_customer_info = $this->MCustomer->get_one(array(
                'id'
            ), array(
                'mobile' => $parent_mobile
            ));
            $customer_id = $parent_customer_info['id'];
        }
        
        $deliver_date = $order_info['deliver_date'];
        
        $billing_info = $this->MBilling->get_one(array(
            'status'
        ), array(
            'customer_id' => $customer_id,
            'start_time <= ' => $deliver_date,
            'end_time > ' => $deliver_date - 24 * 60 * 60
        ));
        if (count($billing_info)) {
            if ($billing_info['status'] == C('billing.status.unpay.code')) {
                $billing_id = $billing_info['id'];
                $deal_price = $order_info['deal_price'];
                $this->MBilling->update($billing_id, array(
                    'total_price' => "total_price + $deal_price"
                ));
                return $this->_return_json(array(
                    'status' => C('tips.code.op_success'),
                    'msg' => '已修改'
                ));
            }
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_failed'),
            'msg' => '未修改'
        ));
    }

    /**
     * 按照店铺的纬度获取子订单详细信息(app 使用)
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_store_orders_of_app()
    {
        $billing_id = $_POST['id'];
        $billing_info = $this->MBilling->get_one('*', array(
            'id' => $billing_id
        ));
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime("+1 day", $end_time);
        $customer_id = $billing_info['customer_id'];
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'shop_name',
            'id'
        ), array(
            'id' => $customer_id
        ));
        $customer_mobile = $customer_info['mobile'];
        unset($customer_info['mobile']);
        $children = $this->MCustomer->get_lists(array(
            'id',
            'shop_name'
        ), array('parent_mobile'=> $customer_mobile));

        if(empty($children)) {
            $customer_ids = [$customer_id];
            $children = [$customer_info];
        } else {
            $customer_ids = array_column($children, 'id');
            array_push($customer_ids, $customer_id);
            array_push($children, $customer_info);
        }
        $customer_list_of_key = [];
        foreach ($children as $child) {
            $customer_list_of_key[$child['id']] = $child;
        }
        $suborders = $this->MSuborder->get_lists(array(
            'id',
            'user_id',
            'order_id',
            'order_number',
            'deal_price',
            'final_price'
        ), array(
            'deliver_date >=' => $start_time,
            'deliver_date <' => $end_time,
            'in' => array(
                'user_id' => $customer_ids
            ),
            'status >' => C('order.status.closed.code')
        ));
        
        $new_orders = $this->_set_new_orders($suborders);
        $final_total_price = 0;
        foreach ($suborders as $suborder) {
            $final_total_price += $suborder['deal_price'];
            $user_id = $suborder['user_id'];
            $suborder['deal_price'] = sprintf("%.2f", $suborder['deal_price'] / 100);
            $order_id = $suborder['order_id'];
            $suborder['parent_order_number'] = $new_orders[$order_id];
            $suborder['deal_price'] = sprintf("%.2f", $suborder['deal_price'] / 100);
            $suborder['final_price'] = sprintf("%.2f", $suborder['final_price'] / 100);
            if(!isset($customer_list_of_key[$user_id]['final_total_price'])) {
                $customer_list_of_key[$user_id]['final_total_price'] = 0;
            }
            $customer_list_of_key[$user_id]['final_total_price'] += $suborder['final_price'];
            $customer_list_of_key[$user_id]['final_total_price'] = sprintf('%.2f', $customer_list_of_key[$user_id]['final_total_price']);
            $customer_list_of_key[$suborder['user_id']]['orders'][] = $suborder;
        }
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => array_values($customer_list_of_key),
            'final_total_price' => sprintf('%.2f', $final_total_price / 100),
            'payment_evidence'  => $billing_info['payment_evidence'],
            'payment_status' => $billing_info['status']
        ));
    }

    /**
     * 账单列表(商城使用)
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function shop_billing_list()
    {
        $customer_id = $_POST['customer_id'];
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $start_time = isset($_POST['start_time']) ? intval($_POST['start_time']) : 0;
        $end_time = isset($_POST['end_time']) ? intval($_POST['end_time']) : 0;
        $current_page = isset($_POST['currentPage']) ? intval($_POST['currentPage']) : 1;
        $items_per_page = isset($_POST['itemsPerPage']) ? intval($_POST['itemsPerPage']) : 20;
        $start_point = ($current_page - 1) * $items_per_page;
        $condition = " where 1=1 ";
        $param = array();
        $condition .= " and customer_id = ?";
        $param[] = $customer_id;
        $condition .= " and status > ?";
        $param[] = C('billing.status.invalid.code');
        if ($status) {
            $condition .= " and status = ?";
            $param[] = $status;
        }
        
        $period = "";
        if ($start_time != 0 && $end_time != 0) {
            $period = "($start_time <= end_time and $end_time >= start_time)";
        } else 
            if ($start_time != 0 && $end_time == 0) {
                $period = "($end_time >= start_time)";
            } else 
                if ($start_time == 0 && $end_time != 0) {
                    $period = "($start_time <= end_time)";
                }
        if ($period != "") {
            $condition .= " and $period";
        }
        
        $customer_info = $this->MCustomer->get_one(array(
            'account_type',
            'parent_mobile',
            'mobile'
        ), array(
            'id' => $customer_id
        ));
        $customer_mobile = $customer_info['mobile'];
        $account_type = $customer_info['account_type'];
        if ($account_type == C('customer.account_type.child.value')) {
            $parent_mobile = $customer_info['parent_mobile'];
            $parent_customer_info = $this->MCustomer->get_one(array(
                'id',
                'mobile'
            ), array(
                'mobile' => $parent_mobile
            ));
            $customer_id = $parent_customer_info['id'];
            $customer_mobile = $parent_customer_info['mobile'];
        }

        //获取子账号id
        $user_ids = $this->MCustomer->get_lists(array(
            'id'
        ), array(
            'in' => array(
                'parent_mobile' => $customer_mobile
            )
        ));
        $user_ids = array_column($user_ids, "id");
        //合并子母账号
        array_push($user_ids, $customer_id);
        $billing_count = $this->db->query("select count(id) as total_count from t_billing $condition", $param)->row();
        $total_count = $billing_count->total_count;
        $billing_list = array();
        if (count($total_count)) {
            $billing_list = $this->db->query("select id, theory_start, theory_end, start_time, end_time, status, payment_evidence, total_price from t_billing $condition order by theory_start desc limit $start_point, $items_per_page", $param)->result_array();
            $min_start = 0;
            $max_end = 0;
            
            $counter = 0;
            foreach ($billing_list as $billing) {
                $start_time = $billing['start_time'];
                if ($counter == 0) {
                    $min_start = $start_time;
                    $counter ++;
                } else {
                    if ($min_start > $start_time) {
                        $min_start = $start_time;
                    }
                }
                $end_time = $billing['end_time'];
                if ($max_end < $end_time) {
                    $max_end = $end_time;
                }
            }
            $max_end = strtotime("+1 day", $max_end);
            $order_lists = $this->MSuborder->get_lists(array(
                '*',
                'total_price -
                (
                deal_price
                - case  deal_price when 0 then 0 else deliver_fee end
                + case  deal_price when 0 then 0 else minus_amount end
                + case  deal_price when 0 then 0 else pay_reduce end
                ) refuse_price',
                '(case deal_price when 0 then 0 else minus_amount end) as  actual_minus_amount',
                '(case deal_price when 0 then 0 else pay_reduce end) as reduction_price',
                '(case deal_price when 0 then 0 else deliver_fee end) as actual_deliver_fee',
                'deal_price-(case deal_price when 0 then 0 else deliver_fee end)+(case deal_price when 0 then 0 else minus_amount end)+(case deal_price when 0 then 0 else pay_reduce end) sign_price'
            ), array(
                'deliver_date >=' => $min_start,
                'deliver_date <' => $max_end,
                'in' => array(
                    'user_id' => $user_ids
                ),
                'status != ' => C('order.status.closed.code')
            ));
            
            // 按照日期分组
            $order_group_date = array();
            foreach ($order_lists as &$order) {
                $deliver_time = $order['deliver_date'];
                $deliver_date = Date('Y-m-d', $deliver_time);
                $order['deliver_date'] = $deliver_date;
                $order['deliver_fee'] = sprintf("%.2f", $order['deliver_fee'] / 100);
                $order['minus_amount'] = sprintf("%.2f", $order['actual_minus_amount'] / 100);
                $order['total_price'] = sprintf("%.2f", $order['total_price'] / 100);
                $order['final_price'] = sprintf("%.2f", $order['final_price'] / 100);
                $order['deal_price'] = sprintf("%.2f", $order['deal_price'] / 100);
                $order['refuse_price'] = sprintf("%.2f", ($order['refuse_price']-$order['deposit']) / 100);
                $order['reduction_price'] = sprintf("%.2f", $order['reduction_price'] / 100);
                $order['actual_deliver_fee'] = sprintf("%.2f", $order['actual_deliver_fee'] / 100);
                $order['sign_price'] = sprintf("%.2f", ($order['sign_price']+$order['deposit']) / 100);
                $order['deposit'] = sprintf("%.2f", $order['deposit'] / 100);
                $order['status_cn'] = $this->_shop_package_status($order['status']);
                if (! isset($order_group_date[$deliver_time]['deal_price'])) {
                    $order_group_date[$deliver_time]['deal_price'] = $order['deal_price'];
                } else {
                    $order_group_date[$deliver_time]['deal_price'] += $order['deal_price'];
                }
                $order_group_date[$deliver_time]['deliver_date'] = $deliver_date;
                $order_group_date[$deliver_time]['order_list'][] = $order;
            }
            foreach ($billing_list as &$billing) {
                $stime = $billing['start_time'];
                $etime = $billing['end_time'];
                $etime = strtotime("+1 day", $etime);
                $sub_order_count = 0;
                foreach ($order_group_date as $k => $order_group) {
                    $order_group_deliver = date('Y-m-d', $k);
                    if (($k >= $stime) && ($k < $etime)) {
                        $billing['order'][] = $order_group;
                        $sub_order_count += count($order_group['order_list']);
                        unset($order_group_date[$k]);
                    }
                }
                $billing['sub_order_count'] = $sub_order_count;
                $billing['status_cn'] = $this->_get_shop_billing_status_msg($billing['status']);
                $billing['theory_start'] = date('Y-m-d', $billing['theory_start']);
                $billing['theory_end'] = date('Y-m-d', $billing['theory_end']);
                $billing['start_time'] = date('Y-m-d', $billing['start_time']);
                $billing['end_time'] = date('Y-m-d', $billing['end_time']);
                $billing['total_price'] = sprintf("%.2f", $billing['total_price'] / 100);
            }
        }
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $billing_list,
            'total' => $total_count
        ));
    }

    /**
     * 同意付款(商城使用)
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function shop_agree_pay()
    {
        $billing_id = $_POST['id'];
        $billing_info = $this->MBilling->get_one(array(
            'status',
            'start_time',
            'end_time',
            'customer_id'
        ), array(
            'id' => $billing_id
        ));
        $status_value = $billing_info['status'];
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime('+1 day', $end_time);
        
        // 检查订单状态 是否允许账单变为同意付款
        $customer_id = $billing_info['customer_id'];
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'id'
        ), array(
            'id' => $customer_id
        ));
        $user_lists = $this->MCustomer->get_lists(array(
            'id'
        ), array(
            'parent_mobile' => $customer_info['mobile']
        ));
        $user_ids = array_column($user_lists, 'id');
        array_push($user_ids, $customer_id);
        // 检查订单状态是否全是已完成，不是的话不可同意结款
        $order_list = $this->MSuborder->get_lists(array(
            'id'
        ), array(
            'deliver_date <' => $end_time,
            'deliver_date >=' => $start_time,
            'not_in' => array(
                'status' => array(
                    C('order.status.closed.code'),
                    C('order.status.success.code'),
                    C('order.status.wait_comment.code'),
                    C('order.status.sales_return.code')
                )
            ),
            'in' => array(
                'user_id' => $user_ids
            )
        ));
        if (count($order_list) > 0) {
            return $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '您在本帐期内还有未完成订单， 不可结款'
            ));
        }
        if (intval($status_value) == intval(C("billing.status.unpay.code"))) {
            $this->MBilling->update($billing_id, array(
                'status' => C("billing.status.prepay.code"),
                'updated_time' => time()
            ));
            return $this->_return_json(array(
                'status' => C('tips.code.op_success'),
                'msg' => '修改成功'
            ));
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_failed'),
            'msg' => '请检查账单状态'
        ));
    }

    public function export_billing()
    {
        $billing_id = $_POST['id'];
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'customer_id',
            'theory_start',
            'theory_end',
            'start_time',
            'end_time',
            'status',
            'total_price'
        ), array(
            'id' => $billing_id
        ));
        $billing_info['total_price'] = sprintf("%.2f", $billing_info['total_price'] / 100);
        $billing_info['status_cn'] = $this->_get_shop_billing_status_msg($billing_info['status']);
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        // unset($billing_info['start_time']);
        // unset($billing_info['end_time']);
        $billing_info['theory_start'] = Date('Y-m-d', $billing_info['theory_start']);
        $billing_info['theory_end'] = Date('Y-m-d', $billing_info['theory_end']);
        $end_time = strtotime('+1 day', $end_time);
        $customer_id = $billing_info['customer_id'];
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'shop_name',
            'id'
        ), array(
            'id' => $customer_id
        ));
        $user_lists = $this->MCustomer->get_lists(array(
            'id',
            'shop_name'
        ), array(
            'parent_mobile' => $customer_info['mobile']
        ));
        $user_ids = array_column($user_lists, 'id');
        array_push($user_ids, $customer_id);
        $wanted_field = array(
            'order_number',
            'status',
            'total_price',
            'total_price -
            (
            deal_price
            - case  deal_price when 0 then 0 else deliver_fee end
            + case  deal_price when 0 then 0 else minus_amount end
            + case  deal_price when 0 then 0 else pay_reduce end
            ) refuse_price',
            'deal_price-(case deal_price when 0 then 0 else deliver_fee end)+(case deal_price when 0 then 0 else minus_amount end)+(case deal_price when 0 then 0 else pay_reduce end) sign_price',
            '(case deal_price when 0 then 0 else deliver_fee end) as deliver_fee',
            '((case deal_price when 0 then 0 else minus_amount end) +
            (case deal_price when 0 then 0 else pay_reduce end)) minus_amount',
            'deal_price',
            'deliver_date',
            'deposit'
        );
        
        $order_lists = $this->MSuborder->get_lists($wanted_field, array(
            'deliver_date >=' => $start_time,
            'deliver_date <' => $end_time,
            'in' => array(
                'user_id' => $user_ids
            )
        ));
        
        // 按照日期分组
        $order_group_date = array();
        foreach ($order_lists as &$order) {
            $deliver_time = $order['deliver_date'];
            $deliver_date = Date('Y-m-d', $deliver_time);
            unset($order['deliver_date']);
            $order['status'] = $this->_get_order_status_msg($order['status']);
            $order['deliver_fee'] = sprintf("%.2f", $order['deliver_fee'] / 100);
            $order['minus_amount'] = sprintf("%.2f", $order['minus_amount'] / 100);
            $order['total_price'] = sprintf("%.2f", $order['total_price'] / 100);
            $order['deal_price'] = sprintf("%.2f", ($order['deal_price']) / 100);
            $order['refuse_price'] = sprintf("%.2f", ($order['refuse_price']- $order['deposit']) /100);
            $order['sign_price'] = sprintf("%.2f", ($order['sign_price']+ $order['deposit']) / 100);
            if (! isset($order_group_date[$deliver_time]['deal_price'])) {
                $order_group_date[$deliver_time]['deal_price'] = $order['deal_price'];
            } else {
                $order_group_date[$deliver_time]['deal_price'] += $order['deal_price'];
            }
            $order_group_date[$deliver_time]['deliver_date'] = $deliver_date;
            $order_group_date[$deliver_time]['order_list'][] = $order;
        }
        $order_lists = array_values($order_group_date);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'billing_info' => $billing_info,
            'orders' => $order_lists
        ));
    }

    /**
     * 支付凭证
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function payment_evidence()
    {
        $billing_id = $_POST['id'];
        $evidence = $_POST['evidence'];
        $this->MBilling->update($billing_id, array(
            'payment_evidence' => $evidence,
            'updated_time' => time(),
            'status' => C('billing.status.payed.code')
        ));
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    public function change_billing_cycle()
    {
        $now_date = isset($_POST['now_date']) ? strval($_POST['now_date']) : date('Y-m-d');
        $customer_id = $_POST['customer_id'];
        $billing_cycle = $_POST['billing_cycle'];
        $check_date = $_POST['check_date'];
        $pay_date = $_POST['pay_date'];
        $max_id = $this->MBilling->get_one(array(
            'max(id) as max_id'
        ), array(
            'customer_id' => $customer_id
        ));
        $time = strtotime($now_date);
        $tomorrow = strtotime('+1 day', $time);
        if (isset($max_id['max_id'])) {
            $billing_info = $this->MBilling->get_one('*', array(
                'id' => $max_id['max_id']
            ));
            $theory_start = $billing_info['theory_start'];
            // 判断今天的是否多次改变
            if ($billing_info['start_time'] == $tomorrow) {
                $this->MBilling->update($max_id['max_id'], array(
                    'status' => 0,
                    'updated_time' => time()
                ));
            } else {
                // 终止上一个账期
                $theory_start_time = $billing_info['theory_start'];
                $theory_end_time = $billing_info['theory_end'];
                if ($theory_start_time <= $time && $theory_end_time >= $time) {
                    $start_time = $billing_info['start_time'];
                    $end_time = $billing_info['end_time'];
                    $end_time = strtotime("+1 day", $end_time);
                    $this->MBilling->update($max_id['max_id'], array(
                        'end_time' => $time,
                        'updated_time' => time()
                    ));
                }
            }
        }
        switch ($billing_cycle) {
            case 'month':
                $curr_year = date('Y', $tomorrow);
                $curr_month = date('m', $tomorrow);
                $curr_check_date = strtotime($curr_year . "-" . $curr_month . "-" . $check_date);
                if ($curr_check_date <= $tomorrow) {
                    $curr_check_date = strtotime("+1 month", $curr_check_date);
                }
                $theory_end_time = strtotime("-1 day", $curr_check_date);
                $theory_start_time = strtotime("-1 month +1 day", $theory_end_time);
                $start_time = $tomorrow;
                $end_time = $theory_end_time;
                $pay_time = strtotime("+${pay_date} day", $curr_check_date);
                break;
            
            case 'half_month':
                $check_date_array = explode(",", $check_date);
                $first_check_date = $check_date_array[0];
                $next_check_date = $check_date_array[1];
                $current_day = date("d", $tomorrow);
                $curr_year = date('Y', $tomorrow);
                $curr_month = date('m', $tomorrow);
                $first_check_date_a = strtotime($curr_year . "-" . $curr_month . "-" . $first_check_date);
                $next_check_date_a = strtotime($curr_year . "-" . $curr_month . "-" . $next_check_date);
                if ($current_day < $first_check_date) {
                    $theory_start_time = strtotime('-1 month', $next_check_date_a);
                    $theory_end_time = strtotime("-1 day", $first_check_date_a);
                } else 
                    if ($current_day >= $next_check_date) {
                        $theory_start_time = $next_check_date_a;
                        $theory_end_time = strtotime('+1 month -1 day', $first_check_date_a);
                    } else {
                        $theory_start_time = $first_check_date_a;
                        $theory_end_time = strtotime("-1 day", $next_check_date_a);
                    }
                $start_time = $tomorrow;
                $end_time = $theory_end_time;
                $curr_check_date = strtotime("+1 day", $theory_end_time);
                $pay_time = strtotime("+${pay_date} day", $curr_check_date);
                break;
            
            case 'week':
                $current_week = date('l', $tomorrow);
                if ($this->get_week($current_week) >= $this->get_week($check_date)) {
                    $diff = $this->get_week($current_week) - $this->get_week($check_date);
                    $theory_start_time = strtotime(date('Y-m-d', strtotime("-${diff} day", $tomorrow)));
                } else {
                    $diff = 7 + $this->get_week($current_week) - $this->get_week($check_date);
                    $theory_start_time = strtotime(date('Y-m-d', strtotime("-${diff} day", $tomorrow)));
                }
                $theory_end_time = strtotime('+6 day', $theory_start_time);
                $start_time = $tomorrow;
                $end_time = $theory_end_time;
                $curr_check_date = strtotime("+1 day", $theory_end_time);
                $pay_time = strtotime("+${pay_date} day", $curr_check_date);
                break;
            
            case 'day':
                $start_time = $tomorrow;
                $end_time = $tomorrow;
                $theory_end_time = $end_time;
                $theory_start_time = $start_time;
                $curr_check_date = strtotime("+1 day", $theory_end_time);
                $pay_time = strtotime("+${pay_date} day", $curr_check_date);
        }
        
        if ($billing_cycle != 'none') {
            // used to generate billing num
            // $billing_num_info = $this->MBilling->get_one(array(
            // "max(billing_num) as zero_point"
            // ), array());
            // if (isset($billing_num_info['zero_point']) && (trim($billing_num_info['zero_point'] != ''))) {
            // $start_point = intval($billing_num_info['zero_point']) + 1;
            // } else {
            // $start_point = 1;
            // }
            $createdData = array(
                'start_time' => $start_time,
                'end_time' => $end_time,
                'theory_start' => $theory_start_time,
                'theory_end' => $theory_end_time,
                'status' => - 1,
                'updated_time' => time(),
                'created_time' => time(),
                'billing_cycle' => $billing_cycle,
                'billing_num' => '',
                'customer_id' => $customer_id,
                'total_price' => 0,
                'expire_time' => $pay_time,
                'expire_status' => 0,
                'check_date' => $check_date,
                'pay_date' => $pay_date
            );
            $this->MBilling->create($createdData);
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '修改成功'
        ));
    }

    /**
     * 禁用客户
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @param string $week            
     * @deprecated
     *
     */
    public function forbid_customer()
    {
        $customer_id = $_POST['customer_id'];
        $customer_info = $this->MCustomer->get_one(array(
            'id',
            'mobile'
        ), array(
            'id' => $customer_id
        ));
        $mobile = $customer_info['mobile'];
        if ($this->_can_forbid($customer_id, $mobile)) {
            $this->MCustomer->update_info(array(
                'pre_forbid' => 0,
                'status' => C('customer.status.disabled.code')
            ), array(
                'parent_mobile',
                $mobile
            ));
            $this->MCustomer->update($customer_id, array(
                'pre_forbid' => 0,
                'status' => C('customer.status.disabled.code')
            ));
            return $this->_return_json(array(
                'status' => C('tips.code.op_success'),
                'msg' => '目前为禁用状态'
            ));
        }
        $this->MCustomer->update($customer_id, array(
            'pre_forbid' => 1
        ));
        $this->MCustomer->update_info(array(
            'pre_forbid' => 1
        ), array(
            'parent_mobile' => $mobile
        ));
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_failed'),
            'msg' => '目前为欲禁用状态'
        ));
    }

    /**
     * 导出
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     *       
     */
    public function export_billing_ex()
    {
        $billing_ids = $_POST['billing_ids'];
        //$billing_ids = "1,3";
        $billing_ids_arr = explode(",", $billing_ids);
        $billing_lists = $this->MBilling->get_lists([
            'id',
            'billing_cycle',
            'start_time',
            'end_time',
            'customer_id',
            'status'
        ], [
            'in' => [
                'id' => $billing_ids_arr
            ]
        ]);
        
        $customer_ids = array_column($billing_lists, 'customer_id');
        $customer_ids = array_unique($customer_ids);
        
        list ($customer_name_map, $customer_map, $customer_city_map) = $this->_get_customer_info($customer_ids);
        
        $suborder_ids = [];
        $suborder_numbers = [];
        $suborder_number_map = [];
        
        $wanted_suborder = [
            'id',
            'deliver_date',
            'deliver_fee',
            'deposit',
            'minus_amount',
            'pay_reduce',
            'deal_price',
            'order_number'
        ];
        
        foreach ($billing_lists as &$billing) {
            $customer_id = $billing['customer_id'];
            $start_time = $billing['start_time'];
            $end_time = $billing['end_time'];
            
            $end_time = strtotime('+1 day', $end_time);
            $where = [];
            $where['deliver_date >='] = $start_time;
            $where['deliver_date <'] = $end_time;
            $where['in'] = [
                'user_id' => $customer_map[$customer_id]
            ];
            
            $suborder_lists = $this->MSuborder->get_lists($wanted_suborder, $where);
            $billing['suborder_list'] = $suborder_lists;
            $temp_suborder_ids = array_column($suborder_lists, 'id');
            $suborder_ids = array_merge($suborder_ids, $temp_suborder_ids);
            
            $suborder_numbers = array_column($suborder_lists, 'order_number');
            $suborder_number_map = array_column($suborder_lists, 'order_number', 'id');
        }
        
        $order_detail_of_key = $this->_get_order_details_of_key($suborder_ids);
        
        $rejected_map = $this->_get_rejected_lists($suborder_numbers);
        
        $rejected_map_of_suborder_ids = [];

        foreach ($suborder_number_map as $suborder_number=>$v) {
            if (isset($rejected_map[$suborder_number])) {
                $rejected_map_of_suborder_ids[$id] = $rejected_map[$suborder_number];
            }
        }
        
        foreach ($billing_lists as $key => $billing) {
            $suborder_lists = $billing['suborder_list'];
            foreach ($suborder_lists as &$suborder) {
                $suborder['order_details'] = $order_detail_of_key[$suborder['id']];
                $suborder['deliver_date'] = date('Y-m-d', $suborder['deliver_date']);
                $suborder['deal_price'] = sprintf("%.2f", $suborder['deal_price'] / 100);
                $suborder['minus_amount']  =  sprintf("%.2f" , ($suborder['minus_amount'] + $suborder['pay_reduce'])/100);
                $sum_actual_quantity = 0;
                $sum_actual_price = 0;
                $sum_rejected_quantity = 0;
                $sum_rejected_price = 0;
                
                $rejected_map_of_suborder_id = [];
                if  (isset($rejected_map_of_suborder_ids[$suborder['id']])) {
                    $rejected_map_of_suborder_id = $rejected_map_of_suborder_ids[$suborder['id']];
                }
                
                foreach ($suborder['order_details'] as &$detail) {
                    $sum_actual_price += $detail['actual_sum_price'];
                    $sum_actual_quantity += $detail['actual_quantity'];
                    $detail['rejected_quantity'] = 0.00;
                    $detail['rejected_price'] = 0.00;
                    $detail['rejected_sum_price'] = 0.00;
                    foreach ($rejected_map_of_suborder_id as $rejected_map) {
                        if ($rejected_map['product_id'] == $detail['product_id']) {
                            $detail['rejected_quantity'] = $rejected_map['quantity'];
                            $detail['rejected_price'] = sprintf("%.2f", $rejected_map['price']);
                            $detail['rejected_sum_price'] = sprintf("%.2f", $rejected_map['sum_price']);
                            break;
                        }
                    }
                    $sum_rejected_price += $detail['rejected_sum_price'];
                    $sum_rejected_quantity += $detail['rejected_quantity'];
                }
                $suborder['sum_actual_price'] = sprintf("%.2f", $sum_actual_price);
                $suborder['sum_actual_quantity'] = $sum_actual_quantity;
                $suborder['sum_rejected_quantity'] = $sum_rejected_quantity;
                $suborder['sum_rejected_price'] = sprintf("%.2f", $sum_rejected_price);
                unset($suborder['pay_reduce']);
            }
            $billing_lists[$key]['suborder_list'] = $suborder_lists;
            $billing_lists[$key]['status_cn'] = $this->_get_billing_status_msg($billing['status']);
            $billing_lists[$key]['start_time'] = date('Y-m-d', $billing['start_time']);
            $billing_lists[$key]['end_time'] = date('Y-m-d', $billing['end_time']);
            $billing_lists[$key]['billing_cycle_cn'] = $this->_get_billing_cycle_cn($billing['billing_cycle']);
            $billing_lists[$key]['shop_name'] = $customer_name_map[$billing['customer_id']];
            $billing_lists[$key]['city_cn'] = $customer_city_map[$billing['customer_id']];
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $billing_lists
        ));
    }

    private function _get_rejected_lists($suborder_numbers)
    {
        $rejected_lists = $this->MRejected->get_lists([
            'id',
            'suborder_number'
        ], [
            'in' => [
                'suborder_number' => $suborder_numbers
            ]
        ]);
        $order_number_map = [];
        if (count($rejected_lists) > 0) {
            $rejected_ids = array_column($rejected_lists, 'id');
            $rejected_ids = array_unique($rejected_ids);
            $rejected_map = array_column($rejected_lists, 'order_number', 'id');
            
            $rejected_orders = $this->MRejected_content->get_lists([
                'rejected_id',
                'product_id',
                'quantity',
                'price',
                'sum_price'
            ], [
                'in' => [
                    'rejected_id' => $rejected_ids
                ]
            ]);
            
            $order_number_map = [];
            foreach ($rejected_map as $rejected_id->$order_number) {
                foreach ($rejected_orders as $rejected_order) {
                    if ($rejected_id == $rejected_order['rejected_id']) {
                        $order_number_map[$order_number][] = [
                            'product_id' => $rejected_order['product_id'],
                            'quantity' => $rejected_order['quantity'],
                            'price' => $rejected_order['price'],
                            'sum_price' => $rejected_order['sum_price']
                        ];
                    }
                }
            }
        }
        return $order_number_map;
    }

    /**
     * 获取客户信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     *       
     */
    private function _get_customer_info($customer_ids)
    {
        $customer_info = $this->MCustomer->get_lists([
            'mobile',
            'id',
            'shop_name',
            'province_id'
        ], [
            'in' => [
                'id' => $customer_ids
            ]
        ]);
        $mobiles = array_column($customer_info, 'mobile');
        
        $customer_name_map = array_column($customer_info, "shop_name", "id");
        
        $customer_city_map = [];
        foreach ($customer_info as $customer) {
            $customer_city_map[$customer['id']] = $this->_get_city_cn($customer['province_id']);
        }
        
        $user_lists = $this->MCustomer->get_lists(array(
            'id',
            'shop_name',
            'province_id',
            'parent_mobile'
        ), array(
            'in' => array(
                'parent_mobile' => $mobiles
            )
        ));
        
        foreach ($user_lists as $user) {
            $customer_name_map[$user['id']] = $user['shop_name'];
            $customer_city_map[$user['id']] = $this->_get_city_cn($user['province_id']);
        }
        
        $user_list_of_mobile = [];
        foreach ($user_lists as $user) {
            $user_list_of_mobile[$user['parent_mobile']][] = $user['id'];
        }
        $customer_map = [];
        foreach ($customer_info as $customer) {
            if (isset($user_list_of_mobile[$customer['mobile']])) {
                $customer_map[$customer['id']] = $user_list_of_mobile[$customer['mobile']];
            }
            $customer_map[$customer['id']] = [
                $customer['id']
            ];
        }
        return array(
            $customer_name_map,
            $customer_map,
            $customer_city_map
        );
    }

    private function _get_city_cn($province_id)
    {
        $open_cities = C('open_cities');
        foreach ($open_cities as $city) {
            if (intval($province_id) == intval($city['id'])) {
                return $city['name'];
            }
        }
        return '未知';
    }

    /**
     * 获取子订单详情
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     *       
     */
    private function _get_order_details_of_key($suborder_ids)
    {
        // 获取子订单详情
        $order_detail_fields = [
            'id',
            'category_id',
            'product_id',
            'quantity',
            'price',
            'actual_quantity',
            'actual_price',
            'suborder_id',
            'unit_id',
            'spec',
            'name',
            'actual_sum_price'
        ];
        $order_details = $this->MOrder_detail->get_lists($order_detail_fields, [
            'in' => [
                'suborder_id' => $suborder_ids
            ]
        ]);
        
        $order_detail_category_ids = array_column($order_details, 'category_id');
        $order_detail_category_ids = array_unique($order_detail_category_ids);
        
        $category_map = $this->_get_primary_category_map($order_detail_category_ids);
        
        $order_detail_of_key = [];
        foreach ($order_details as $order_detail) {
            $order_detail['refuse_quantity'] = $order_detail['quantity'] - $order_detail['actual_quantity'];
            $order_detail['refuse_price'] = sprintf("%.2f", $order_detail['refuse_quantity'] * $order_detail['actual_price'] / 100);
            $order_detail['price'] = sprintf("%.2f", $order_detail['price'] / 100);
            $order_detail['actual_price'] = sprintf("%.2f", $order_detail['actual_price'] / 100);
            $order_detail['actual_sum_price'] = sprintf("%.2f", $order_detail['actual_sum_price'] / 100);
            $order_detail['unit_cn'] = $this->_get_unit_cn($order_detail['unit_id']);
            $order_detail['primary_category_cn'] = $category_map[$order_detail['category_id']]['name'];
            $order_detail['primary_category'] = $category_map[$order_detail['category_id']]['id'];
            $order_detail_of_key[$order_detail['suborder_id']][] = $order_detail;
        }
        return $order_detail_of_key;
    }

    /**
     * 获取分类和一级分类名称的映射
     */
    private function _get_primary_category_map($order_detail_category_ids)
    {
        $category_lists = $this->MCategory->get_lists([
            'id',
            'path'
        ], [
            'in' => [
                'id' => $order_detail_category_ids
            ]
        ]);
        $category_map = [];
        $primary_ids = [];
        foreach ($category_lists as $category) {
            $path = $category['path'];
            $temp_ids = explode('.', trim($path, '.'));
            
            $primary_id = $temp_ids[0];
            $category_map[$category['id']] = $primary_id;
            $primary_ids[] = $primary_id;
        }
        
        $primary_category_info = $this->MCategory->get_lists([
            'id',
            'name'
        ], [
            'in' => [
                'id' => $primary_ids
            ]
        ]);
        $primary_category_info_map = array_column($primary_category_info, 'name', 'id');
        foreach ($category_map as $key => $category_id) {
            $category_map[$key] = [
                'name' => $primary_category_info_map[$category_id],
                'id' => $category_id
            ];
        }
        return $category_map;
    }

    protected function _get_unit_cn($unit_id)
    {
        $units = C('unit');
        foreach ($units as $unit) {
            if (intval($unit['id']) == intval($unit_id)) {
                return $unit['name'];
            }
        }
        return '未知';
    }

    protected function _get_billing_cycle_cn($billing_value)
    {
        $billing_cycle = C('customer.billing_cycle');
        foreach ($billing_cycle as $billing) {
            if (strval($billing['value']) == strval($billing_value)) {
                return $billing['name'];
            }
        }
        return '未知';
    }

    /**
     * 获取数字的一周当中的第几天
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @param string $week            
     */
    protected function get_week($week)
    {
        $local_week = array(
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7
        );
        return $local_week[$week];
    }

    /**
     * 账单状态记录器
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    private function _billing_status_logger($id, $content, $author_id, $author_name, $role_name, $role_id, $auto)
    {
        $createdData = array(
            'id' => '',
            'billing_id' => $id,
            'content' => $content,
            'author_id' => $author_id,
            'author_name' => $author_name,
            'role_name' => $role_name,
            'role_id' => $role_id,
            'auto' => $auto,
            'updated_time' => time(),
            'created_time' => time()
        );
        $this->MBilling_log->create($createdData);
    }

    /**
     * 按照时间的纬度获取子订单详细信息(app 使用)
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function get_billing_detail_by_date()
    {
        $return = array(
            'status' => C('tips.code.op_success'),
            'data' => []
        );
        if(empty($_POST['user_id']) || empty($_POST['id'])) {
            $this->_return_json($return);
        }
        $billing_id = $_POST['id'];
        $billing_info = $this->MBilling->get_one(array(
            'id',
            'status',
            'payment_evidence',
            'customer_id',
            'start_time',
            'end_time'
        ), array(
            'id' => $billing_id
        ));
        if(empty($billing_info)) {
            $this->_return_json($return);
        }
        $customer_id = $billing_info['customer_id'];
        if ($_POST['account_type'] == C('customer.account_type.parent.value')) {
            $customer_info = $this->MCustomer->get_one(array(
                'mobile',
                'shop_name',
                'id'
            ), array(
                'id' => $customer_id
            ));
            $customer_mobile = $customer_info['mobile'];
            unset($customer_info['mobile']);
            $children = $this->MCustomer->get_lists(array(
                'id',
                'shop_name'
            ), array(
                'parent_mobile' => $customer_mobile
            ));
            if (count($children) > 0) {
                $customer_ids = array_column($children, 'id');
                array_push($customer_ids, $customer_id);
            }
        }
        if(!isset($customer_ids)) {
            $customer_ids = array($customer_id);
        }
        $start_time = $billing_info['start_time'];
        $end_time = $billing_info['end_time'];
        $end_time = strtotime("+1 day", $end_time);
        $suborders = $this->MSuborder->get_lists(array(
            'id',
            'user_id',
            'order_id',
            'order_number',
            'final_price',
            'deliver_date'
        ), array(
            'deliver_date >=' => $start_time,
            'deliver_date <' => $end_time,
            'in' => array(
                'user_id' => $customer_ids
            ),
            'status >' => C('order.status.closed.code')
        ));
        $new_orders = $this->_set_new_orders($suborders);
        $new_suborders = array();
        $final_total_price = 0;
        foreach ($suborders as $suborder) {
            $final_total_price += $suborder['deal_price'];
            $date = date('Y.m.d', $suborder['deliver_date']);
            $suborder['final_price'] = sprintf("%.2f", $suborder['final_price'] / 100);
            $order_id = $suborder['order_id'];
            $suborder['parent_order_number'] = $new_orders[$order_id];
            if(!isset($new_suborders[$date])) {
                $new_suborders[$date]['final_total_price'] = 0;
                $new_suborders[$date]['date'] = $date;
            } else {
                $new_suborders[$date]['final_total_price'] += $suborder['final_price'];
                $new_suborders[$date]['final_total_price'] = sprintf('%.2f', $new_suborders[$date]['final_total_price']);
            }
            $new_suborders[$date]['list'][] = $suborder;
        }
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'data' => array_values($new_suborders),
            'final_total_price' => sprintf('%.2f', $final_total_price / 100),
            'payment_evidence'  => $billing_info['payment_evidence'],
            'payment_status'    => $billing_info['status']
       ));
    }

    private function _set_new_orders($suborders)
    {
        $order_ids = array_column($suborders, 'order_id');
        $orders = $this->MOrder->get_lists('id, order_number', array(
            'in' => array(
                'id' => $order_ids
            ),
            'id > ' => 0
        ));
        $new_orders = array_column($orders, 'order_number', 'id');
        return $new_orders;
    }

    /**
     * 根据状态码获取状态消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     *       
     * @param int $status_code            
     */
    private function _get_billing_status_msg($status_code)
    {
        $billing_status_list = C('billing.status');
        foreach ($billing_status_list as $billing_status) {
            if ($status_code == trim($billing_status['code'])) {
                return $billing_status['msg'];
            }
        }
    }

    private function _get_shop_billing_status_msg($status_code)
    {
        $billing_status_list = C('billing.shop_status');
        foreach ($billing_status_list as $billing_status) {
            if ($status_code == trim($billing_status['code'])) {
                return $billing_status['msg'];
            }
        }
    }

    /**
     * 获取订单状态中文信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     *       
     * @param int $status_code            
     */
    private function _get_order_status_msg($status_code)
    {
        $order_status_list = C('order.status');
        foreach ($order_status_list as $order_status) {
            if ($status_code == trim($order_status['code'])) {
                return $order_status['msg'];
            }
        }
    }

    /**
     * 更改欲禁用状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @deprecated
     *
     * @param int $customer_id            
     */
    private function _forbid($customer_id)
    {
        $customer_info = $this->MCustomer->get_one(array(
            'mobile',
            'pre_forbid'
        ), array(
            'id' => $customer_id
        ));
        $pre_forbid = $customer_info['pre_forbid'];
        $mobile = $customer_info['mobile'];
        if ($pre_forbid) {
            if ($this->_can_forbid($customer_id, $mobile)) {
                $this->MCustomer->update_info(array(
                    'pre_forbid' => 0,
                    'status' => C('customer.status.disabled.code')
                ), array(
                    'parent_mobile',
                    $mobile
                ));
                $this->MCustomer->update($customer_id, array(
                    'pre_forbid' => 0,
                    'status' => C('customer.status.disabled.code')
                ));
                return true;
            }
        }
        
        return false;
    }

    /**
     * 能付欲禁用
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @deprecated
     *
     * @param int $customer_id            
     */
    private function _can_forbid($customer_id, $mobile)
    {
        $billing_info = $this->MBilling->get_lists(array(
            'id',
            'start_time',
            'end_time'
        ), array(
            'status' => C('billing.status.disabled.code'),
            'customer_id' => $customer_id
        ), array(
            'id' => 'asc'
        ));
        if (count($billing_info) == 1) {
            foreach ($billing_info as $billing) {
                $start_time = $billing['start_time'];
                $end_time = $billing['end_time'];
                $end_time = strtotime("+1 day", $end_time);
            }
            $user_ids = $this->MCustomer->get_lists(array(
                'id'
            ), array(
                'parent_mobile' => $mobile
            ));
            $user_ids = array_column($user_ids, 'id');
            array_push($user_ids, $customer_id);
            $exists_order = $this->MOrder->get_one(array(
                'count(id) as order_count'
            ), array(
                'in' => array(
                    'user_id' => $user_ids
                ),
                'deliver_date >=' => $start_time,
                'deliver_date <' => $end_time,
                'status != ' => C('order.status.closed.code')
            ));
            $exists_order = $exists_order['order_count'];
            if ($exists_order == 0) {
                // 检查是否有没有付账的账单
                $billing_info = $this->MBilling->get_lists(array(
                    'id'
                ), array(
                    'in' => array(
                        'status' => array(
                            C('billing.status.unpay.code'),
                            C('billing.status.prepay.code')
                        )
                    ),
                    'customer_id' => $customer_id
                ));
                if (count($billing_info) == 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 设置账单查询条件
     *
     * @author yugang@dachuwang.com
     * @since 2015-07-25
     */
    private function _set_where_condition()
    {
        $area = isset($_POST['area']) ? intval($_POST['area']) : 0;
        $bd = isset($_POST['bd']) ? intval($_POST['bd']) : 0;
        $billing_cycle = isset($_POST['billing_cycle']) ? trim($_POST['billing_cycle']) : '';
        $expire_status = isset($_POST['expire_status']) ? intval($_POST['expire_status']) : - 1;
        $keyword = isset($_POST['keyword']) ? strval($_POST['keyword']) : null;
        $start_time = isset($_POST['start_time']) ? intval($_POST['start_time']) : 0;
        $end_time = isset($_POST['end_time']) ? intval($_POST['end_time']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : - 1;
        $customer_ids = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : null;
        $condition = ' where 1 = 1 ';
        $param = array();
        
        if ($area != 0) {
            $condition .= " and c1.province_id = ?";
            array_push($param, $area);
        }
        if ($bd != 0) {
            $condition .= " and c1.invite_id = ?";
            array_push($param, $bd);
        }
        if ($billing_cycle != '') {
            $condition .= " and b.billing_cycle = ?";
            array_push($param, $billing_cycle);
        }
        // store_name or mobile or db name support vague query
        if (! is_null($keyword)) {
            $condition .= " and (c1.mobile like ? or  c1.shop_name like ? or c2.name like  ?)";
            array_push($param, "%$keyword%", "%$keyword%", "%$keyword%");
        }
        // is already expire
        if ($expire_status != - 1) {
            $condition .= " and b.expire_status = ?";
            array_push($param, $expire_status);
        }
        
        $period = "";
        if ($start_time != 0 && $end_time != 0) {
            $period = "($start_time <= b.end_time and $end_time >= b.start_time)";
        } else 
            if ($start_time != 0 && $end_time == 0) {
                $period = "($end_time >= b.start_time)";
            } else 
                if ($start_time == 0 && $end_time != 0) {
                    $period = "($start_time <= b.end_time)";
                }
        if ($period != "") {
            $condition .= " and $period";
        }
        
        if ($status != - 1) {
            $condition .= " and b.status = ?";
            array_push($param, $status);
        } else {
            $condition .= " and b.status > " . C('billing.status.invalid.code');
        }
        if ($customer_ids != null) {
            $customer_ids = implode(",", $customer_ids);
            $condition .= " and c1.id in ($customer_ids)";
        }
        
        return [
            'condition' => $condition,
            'param' => $param
        ];
    }

    private function _shop_package_status($status_code)
    {
        $wait_deliver = array(
            2,
            3,
            11,
            12,
            4,
            13,
            14,
            5,
            8,
            100
        );
        $finished = array(
            6,
            7,
            1
        );
        if (in_array($status_code, $wait_deliver)) {
            return "待收货";
        }
        if (in_array($status_code, $finished)) {
            return "已完成";
        }
        return "未知";
    }

    private function _package_status($status_code)
    {
        $wait_deliver = array(
            2,
            3,
            11,
            12,
            4,
            13,
            14,
            5,
            8,
            100
        );
        $finished = array(
            6,
            7,
            1
        );
        if (in_array($status_code, $wait_deliver)) {
            return "未签收";
        }
        if (in_array($status_code, $finished)) {
            return "已签收";
        }
        return "未知";
    }
}
