<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 静态化接口类
 * @author zhangxiao@dachuwang.com
 */
class Staticize extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
            'MStatics',
            'MStatics_history',
            'MStatics_month',
            'MStatics_sku_day',
            'MStatics_sku_week',
            'MStatics_sku_month'
        ));
    }

    /**
     * 总统计表：把按天数据更新至数据库
     * @author zhangxiao@dachuwang.com
     */
    public function staticize_data() {
        $data = $this->input->post('data');
        $success = $this->MStatics->update_statics($data);
        $this->_return($success);
    }

    /**
     * 总统计表：把历史数据更新至数据库
     * @author zhangxiao@dachuwang.com
     */
    public function staticize_data_history() {
        $data = $this->input->post('data');
        $success = $this->MStatics_history->update_statics($data);
        $this->_return($success);
    }

    /**
     * 总统计表：把月总计数据更新至数据库
     * @author zhangxiao@dachuwang.com
     */
    public function staticize_data_month() {
        $data = $this->input->post('data');
        $success = $this->MStatics_month->update_statics($data);
        $this->_return($success);
    }

    /**
     * 返回总统计表的历史数据
     * @author zhangxiao@dachuwang.com
     */
    public function get_history_statics_data() {
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = array();
        if ($city_id || $city_id == 0) {
            $where['city_id'] = $city_id;
        }
        if ($customer_type) {
            $where['customer_type'] = $customer_type;
        }

        $fields = array(
            'SUM(order_count) as period_valid_order_cnt',
            'SUM(order_amount) as period_valid_order_amount',
            'SUM(potential_cus_count) as period_potential_cus',
            'SUM(register_cus_count) as period_resign_cus',
            'SUM(ordered_cus_count) as period_ordered_customers_total',
            'SUM(again_order_cus_count) as period_again_customers_total',
            'SUM(first_order_cus_count) as period_first_customer_total'
        );

        $result = $this->MStatics_history->get_lists($fields, $where);
        $this->_return_result_json($result);
    }

    /**
     * 返回总统计表的月总计数据
     * @author zhangxiao@dachuwang.com
     */
    public function get_month_statics_data() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = array();
        if ($stime) {
            $where['date_time >='] = $stime;
        }
        if ($etime) {
            $where['date_time <='] = $etime;
        }
        if ($city_id || $city_id == 0) {
            $where['city_id'] = $city_id;
        }
        if ($customer_type) {
            $where['customer_type'] = $customer_type;
        }

        $fields = array(
            'SUM(order_count) as period_valid_order_cnt',
            'SUM(order_amount) as period_valid_order_amount',
            'SUM(potential_cus_count) as period_potential_cus',
            'SUM(register_cus_count) as period_resign_cus',
            'SUM(ordered_cus_count) as period_ordered_customers_total',
            'SUM(again_order_cus_count) as period_again_customers_total',
            'SUM(first_order_cus_count) as period_first_customer_total'
        );

        $result = $this->MStatics_month->get_lists($fields, $where);
        $this->_return_result_json($result);
    }

    /**
     * 返回总统计表的每天数据
     * @author zhangxiao@dachuwang.com
     */
    public function get_daylist_statics_data() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = array();
        if ($stime) {
            $where['date_time >='] = $stime;
        }
        if ($etime) {
            $where['date_time <='] = $etime;
        }
        if ($city_id || $city_id == 0) {
            $where['city_id'] = $city_id;
        }
        if ($customer_type) {
            $where['customer_type'] = $customer_type;
        }

        $fields = array(
            'FROM_UNIXTIME(date_time, "%Y-%m-%d") as date',
            'date_time as time_stamp',
            'SUM(order_amount) as valid_order_amount',
            'SUM(order_count) as valid_order_cnt',
            'SUM(potential_cus_count) as potential_cus_cnt',
            'SUM(register_cus_count) as resign_cus_cnt',
            'SUM(ordered_cus_count) as order_cus_cnt',
            'SUM(first_order_cus_count) as first_ordered_count',
            'SUM(first_order_amount) as first_amount',
            'SUM(again_order_cus_count) as again_ordered_count',
            'SUM(again_order_amount) as again_amount'
        );

        $group_by = array('date');
        $order_by = array('date_time' => 'desc');
        $result = $this->MStatics->get_lists($fields, $where, $order_by, $group_by);
        $this->_return_result_json($result);
    }

    /**
     * 获取静态表数据
     * @throws Exception
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_sku_daylists(){
        //$this->load->model(['MSku_day','MSku_week','MSku_month']);
        
        try {
            $param['date_mode'] = $this->input->post('date_mode');
            $param['sdate'] = $this->input->post('sdate');
            $param['edate'] = $this->input->post('edate');
            $param['sku_number'] = $this->input->post('sku_number');
            $param['warehouse_id'] = $this->input->post('warehouse_id');
            $param['city_id'] = $this->input->post('city_id');
            if(!$param['sdate']){
                throw new Exception('缺少sdate参数');
            }
            if(!$param['edate']){
                throw new Exception('缺少edate参数');
            }
            if(!$param['sku_number']){
                throw new Exception('缺少sku_number参数');
            }
            
            $data['day_lists'] = $this->MStatics_sku_day->get_day_lists($param);
            
            if ($param['date_mode'] == 3) {//按周
                $data['period_lists'] = $this->MStatics_sku_week->get_week_lists($param);
            }
            if ($param['date_mode'] == 2) {//按月
                $data['period_lists'] = $this->MStatics_sku_month->get_month_lists($param);
            }
            $this->_return_json(array(
                'status' => C('status.req.success'),
                'msg'    => 'success',
                'data'   => $data
            ));
        } catch (Exception $e) {
            $this->_return_json(array(
                'status'    => C('status.req.failed'),
                'msg'    => $e->getMessage(),
            ));
        }
    }
    
    /**
     * 返回json消息
     * @author zhangxiao@dachuwang.com
     */
    private function _return_result_json($result, $success_msg = '操作成功', $fail_msg = '操作失败') {
        if($result) {
            $this->_return_json(array(
                'status' => C('status.req.success'),
                'msg'    => $success_msg,
                'data'   => $result
            ));
        } else {
            $this->_return_json(array(
                    'status'    => C('status.req.failed'),
                    'msg'    => $fail_msg,
                    'data'   => $result
            ));
        }
    }

    /**
     * 
     * @author wangzejun@dachuwang.com
     */
    public function get_search_sku_lists() {
//        $page       = $this->get_page();
        $where      = $this->format_post();
        $date_mode  = (int)$this->input->post('date_mode', true) ?: 1;
        $sort_name  = $this->input->post('sort_name', true) ?: 'sale_amount';
        $sort_value = $this->input->post('sort_value', true) ?: 'desc';
        $page['page_size'] = $this->input->post('itemsPerPage') !== false ? $this->input->post('itemsPerPage') : 10;
        $page['cur_page'] = $this->input->post('currentPage') !== false ? $this->input->post('currentPage') : 1;
        $page['offset'] = ($page['cur_page'] - 1) * $page['page_size'];
//        var_dump($where,$page);
        $sku_lists = array();
        switch ($date_mode) {
            case 1:
                // 按天
                $sku_lists = $this->MStatics_sku_day->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_day->count($where);
                break;
            case 3:
                // 按周                
                $sku_lists = $this->MStatics_sku_week->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_week->count($where);
                break;
            case 2:
                // 按月
                $sku_lists = $this->MStatics_sku_month->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_month->count($where);
                break;
            default:
                break;
        }
        $this->_return_json(array('total' => $total, 'data' => $sku_lists));
    }

    /**
     * 
     * @author wangzejun@dachuwang.com
     */
    public function get_sku_lists_by_tab_id() {
//        $page       = $this->get_page();
        $page['page_size'] = $this->input->post('itemsPerPage') !== false ? $this->input->post('itemsPerPage') : 10;
        $page['cur_page'] = $this->input->post('currentPage') !== false ? $this->input->post('currentPage') : 1;
        $page['offset'] = ($page['cur_page'] - 1) * $page['page_size'];
        $where      = $this->format_post();
        $tab_id     = (int)$this->input->post('tab_id', true);
        $sort_name  = $this->input->post('sort_name', true);
        $sort_value = $this->input->post('sort_value', true);
        $sku_lists  = array();
        $total      = 0;
        log_message('error','error test');
        switch ($tab_id) {
            case 2:
                // 按天
                $sku_lists = $this->MStatics_sku_day->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_day->count($where);
                break;
            case 3:
                // 按周                
                $sku_lists = $this->MStatics_sku_week->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_week->count($where);
                break;
            case 4:
                // 按月
                $sku_lists = $this->MStatics_sku_month->get_lists(array(), $where, array($sort_name => $sort_value), array(), $page['offset'], $page['page_size']);
                $total     = $this->MStatics_sku_month->count($where);
                break;
            default:
                break;
        }
        $this->_return_json(array('total' => $total, 'data' => $sku_lists));
    }

    /**
     * 接收处理请求数据
     * @author wangzejun@dachuwang.com
     */
    public function format_post() {
        $where = array();
        if ((int)$this->input->post("category_id3", true)) {
            $where["substring_index(path, '.', 4)="] = '.'.$this->input->post("category_id1", true).'.'.$this->input->post("category_id2", true).'.'.$this->input->post("category_id3", true);
        } elseif ((int)$this->input->post("category_id2", true)) {
            $where["substring_index(path, '.', 3)="] = '.'.$this->input->post("category_id1", true).'.'.$this->input->post("category_id2", true);
        } else {
            $where["substring_index(path, '.', 2)="] = '.'.$this->input->post("category_id1", true);
        }
        $where['sku_number']   = $this->input->post('sku_number', true);
        $where['like']         = $this->input->post('sku_name', true) ? array('sku_name' => $this->input->post('sku_name', true)) : '';
        $where['warehouse_id'] = $this->input->post('warehouse_id', true);
        $where['city_id']      = $this->input->post('city_id', true);
        $where['data_date >=']      = $this->input->post('stime', true);
        $where['data_date <=']      = $this->input->post('etime', true);

        $where = array_filter($where);
        return $where;
    }

}
//end of php file