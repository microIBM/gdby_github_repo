<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 会员折扣控制器
 * @author yugang@dachuwang.com
 * @since 2015-08-10
 */
class Member_rebate extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
                'MProduct',
                'MLocation',
                'MCustomer',
                'MOrder',
                'MUser',
            )
        );
        $this->load->library(
            array(
                'form_validation',
            )
        );

    }

    /**
     * 列出KA列表页面的下拉列表选项
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     */
    public function list_options() {
        $this->check_validation('product', 'list', '', FALSE);
        $data = $this->format_query('member_rebate/list_options', $_POST);
        $this->_return_json($data);
    }

    /**
     * 列表
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     */
    public function lists() {
        $this->check_validation('product', 'list', '', FALSE);
        $data = $this->format_query('/member_rebate/lists', $this->post);
        $this->_return_json($data);
    }

    /**
     * KA折扣编辑页面
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     */
    public function edit_input() {
        $this->check_validation('product', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/member_rebate/edit_input', $_POST);
        $customer_info = $this->format_query('/customer/view', $_POST);
        $return['info'] = $customer_info['info'];
        $this->_return_json($return);
    }

    public function edit() {
        $this->check_validation('product', 'edit', '', FALSE);
        $this->form_validation->set_rules('customerId', '客户ID', 'required');
        $this->form_validation->set_rules('rebateGroup', '折扣', 'required');
        $this->validate_form();

        $cur = $this->userauth->current(false);
        $_POST['cur'] = $cur;
        $data = $this->format_query('/member_rebate/edit', $_POST);
        $this->_return_json($data);
    }


}

/* End of file member_rebate.php */
/* Location: ./application/controllers/member_rebate.php */
