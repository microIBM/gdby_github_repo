<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户下单TOP信息类
 * @author zhangxiao@dachuwang.com
 * @since 2015-09-02
 */
class Cus_top extends MY_Controller {

    const PAGE = 1;
    const PAGESIZE = 10;
    //蔬菜加肉的标记
    const VEGETABLE_MEAT = 'vm';
    public function __construct() {
        parent::__construct();
        $this->load->library('pagination');
        $this->load->helper('paginationer');
        $this->load->model(['MCustomer', 'MStatics_customer_cate']);
        $this->_init_req_params();
    }

    public function index() {
        $cus_cate_info = $this->get_cus_cate_top_info();
        //获取用户品类信息
        $this->data['cus_cate_info'] = $cus_cate_info['data'];
        //获取用户品类信息总记录数
        $this->data['total_records'] = $cus_cate_info['count'];
        //添加分页
        $pagination_params = array(
            'search_key'    => $this->data['search_key'],
            'search_value'  => $this->data['search_value'],
            'pagesize'      => $this->data['pagesize'],
            'city_id'       => $this->data['city_id'],
            'customer_type' => $this->data['customer_type'],
            'tab_id'        => $this->data['tab_id'],
            'order_key'     => $this->data['order_key'],
            'order_value'   => $this->data['order_value'],
        );
        if($this->data['sdate_picker'] && $this->data['edate_picker']) {
            $pagination_params['sdate_picker'] = $this->data['sdate_picker'];
            $pagination_params['edate_picker'] = $this->data['edate_picker'];
        }
        if($this->data['tab_id']) {
            $pagination_params['sdate'] = $this->data['sdate'];
            $pagination_params['edate'] = $this->data['edate'];
        }

        $pagination = paginationer('/cus_top', $pagination_params, $this->data['total_records'], $this->data['pagesize'], 4);
        $this->data['pagination'] = $pagination;
        $this->load->view('cus_top', $this->data);
    }

    /**
     * 初始化所需的参数数据
     * @author zhangxiao@dachuwang.com
     */
    private function _init_req_params() {
        $this->data['current_url']   = current_url();

        $city_id  = $this->input->get('city_id');
        $this->data['city_id'] = $city_id ? $city_id : C('open_cities.quanguo.id');

        $customer_type = $this->input->get('customer_type');
        $this->data['customer_type'] = $customer_type ? $customer_type : 0;

        $tab_id = $this->input->get('tab_id');
        $this->data['tab_id'] = $tab_id ? $tab_id : 0;

        $sdate = $this->input->get('sdate');
        $this->data['sdate'] = $sdate ? $sdate : 0;
        $edate = $this->input->get('edate');
        $this->data['edate'] = $edate ? $edate : 0;

        $sdate_picker = $this->input->get('sdate_picker');
        $this->data['sdate_picker'] = $sdate_picker ? $sdate_picker : 0;
        $edate_picker = $this->input->get('edate_picker');
        $this->data['edate_picker'] = $edate_picker ? $edate_picker : 0;
        if($sdate_picker && $edate_picker) {
            $this->data['tab_id'] = '';
            $this->data['sdate'] = $sdate_picker;
            $this->data['edate'] = $edate_picker;
        }

        $this->data['cate_top'] = $this->_get_top_cate();

        //默认显示蔬菜和肉的降序排列
        $order_key = $this->input->get('order_key');
        $this->data['order_key'] = $this->_check_field_exsits($order_key) ? $order_key : self::VEGETABLE_MEAT;
        $order_value = $this->input->get('order_value');
        $this->data['order_value'] = $order_value ? $order_value : 'desc';

        $search_key = $this->input->get('search_key');
        $this->data['search_key'] = $search_key ? $search_key : "";
        $search_value = $this->input->get('search_value');
        $this->data['search_value'] = $search_value ? $search_value : "";

        $page = $this->input->get('page');
        $this->data['page'] = $page ? $page : self::PAGE;
        $pagesize = $this->input->get('pagesize');
        $this->data['pagesize'] = $pagesize ? $pagesize : self::PAGESIZE;

        $this->data['date'] = $this->_get_sdate_edate();
    }

    /**
     * 验证排序字段是否存在
     * @author zhangxiao@dachuwang.com
     */
    private function _check_field_exsits($field) {
        $cate_top_ids = array_column($this->data['cate_top'], 'category_id');
        array_push($cate_top_ids, 'vm', 'total');
        return in_array($field, $cate_top_ids);
    }

    /**
     * 获取符合筛选条件的用户的信息
     * @author zhangxiao@dachuwang.com
     */
    private function _get_filtered_cus_info () {
        $fields = ['id', 'name', 'shop_name', 'mobile'];
        $where  = [];
        //按照关键字搜索
        $search_key   = $this->data['search_key'];
        $search_value = $this->data['search_value'];
        if($search_key && $search_value){
            switch($search_key) {
            case 'c_name' :
               $where['like'] = ['name' => $search_value];
               break;
            case 'c_shop' :
               $where['like'] = ['shop_name' => $search_value];
               break;
            case 'c_tel' :
               $where['mobile'] = $search_value;
               break;
            case 'c_id' :
               $where['id'] = $search_value;
               break;
            }
            $results = $this->MCustomer->get_lists($fields, $where);
        } else {
            // 标识没有筛选
            return FALSE;
        }
        $final_results = array_column($results, NULL, 'id');
        return $final_results;
    }

    /**
     * 根据客户IDs来获取客户信息
     * @author zhangxiao@dachuwang.com
     */
    private function _get_cus_info_by_ids($customer_ids) {
        if($customer_ids) {
            $fields = ['id', 'name', 'shop_name', 'mobile'];
            $where['in'] = ['id' => $customer_ids];
            $result = $this->MCustomer->get_lists($fields, $where);
            return array_column($result, NULL, 'id');
        } else {
            return array();
        }
    }

    /**
     * 获取一级品类的信息
     * @author zhangxiao@dachuwang.com
     */
    private function _get_top_cate() {
        $results = $this->format_query('http://bi.dachuwang.com/interface_bi/get_category_child', array(
            'category_id' => 0
        ), FALSE, TRUE);
        if (isset($results['status']) && $results['status'] == 0) {
            $final_results = array_column($results['data'], NULL, 'category_id');
        } else {
            $final_results = array();
        }
        return $final_results;
    }

    /**
     * 获取客户下单品类详情
     * @author zhangxiao@dachuwang.com
     */
    public function get_cus_cate_top_info() {
        //获取筛选的客户信息
        $cus_info_filtered = $this->_get_filtered_cus_info();
        //如果用户进行了筛选
        if($cus_info_filtered !== FALSE) {
            //如果筛选不是空
            if($cus_info_filtered) {
                $cus_ids_filtered  = array_column($cus_info_filtered, 'id');
            } else {
                return ['data' => array(), 'count' => 0];
            }
        }
        //获取一级分类信息
        $cate_top = array_column($this->data['cate_top'], NULL, 'category_id');

        /**
         * 组装需要的数据库查询参数
         * $fields $where $group_by $order_by $offset $pagesize
         */
        $fields =[];
        array_push($fields, 'customer_id', 'city_id');
        foreach($cate_top as $value) {
            $field_cate = "SUM(CASE category_id WHEN ".$value['category_id']." THEN sale_amount ELSE 0 END) '".$value['category_id']."'";
            array_push($fields, $field_cate);
        }
        //再添加上特殊的需求:素菜和肉
        array_push($fields, "SUM(CASE category_id WHEN ".C('category.category_type.meat.code')." THEN sale_amount WHEN ".C('category.category_type.vegetable.code')." THEN sale_amount ELSE 0 END) vm");
        //再添加上下单总金额
        $cate_top_ids = array_column($cate_top, 'category_id');
        $condition = [];
        foreach($cate_top_ids as $id) {
            array_push($condition, 'category_id='.$id);
        }
        $condition_str = implode(' OR ', $condition);
        array_push($fields, "SUM(CASE WHEN ".$condition_str." THEN sale_amount ELSE 0 END) total");

        $where = [];
        $customer_type = $this->data['customer_type'];
        $city_id       = $this->data['city_id'];
        $sdate         = $this->data['sdate'];
        $edate         = $this->data['edate'];
        if($customer_type !== 0) {
            $where['customer_type'] = $customer_type;
        }
        if($city_id !== 0) {
            $where['city_id'] = $city_id;
        }
        if($sdate && $edate) {
            $where['data_date >='] = $sdate;
            $where['data_date <='] = $edate;
        }
        //如果有客户关键字搜索项则加入wherein
        if ($cus_info_filtered !== FALSE) {
            $where['in'] = ['customer_id' => $cus_ids_filtered];
        }

        $group_by = array('customer_id');
        $order_by = array($this->data['order_key'] => $this->data['order_value']);
        $pagesize = $this->data['pagesize'];
        $offset   = ($this->data['page'] - 1) * $pagesize;

        $result = $this->MStatics_customer_cate->get_lists($fields, $where, $order_by, $group_by, $offset, $pagesize);
        $count  = $this->MStatics_customer_cate->get_lists(['count(distinct(customer_id)) as count'], $where);

        $final_result = [];
        //如果没有客户关键字的搜索项则根据所获得客户ID反查所需的客户信息
        if($cus_info_filtered === FALSE) {
            if($result) {
                $cus_top_ids = array_column($result, 'customer_id');
                $cus_info = $this->_get_cus_info_by_ids($cus_top_ids);
                $final_result = $this->_merge_cus_cate_result($result, $cus_info);
            }
        } else {
            $final_result = $this->_merge_cus_cate_result($result, $cus_info_filtered);
        }

        return ['data' => $final_result, 'count' => $count[0]['count']];
    }

    /**
     * 获取单个客户下单品类详情
     * @author zhangxiao@dachuwang.com
     */
    public function get_one_cus_cate_info() {
        //获取一级分类信息
        $cate_top = array_column($this->data['cate_top'], NULL, 'category_id');

        /**
         * 组装需要的数据库查询参数
         * $fields $where
         */
        $fields =[];
        foreach($cate_top as $value) {
            $field_cate = "SUM(CASE category_id WHEN ".$value['category_id']." THEN sale_amount ELSE 0 END) '".$value['category_name']."'";
            array_push($fields, $field_cate);
        }

        $where = [];
        $sdate         = $this->input->post('psdate');
        $edate         = $this->input->post('pedate');
        if($sdate && $edate) {
            $where['data_date >='] = $sdate;
            $where['data_date <='] = $edate;
        }
        $cus_id = $this->input->post('cus_id');
        if(!$cus_id) {
            $this->_return_json([
                'status' => C('status.req.failed'),
                'msg'    => 'lack of cus_id'
            ]);
        }
        $where['customer_id'] = $cus_id;
        $result = $this->MStatics_customer_cate->get_lists($fields, $where);
        //拼装接口所需格式
        $final_result =[];
        $total_amount = array_sum($result[0]) ?: 1;
        foreach($result[0] as $key => $value) {
            if($value === null) {
                $final_result = [];
                break;
            }
            array_push($final_result, ['name' => $key, 'value' => $value, 'percentage' => (number_format($value/$total_amount, 4) * 100).'%']);
        }

        $this->_return_json([
            'status' => C('status.req.success'),
            'msg'    => 'req sucess',
            'info'   => $final_result
        ]);

    }

    /**
     * 合并客户信息和客户品类信息
     * @author zhangxiao@dachuwang.com
     */
    private function _merge_cus_cate_result($result, $cus_info) {
        $city_names = $this->_get_city_names();
        foreach($result as &$value) {
            $cus_id = $value['customer_id'];
            if(isset($cus_info[$cus_id])) {
                $value['name'] = $cus_info[$cus_id]['name'];
                $value['shop_name'] = $cus_info[$cus_id]['shop_name'];
                $value['mobile'] = $cus_info[$cus_id]['mobile'];
            } else {
                $value['name'] = '-';
                $value['shop_name'] = '-';
                $value['mobile'] = '-';
            }
            $value['city_name'] = $city_names[$value['city_id']];
        }
        return $result;
    }

    /**
     * 获取城市名称
     * @author zhangxiao@dachuwang.com
     */
    private function _get_city_names() {
        $cities = C('open_cities');
        $return = array_column($cities, 'name', 'id');
        return $return;
    }

    /**
     * 生成时间筛选开始时间和结束时间
     * @author zhangxiao@dachuwang.com
     */
    private function _get_sdate_edate() {
        $return = [];
        $now = strtotime('now');
        //今天
        $return['today']['sdate'] = date('Y-m-d', $now);
        $return['today']['edate'] = date('Y-m-d', $now);
        //昨天
        $return['yesterday']['sdate'] = date('Y-m-d', strtotime('-1 day', $now));
        $return['yesterday']['edate'] = date('Y-m-d', strtotime('-1 day', $now));
        //本周
        $return['this_week']['sdate'] = date('Y-m-d', strtotime('-'.(date('w') - 1).' day', $now));
        $return['this_week']['edate'] = date('Y-m-d', $now);
        //上周
        $return['last_week']['sdate'] = date('Y-m-d', strtotime('-'.(date('w') - 1 + 7).' day', $now));
        $return['last_week']['edate'] = date('Y-m-d', strtotime('-'.(date('w') - 1 + 1).' day', $now));
        //本月
        $return['this_month']['sdate'] = date('Y-m', $now).'-01';
        $return['this_month']['edate'] = date('Y-m-t', $now);
        //上月
        $return['last_month']['sdate'] = date('Y-m', strtotime('-1 month', $now)).'-01';
        $return['last_month']['edate'] = date('Y-m-t', strtotime('-1 month', $now));

        return $return;
    }

}
