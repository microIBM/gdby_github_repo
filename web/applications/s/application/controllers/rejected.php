<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * @author maqiang
 *        
 */
class Rejected extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'MUser',
            'MOrder',
            'MCustomer',
            'MSuborder',
            'MOrder_detail',
            'MSku',
            'MLine',
            'MRejected_content',
            'MRejected_log',
            'MProduct',
            'MRejected',
            'MCategory'
        ));
        $this->load->library(array(
            'redisclient'
        ));
    }

    /**
     * 获取查询条件
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function get_condition()
    {
        $rejected_status = C('rejected.status');
        $area = C('open_cities');
        
        $final_area = array();
        $final_rejected_status = array();
        foreach ($rejected_status as $key => $val) {
            if (intval($val['code']) == - 1) {
                $param = array();
            } else {
                $param = array(
                    'status' => $val['code']
                );
            }
            $tempCountInfo = $this->MRejected->get_one(array(
                'count(id) as total_count'
            ), $param);
            $final_rejected_status[] = array(
                'id' => $val['code'],
                'name' => $val['msg'],
                'total' => $tempCountInfo['total_count']
            );
        }
        $final_area = array_values($area);
        
        foreach ($final_area as $k => $v) {
            if (intval($v['id']) == 0) {
                unset($final_area[$k]);
            }
        }
        
        $refund_methods = C('rejected.refund_methods');
        $refund_methods_map = [];
        foreach ($refund_methods as $refund_method) {
            $refund_methods_map[] = [
                'id' => $refund_method['code'],
                'name' => $refund_method['msg']
            ];
        }
        
        $operator_type = C('user.admingroup.operator.type');
        
        $operatorList = $this->MUser->get_lists(array(
            'id',
            'name'
        ), array(
            'role_id' => $operator_type,
            'status >' => C('status.common.del')
        ));
        
        $bd = $this->MUser->get_lists(array(
            'id',
            'name',
            'province_id'
        ), array(
            'role_id' => C('role.BD.code'),
            'status >' => C('status.common.del')
        ));
        
        $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'rejected_status' => $final_rejected_status,
            'area' => array_values($final_area),
            'operators' => $operatorList,
            'refund_methods' => $refund_methods_map
        ));
    }

    /**
     * 获取列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function lists()
    {
        $page = $this->get_page();
        $rejected_status = isset($_POST['status']) ? intval($_POST['status']) : - 1;
        $keyword = (isset($_POST['keyword']) && $_POST['keyword'] != '') ? trim($_POST['keyword']) : '';
        $area_id = isset($_POST['area_id']) && ! empty($_POST['area_id']) ? intval($_POST['area_id']) : - 1;
        $operator_id = isset($_POST['operator_id']) && ! empty($_POST['operator_id']) ? intval($_POST['operator_id']) : - 1;
        $start_time = isset($_POST['startTime']) && ! empty($_POST['startTime']) ? intval($_POST['startTime']) : 0;
        $end_time = isset($_POST['endTime']) && ! empty($_POST['endTime']) ? intval($_POST['endTime']) : 0;
        $refund_method = isset($_POST['refund_method']) ? intval($_POST['refund_method']) : - 1;
        $currentPage = isset($_POST['currentPage']) && ! empty($_POST['currentPage']) ? intval($_POST['currentPage']) : 1;
        $itemsPerPage = isset($_POST['itemsPerPage']) && ! empty($_POST['itemsPerPage']) ? intval($_POST['itemsPerPage']) : 20;
        $offset = ($currentPage - 1) * $itemsPerPage;
        $condition = " where 1=1";
        $params = array();
        if ($rejected_status != - 1) {
            $condition .= " and rorder.status = ?";
            $params[] = $rejected_status;
        }
        if ($area_id != - 1) {
            $condition .= " and city_id = ?";
            $params[] = $area_id;
        }
        if ($operator_id != - 1) {
            $condition .= " and operator_id = ?";
            $params[] = $operator_id;
        }
        if ($start_time != 0) {
            $condition .= " and created_time  >= ?";
            $params[] = $start_time;
        }
        if ($end_time != 0) {
            $condition .= " and created_time <= ?";
            $params[] = $end_time;
        }
        
        if ($refund_method != - 1) {
            $condition .= " and refund_method = ?";
            $params[] = $refund_method;
        }
        
        if ($keyword != '') {
            $condition .= " and (shop_name like ? or rorder.name like ? or mobile like ? or order_number like ? or suborder_number like ? or rcontent.name like ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $count_sql = "select distinct rorder.id as content FROM t_rejected_order rorder inner join t_rejected_content rcontent  on rorder.id = rcontent.rejected_id $condition";
        $total_count = 0;
        $total_count = $this->db->query($count_sql, $params)->result_array();
        if (count($total_count) > 0) {
            $total_count = count($total_count);
            if ($total_count > 0) {
                $sql = "select distinct rorder.id, rorder.created_time,rorder.operator_name, rorder.status, city_id,rorder.order_number, suborder_number, shop_name, rorder.name,rorder.pay_method, rorder.refund_method,mobile, rejected_sum_price  FROM t_rejected_order rorder inner join t_rejected_content rcontent  on rorder.id = rcontent.rejected_id $condition order by rorder.created_time desc limit $offset, $itemsPerPage ";
                $rejected_list = $this->db->query($sql, $params)->result_array();
                if (count($rejected_list) > 0) {
                    $rejected_ids = array_column($rejected_list, 'id');
                    $contents = $this->_get_rejected_content($rejected_ids);
                    foreach ($rejected_list as &$rejected) {
                        $rejected['created_date'] = date('Y-m-d H:i:s', $rejected['created_time']);
                        $rejected['status_cn'] = $this->_get_status_cn($rejected['status']);
                        $rejected['city_cn'] = $this->_get_city_cn($rejected['city_id']);
                        $rejected['content'] = $contents[$rejected['id']];
                        $rejected['pay_method_cn'] = $this->_get_pay_type($rejected['pay_method']);
                        $rejected['refund_method_cn'] = $this->_get_refund_method_cn($rejected['refund_method']);
                        $rejected['rejected_sum_price'] = sprintf('%.2f', $rejected['rejected_sum_price'] / 100);
                        ;
                    }
                }
            }
        }
        $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => isset($rejected_list) ? $rejected_list : [],
            'total' => $total_count
        ));
    }

    /**
     * 检查账单是否有效
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function view()
    {
        $rejected_id = $_POST['rejected_id'];
        
        $wanted_field = array(
            'id',
            'updated_time',
            'created_time',
            'operator_id',
            'operator_name',
            'status',
            'city_id',
            'line_id',
            'order_number',
            'suborder_number',
            'shop_name',
            'name',
            'mobile',
            'address',
            'deliver_date',
            'bd_id',
            'total_price',
            'minus_amount',
            'deposit',
            'deliver_fee',
            'refuse_price',
            'deal_price',
            'pay_method',
            'reason',
            'refund_method',
            'deposit_bank',
            'bank_number',
            'account_holder',
            'deal_price',
            'deal_method',
            'suggestion',
            'withdraw_storage_number',
            'rejected_sum_price',
            'refund_evidence'
        );
        $rejected_info = $this->MRejected->get_one($wanted_field, array(
            'id' => $rejected_id
        ));
        if (count($rejected_info) == 0) {
            // 返回结果
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '退货退款单无效'
            ));
        }
        $rejected_info['bd_name'] = $this->_get_bd_name($rejected_info['bd_id']);
        $rejected_info['status_cn'] = $this->_get_status_cn($rejected_info['status']);
        $rejected_info['city_cn'] = $this->_get_city_cn($rejected_info['city_id']);
        $rejected_info['line_cn'] = $this->_get_line_cn($rejected_info['line_id']);
        $rejected_info['deliver_date'] = date('Y-m-s H:i:s', $rejected_info['deliver_date']);
        $rejected_info['pay_method_cn'] = $this->_get_pay_type($rejected_info['pay_method']);
        $rejected_info['refund_method_cn']  = $this->_get_refund_method_cn($rejected_info['refund_method']);
        $rejected_info['deal_method_cn'] = $this->_get_deal_method_cn($rejected_info['deal_method']);
        $order_info = $this->_get_order_by_number($rejected_info['order_number']);
        $suborder_info = $this->_get_suborder_by_number($rejected_info['suborder_number']);
        $rejected_info['order_number'] = $rejected_info['order_number'] . " ({$order_info['id']})";
        $rejected_info['suborder_number'] = $rejected_info['suborder_number'] . " ({$suborder_info['id']})";
        
        $rejected_info['total_price'] = sprintf('%.2f', $rejected_info['total_price'] / 100);
        $rejected_info['minus_amount'] = sprintf('%.2f', $rejected_info['minus_amount'] / 100);
        $rejected_info['deposit'] = sprintf('%.2f', $rejected_info['deposit'] / 100);
        $rejected_info['deliver_fee'] = sprintf('%.2f', $rejected_info['deliver_fee'] / 100);
        $rejected_info['refuse_price'] = sprintf('%.2f', $rejected_info['refuse_price'] / 100);
        $rejected_info['deal_price'] = sprintf('%.2f', $rejected_info['deal_price'] / 100);
        $rejected_info['rejected_sum_price'] = sprintf('%.2f', $rejected_info['rejected_sum_price'] / 100);
        $rejected_info['content'] = array_values($this->_get_rejected_content($rejected_id));
        $rejected_info['bd_name'] = $this->_get_bd_name($rejected_info['bd_id']);
        $rejected_info['created_date'] = date('Y-m-s H:i:s', $rejected_info['created_time']);
        $rejected_info['updated_date'] = date('Y-m-s H:i:s', $rejected_info['updated_time']);
        
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'rejected_info' => $rejected_info,
            'reasons' => $this->_get_reasons(),
            'deal_methods' => $this->_get_deal_methods()
        ));
    }

    /**
     * 创建退货单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function create()
    {
        $reason = intval($_POST['reason']);
        $content = $_POST['content'];
        $deposit_bank = isset($_POST['deposit_bank']) ? strval($_POST['deposit_bank']) : '';
        $bank_number = isset($_POST['bank_number']) ? strval($_POST['bank_number']) : '';
        $account_holder = isset($_POST['account_holder']) ? strval($_POST['account_holder']) : '';
        $deal_method = intval($_POST['deal_method']);
        $suggestion = isset($_POST['suggestion']) ? strval($_POST['suggestion']) : '';
        $operator_id = intval($_POST['operator_id']);
        $operator_name = strval($_POST['operator_name']);
        $role_id = intval($_POST['role_id']);
        $role_name = strval($_POST['role_name']);
        
        if (count($content) == 0) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '退货内容不能为空'
            ));
        }
        
        $suborder_id = intval($_POST['suborder_id']);
        $suborder_info = $this->_get_order_info($suborder_id, 0, 1);
        if (count($suborder_info) == 0) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '订单不存在'
            ));
        }
        // 检查数量是否超过最大值 && 判断是否有小数
        $suborder_details = $this->_get_order_detail($suborder_id);
        
        $has_dot = 0;
        foreach ($content as $item) {
            $exists = 0;
            foreach ($suborder_details as $suborder_detail) {
                if (intval($item['id']) == intval($suborder_detail['id'])) {
                    if ($item['quantity'] > $suborder_detail['actual_quantity']) {
                        return $this->_return_json(array(
                            'status' => C('status.req.invalid'),
                            'msg' => '数量超过订购数量'
                        ));
                    }
                    $exists = 1;
                    break;
                }
            }
            if ($exists == 0) {
                return $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '内容无效'
                ));
            }
            if ($has_dot == 0) {
                if (is_float($item['quantity'])) {
                    $deal_method = C('deal_method.loss.code');
                    $has_dot = 1;
                }
            }
        }
        
        $order_info = $this->_get_order_info($suborder_id, 1);
        $user_id = $order_info['user_id'];
        $customer_info = $this->_get_customer_info($user_id);
        $customer_info = $customer_info[0];
        $rejected_sum_price = 0;
        $rejected_contents = $this->_rejected_content($content, $suborder_id);
        
        $time = time();
        $wms_sku_info = [];
        foreach ($rejected_contents as &$rejected_content) {
            $rejected_sum_price += $rejected_content['sum_price'];
            $content['created_time'] = $time;
            $content['updated_time'] = $time;
            $wms_sku_info[] = [
                'code' => $rejected_content['sku_number'],
                'qty' => $rejected_content['quantity']
            ];
        }
        
        $withdraw_storage_number = '';
        if ($deal_method == C('rejected.deal_method.storage.code')) {
            // 获取客退入库单
            $url = rtrim(C('service.wms'), '/')."/Order/guestBackStorage";
            $request_param = [
                'order_number' => $suborder_info['order_number'],
                'sku_info' => $wms_sku_info
            ];
            $return_data = $this->http->query($url, json_encode($request_param));
            $res = json_decode($return_data, TRUE);
            if ($res['status'] == 0) {
                $withdraw_storage_number = $res['data']['in_code'];
            } else {
                return $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => $res['msg']
                ));
            }
        }
        
        if ($deal_method == C('rejected.deal_method.loss.code')) {
            $content = '待财务处理';
            $status = C('rejected.status.wait_finance.code');
        } else {
            $content = '待物流处理';
            $status = C('rejected.status.wait_logistics.code');
        }
        
        $created_fields = array(
            'operator_id' => $operator_id,
            'operator_name' => $operator_name,
            'city_id' => $suborder_info['city_id'],
            'line_id' => $suborder_info['line_id'],
            'suborder_number' => $suborder_info['order_number'],
            'order_number' => $order_info['order_number'],
            'shop_name' => $customer_info['shop_name'],
            'name' => $customer_info['name'],
            'mobile' => $customer_info['mobile'],
            'address' => $customer_info['address'],
            'deliver_date' => $suborder_info['deliver_date'],
            'bd_id' => $customer_info['invite_id'],
            'total_price' => $suborder_info['total_price'],
            'minus_amount' => $suborder_info['actual_minus_amount'] + $suborder_info['reduction_price'],
            'deposit' => $suborder_info['deposit'],
            'withdraw_storage_number' => $withdraw_storage_number,
            'deliver_fee' => $suborder_info['actual_deliver_fee'],
            'refuse_price' => $suborder_info['refuse_price'],
            'deal_price' => $suborder_info['deal_price'],
            'pay_method' => $suborder_info['pay_type'],
            'deal_method' => $deal_method,
            'reason' => $reason,
            'status' => $status,
            'rejected_sum_price' => $rejected_sum_price,
            'refund_method' => $this->_build_pay_refund_relation($suborder_info['pay_type']),
            'created_time' => $time,
            'updated_time' => $time
        );
        
        if ($deposit_bank != '') {
            $created_fields['deposit_bank'] = $deposit_bank;
        }
        if ($bank_number != '') {
            $created_fields['bank_number'] = $bank_number;
        }
        if ($account_holder != '') {
            $created_fields['account_holder'] = $account_holder;
        }
        if ($suggestion != '') {
            $created_fields['suggestion'] = $suggestion;
        }
        
        $rejected_id = $this->MRejected->create($created_fields);
        
        foreach ($rejected_contents as &$rejected_content) {
            $rejected_content['rejected_id'] = $rejected_id;
        }
        
        $this->MRejected_content->create_batch($rejected_contents);
        
        $this->_add_remark($rejected_id, $content, $operator_id, $operator_name, $role_id, $role_name);
        
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '创建成功'
        ));
    }

    /**
     * 创建退货单页面
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function for_create()
    {
        $suborder_id = intval($_POST['suborder_id']);
        $suborder_info = $this->_get_order_info($suborder_id);
        
        if (count($suborder_info) > 0) {
            if ($suborder_info['deal_price'] == 0) {
                return $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '该子订单全部拒收了'
                ));
            }
        } else {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '该子订单无效'
            ));
        }
        
        $suborder_info['city_cn'] = $this->_get_city_cn($suborder_info['city_id']);
        $suborder_info['line_cn'] = $this->_get_line_cn($suborder_info['line_id']);
        $suborder_info['reasons'] = $this->_get_reasons();
        $suborder_info['deal_methods'] = $this->_get_deal_methods();
        $suborder_info['suborder_number'] = $suborder_info['order_number'] . " (${suborder_info['id']})";
        $order_info = $this->_get_order_info($suborder_id, 1);
        $suborder_info['order_number'] = $order_info['order_number'] . " (${order_info['id']})";
        $suborder_info['deliver_date'] = date('Y-m-d', $suborder_info['deliver_date']);
        // get order_detail list
        $suborder_info['content'] = $this->_get_order_detail($suborder_id);
        $customer_info = $this->_get_customer_info($suborder_info['user_id']);
        $suborder_info['shop_name'] = $customer_info[0]['shop_name'];
        $suborder_info['name'] = $customer_info[0]['name'];
        $suborder_info['mobile'] = $customer_info[0]['mobile'];
        $suborder_info['address'] = $customer_info[0]['address'];
        
        list ($bd_name, $bd_mobile) = $this->_get_bd_name($customer_info[0]['invite_id']);
        $suborder_info['bd_name'] = $bd_name . "($bd_mobile)";
        $suborder_info['pay_type_cn'] = $this->_get_pay_type($suborder_info['pay_type']);
        $suborder_info['refund_method_cn'] = $this->_get_refund_method_cn($this->_build_pay_refund_relation($suborder_info['pay_type']));
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'rejected_info' => $suborder_info
        ));
    }

    public function update()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $reason = isset($_POST['reason']) ? intval($_POST['reason']) : - 1;
        $deposit_bank = isset($_POST['deposit_bank']) ? strval($_POST['deposit_bank']) : '';
        $bank_number = isset($_POST['bank_number']) ? strval($_POST['bank_number']) : '';
        $account_holder = isset($_POST['account_holder']) ? strval($_POST['account_holder']) : '';
        $suggestion = isset($_POST['suggestion']) ? strval($_POST['suggestion']) : '';
        $update_field = array();
        if ($reason > 0) {
            $update_field['reason'] = $reason;
        }
        if ($deposit_bank != '') {
            $update_field['deposit_bank'] = $deposit_bank;
        }
        if ($bank_number != '') {
            $update_field['bank_number'] = $bank_number;
        }
        if ($account_holder != '') {
            $update_field['account_holder'] = $account_holder;
        }
        if ($suggestion != '') {
            $update_field['suggestion'] = $suggestion;
        }
        if (count($update_field) == 0) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '请填写要更新的项'
            ));
        }
        $this->MRejected->update($rejected_id, $update_field);
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 上传凭证
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function upload_evidence()
    {
        $rejected_id = $_POST['rejected_id'];
        $evidence = $_POST['evidence'];
        $this->MRejected->update($rejected_id, array(
            'refund_evidence' => $evidence,
            'updated_time' => time()
        ));
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 日志列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function log_list()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $rejected_logs = $this->MRejected_log->get_lists('*', array(
            'rejected_id' => $rejected_id,
            'status >' => C('status.common.del')
        ), array(
            'created_time' => 'desc'
        ), array());
        
        foreach ($rejected_logs as &$rejected_log) {
            $rejected_log['created_date'] = Date('Y-m-d H:i:s', $rejected_log['created_time']);
            $rejected_log['updated_date'] = Date('Y-m-d H:i:s', $rejected_log['updated_time']);
        }
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'list' => $rejected_logs
        ));
    }

    /**
     * 运营人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function operator_change_status()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $operator_id = intval($_POST['operator_id']);
        $operator_name = strval($_POST['operator_name']);
        $role_id = intval($_POST['role_id']);
        $role_name = strval($_POST['role_name']);
        
        // 检查当前状态
        $rejected_info = $this->MRejected->get_one('status', array(
            'id' => $rejected_id
        ));
        $status = $rejected_info['status'];
        if ($status != C('rejected.status.wait_operator.code')) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '您无法更改状态'
            ));
        }
        $next_status = C('rejected.status.finish.code');
        $this->MRejected->update($rejected_id, array(
            'status' => $next_status
        ));
        $content = "已完成";
        $this->_add_remark($rejected_id, $content, $operator_id, $operator_name, $role_id, $role_name);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 物流人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function logistics_change_status()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $operator_id = intval($_POST['operator_id']);
        $operator_name = strval($_POST['operator_name']);
        $role_id = intval($_POST['role_id']);
        $role_name = strval($_POST['role_name']);
        // 检查当前状态
        $rejected_info = $this->MRejected->get_one('status', array(
            'id' => $rejected_id
        ));
        $status = $rejected_info['status'];
        if ($status != C('rejected.status.wait_logistics.code')) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '您无法更改状态'
            ));
        }
        $next_status = C('rejected.status.wait_finance.code');
        $this->MRejected->update($rejected_id, array(
            'status' => $next_status
        ));
        $content = "待财务处理";
        $this->_add_remark($rejected_id, $content, $operator_id, $operator_name, $role_id, $role_name);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 财务人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function finance_change_status()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $operator_id = intval($_POST['operator_id']);
        $operator_name = strval($_POST['operator_name']);
        $role_id = intval($_POST['role_id']);
        $role_name = strval($_POST['role_name']);
        // 检查当前状态
        $rejected_info = $this->MRejected->get_one('status', array(
            'id' => $rejected_id
        ));
        $status = $rejected_info['status'];
        if ($status != C('rejected.status.wait_finance.code')) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '您无法更改状态'
            ));
        }
        $next_status = C('rejected.status.wait_operator.code');
        $this->MRejected->update($rejected_id, array(
            'status' => $next_status
        ));
        $content = "待客服确认";
        $this->_add_remark($rejected_id, $content, $operator_id, $operator_name, $role_id, $role_name);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 关闭退货单状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function close_rejected()
    {
        $rejected_id = intval($_POST['rejected_id']);
        $operator_id = intval($_POST['operator_id']);
        $operator_name = strval($_POST['operator_name']);
        $role_id = intval($_POST['role_id']);
        $role_name = strval($_POST['role_name']);
        $content = strval($_POST['content']);
        // 检查当前状态
        $rejected_info = $this->MRejected->get_one('status,  deal_method,withdraw_storage_number', array(
            'id' => $rejected_id
        ));
        $status = $rejected_info['status'];
        // if (($status = C('rejected.status.finish.code')) or ($status = C('rejected.status.closed.code'))) {
        // return $this->_return_json(array(
        // 'status' => C('status.req.invalid'),
        // 'msg' => '无法关闭退货退款单'
        // ));
        // }
        
        if ($rejected_info['deal_method'] == C('rejected.deal_method.storage.code')) {
            
            if (! $this->_get_stock_status($rejected_info['withdraw_storage_number'])) {
                $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '无法关闭退货退款单: (客退入库单状态不是待收货)'
                ));
            }
            
            if (! $this->_close_stock($rejected_info['withdraw_storage_number'])) {
                $this->_return_json(array(
                    'status' => C('status.req.invalid'),
                    'msg' => '无法关闭退货退款单:'
                ));
            }
        }
        
        $next_status = C('rejected.status.closed.code');
        $this->MRejected->update($rejected_id, array(
            'status' => $next_status
        ));
        $content = "已关闭(关闭原因:$content)";
        $this->_add_remark($rejected_id, $content, $operator_id, $operator_name, $role_id, $role_name);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 增加备注
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function add_remark()
    {
        $rejected_id = $_POST['rejected_id'];
        $content = $_POST['content'];
        $author_id = $_POST['operator_id'];
        $author_name = $_POST['operator_name'];
        $role_name = $_POST['role_name'];
        $role_id = $_POST['role_id'];
        $this->_add_remark($rejected_id, $content, $author_id, $author_name, $role_id, $role_name);
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'msg' => '增加成功'
        ));
    }

    /**
     * 导出
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function export()
    {
        $ids = $_POST['ids'];
        if (count($ids) == 0) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '请选择要导出的退货退款单'
            ));
        }
        $where['in'] = array(
            'id' => $ids
        );
        $rejeced_lists = $this->MRejected->get_lists(array(
            'id',
            'mobile',
            'created_time',
            'line_id',
            'shop_name',
            'name',
            'mobile',
            'address',
            'order_number',
            'suborder_number',
            'reason',
            'suggestion'
        ), $where, array(
            'created_time' => 'desc'
        ));
        if (count($rejeced_lists) == 0) {
            return $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '请使用正确的数据'
            ));
        }
        $mobiles = array_column($rejeced_lists, 'mobile');
        $rejeced_ids = array_column($rejeced_lists, 'id');
        $customer_list = $this->_get_customer_info($mobiles, 1);
        $rejected_contents = $this->_get_rejected_content($rejeced_ids);
        $customer_list_of_mobile = [];
        foreach ($customer_list as $customer) {
            $customer_list_of_mobile[$customer['mobile']] = array(
                'recieve_name' => $customer['recieve_name'],
                'recieve_mobile' => $customer['recieve_mobile']
            );
        }
        
        foreach ($rejeced_lists as &$rejected) {
            $customer = $customer_list_of_mobile[$rejected['mobile']];
            $rejected['recieve_name'] = $customer['recieve_name'];
            $rejected['recieve_mobile'] = $customer['recieve_mobile'];
            $rejected['content'] = $rejected_contents[$rejected['id']];
            $rejected['reason_cn'] = $this->_get_reason_cn($rejected['reason']);
            $rejected['line_cn'] = $this->_get_line_cn($rejected['line_id']);
            $rejected['created_time'] = date('Y-m-d H:i:s', $rejected['created_time']);
        }
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $rejeced_lists
        ));
    }

    protected function _build_pay_refund_relation($pay_method)
    {
        $relation = [
            C('payment.type.weixin.code') => C('rejected.refund_methods.weixin.code'),
            C('payment.type.offline.code') => C('rejected.refund_methods.bank.code'),
            C('payment.type.bill_pay.code') => C('rejected.refund_methods.bank.code')
        ];
        if  (isset($relation[$pay_method])){
            return  $relation[$pay_method];
        }
        return  "-1";
    }

    protected function _get_refund_method_cn($refund_type)
    {
        $refund_methods = C('rejected.refund_methods');
        foreach ($refund_methods as $refund_method) {
            if (intval($refund_type) == $refund_method['code']) {
                return $refund_method['msg'];
            }
        }
        return "未知";
    }

    protected function _get_order_by_number($order_number)
    {
        $order_info = $this->MOrder->get_one('*', [
            'order_number' => $order_number,
            'status > ' => C('order.status.closed.code')
        ]);
        return $order_info;
    }

    protected function _get_suborder_by_number($suborder_number)
    {
        $suborder_info = $this->MSuborder->get_one('*', [
            'order_number' => $suborder_number,
            'status > ' => C('order.status.closed.code')
        ]);
        return $suborder_info;
    }

    protected function _close_stock($withdraw_storage_number)
    {
        $url = rtrim(C('service.wms'), '/')."/StockIn/closeStockIn";
        $request_param = [
            'in_code' => $withdraw_storage_number
        ];
        $return_data = $this->http->query($url, json_encode($request_param));
        $res = json_decode($return_data, TRUE);
        if ($res['status'] == 0) {
            return true;
        }
        return false;
    }

    protected function _get_stock_status($withdraw_storage_number)
    {
        // 获取客退入库单
      $url = rtrim(C('service.wms'), '/')."/StockIn/getStockInStatus";
        $request_param = [
            'in_code' => $withdraw_storage_number
        ];
        $return_data = $this->http->query($url, json_encode($request_param));
        $res = json_decode($return_data, TRUE);
        if ($res['status'] == 0) {
            $status = $res['data']['status'];
            if (strval($status) == 'unreceived') {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取退货原因列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_reasons()
    {
        $reasons = C('rejected.reason');
        $final_reasons = array();
        foreach ($reasons as $key => $val) {
            $final_reasons[] = array(
                'id' => $val['code'],
                'name' => $val['msg']
            );
        }
        return $final_reasons;
    }

    /**
     * 获取退货原因中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_reason_cn($reason_id)
    {
        $reasons = C('rejected.reason');
        $final_reasons = array();
        foreach ($reasons as $key => $val) {
            if (intval($val['code']) == $reason_id) {
                return $val['msg'];
            }
        }
    }

    /**
     * 获取退货处理方式列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_deal_methods()
    {
        $deal_methods = C('rejected.deal_method');
        $final_deal_methods = array();
        foreach ($deal_methods as $key => $val) {
            $final_deal_methods[] = array(
                'id' => $val['code'],
                'name' => $val['msg']
            );
        }
        return $final_deal_methods;
    }

    /**
     * 获取状态中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_status_cn($status)
    {
        $rejected_status = C('rejected.status');
        foreach ($rejected_status as $val) {
            if (intval($val['code']) == intval($status)) {
                return $val['msg'];
            }
        }
        return "未知状态";
    }

    /**
     * 获取城市中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_city_cn($city_id)
    {
        $city = C('open_cities');
        foreach ($city as $val) {
            if ($val['id'] == $city_id) {
                return $val['name'];
            }
        }
        return "未知城市";
    }

    /**
     * 获取线路中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_line_cn($line_id)
    {
        $line_info = $this->MLine->get_one(array(
            'name'
        ), array(
            'id' => intval($line_id)
        ));
        if (count($line_info) > 0) {
            return $line_info['name'];
        }
        return "未知";
    }

    /**
     * 获取支付方式中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_pay_type($pay_id)
    {
        $pay_types = C('payment.pay_types');
        foreach ($pay_types as $pay_type) {
            if (intval($pay_type['code']) == intval($pay_id)) {
                return $pay_type['msg'];
            }
        }
        return "未知支付方式";
    }

    /**
     * 退货处理方式中文消息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_deal_method_cn($deal_method)
    {
        $deal_methods = C('rejected.deal_method');
        foreach ($deal_methods as $val) {
            if (intval($val['code']) == intval($deal_method)) {
                return $val['msg'];
            }
        }
        return "处理方式无效";
    }

    /**
     * 获取退货单详情
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_rejected_content($rejected_ids)
    {
        $contents = $this->MRejected_content->get_lists(array(
            'category_id',
            'name',
            'sku_number',
            'price',
            'quantity',
            'sum_price',
            'rejected_id'
        ), array(
            'in' => array(
                'rejected_id' => $rejected_ids
            ),
            'status >' => C('status.common.del')
        ));
        
        $categorys_ids = array_column($contents, "category_id");
        $categorys_ids = array_unique($categorys_ids);
        $categorys_lists = $this->_get_category_name($categorys_ids);
        
        $final_contents = array();
        foreach ($contents as $item) {
            $item['price'] = sprintf('%.2f', $item['price'] / 100);
            $item['sum_price'] = sprintf('%.2f', $item['sum_price'] / 100);
            $item['category_name'] = $categorys_lists[$item['category_id']];
            $final_contents[$item['rejected_id']][] = $item;
        }
        return $final_contents;
    }

    /**
     * 获取订单信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_order_info($suborder_id, $is_parent = 0, $divison = 0)
    {
        $suborder_info = $this->MSuborder->get_one(array(
            'id,order_number,user_id, status, created_time, updated_time, total_price, deal_price, city_id, deliver_time, deliver_date, line_id, final_price,pay_type,order_id, deposit',
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
            'deal_price-(case deal_price when 0 then 0 else deliver_fee end)+(case deal_price when 0 then 0 else minus_amount end)+(case deal_price when 0 then 0 else minus_amount end) sign_price'
        ), array(
            'id' => $suborder_id,
            'in' => array(
                'status' => array(
                    C('order.status.success.code'),
                    C('order.status.wait_comment.code')
                )
            )
        ));
        if (count($suborder_info) > 0) {
            if ($is_parent == 0) {
                if ($divison == 0) {
                    $suborder_info['total_price'] = sprintf("%.2f", $suborder_info['total_price'] / 100);
                    $suborder_info['deal_price'] = sprintf("%.2f", $suborder_info['deal_price'] / 100);
                    $suborder_info['final_price'] = sprintf("%.2f", $suborder_info['final_price'] / 100);
                    $suborder_info['minus_amount'] = sprintf("%.2f", $suborder_info['actual_minus_amount'] / 100);
                    $suborder_info['refuse_price'] = sprintf("%.2f", $suborder_info['refuse_price'] / 100);
                    $suborder_info['reduction_price'] = sprintf("%.2f", $suborder_info['reduction_price'] / 100);
                    $suborder_info['deliver_fee'] = sprintf("%.2f", $suborder_info['actual_deliver_fee'] / 100);
                    $suborder_info['sign_price'] = sprintf("%.2f", $suborder_info['sign_price'] / 100);
                    $suborder_info['deposit'] = sprintf("%.2f", $suborder_info['deposit'] / 100);
                    unset($suborder_info['actual_deliver_fee']);
                    unset($suborder_info['actual_minus_amount']);
                    unset($suborder_info['final_price']);
                }
                return $suborder_info;
            }
            $order_id = $suborder_info['order_id'];
            $order_info = $this->MOrder->get_one('*', array(
                'id' => $order_id
            ));
            return $order_info;
        } else {
            return [];
        }
    }

    /**
     * 获取客户列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_customer_info($customer_info, $is_mobile = 0)
    {
        $conditon = array();
        if (is_array($customer_info)) {
            if ($is_mobile == 0) {
                $conditon['in']['id'] = $customer_info;
            } else {
                $conditon['in']['mobile'] = $customer_info;
            }
        } else {
            if ($is_mobile == 0) {
                $conditon['id'] = $customer_info;
            } else {
                $conditon['mobile'] = $customer_info;
            }
        }
        
        $customer_info = $this->MCustomer->get_lists('*', $conditon);
        return $customer_info;
    }

    /**
     * 增加备注
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _add_remark($rejected_id, $content, $author_id, $author_name, $role_id, $role_name)
    {
        $createdData = array(
            'id' => '',
            'rejected_id' => $rejected_id,
            'content' => $content,
            'author_id' => $author_id,
            'author_name' => $author_name,
            'role_name' => $role_name,
            'role_id' => $role_id,
            'updated_time' => time(),
            'created_time' => time()
        );
        $this->MRejected_log->create($createdData);
        
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '增加备注成功'
        ));
    }

    /**
     * 组装退货单详情
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _rejected_content($contents)
    {
        $createdList = array();
        $ids = array_column($contents, 'id');
        
        $order_detail_list = $this->MOrder_detail->get_lists('*', array(
            'in' => array(
                'id' => $ids
            )
        ));
        $order_detail_list_of_key = array();
        foreach ($order_detail_list as $order_detail) {
            $order_detail_list_of_key[$order_detail['id']] = $order_detail;
        }
        $product_ids = array_column($order_detail_list, 'product_id');
        $product_lists = $this->MProduct->get_lists('*', array(
            'in' => array(
                'id' => $product_ids
            )
        ));
        
        $product_lists_of_key = array();
        foreach ($product_lists as $product) {
            $product_lists_of_key[$product['id']] = $product;
        }
        
        foreach ($contents as &$content) {
            $order_detail = $order_detail_list_of_key[$content['id']];
            $product_info = $product_lists_of_key[$content['product_id']];
            $content['product_number'] = $product_info['product_number'];
            $content['category_id'] = $order_detail['category_id'];
            $content['name'] = $order_detail['name'];
            $content['sku_number'] = $order_detail['sku_number'];
            $content['price'] = $order_detail['actual_price'];
            $content['sum_price'] = $content['quantity'] * $content['price'];
            $content['spec'] = $order_detail['spec'];
            $content['status'] = C('status.common.success');
            unset($content['id']);
        }
        return $contents;
    }

    /**
     * 获取订单详情
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_order_detail($suborder_id)
    {
        $order_detail_list = $this->MOrder_detail->get_lists(array(
            'id',
            'product_id',
            'name',
            'actual_quantity',
            'actual_price'
        ), array(
            'suborder_id' => $suborder_id
        ));
        foreach ($order_detail_list as &$order_detail) {
            if (intval($order_detail['actual_quantity']) == 0) {
                unset($order_detail);
                continue;
            }
            $order_detail['actual_price'] = sprintf("%.2f", $order_detail['actual_price'] / 100);
        }
        
        return $order_detail_list;
    }

    /**
     * 获取bd name, mobile
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    protected function _get_bd_name($bd_id)
    {
        $bd_info = $this->MUser->get_one(array(
            'name',
            'mobile'
        ), array(
            'id' => $bd_id
        ));
        if (count($bd_info) > 0) {
            return array_values($bd_info);
        } else {
            return [];
        }
    }

    private function _get_category_name($category_ids)
    {
        $category_list = $this->MCategory->get_lists(array(
            'id',
            'name'
        ), array(
            'in' => array(
                'id' => $category_ids
            )
        ));
        $category_list_of_key = array();
        foreach ($category_list as $category) {
            $category_list_of_key[$category['id']] = $category['name'];
        }
        return $category_list_of_key;
    }
}
