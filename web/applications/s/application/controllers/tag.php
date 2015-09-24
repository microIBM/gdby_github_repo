<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 面向DM系统标签的基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-06-25
 */
class Tag extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MLine', 'MLocation', 'MCustomer'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 返回固有标签列表
     * @author yugang@dachuwang.com
     * @since 2015-06-25
     */
    public function attr_list() {
        // 数据处理
        $citys = $this->MLocation->get_lists('id, name, upid', ['upid' => '0']);
        $lines = $this->MLine->get_lists('id, name, location_id', ['status' => C('status.common.success')]);
        $shop_types = array_values(C('customer_type.top'));
        $dimensions = array_values(C('customer.dimension'));
        $customer_types = array_values(C('customer.type'));
        $billing_cycles = array_values(C('customer.billing_cycle'));
        $data = [
            ['name' => '所属地区', 'field' => 'province_id', 'value' => $citys],
            ['name' => '配送线路', 'field' => 'line_id', 'value' => $lines],
            ['name' => '客户类型', 'field' => 'customer_type', 'value' => $customer_types],
            ['name' => '客户规模', 'field' => 'dimensions', 'value' => $dimensions],
            ['name' => '餐饮类别', 'field' => 'shop_type', 'value' => $shop_types],
            ['name' => '结账周期', 'field' => 'billing_cycle', 'value' => $billing_cycles],
        ];
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'data'   => $data,
            )
        );
    }

}

