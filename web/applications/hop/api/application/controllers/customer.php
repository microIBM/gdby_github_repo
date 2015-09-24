<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Customer extends MY_Controller {
    protected $_salt  = NULL;

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MLocation',
                'MCustomer',
                'MPotential_customer',
                'MPhone',
                'MOrder',
                'MCustomer_transfer_log',
                'MComplaint',
                'MUser',
                'MWorkflow_log',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'phone'
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 客户注册
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function register() {

    }

    /**
     * 客户登陆
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function login() {

    }

    /**
     * 客户退出
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function logout() {

    }

    /**
     * 客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function lists() {
        // 权限校验
        $this->check_validation('customer', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 移交客户列表
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function lists_transfer() {
        // 权限校验
        $this->check_validation('customer', 'list', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('provinceId', '省份', 'required|numeric|greater_than[1]');
        $this->form_validation->set_rules('customerType', '客户类型', 'trim|required');
        $this->validate_form();

        if($_POST['customerType'] == C('customer.customer_type.customer.code')) {
            // 注册客户
            $return = $this->format_query('/customer/lists_transfer', $_POST);
        } else {
            // 潜在客户
            $return = $this->format_query('/potential_customer/lists_transfer', $_POST);
        }

        $this->_return_json($return);
    }

    /**
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function line_list() {
        // 权限校验
        $this->check_validation('customer', 'list', '', FALSE);
        // 调用基础服务接口
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $return = $this->format_query('/line/lists', $_POST);
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        $this->_return_json($return);
    }

    /**
     * 客户列表下拉列表选项
     * @author yugang@dachuwang.com
     * @since 2015-06-29
     */
    public function lists_options() {
        // 权限校验
        $this->check_validation('customer', 'list', '', FALSE);
        // 调用基础服务接口
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $return = $this->format_query('/line/lists', $_POST);
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        $return['types'] = array_values(C('customer.list_type'));
        $this->_return_json($return);
    }

    /**
     * 客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function list_line() {
        // 权限校验
        $this->check_validation('customer', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑客户线路输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function edit_line_input() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit_line_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑客户线路
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function edit_line() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit_line', $_POST);
        $this->_return_json($return);
    }

    /**
     * 批量编辑客户线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function batch_edit_line() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/batch_edit_line', $_POST);
        $this->_return_json($return);
    }

    /**
     * 客户信息查看页面
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function view() {
        // 权限校验
        $this->check_validation('customer', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/view', $_POST);
        $this->_return_json($return);
    }
    /**
     * 添加客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('customer', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/create_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create() {
        // 权限校验
        $this->check_validation('customer', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        $customer = $this->MCustomer->get_one('*', ['id' => $_POST['id']]);
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit', $_POST);
        if ($customer['billing_cycle'] != $_POST['billing_cycle'] || $customer['check_date'] != $_POST['check_date']) {
            $post_data = [];
            $post_data['customer_id'] = $_POST['id'];
            $post_data['billing_cycle'] = $_POST['billing_cycle'];
            $post_data['check_date'] = $_POST['check_date'];
            $post_data['pay_date'] = $_POST['pay_date'];
            $now_date = isset($_POST['now_date'])? strval($_POST['now_date']) : date('Y-m-d');
            $post_data['now_date'] = $now_date;
            $this->format_query('billing/change_billing_cycle', $post_data);
        }
        $this->_return_json($return);
    }

    /**
     * 删除客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function delete() {
        // 权限校验
        $this->check_validation('customer', 'delete', '', FALSE);

    }

    /**
     * 重置密码
     * @author yugang@dachuwang.com
     * @since 2015-03-12
     */
    public function reset_password() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        $data = $this->MCustomer->get_one('*', array('id' => $this->input->post('uid', TRUE)));
        // 调用基础服务接口
        $return = $this->format_query('/customer/reset_password', $_POST);
        // 发送短信
        if(intval($return['status']) === 0) {
            $sms_return = $this->format_query('/sms/send_captcha',
                array(
                    'content' => $return['content'],
                    'mobile'  => $return['mobile'],
                    'site'    => C('site.dachu')
                )
            );
            unset($return['content']);
        }
        $this->_return_json($return);
    }

    /**
     * 修改状态
     * @author yugang@dachuwang.com
     * @since 2015-03-12
     */
    public function toggle_status() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/toggle_status', $_POST);
        $this->_return_json($return);
    }

    /**
     * 禁用
     * @author yugang@dachuwang.com
     * @since 2015-06-30
     */
    public function disable() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/disable', $_POST);
        if ($return['status'] == C('status.req.success')) {
            // 记录日志
            $cur = $this->userauth->current(FALSE);
            $this->MWorkflow_log->record_op_log($_POST['uid'], C('workflow_log.operate_type.customer.disable'), $cur, $_POST['remark'], '禁用客户', C('workflow_log.edit_type.customer'));
        }
        $this->_return_json($return);
    }

    /**
     * 获取禁用理由
     * @author yugang@dachuwang.com
     * @since 2015-09-01
     */
    public function get_disable_reason() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/get_disable_reason', $_POST);
        $this->_return_json($return);
    }

    /**
     * 启用状态
     * @author yugang@dachuwang.com
     * @since 2015-06-30
     */
    public function enable() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/customer/enable', $_POST);
        if ($return['status'] == C('status.req.success')) {
            // 记录日志
            $cur = $this->userauth->current(FALSE);
            $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
            $this->MWorkflow_log->record_op_log($_POST['uid'], C('workflow_log.operate_type.customer.enable'), $cur, $remark, '启用客户', C('workflow_log.edit_type.customer'));

            $customer = $this->MCustomer->get_one('*', ['id' => $_POST['uid']]);
            // 母账号启用后马上开始计算账期
            if ($customer['account_type'] == C('customer.account_type.parent.value') && $customer['billing_cycle'] != 'none' && $customer['billing_cycle'] != '') {
                $post_data = [];
                $post_data['customer_id'] = $customer['id'];
                $post_data['billing_cycle'] = $customer['billing_cycle'];
                $post_data['check_date'] = $customer['check_date'];
                $post_data['pay_date'] = $customer['pay_date'];
                $now_date = isset($_POST['now_date'])? strval($_POST['now_date']) : date('Y-m-d');
                $post_data['now_date'] = $now_date;
                $this->format_query('billing/change_billing_cycle', $post_data);
            }
        }

        $this->_return_json($return);
    }

    /**
     * 移交客户,修改客户所属客户经理
     * @author yugang@dachuwang.com
     * @since 2015-04-27
     */
    public function set_sales() {
        // 权限校验
        $this->check_validation('customer', 'edit', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('cids', '客户', 'required');
        $this->form_validation->set_rules('pcids', '潜在客户', 'required');
        $this->form_validation->set_rules('userId', '接收销售', 'required');
        $this->validate_form();

        $cur = $this->userauth->current(FALSE);
        $return = [];
        // 调用基础服务接口
        if (!empty($_POST['cids'])) {
            // 记录移交日志
            $this->MCustomer_transfer_log->record($_POST['userId'], $_POST['cids'], $cur);
            $return = $this->format_query('/customer/set_sales', $_POST);
        }
        if (!empty($_POST['pcids'])) {
            // 记录移交日志
            $this->MCustomer_transfer_log->record_potential($_POST['userId'], $_POST['pcids'], $cur);
            $return = $this->format_query('/potential_customer/set_sales', $_POST);
        }

        $this->_return_json($return);
    }

    /**
     * 历史订单
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function history_order() {
        // 权限校验
        $this->check_validation('customer', 'view', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('userId', '客户ID', 'required|numeric');
        $this->validate_form();
        // 调用基础服务接口
        $_POST['user_id'] = $_POST['userId'];
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 历史投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function history_complaint() {
        // 权限校验
        $this->check_validation('customer', 'view', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('userId', '客户ID', 'required|numeric');
        $this->validate_form();
        // 调用基础服务接口
        $return = $this->format_query('/complaint/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 客户移交选项
     * @author yugang@dachuwang.com
     * @since 2015-06-10
     */
    public function list_transfer_options() {
        // 权限校验
        $this->check_validation('customer', 'view', '', FALSE);
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $line_return = $this->format_query('/line/lists', $_POST);
        $return['status'] = C('status.req.success');
        $return['lines'] = $line_return['list'];
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $sale_array = array(C('user.saleuser.BD.type'));
        $sale_list = $this->MUser->get_lists('id, name, province_id, site_id, role_id, max_customer, max_potential_customer', ['in' => ['role_id' => $sale_array], 'status' => C('status.common.success')]);
        $sale_ids = array_column($sale_list, 'id');
        $customer_count_dict = [];
        $customer_count = $this->MCustomer->get_lists('count(*) as count, invite_id', ['status >' => C('status.common.del'), 'in' => ['invite_id' => $sale_ids]], [], ['invite_id']);
        if (!empty($customer_count)) {
            $customer_count_dict = array_combine(array_column($customer_count, 'invite_id'), array_column($customer_count, 'count'));
        }
        $pc_count_dict = [];
        $pc_count = $this->MPotential_customer->get_lists('count(*) as count, invite_id', ['status >' => C('status.common.del'), 'in' => ['invite_id' => $sale_ids]], [], ['invite_id']);
        if (!empty($pc_count)) {
            $pc_count_dict = array_combine(array_column($pc_count, 'invite_id'), array_column($pc_count, 'count'));
        }
        foreach ($sale_list as &$sale) {
            $sale['leftover_customer'] = isset($customer_count_dict[$sale['id']]) ? ($sale['max_customer'] - $customer_count_dict[$sale['id']]) : $sale['max_customer'];
            $sale['leftover_potential_customer'] = isset($pc_count_dict[$sale['id']]) ? ($sale['max_potential_customer'] - $pc_count_dict[$sale['id']]) : $sale['max_potential_customer'];
        }
        unset($sale);
        $return['sales'] = $sale_list;
        $return['customer_types'] = array_values(C('customer.customer_type'));
        $return['order_record'] = array_values(C('customer.order_record'));
        $this->_return_json($return);
    }

    /**
     * 验证母账号手机是否存在
     * @author maqiang@dachuwang.com
     * @since 2015-07-08
     */
    public function check_parent_mobile() {
        $this->form_validation->set_rules('parent_mobile', '手机号', 'trim|required|exact_length[11]|numeric');
        $this->validate_form();
        $data = $this->MCustomer->get_one('id', array('mobile' => $this->input->post('parent_mobile', TRUE), 'status >' => C('customer.status.invalid.code'),'account_type' => C('customer.account_type.parent.value')));
        if (count($data) > 0) {
        // 返回结果
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '母账号存在',
                )
            );
        }
        $this->_return_json(
             array(
                'status' => C('status.req.failed'),
                'msg'    => '母账号不存在',
             )
        );
    }
}

/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
