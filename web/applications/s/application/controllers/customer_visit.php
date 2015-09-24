<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * @author maqiang
 *        
 */
class Customer_visit extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'MCustomer_visit',
            'MVisit_suggestion',
            'MVisit_category',
            'MCategory',
            'MPotential_customer'
        ));
        $this->load->library(array(
            'form_validation'
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
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->validate_form();
        
        $bd_id = $_POST['bd_id'];
        $start_time = isset($_POST['startTime']) && trim($_POST['startTime']) != '' ? intval($_POST['startTime']) : 0;
        $end_time = isset($_POST['endTime']) && trim($_POST['endTime']) != '' ? ($_POST['endTime']) : 0;
        $wanted_fields = [
            'id',
            'shop_name',
            'visit_date',
            'user_id',
            'is_potential',
            'status'
        ];
        
        $where = [];
        $where['bd_id'] = $bd_id;
        $where['status >'] = C('customer_visit.status.invalid.code');
        if ($start_time != 0) {
            $where['visit_date >='] = $start_time;
        }
        if ($end_time != 0) {
            $where['visit_date <='] = $end_time;
        }
        
        $visit_count = $this->MCustomer_visit->count($where);
        
        $visit_list = [];
        if ($visit_count > 0) {
            $visit_list = $this->MCustomer_visit->get_lists($wanted_fields, $where, [
                'visit_date' => 'desc'
            ], [], $page['offset'], $page['page_size']);
            foreach ($visit_list as &$visit) {
                $visit['visit_date'] = date('Y-m-d', $visit['visit_date']);
                $visit['status_cn'] = $this->_get_status_cn($visit['status']);
            }
        }
        
        $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $visit_list,
            'total' => $visit_count
        ));
    }

    /**
     * 拜访详情页
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function view()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->validate_form();
        $visit_id = $_POST['visit_id'];
        $bd_id = $_POST['bd_id'];
        if (! $this->_check_valid($visit_id, $bd_id)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $visit_info = $this->_get_visit_info($visit_id);
        
        if (! count($visit_info)) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '请填写完整必填的信息'
            ));
        }
        $visit_info['visit_date'] = date('Y-m-d', $visit_info['visit_date']);
        $category_list = $this->_get_visit_category($visit_id);
        
        $categorys = $this->_get_category_names($category_list);
        $suggestions = $this->_get_visit_suggestion($visit_id);
        
        $visit_info['focus_categories'] = $categorys;
        $visit_info['suggestion_type'] = $suggestions;
        
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'info' => $visit_info
        ));
    }

    /**
     * 进店拜访页面
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function for_visit()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('is_potential', 'is_potential', 'required|regex_math[/^[01]$/]');
        $this->validate_form();
        $user_id = $_POST['user_id'];
        $bd_id = $_POST['bd_id'];
        $is_potential = $_POST['is_potential'];
        
        $customer_info = $this->_get_customer_info($user_id, $is_potential);
        $data = [
            'shop_name' => $customer_info['shop_name'],
            'user_id' => $customer_info['id']
        ];
        
        if (isset($_POST['visit_id']) && ($_POST['visit_id'] != 0)) {
            if (! $this->_check_valid(intval($_POST['visit_id']), $bd_id)) {
                $this->_return_json(array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '没有权限访问'
                ));
            }
            $visit_info = $this->_get_visit_info(intval($_POST['visit_id']));
            $data['visit_date'] = date('Y-m-d', $visit_info['visit_date']);
            $data['visit_id'] = intval($_POST['visit_id']);
        }
        
        $categories = $this->_get_category_lists();
        $suggestion_list = $this->_get_suggestion_lists();
        
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'focus_categories' => $categories,
            'suggestions' => $suggestion_list,
            'data' => $data
        ));
    }

    /**
     * 创建拜访
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function create()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('type', 'type', 'required|regex_match[/^[01]$/]');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('is_potential', 'is_potential', 'required|regex_match[/^[01]$/]');
        if (isset($_POST['type']) && intval($_POST['type']) == 0) {
            $this->form_validation->set_rules('visit_date', 'visit_date', 'required|integer');
            $this->validate_form();
            $visit_id = $this->_create_visit_plan($_POST['bd_id'], $_POST['visit_date'], $_POST['user_id'], $_POST['is_potential']);
        } else {
            $remarks = '';
            $this->form_validation->set_rules('focus_category', 'focus_category', 'required|regex_match[/^\d+(\,\d+)*$/]');
            $this->form_validation->set_rules('suggestion_type', 'suggestion_type', 'required|regex_match[/^\d+(\,\d+)*$/]');
            if (isset($_POST['remarks']) && $_POST['remarks'] != '') {
                $this->form_validation->set_rules('remarks', 'remarks', 'max_length[100]');
                $remarks = $_POST['remarks'];
            }
            $this->validate_form();
            $visit_id = $this->_create($_POST['bd_id'], $_POST['user_id'], $_POST['focus_category'], $_POST['suggestion_type'], $remarks, $_POST['is_potential']);
        }
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '创建成功',
            'visit_id' => $visit_id
        ));
    }

    /**
     * 更新拜访
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function update()
    {
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('focus_category', 'focus_category', 'required|regex_match[/\d+(\,\d+)*/]');
        $this->form_validation->set_rules('suggestion_type', 'suggestion_type', 'required|regex_match[/\d+(\,\d+)*/]');
        if (isset($_POST['remarks']) && trim($_POST['remarks']) != '') {
            $this->form_validation->set_rules('remarks', 'remarks', 'required|max_length[100]');
        }
        
        $this->validate_form();
        
        $visit_id = $_POST['visit_id'];
        $bd_id = $_POST['bd_id'];
        if (! $this->_check_valid($visit_id, $bd_id)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $focus_category = $_POST['focus_category'];
        $suggestion_type = $_POST['suggestion_type'];
        $remarks = isset($_POST['remarks']) && trim($_POST['remarks']) != '' ? trim($_POST['remarks']) : '';
        
        $invit_info = $this->_get_visit_info($visit_id);
        
        if (count($invit_info) == 0) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '找不到拜访信息'
            ));
        }
        
        $time = date('Y-m-d', time());
        if (date('Y-m-d', $invit_info['visit_date']) != $time) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '拜访时间不是今日'
            ));
        }
        
        $status = $invit_info['status'];
        if ($status != C('customer_visit.status.plan.code')) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '无法更新拜访信息'
            ));
        }
        $this->_update($visit_id, $focus_category, $suggestion_type, $remarks);
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '更新成功'
        ));
    }

    public function update_remarks()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('remarks', 'remarks', 'required|max_length[100]');
        $this->validate_form();
        $visit_id = $_POST['visit_id'];
        $remarks = $_POST['remarks'];
        $bd_id = $_POST['bd_id'];
        if (! $this->_check_valid($visit_id, $bd_id)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $visit_info = $this->_get_visit_info($visit_id);
        
        $status = $visit_info['status'];
        if ($status != C('customer_visit.status.finished.code')) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '无法更新拜访信息'
            ));
        }
        $this->MCustomer_visit->update($visit_id, [
            'remarks' => $remarks,
            'updated_time' => time()
        ]);
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '更新成功'
        ));
    }

    public function update_visit_date()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('visit_date', 'visit_date', 'required|integer|exact_length[10]');
        $this->validate_form();
        
        $visit_id = $_POST['visit_id'];
        $visit_date = $_POST['visit_date'];
        $bd_id = $_POST['bd_id'];
        if (! $this->_check_valid($visit_id, $bd_id)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $invit_info = $this->_get_visit_info($visit_id);
        if (count($invit_info) == 0) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '找不到拜访信息'
            ));
        }
        $status = $invit_info['status'];
        if ($status != C('customer_visit.status.plan.code')) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '无法更新拜访信息'
            ));
        }
        $this->MCustomer_visit->update($visit_id, [
            'visit_date' => $visit_date,
            'updated_time' => time()
        ]);
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '更新成功'
        ));
    }

    /**
     * 拜访统计
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function statistics()
    {
        $this->form_validation->set_rules('bdm_id', 'bdm_id', 'required|integer');
        $this->form_validation->set_rules('date_type', 'date_type', 'required|regex[/[012]/]');
        $this->validate_form();
        
        $bdm_id = $_POST['bdm_id'];
        
        $date_type = $_POST['date_type'];
        
        $date_period = $this->_get_date_period($date_type);
        $start_date = $date_period[0];
        $end_date = $date_period[1];
        
        $bd_ids = $this->_get_bd_ids($bdm_id);
        
        $count_by_bd = $this->MCustomer_visit->get_lists([
            'count(id) as total_count',
            'bd_id'
        ], [
            'visit_date >=' => strtotime($start_date),
            'visit_date <=' => strtotime($end_date),
            'in' => [
                'bd_id' => $bd_ids
            ]
        ], [
            'total_count' => 'asc'
        ], [
            'bd_id'
        ]);
        
        $bd_maps = array_column($count_by_bd, 'total_count', 'bd_id');
        
        $bd_lists = $this->MUser->get_lists([
            'id',
            'name'
        ], [
            'in' => [
                'id' => $bd_ids
            ],
            'status >' => C('status.common.del')
        ]);
        
        $count_lists = [];
        foreach ($bd_lists as &$bd_info) {
            $bd_info['bd_id'] = $bd_info['id'];
            $bd_info['bd_name'] = $bd_info['name'];
            if (isset($bd_maps[$bd_info['id']])) {
                $bd_info['count'] = $bd_maps[$bd_info['id']];
            } else {
                $bd_info['count'] = 0;
            }
            $count_lists[] = $bd_info['count'];
            unset($bd_info['id']);
            unset($bd_info['name']);
        }
        
        array_multisort($count_lists, $bd_lists);
        
        return $this->_return_json([
            'status' => C('status.req.success'),
            'list' => $bd_lists,
            'date_period' => [
                'startTime' => strtotime($start_date),
                'endTime' => strtotime($end_date)
            ]
        ]);
    }

    /**
     * 拜访日期
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function calendar()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->validate_form();
        
        $bd_id = $_POST['bd_id'];
        
        $plan_visits = $this->MCustomer_visit->get_lists([
            'visit_date'
        ], [
            'bd_id' => $bd_id,
            'status' => C('customer_visit.status.plan.code')
        ]);
        $finish_visits = $this->MCustomer_visit->get_lists([
            'visit_date'
        ], [
            'bd_id' => $bd_id,
            'status' => C('customer_visit.status.finished.code')
        ]);
        
        $plan_visit_values = array_column($plan_visits, 'visit_date');
        $finish_visit_values = array_column($finish_visits, 'visit_date');
        
        $plan_visit_values = array_unique($plan_visit_values);
        
        $finish_visit_values = array_unique($finish_visit_values);
        
        $intersection = array_intersect($plan_visit_values, $finish_visit_values);
        $only_finish_visit_values = array_diff($finish_visit_values, $intersection);
        
        $result = [];
        foreach ($plan_visit_values as $plan_visit_value) {
            $result[] = [
                'date' => date('Y-m-d', $plan_visit_value),
                'value' => 1
            ];
        }
        
        foreach ($only_finish_visit_values as $finish_visit_value) {
            $result[] = [
                'date' => date('Y-m-d', $finish_visit_value),
                'value' => 0
            ];
        }
        
        return $this->_return_json(array(
            'status' => C('tips.code.op_success'),
            'list' => $result
        ));
    }

    public function del()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->validate_form();
        $bd_id = intval($_POST['bd_id']);
        $visit_id = intval($_POST['visit_id']);
        if (! $this->_check_valid($visit_id, $bd_id)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->MCustomer_visit->update($visit_id, [
            'status' => C('customer_visit.status.invalid.code')
        ]);
        $this->MVisit_category->update_info([
            'status' => C('status.common.del')
        ], [
            'visit_id' => $visit_id
        ]);
        $this->MVisit_suggestion->update_info([
            'status' => C('status.common.del')
        ], [
            'visit_id' => $visit_id
        ]);
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '删除成功'
        ));
    }

    public function get_lists_of_bd()
    {
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->form_validation->set_rules('startTime', 'startTime', 'required|integer');
        $this->form_validation->set_rules('endTime', 'endTime', 'required|integer');
        $this->validate_form();
        $start_time = $_POST['startTime'];
        $end_time = $_POST['endTime'];
        $bd_id = $_POST['bd_id'];
        
        $visit_lists = $this->MCustomer_visit->get_lists('*', [
            'bd_id' => $bd_id,
            'visit_date >= ' => $start_time,
            'visit_date <=' => $end_time,
            'status' => C('customer_visit.status.finished.code')
        ]);
        
        if (count($visit_lists) ==  0){
             $this->_return_json(array(
                'status' => C('status.req.success'),
                'info' => []
            ));
        }
        
        $visit_ids = array_column($visit_lists, 'id');
        
        $visit_categorys = $this->MVisit_category->get_lists('*', [
            'status >' => C('status.common.del'),
            'in' => [
                'visit_id' => $visit_ids
            ]
        ]);
        
        $visit_category_ids = array_column($visit_categorys, 'category_id');
        
        $category_name_maps = $this->_get_category_names($visit_category_ids);
        
        $category_name_maps = array_column($category_name_maps, 'name', 'id');
        
        $visit_category_maps = [];
        foreach ($visit_categorys as $visit_category) {
            unset($visit_category['created_time']);
            unset($visit_category['updated_time']);
            $visit_category['category_name'] = $category_name_maps[$visit_category['category_id']];
            $visit_category_maps[$visit_category['visit_id']][] = $visit_category;
        }
        
        $visit_suggestions = $this->MVisit_suggestion->get_lists('*', [
            'status >' => C('status.common.del'),
            'in' => [
                'visit_id' => $visit_ids
            ]
        ]);
        
        $visit_suggestion_maps = [];
        foreach ($visit_suggestions as $visit_suggestion) {
            $visit_suggestion['suggestion_name'] = $this->_get_suggestion_cn($visit_suggestion['suggestion_id']);
            unset($visit_suggestion['created_time']);
            unset($visit_suggestion['updated_time']);
            $visit_suggestion_maps[$visit_suggestion['visit_id']][] = $visit_suggestion;
        }
        
        //获取坐标信息
        $customer_ids =  array_column($visit_lists,  "user_id");
        $coordinate_lists   =  $this->MCustomer->get_lists(['id', 'lng', 'lat'], ['in'=>['id'=> $customer_ids]]);
        
        $coordinates_of_customer  =    array_column( $coordinate_lists, null,  'id');
        
        foreach ($visit_lists as &$visit) {
            unset($visit['created_time']);
            unset($visit['updated_time']);
            $visit['visit_date']  = date('Y-m-d', $visit['visit_date']);
            if (isset($visit_category_maps[$visit['id']])) {
                $visit['categorys'] = $visit_category_maps[$visit['id']];
            } else {
                $visit['categorys'] = [];
            }
            if (isset($visit_suggestion_maps[$visit['id']])) {
                $visit['suggestions'] = $visit_suggestion_maps[$visit['id']];
            } else {
                $visit['suggestions'] = [];
            }
            $visit['lng'] =  $coordinates_of_customer[$visit['user_id']]['lng'];
            $visit['lat']  = $coordinates_of_customer[$visit['user_id']]['lat'];
        }
        
      $this->_return_json(array(
            'status' => C('status.req.success'),
            'info' => $visit_lists
        ));
    }

    /**
     *
     * @param
     *            date_type
     */
    protected function _get_date_period($date_type)
    {
        $end_date = date('Y-m-d', time());
        $start_date = 0;
        switch ($date_type) {
            // 今天
            case 0:
                $start_date = date('Y-m-d', time());
                break;
            case 1:
                $date = new DateTime();
                $date->modify('this week');
                $start_date = $date->format('Y-m-d');
                break;
            case 2:
                $start_date = date('Y-m-01', time());
                break;
        }
        return [
            $start_date,
            $end_date
        ];
    }

    /**
     */
    protected function _get_bd_ids($bdm_id)
    {
        $department = $this->MUser->get_one([
            'dept_id'
        ], [
            'id' => $bdm_id
        ]);
        
        $bd_list = $this->MUser->get_lists([
            'id',
            'name'
        ], [
            'dept_id' => $department['dept_id'],
            'role_id' => C('role.BD.code')
        ]);
        $bd_ids = array_column($bd_list, 'id');
        return $bd_ids;
    }

    protected function _get_suggestion_lists()
    {
        $suggestion_list = C('customer_visit.suggestion_type');
        $suggestions = [];
        foreach ($suggestion_list as $suggestion) {
            $suggestions[] = [
                'id' => $suggestion['code'],
                'name' => $suggestion['msg']
            ];
        }
        return $suggestions;
    }

    protected function _get_category_lists()
    {
        $categories = $this->MCategory->get_lists([
            'id',
            'name'
        ], [
            'upid' => 0,
            'status >' => C('status.common.del')
        ]);
        
        return $categories;
    }

    protected function _get_status_cn($status)
    {
        $status_list = C('customer_visit.status');
        foreach ($status_list as $local_status) {
            if (intval($local_status['code']) == intval($status)) {
                return $local_status['msg'];
            }
        }
        return '未知';
    }

    protected function _get_suggestion_cn($suggestion_code)
    {
        $status_list = C('customer_visit.suggestion_type');
        foreach ($status_list as $local_status) {
            if (intval($local_status['code']) == intval($suggestion_code)) {
                return $local_status['msg'];
            }
        }
        return '未知';
    }

    protected function _create_visit_plan($bd_id, $visit_date, $user_id, $is_potential)
    {
        $status = C('customer_visit.status.plan.code');
        $customer_info = $this->_get_customer_info($user_id, $is_potential);
        if ($customer_info['invite_id'] != $bd_id) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $time = time();
        $created_field = [
            'user_id' => $user_id,
            'shop_name' => $customer_info['shop_name'],
            'bd_id' => $bd_id,
            'visit_date' => $visit_date,
            'is_potential' => $is_potential,
            'status' => $status,
            'updated_time' => $time,
            'created_time' => $time
        ];
        $visit_id = $this->MCustomer_visit->create($created_field);
        return $visit_id;
    }

    protected function _update($visit_id, $focus_category, $suggestion_type, $remarks = '')
    {
        $time = time();
        $this->MCustomer_visit->update($visit_id, [
            'remarks' => $remarks,
            'updated_time' => $time,
            'status' => C('customer_visit.status.finished.code')
        ]);
        
        $category_map = $this->_pack_category($focus_category, $visit_id, $time);
        $suggestion_map = $this->_pack_suggestion($suggestion_type, $visit_id, $time);
        
        $this->MVisit_category->create_batch($category_map);
        $this->MVisit_suggestion->create_batch($suggestion_map);
    }

    protected function _create($bd_id, $user_id, $focus_category, $suggestion_type, $remarks = '', $is_potential)
    {
        $customer_info = $this->_get_customer_info($user_id, $is_potential);
        if ($customer_info['invite_id'] != $bd_id) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $shop_name = $customer_info['shop_name'];
        
        $time = time();
        
        $visit_id = $this->MCustomer_visit->create([
            'remarks' => $remarks,
            'user_id' => $user_id,
            'shop_name' => $shop_name,
            'visit_date' => strtotime(date('Y-m-d', $time)),
            'bd_id' => $bd_id,
            'is_potential' => $is_potential,
            'created_time' => $time,
            'updated_time' => $time,
            'status' => C('customer_visit.status.finished.code')
        ]);
        
        $category_map = $this->_pack_category($focus_category, $visit_id, $time);
        $suggestion_map = $this->_pack_suggestion($suggestion_type, $visit_id, $time);
        
        $this->MVisit_category->create_batch($category_map);
        $this->MVisit_suggestion->create_batch($suggestion_map);
        return $visit_id;
    }

    /**
     *
     * @param
     *            suggestion_type
     */
    protected function _pack_suggestion($suggestion_type, $visit_id, $time)
    {
        $suggestion_type = explode(",", $suggestion_type);
        $suggestion_map = [];
        foreach ($suggestion_type as $suggestion_id) {
            $suggestion_map[] = [
                'visit_id' => $visit_id,
                'suggestion_id' => $suggestion_id,
                'updated_time' => $time,
                'created_time' => $time
            ];
        }
        return $suggestion_map;
    }

    /**
     *
     * @param
     *            focus_category
     */
    protected function _pack_category($focus_category, $visit_id, $time)
    {
        $category_ids = explode(",", $focus_category);
        $category_map = [];
        foreach ($category_ids as $category_id) {
            $category_map[] = [
                'visit_id' => $visit_id,
                'category_id' => $category_id,
                'updated_time' => $time,
                'created_time' => $time
            ];
        }
        return $category_map;
    }

    protected function _get_category_names($category_ids)
    {
        $category_ids = array_unique($category_ids);
        
        $categories = $this->MCategory->get_lists([
            'id',
            'name'
        ], [
            'in' => [
                'id' => $category_ids
            ]
        ]);
        
        // $category_map = array_column($categories, 'name', 'id');
        return $categories;
    }

    /**
     *
     * @param int $visit_id            
     * @return array
     */
    protected function _get_visit_info($visit_id)
    {
        $invit_info = $this->MCustomer_visit->get_one('*', [
            'id' => $visit_id,
            'status >' => C('customer_visit.status.invalid.code')
        ]);
        if (count($invit_info) == 0) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '找不到拜访'
            ));
        }
        return $invit_info;
    }

    /**
     */
    protected function _get_visit_suggestion($visit_id)
    {
        $suggestion_list = $this->MVisit_suggestion->get_lists([
            'suggestion_id'
        ], [
            'visit_id' => $visit_id,
            'status >' => C('status.common.del')
        ]);
        
        $suggestions = [];
        foreach ($suggestion_list as $suggestion) {
            $suggestions[] = [
                'id' => $suggestion['suggestion_id'],
                'name' => $this->_get_suggestion_cn($suggestion['suggestion_id'])
            ];
        }
        return $suggestions;
    }

    /**
     */
    protected function _get_visit_category($visit_id)
    {
        $category_list = $this->MVisit_category->get_lists([
            'category_id'
        ], [
            'visit_id' => $visit_id,
            'status >' => C('status.common.del')
        ]);
        $category_list = array_column($category_list, 'category_id');
        $category_list = array_unique($category_list);
        return $category_list;
    }

    protected function _check_valid($visit_id, $bd_id)
    {
        $visit_info = $this->MCustomer_visit->get_one([
            'id'
        ], [
            'id' => $visit_id,
            'bd_id' => $bd_id
        ]);
        if (count($visit_info) == 0) {
            return false;
        }
        return true;
    }

    private function _get_customer_info($user_id, $is_potential)
    {
        if (! $is_potential) {
            $customer_info = $this->MCustomer->get_one('*', [
                'id' => $user_id,
                'status >' => C('customer.status.invalid.code')
            ]);
        } else {
            $customer_info = $this->MPotential_customer->get_one('*', [
                'id' => $user_id,
                'status >' => C('customer.status.invalid.code')
            ]);
        }
        
        if (count($customer_info) == 0) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '找不到店铺信息'
            ));
        }
        return $customer_info;
    }
}
