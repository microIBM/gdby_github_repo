<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Suborder extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MCustomer',
                'MOrder',
                'MOrder_detail',
                'MUser',
                'MWorkflow_log',
                'MRole',
                'MLine',
                'MPromo_event',
                'MPromo_event_rule',
                'MCategory',
                'MPick_task',
                'MDeliver_fee',
                'MLocation',
                'MSuborder',
                'MSku',
            )
        );
        $this->load->library(
            array(
                'form_validation',
            )
        );


        //订单状态和对应中文字典
        $code_with_cn = array_values(C('order.status'));
        $codes        = array_column($code_with_cn, 'code');
        $msg          = array_column($code_with_cn, 'msg');
        $this->_status_dict = array_combine($codes, $msg);

        //deliver的code和相应文字的对应关系
        $code_with_deliver_time = array_values(C('order.deliver_time'));
        $codes                  = array_column($code_with_deliver_time, 'code');
        $msg                    = array_column($code_with_deliver_time, 'msg');
        $this->_deliver_dict    = array_combine($codes, $msg);

        //unit_id  => unit_name
        $unit_config = C('unit');
        $codes       = array_column($unit_config, 'id');
        $msg         = array_column($unit_config, 'name');
        $this->_unit_dict = array_combine($codes, $msg);
        $this->_unit_dict[0] = '无';

        //pay_type_dict
        $pay_type_arr = array_values(C('payment.type'));
        $codes = array_column($pay_type_arr, 'code');
        $msgs = array_column($pay_type_arr, 'msg');
        $this->_pay_type_dict = array_combine($codes, $msgs);

        //pay_status_dict
        $pay_status_arr = array_values(C('payment.status'));
        $codes = array_column($pay_status_arr, 'code');
        $msgs = array_column($pay_status_arr, 'msg');
        $this->_pay_status_dict = array_combine($codes, $msgs);
    }

    /**
     * @description 子订单列表
     * @author caochunhui@dachuwang.com
     */
    public function lists() {
        $page  = $this->get_page();
        $uid   = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
        $where = [];

        //查看特定状态的订单,不传即查看全部
        if(isset($_POST['status']) && $_POST['status'] != -1 && $_POST['status'] != '') {
            if(is_array($_POST['status'])) {
                $where['in']['status'] = $_POST['status'];
            } else {
                $where['status'] = $_POST['status'];
            }
        }

        //查看指定用户的订单或全部
        if($uid > 0) {
            $where['user_id'] = $uid;
        }
        if(!empty($_POST['orderType'])) {
            $where['order_type'] = $_POST['orderType'];
        }

        // 根据城市筛选
        if(!empty($_POST['cityId'])) {
            $where['location_id'] = $_POST['cityId'];
        }
        //查看大厨、大果的订单
        //0 大厨 1 大果
        if(!empty($_POST['site_src'])) {
            $where['site_src'] = $_POST['site_src'];
        }

        // 配送时间筛选
        if(!empty($_POST['startTime'])) {
            $where['deliver_date >='] = $_POST['startTime'] / 1000;
        }
        if(!empty($_POST['endTime'])) {
            $where['deliver_date <='] = $_POST['endTime'] / 1000;
        }
        if(!empty($_POST['deliver_date'])) {
            $where['deliver_date'] = $_POST['deliver_date'];
        }
        if(!empty($_POST['deliver_time'])) {
            $where['deliver_time'] = $_POST['deliver_time'];
        }

        // 客户筛选，根据姓名、手机号或订单号
        if(!empty($_POST['searchValue'])) {
            // 如果输入的为大于11位的数字，按照订单号查询
            if(preg_match("/^\d{12,}$/", $_POST['searchValue'])) {
                $where['like'] = array('order_number' => $_POST['searchValue']);
            }else{
                // 如果输入关键词为数字，则匹配手机号
                if(preg_match("/^\d{11}$/", $_POST['searchValue'])){
                    $where_user['like'] = array('mobile' => $_POST['searchValue']);
                } else if (preg_match("/^\d+$/", $_POST['searchValue'])){
                    $where['id'] = $_POST['searchValue'];
                } else {
                    $where_user['like'] = array('name' => $_POST['searchValue']);
                }
                if(!empty($where_user)) {
                    $user_ids = $this->MCustomer->get_lists('id', $where_user);
                    $user_ids = array_column($user_ids, 'id');
                    if(!empty($user_ids)) {
                        $where['in']['user_id'] = $user_ids;
                    } else { // 如果没有匹配的，直接强制无结果即可
                        $where['id'] = 0;
                    }
                }
            }

        }

        // 根据线路筛选
        if(!empty($_POST['line_id'])) {
            $where['line_id'] = $_POST['line_id'];
        }
        // 根据订单ID筛选
        if(!empty($_POST['order_ids'])) {
            $where['in']['id'] = $_POST['order_ids'];
        }
        // 根据配送单号筛选
        if(!empty($_POST['dist_id'])) {
            $where['dist_id'] = $_POST['dist_id'];
        }
        if(!empty($_POST['dist_ids'])) {
            $where['in']['dist_id'] = $_POST['dist_ids'];
        }
        // 按照分拣单号筛选
        if(!empty($_POST['pick_ids'])) {
            $where['in']['pick_task_id'] = $_POST['pick_ids'];
        }
        // 线路规划只显示未分配生成配送单的订单
        if(!empty($_POST['list_type']) && $_POST['list_type'] == 'distribution') {
            $where['dist_id'] = 0;
        }

        // 排序
        if (!empty($_POST['order_by'])) {
            $order_by = $_POST['order_by'];
        } else {
            $order_by = array('created_time' => 'DESC');
        }

        // 获取订单列表
        $result = $this->MSuborder->get_lists(
            '*',
            $where, $order_by,
            array(), $page['offset'], $page['page_size']
        );

        $total_count = $this->MSuborder->count($where);

        //计算每种状态的订单数目
        //从配置文件里取道所有的code
        $status_dict = array_column(
            array_values(
                C('order.status')
            ),
            'code'
        );

        foreach($status_dict as $v) {
            if($v != -1) {
                $where['status'] = $v;
            }else{
                unset($where['status']);
            }
            $total[$v] = $this->MSuborder->count($where);
        }

        if(!empty($result)) {
            $result = $this->_format_order_list($result);
        }

        // 设置不同订单状态的颜色
        $order_status = array_values(C('order.status'));
        $status_class = array();
        foreach($order_status as $v) {
            $status_class[$v['code']] = $v['class'];
        }
        foreach($result as &$order) {
            $order['class'] = isset($status_class[$order['status']]) ? $status_class[$order['status']] : 'label-info';
        }

        $arr['status'] = C("status.req.success");
        $arr['orderlist'] = $result;
        $arr['total'] = $total;
        $arr['total_count'] = $total_count;
        $this->_return_json($arr);
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 格式化子订单列表
     * @todo 需要参考product的spec来合并属性
     */
    private function _format_order_list($suborder_list = array()) {
        if(empty($suborder_list)) {
            return $suborder_list;
        }

        //批量取出下单用户信息
        $user_ids = array_column($suborder_list, 'user_id');
        $user_ids = array_unique($user_ids);
        $users = $this->MCustomer->get_lists(
            '*',
            [
                'in' => [
                    'id' => $user_ids
                ]
            ]
        );
        $user_ids = array_column($users, 'id');
        $user_map = array_combine($user_ids, $users);
        //批量取出bd和am的信息
        $bd_ids = array_column($users, 'invite_id');
        $am_ids = array_column($users, 'am_id');
        $bd_ids = array_merge($bd_ids, $am_ids);
        $bd_ids = array_unique($bd_ids);
        $bd_ids = array_filter($bd_ids);
        $bd_users = $this->MUser->get_lists(
            'name, mobile, id',
            array(
                'in' => array(
                    'id' => $bd_ids
                )
            )
        );
        $bd_ids = array_column($bd_users, 'id');
        $bd_map = array_combine($bd_ids, $bd_users);

        // 批量取出线路信息
        $line_list = $this->MLine->get_lists('id, name, warehouse_id', array('status' => C('status.common.success')));
        $line_ids = array_column($line_list, 'id');
        $line_names = array_column($line_list, 'name');
        $line_map = array_combine($line_ids, $line_names);
        $warehouse_ids = array_column($line_list, 'warehouse_id');
        $line_to_warehouse = array_combine($line_ids, $warehouse_ids);

        // 批量取出城市信息
        $city_ids = array_column($suborder_list, 'city_id');
        $city_list = $this->MLocation->get_lists('id, name', array('in' => array('id' => $city_ids)));
        $city_dict = array_combine(array_column($city_list, 'id'), array_column($city_list, 'name'));

        // 批量取出订单分拣单号
        $pick_ids = array_column($suborder_list, 'pick_task_id');
        $pick_ids = array_unique(array_filter($pick_ids));
        if(!empty($pick_ids)) {
            $pick_list = $this->MPick_task->get_lists('*', array('in' => array('id' => $pick_ids)));
            $pick_dict = array_combine(array_column($pick_list, 'id'), $pick_list);
        } else {
            $pick_dict = array();
        }

        // 批量取出客户类型
        $customer_types = array_values(C('customer.type'));
        $customer_type_dict = array_combine(array_column($customer_types, 'value'), array_column($customer_types, 'name'));

        //批量取出母订单order_number
        $order_ids = array_column($suborder_list, 'order_id');
        $orders = $this->MOrder->get_lists(
            'id, order_number',
            array(
                'in' => array(
                    'id' => $order_ids
                )
            )
        );
        $order_ids = array_column($orders, 'id');
        $order_numbers = array_column($orders, 'order_number');
        $order_id_to_number = array_combine($order_ids, $order_numbers);

        //批量取出订单详情
        $suborder_ids = array_column($suborder_list, 'id');
        $where = [
            'in' => [ 'suborder_id' => $suborder_ids ]
        ];
        $order_details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );
        $detail_map = [];

        //取净重
        $sku_numbers  = array_column($order_details, 'sku_number');
        $skus = $this->MSku->get_lists(
            'net_weight, sku_number',
            array(
                'in' => array(
                    'sku_number' => $sku_numbers
                )
            )
        );
        $sku_number_to_sku = array_column($skus, NULL, 'sku_number');
        foreach($order_details as &$item) {
            $sku_number = $item['sku_number'];
            if(!empty($sku_number_to_sku[$sku_number])) {
                $net_weight = $sku_number_to_sku[$sku_number]['net_weight'];
                $item['net_weight'] = $net_weight;
            }
            $order_id = $item['suborder_id'];
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
            $item['actual_price'] /= 100;
            $item['actual_sum_price'] /= 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $item['updated_time'] = date('Y/m/d H:i', $item['updated_time']);
            $item['single_price'] /= 100;
            $item['unit_id'] = $this->_unit_dict[$item['unit_id']];
            $item['close_unit'] = $this->_unit_dict[$item['close_unit']];
            $spec = json_decode($item['spec'], TRUE);
            if(!empty($spec)) {
                foreach($spec as $idx => $spec_arr) {
                    if(empty($spec_arr['name']) || empty($spec_arr['val'])) {
                        unset($spec[$idx]);
                    }
                }
                $item['spec'] = !empty($spec) ? array_values($spec) : array();
            } else {
                $item['spec'] = '';
            }
            if(isset($detail_map[$order_id])) {
                $detail_map[$order_id][] = $item;
            } else {
                $detail_map[$order_id] = [
                    $item
                ];
            }
        }
        unset($item);

        // 角色ID和名称字典
        $role_list  = $this->MRole->get_lists('id, name', array('status' => C('status.common.success')));
        $role_ids   = array_column($role_list, 'id');
        $role_names = array_column($role_list, 'name');
        $role_dict  = array_combine($role_ids, $role_names);

        foreach($suborder_list as &$item) {
            //母订单number
            $main_order_id = $item['order_id'];
            $item['main_order_number'] = $order_id_to_number[$main_order_id];
            //价格和时间
            $item['total_price']  = $item['total_price'] / 100;
            $item['deal_price']   = $item['deal_price'] / 100;
            $item['minus_amount'] = $item['minus_amount'] / 100;
            $item['deliver_fee'] = $item['deliver_fee'] / 100;
            $item['deposit'] /= 100;
            $item['neglect_payment'] /= 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $item['final_price'] = $item['final_price'] / 100;
            //$item['pay_reduce'] = $item['pay_reduce'] / 100;
            $deliver_arr          = $this->_deliver_dict;
            $item['deliver_time_real'] = $item['deliver_time'];
            $item['deliver_time'] = isset($deliver_arr[$item['deliver_time']]) ?
                $deliver_arr[$item['deliver_time']] : '';
            $item['deliver_date'] = date('Y/m/d', $item['deliver_date']);
            $item['pick_number'] = isset($pick_dict[$item['pick_task_id']]) ? (C('barcode.prefix.picking') . $pick_dict[$item['pick_task_id']]['pick_number']) : '';
            $item['city_name'] = isset($city_dict[$item['city_id']]) ? $city_dict[$item['city_id']] : '';
            $item['site_name'] = $item['site_src'] == C('site.dachu') ? '大厨' : '大果';

            //用户相关
            $user_id                 = $item['user_id'];
            $order_user              = $user_map[$user_id];
            $item['deliver_addr']    = $order_user['address'];
            $item['mobile']          = $order_user['mobile'];
            $item['shop_name']       = $order_user['shop_name'];
            $item['realname']        = $order_user['name'];
            $item['geo']             = json_encode(['lng' => $order_user['lng'], 'lat' => $order_user['lat']]);
            $item['address']         = $order_user['address'];
            $item['line']            = isset($line_map[$item['line_id']]) ? $line_map[$item['line_id']] : '';
            $line_id = $item['line_id'];
            $item['warehouse_id']    = isset($line_to_warehouse[$line_id]) ? $line_to_warehouse[$line_id] : '';
            $item['customer_type_name'] = isset($customer_type_dict[$order_user['customer_type']]) ? $customer_type_dict[$order_user['customer_type']] : '';

            //支付相关
            $pay_type = $item['pay_type'];
            $pay_status = $item['pay_status'];
            $item['pay_type_cn'] = isset($this->_pay_type_dict[$pay_type]) ? $this->_pay_type_dict[$pay_type] : '';
            $item['pay_status_cn'] = $this->_pay_status_dict[$pay_status];

            //bd信息
            $invite_id = $order_user['invite_id'];
            $bd_info = isset($bd_map[$invite_id]) ? $bd_map[$invite_id] : [];
            $bd_info['role'] = 'BD';
            $item['bd'] = $bd_info;
            $am_info = isset($bd_map[$order_user['am_id']]) ? $bd_map[$order_user['am_id']] : [];
            $am_info['role'] = 'AM';
            $item['am'] = $am_info;
            if($order_user['invite_id'] == C('customer.public_sea_code')) {
                $item['sale'] = ['role' => '公海客户', 'name' => '无对应销售'];
            } elseif ($order_user['status'] == C('customer.status.allocated.code')) {
                $item['sale'] = $am_info;
            } else {
                $item['sale'] = $bd_info;
            }


            //订单状态
            $status            = $item['status'];
            $item['status_cn'] = isset($this->_status_dict[$status]) ? $this->_status_dict[$status] : '';
            $order_id          = $item['id'];
            $item['detail']    = isset($detail_map[$order_id]) ? $detail_map[$order_id] : [];
            // 订单动态
            $log_list = $this->MWorkflow_log->get_lists(
                '*',
                array(
                    'obj_id'    => $item['id'],
                    'edit_type' => C('workflow_log.edit_type.order'),
                    'status'    => C("status.common.success")
                ),
                array(
                    'created_time' => 'ASC'
                )
            );

            foreach ($log_list as &$log) {
                $log['created_time'] = date('Y-m-d H:i:s', $log['created_time']);
                $log['operator_type_cn'] = isset($role_dict[$log['operator_type']]) ? $role_dict[$log['operator_type']] : '';
            }
            unset($log);
            $item['log_list'] = $log_list;
        }
        unset($item);
        return $suborder_list;
    }

    /**
     *
     * @description 子订单纬度的订单详情
     */
    public function info() {
        $where = [];
        if(!empty($_POST['suborder_id'])) {
            $where['id'] = intval($_POST['suborder_id']);
        }

        if(!empty($_POST['order_number'])) {
            $where['order_number'] = $_POST['order_number'];
        }

        if(empty($where)) {
            $arr = array(
                'status' => -1,
                'msg'    => '订单id和订单号中至少需要一个不为空'
            );
            $this->_return_json($arr);
        }

        $suborder = $this->MSuborder->get_one(
            '*',
            $where
        );

        if(empty($suborder)) {
            $res['msg'] = '没有相关的订单信息';
            $this->_return_json($res);
        }

        $suborder = $this->_format_order_list(
            array($suborder)
        );
        $suborder = $suborder[0];
        $arr = array(
            'status' => 0,
            'info'   => $suborder,
        );
        $this->_return_json($arr);

    }

    /*
     * @description 运营添加备注
     * 会把这个备注记录到workflow_log
     */
    public function add_comment() {
        if(empty($_POST['cur'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '用户信息不能为空'
                )
            );
        }
        if(empty($_POST['remark'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '备注不能为空'
                )
            );
        }

        if(empty($_POST['suborder_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '子订单id不能为空'
                )
            );
        }

        $cur = $_POST['cur'];
        $remark = $_POST['remark'];


        $result = $this->MWorkflow_log->record_order_comment($suborder_id, $cur, $remark);
        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => '记录成功',
                'result' => $result
            )
        );
    }


    //获取子订单的log
    private function _get_order_logs($order_id) {
        if(!$order_id) {
            return [];
        }
        $log_list = $this->MWorkflow_log->get_lists('*', array('obj_id' => $order_id, 'edit_type' => C('workflow_log.edit_type.order')), array('created_time' => 'asc'));

        foreach ($log_list as &$log) {
            $log['created_time'] = date('Y-m-d H:i:s', $log['created_time']);
            $log['operator_type_cn'] = isset($this->_role_dict[$log['operator_type']]) ? $this->_role_dict[$log['operator_type']] : '';
        }
        unset($log);

        return $log_list;
    }


    /*
     * @description 设置订单已发货
     * 需要把母订单也设置为已发货
     */
    public function set_status_delivering() {
        if(!isset($_POST['suborder_id']) || intval($_POST['suborder_id']) <= 0) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供子订单id'
            );
            $this->_return_json($arr);
        }

        if(!isset($_POST['cur'])) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供操作者信息'
            );
            $this->_return_json($arr);
        }

        $suborder_id = intval($_POST['suborder_id']);
        $status = C('order.status.delivering.code');
        $cur = $_POST['cur'];
        $remark = empty($_POST['remark']) ? '' : $_POST['remark'];

        $this->db->trans_start();
        $suborder = $this->MSuborder->get_one(
            'order_id',
            array(
                'id' => $suborder_id
            )
        );
        $suborder_update_res = $this->MSuborder->update_info(
            array(
                'status' => $status
            ),
            array(
                'id' => $suborder_id
            )
        );

        //更新母订单的状态也变成已出库
        if(!empty($suborder)) {
            $order_id = $suborder['order_id'];
            $order_update_res = $this->MOrder->update_info(
                array(
                    'status' => $status
                ),
                array(
                    'id' => $order_id
                )
            );
        }

        $this->MWorkflow_log->record_order($suborder_id, $status, $cur, $remark);
        $this->db->trans_complete();
        if($this->db->trans_status() === FALSE) {
            $this->_return_json(['status' => -1, 'msg' => 'set status delivering failed']);
        }

        if(!$suborder_update_res) {
            $arr = [
                'status' => -1,
                'msg'    => '订单状态更新失败'
            ];
            $this->_return_json($arr);
        }

        $arr = [
            'status' => 0,
            'msg'    => '订单更新成功'
        ];
        $this->_return_json($arr);
    }

    /*
     * @description 设置订单已签收
     */
    public function set_status_signed() {
        if(!isset($_POST['suborder_id']) || intval($_POST['suborder_id']) <= 0) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供子订单id'
            );
            $this->_return_json($arr);
        }

        if(!isset($_POST['cur'])) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供操作者信息'
            );
            $this->_return_json($arr);
        }

        $suborder_id = intval($_POST['suborder_id']);
        $status = C('order.status.wait_comment.code');
        $cur = $_POST['cur'];
        $remark = empty($_POST['remark']) ? '' : $_POST['remark'];

        // 更新订单详情
        $order_details = $this->input->post('order_details', TRUE);
        foreach ($order_details as $detail) {
            $data = array();
            $data['actual_price'] = $detail['actual_price'] * 100;
            $data['actual_quantity'] = $detail['actual_quantity'];
            $data['actual_sum_price'] = $detail['actual_sum_price'] * 100;
            $data['status'] = $status;
            $this->MOrder_detail->update_info($data, array('id' => $detail['id']));
        }
        // 更新订单
        $data = array();
        $data['deal_price'] = $this->input->post('deal_price', TRUE) * 100;
        $data['sign_msg'] = $this->input->post('sign_msg', TRUE);
        $data['status'] = $status;
        $suborder_update_res = $this->MSuborder->update_info(
            $data,
            array(
                'id' => $suborder_id
            )
        );
        $suborder_info = $this->MSuborder->get_one('order_id', ['id' => $suborder_id]);

        //设置母订单为已签收
        $order_id = $suborder_info['order_id'];
        $related_suborders = $this->MSuborder->get_lists(
            '*',
            array(
                'order_id' => $order_id
            )
        );

        $signed_flag = TRUE;
        //当所有子订单都在已签收或更加向后的状态，才能把母订单的内部状态改为已签收
        foreach($related_suborders as $related_suborder) {
            $status = $related_suborder['status'];
            if($status != C('order.status.success.code') //回款
                && $status != C('order.status.closed.code') //关闭
                && $status != C('order.status.wait_comment.code') //签收
                && $status != C('order.status.sales_return.code') //退货
              ) {
                  $signed_flag = FALSE;
            }
        }
        if($signed_flag) {
            $this->MOrder->update_info(
                array(
                    'status' => C('order.status.wait_comment.code')
                ),
                array(
                    'id' => $order_id
                )
            );
        }

        //TODO complete_main_order需要拆成三个函数，根据需求自由组合
        //TODO 1.设置母订单完成
        //TODO 2.设置母订单客户侧状态为完成
        //TODO 3.设置母订单内部状态为已签收
        //否则会造成重复判断

        $this->_complete_main_order($order_id);

        $this->MWorkflow_log->record_order($suborder_id, $status, $cur, $remark);

        if(!$suborder_update_res) {
            $arr = [
                'status' => -1,
                'msg'    => '订单状态更新失败'
            ];
            $this->_return_json($arr);
        }

        $arr = [
            'status' => 0,
            'msg'    => '订单更新成功'
        ];
        $this->_return_json($arr);
    }


    /*
     * @description 设置订单已装车
     */
    public function set_status_loading() {
        $res = array('status' => C('status.req.failed'), 'msg' => '');
        if(!isset($_POST['suborder_id']) && ! $_POST['suborder_id']) {
            $res['msg'] = '需要提供子订单id';
            $this->_return_json($res);
        }

        if(!isset($_POST['cur'])) {
            $res['msg'] = '需要提供操作者信息';
            $this->_return_json($res);
        }

        $driver_name = isset($_POST['driver_name']) ? $_POST['driver_name'] : '';
        $driver_mobile = isset($_POST['driver_mobile']) ? $_POST['driver_mobile'] : '';

        $suborder_id = $_POST['suborder_id'];
        $status = C('order.status.loading.code');
        $cur    = empty($_POST['cur']) ? NULL : $_POST['cur'];
        $remark = empty($_POST['remark']) ? '' : $_POST['remark'];

        // 更新订单
        $data['status'] = $status;
        $detail_where = is_array($suborder_id) ? array('in' => array('suborder_id' => $suborder_id)) : array('suborder_id' => $suborder_id);
        $order_where  = is_array($suborder_id) ? array('in' => array('id' => $suborder_id)) : array('id' => $suborder_id);
        // 更新订单详情
        // 事务start
        $this->db->trans_start();
        $this->MOrder_detail->update_info($data, $detail_where);

        $data['driver_mobile'] = $driver_mobile;
        $data['driver_name'] = $driver_name;
        $suborder_update_res = $this->MSuborder->update_info($data, $order_where);
        $this->db->trans_complete();
        if (is_array($suborder_id)) {
            foreach ($suborder_id as $id) {
                $this->MWorkflow_log->record_order($id, $status, $cur, $remark);
            }
        } else {
            $this->MWorkflow_log->record_order($suborder_id, $status, $cur, $remark);
        }
        if($this->db->trans_status() === FALSE) {
            $res['msg'] = '设置订单已装车失败!!';
            $this->_return_json($res);
        }
        $res['status'] = C('status.req.success');
        $this->_return_json($res);
    }

    /*
     * @description 设置订单已退货
     */
    public function set_status_rejected() {
        if(!isset($_POST['suborder_id']) || intval($_POST['suborder_id']) <= 0) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供子订单id'
            );
            $this->_return_json($arr);
        }

        if(!isset($_POST['cur'])) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供操作者信息'
            );
            $this->_return_json($arr);
        }

        $suborder_id = intval($_POST['suborder_id']);
        $status = C('order.status.sales_return.code');
        $cur = $_POST['cur'];
        $remark = empty($_POST['remark']) ? '' : $_POST['remark'];

        $current_suborder = $this->MSuborder->get_one(
            '*',
            array(
                'id' => $suborder_id
            )
        );

        $suborder_update_res = 0;
        if(!empty($current_suborder)) {
            $suborder_update_res = $this->MSuborder->update_info(
                array(
                    'status' => $status
                ),
                array(
                    'id' => $suborder_id
                )
            );

            $this->MWorkflow_log->record_order($suborder_id, $status, $cur, $remark);

            $order_id = $current_suborder['order_id'];
            //拒收需要先更新actual_quantity ,actual_sum_price ,deal_price为0
            $this->MOrder_detail->update_info(
                array(
                    'actual_quantity'  => 0,
                    'actual_sum_price' => 0,
                ),
                array(
                    'suborder_id' => $suborder_id
                )
            );

            $this->MSuborder->update_info(
                array(
                    'deal_price' => 0
                ),
                array(
                    'id' => $suborder_id
                )
            );
            $this->_complete_main_order($order_id);
        }

        if(!$suborder_update_res) {
            $arr = [
                'status' => -1,
                'msg'    => '订单状态更新失败'
            ];
            $this->_return_json($arr);
        }

        $arr = [
            'status' => 0,
            'msg'    => '订单更新成功'
        ];
        $this->_return_json($arr);
    }

    /*
     * @description 设置订单已回款
     *   注意：子订单全部完成时，需要把母订单也置为完成
     *   这是为了给bd算业绩更方便
     */
    public function set_status_success() {
        if(!isset($_POST['suborder_id']) || intval($_POST['suborder_id']) <= 0) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供子订单id'
            );
            $this->_return_json($arr);
        }

        if(!isset($_POST['cur'])) {
            $arr = array(
                'status' => -1,
                'msg'    => '需要提供操作者信息'
            );
            $this->_return_json($arr);
        }

        $suborder_id = intval($_POST['suborder_id']);
        $status = C('order.status.success.code');
        $cur = $_POST['cur'];
        $remark = empty($_POST['remark']) ? '' : $_POST['remark'];

        // 更新订单详情
        $order_details = $this->input->post('order_details', TRUE);
        if(!empty($order_details)) {
            foreach ($order_details as $detail) {
                $data = array();
                $data['actual_price'] = $detail['actual_price'] * 100;
                $data['actual_quantity'] = $detail['actual_quantity'];
                $data['actual_sum_price'] = $detail['actual_sum_price'] * 100;
                $data['status'] = $status;
                $data['complete_time'] = $this->input->server('REQUEST_TIME');
                $this->MOrder_detail->update_info($data, array('id' => $detail['id']));
            }
        }
        // 更新订单
        $data = array();
        $curtime = $this->input->server("REQUEST_TIME");
        $data['deal_price'] = $this->input->post('deal_price', TRUE) * 100;
        $data['sign_msg'] = $this->input->post('sign_msg', TRUE);
        $data['status'] = $status;
        $data['payment_time'] = $curtime;
        $data['complete_time'] = $curtime;
        $suborder_update_res = $this->MSuborder->update_info(
            $data,
            array(
                'id' => $suborder_id
            )
        );


        $this->MWorkflow_log->record_order($suborder_id, $status, $cur, $remark);

        //查看是否还有没完成的子订单，没有的话需要把母订单置为完成状态
        //并且把子订单的deal_price加起来写回到母单去
        $current_suborder = $this->MSuborder->get_one(
            'id, order_id',
            array(
                'id' => $suborder_id
            )
        );
        if(!empty($current_suborder)) {
            $order_id = $current_suborder['order_id'];
            // 更新母订单的最新回款时间
            $this->MOrder->update_info(
                ['payment_time' => $curtime],
                ['id' => $order_id]
            );
            $this->_complete_main_order($order_id);
        }

        if(!$suborder_update_res) {
            $arr = array(
                'status' => -1,
                'msg'    => '订单状态更新失败'
            );
            $this->_return_json($arr);
        }

        $arr = array(
            'status' => 0,
            'msg'    => '订单更新成功'
        );
        $this->_return_json($arr);
    }

    /**
     * @description 设置母订单状态
     */
    private function _complete_main_order($order_id = 0) {
        if(intval($order_id) <= 0) {
            return;
        }

        $suborders = $this->MSuborder->get_lists(
            '*',
            array(
                'order_id' => $order_id
            )
        );

        $deal_price_total = 0;
        //内部状态是否可以置为已完成，如果都回款、关闭或者退货，那么就完成了
        $complete_flag = TRUE;

        //对于客户侧是否可以置为已完成，如果都回款、关闭、退货或者签收，那么就完成了
        $customer_side_complete_flag = TRUE;

        foreach($suborders as $suborder) {
            $status = $suborder['status'];
            $deal_price = $suborder['deal_price'];
            $deal_price_total += $deal_price;

            if($status != C('order.status.success.code') //回款
                && $status != C('order.status.closed.code') //关闭
                && $status != C('order.status.sales_return.code') //退货
              ) {
                  $complete_flag = FALSE;
            }

            if($status != C('order.status.success.code') //回款
                && $status != C('order.status.closed.code') //关闭
                && $status != C('order.status.wait_comment.code') //签收
                && $status != C('order.status.sales_return.code') //退货
              ) {
                  $customer_side_complete_flag = FALSE;
            }

        }

        //1 如果下属子订单都已完成或者回款，或者关闭，那么母订单的状态应该就是完成，并将deal_price写回母订单
        //2 如果不是，那么只将子订单的deal_price写回到母订单，不更改母订单的状态
        if($customer_side_complete_flag) {
            $this->MOrder->update_info(
                array(
                    'customer_side_status'     => C('order.customer_side_status.success.code'),
                ),
                array(
                    'id' => $order_id
                )
            );
        }

        if($complete_flag) {
            $this->MOrder->update_info(
                array(
                    'status'        => C('order.status.success.code'),
                    'deal_price'    => $deal_price_total,
                    'complete_time' => $this->input->server('REQUEST_TIME')
                ),
                array(
                    'id' => $order_id
                )
            );
        } else {
            $this->MOrder->update_info(
                array(
                    'deal_price' => $deal_price_total
                ),
                array(
                    'id' => $order_id
                )
            );
        }
    }

    /**
     * 根据母单ID更新子单状态
     * @author zhangxiao@dachuwang.com
     */
    public function update_by_orderid () {
        try {
            $order_id    = $this->input->post('order_id');
            $pay_status = $this->input->post('pay_status');
            if ($order_id && $pay_status) {
                $sub_ids = $this->MSuborder->get_suborder_ids_by_orderid($order_id);
                if(is_array($sub_ids) && !empty($sub_ids)){
                    $result  = $this->MSuborder->update_batch_orders($sub_ids, $pay_status);
                    if ($result) {
                        return $this->_return_json(array(
                            'status' => 0,
                            'msg'    => '子单更新成功'
                        ));
                    } else {
                        throw new Exception('子单更新失败');
                    }
                }else {
                    throw new Exception('没有子单信息');
                }
            } else {
                throw new Exception('order_id and pay_status required');
            }
        } catch (Exception $e) {
            return $this->_return_json(array(
                'status' => -1,
                'msg'    => $e->getMessage()
            ));
        }

    }

    /**
     * 修改运费
     * 1）修改子单运费deliver_fee和最终价格final_price
     * 2）修改母单运费deliver_fee和最终价格final_price
     * @author yugang@dachuwang.com
     * @since 2015-07-10
     */
    public function change_deliver_fee() {
        // 表单校验
        $this->form_validation->set_rules('suborder_id', '子订单ID', 'required|numeric') ;
        $this->form_validation->set_rules('deliver_fee', '运费', 'required|numeric') ;
        $this->validate_form() ;

        // 1 修改子单运费deliver_fee和最终价格final_price
        $suborder = $this->MSuborder->get_one('id, order_id, deliver_fee, final_price', ['id' => $_POST['suborder_id']]);
        if (empty($suborder) || empty($suborder['deliver_fee'])){
            $this->_return(FALSE, '修改运费失败，该子订单没有运费!');
        }

        $deliver_fee = intval($_POST['deliver_fee']) * 100;
        $final_price = $suborder['final_price'] - ($suborder['deliver_fee'] - $deliver_fee);
        $this->MSuborder->update_info(['deliver_fee' => $deliver_fee, 'final_price' => $final_price], ['id' => $suborder['id']]);
        // 2 修改母单运费deliver_fee和最终价格final_price
        $order = $this->MOrder->get_one('id, deliver_fee, final_price', ['id' => $suborder['order_id']]);
        $final_price = $order['final_price'] - ($order['deliver_fee'] - $deliver_fee);
        $this->MOrder->update_info(['deliver_fee' => $deliver_fee, 'final_price' => $final_price], ['id' => $order['id']]);

        $this->_return(TRUE);
    }

    /**
     * @description 更新子订单的签收图片地址
     */
    public function set_sign_img() {
        if(empty($_POST['suborder_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '子订单id不能为空'
                )
            );
        }

        if(empty($_POST['sign_img'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '签收图片url不能为空'
                )
            );
        }
        $update_res = $this->MSuborder->update_info(
            array(
                'sign_img_url' => $_POST['sign_img']
            ),
            array(
                'id' => intval($_POST['suborder_id'])
            )
        );

        if($update_res) {
            $this->_return_json(
                array(
                    'status' => 0,
                    'msg'    => '更新签收图片地址成功！'
                )
            );
        }

        $this->_return_json(
            array(
                'status' => -1,
                'msg'    => '更新失败'
            )
        );
    }

    public function set_deposit_and_neglect() {
        //suborder_id
        if(!isset($_POST['suborder_id']) || intval($_POST['suborder_id']) == 0) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg' => '子订单id不能为空'
                )
            );

        }
        $suborder_id = intval($_POST['suborder_id']);
        //抹零neglect_payment
        if(!isset($_POST['neglect_payment'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg' => '抹零金额不能为空！'
                )
            );
        }
        $neglect_payment = intval($_POST['neglect_payment'] * 100);
        //押金deposit
        if(!isset($_POST['deposit'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg' => '押金不能为空！'
                )
            );
        }
        $deposit = intval($_POST['deposit'] * 100);
        $update_res = $this->MSuborder->update_info(
            array(
                'neglect_payment' => $neglect_payment,
                'deposit' => $deposit
            ),
            array(
                'id' => $suborder_id
            )
        );
        if($update_res) {
            $this->_return_json(
                array(
                    'status' => 0,
                    'msg' => '更新成功'
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status' => 0,
                    'msg' => '更新失败'
                )
            );
        }
    }
}

/* End of file suborder.php */
/* Location: ./application/controllers/suborder.php */
