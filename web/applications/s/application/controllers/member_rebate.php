<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 会员折扣基础服务
 * @author yugang@dachuwang.com
 * @since 2015-08-08
 */
class Member_rebate extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MMember_rebate', 'MCustomer', 'MCategory', 'MLocation'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 列出KA折扣页面的下拉列表选项
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     */
    public function list_options() {
        $categories = $this->MCategory->get_lists(
            'id, name',
            [
                'upid' => C('status.common.top'),
                'status' => C('status.common.success')
            ]
        );
        $cities = $this->MLocation->get_lists(
            'id, name',
            [
                'upid' => C('status.common.top'),
                'status' => C('status.common.success')
            ]
        );

        $this->_return_json(
            [
                'status'     => C('status.req.success'),
                'categories' => $categories,
                'cities'     => $cities,
            ]
        );
    }

    /**
     * 折扣列表
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status >'] = C('status.common.del');
        $where['customer_type'] = C('customer.type.KA.value');
        $where['account_type'] = C('customer.account_type.parent.value');
        // 所属城市
        if(!empty($_POST['provinceId'])) {
            $where['province_id'] = $_POST['provinceId'];
        }
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }
        $list = $this->MCustomer->get_lists(
            '*',
            $where,
            array(),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $list = $this->_format_list($list);
        // 获取KA客户的折扣信息
        $total = $this->MCustomer->count($where);

        $arr = array(
            'status'     => C('status.req.success'),
            'list'       => $list,
            'total'      => $total,
        );

        // 返回结果
        $this->_return_json($arr);
    }


    /**
     * 修改折扣页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-08-08
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        $top_categories = $this->MCategory->get_lists(
            'id, name, path, upid',
            [
                'upid' => C('status.common.top')
            ]
        );
        // 数据查询
        $list = $this->MMember_rebate->get_lists(
            '*',
            [
                'customer_id' => $_POST['id'],
                'status' => C('status.common.normal')
            ]
        );
        $rebate_dict = array_column($list, 'rebate', 'category_id');
        foreach ($top_categories as &$top_category) {
            if (isset($rebate_dict[$top_category['id']])) {
                $top_category['rebate'] = intval($rebate_dict[$top_category['id']]);
            } else {
                $top_category['rebate'] = 100;
            }
        }

        // 返回结果
        $this->_return_json(
            array(
                'status'     => C('status.req.success'),
                'categories' => $top_categories,
            )
        );
    }

    /**
     * 修改折扣
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('customerId', '客户ID', 'required');
        $this->form_validation->set_rules('rebateGroup', '折扣', 'required');
        $this->validate_form();

        $cur = $_POST['cur'];
        // 获取当前设置的折扣
        $rebateGroup = $_POST['rebateGroup'];
        $rebateGroup = array_column($rebateGroup, 'group');
        $rebates = [];
        foreach ($rebateGroup as $rebate) {
            $rebate = array_values($rebate);
            foreach ($rebate as $item) {
                $rebates[] = $item;
            }
        }
        unset($rebate);
        $this->_check_rebate($rebates);

        // 删除原有折扣信息
        $this->MMember_rebate->false_delete(['customer_id' => $_POST['customerId']]);

        $list = [];
        // 添加新的折扣信息
        foreach ($rebates as $rebate) {
            // 折扣率为100%的代表没有折扣，不存储
            if ($rebate['rebate'] == 100) {
                continue;
            }
            $data = [
                'customer_id'   => $_POST['customerId'],
                'category_id'   => $rebate['id'],
                'category_name' => $rebate['name'],
                'rebate'        => $rebate['rebate'],
                'created_time'  => $this->input->server('REQUEST_TIME'),
                'updated_time'  => $this->input->server('REQUEST_TIME'),
                'operator_id'   => empty($cur) ? '0' : $cur['id'],
                'operator'      => empty($cur) ? '' : $cur['name'],
                'status'        => C('status.common.normal')
            ];
            $list[] = $data;
        }
        $result = $this->MMember_rebate->create_batch($list);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 检查用户设置的折扣是否合理
     * @author yugang@dachuwang.com
     * @since 2015-08-11
     */
    private function _check_rebate($rebates) {
        foreach ($rebates as $rebate) {
            if ($rebate['rebate'] <= 0) {
                $this->_return(false, '折扣设置不合理，必须大于0！');
            }
        }
    }


    /**
     * 处理KA客户列表数据,获每个KA客户折扣并设置
     * @author yugang@dachuwang.com
     * @since 2015-08-11
     */
    private function _format_list($list) {
        $result = array();
        if (empty($list)) {
            return $result;
        }

        $customer_ids = array_column($list, 'id');
        $rebates = $this->MMember_rebate->get_lists(
            '*',
            [
                'in' => ['customer_id' => $customer_ids],
                'status' => C('status.common.normal')
            ]
        );
        foreach ($list as $item) {
            $rebate_arr = [];
            foreach ($rebates as $rebate) {
                if ($item['id'] == $rebate['customer_id']) {
                    $rebate_arr[] = $rebate;
                }
            }
            $item['rebates'] = $rebate_arr;
            if (!empty($rebate_arr)) {
                $item['operator'] = $rebate_arr[0]['operator'];
                $item['rebate_updated_time'] = date('Y-m-d H:i:s', $rebate_arr[0]['updated_time']);
            }
            $result[] = $item;
        }

        return $result;
    }
}

/* End of file member_rebate.php */
/* Location: :./application/controllers/member_rebate.php */
