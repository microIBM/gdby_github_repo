<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * statics for SKU
 * @author zhangxiao@dachuwang.com
 * @version 1.0.0
 * @since 2015-07-20
 */

class Statics_sku extends MY_Controller {

    const TODAY                 = 1;  // 今天,按自然日
    const YESTERDAY             = 2;  // 昨日
    const LAST_WEEK             = 3;  // 上周
    const LAST_MONTH            = 4;  // 上月

    const BY_DAY                = 1; // 按日统计,按自然日
    const BY_WEEK               = 3; // 按周统计,按自然周
    const BY_MONTH              = 2; // 按月统计,按自然月

    const DAY_UNIXTIME          = 86400;//一天的时间戳

    const PAGE = 1; // 默认显示第一页
    const OFFSET = 10; // 默认每页10条纪录
    const OFFSET_15 = 15; // 每页15条纪录
    
    const WHITE_SKU_MODULE = 8; //sku分析白名单模块ID

    public function __construct() {
        parent::__construct();
        $this->load->helper('date', 'url');
        $this->load->library(['pagination', 'excel_export']);
        $this->load->helper('pagination');
        $this->load->model('MSku');
    }

    public function index() {
        
        $common_param = $this->get_query_params();
        $sku_param    = $this->get_sku_params();
        $quik_date    = $this->get_quik_date();
        $this->data   = array_merge($this->data,$common_param,$sku_param);
        $data         = $this->data;
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_SKU_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $data['load_js'] = ['sku_staticize.js', 'sku_lists_ajax.js'];
            $this->load->view('statics_sku', $data);
        }
    }

    public function detail(){
        $common_param = $this->get_query_params();
        $sku_param = $this->get_sku_params();
        $this->data = array_merge($this->data,$common_param,$sku_param);
        $this->data['sku_details'] =$this->get_sku_details();
        $this->data['quik_date'] = $this->get_quik_date();
        $this->data['sku_lists'] = $this->get_sku_day_lists();
        $warehouse_lists = $this->get_warehouse_info($this->data['city_id'], true);
        $temp_warehouse_lists = array();
        foreach($warehouse_lists as $value) {
            if(!preg_match('/测试/', $value['warehouse_name'])) {
                array_push($temp_warehouse_lists, $value);
            }
        }
        $this->data['warehouse_lists'] = $temp_warehouse_lists;
        $this->data['load_js'] = ['sku_staticize_detail.js'];
        $this->load->view('statics_sku_detail', $this->data);
    }

    /**
     * 返回品类信息接口
     * @author zhangxiao@dachuwang.com
     */
    public function get_category_info() {
        $category_id = $this->input->post('category_id') ? $this->input->post('category_id') : NULL;
        $return = $this->format_query('statistics_bi/get_category_child', array(
            'category_id' => $category_id,
            // 'timeout'     => 3600
        ));
        if ($return['status'] === 0) {
            $this->_return_result($return['data']);
        } else {
            $this->_return_result();
        }
    }

    /**
     * 返回所有仓库信息接口
     * @author zhangxiao@dachuwang.com
     */
    public function get_warehouse_info($city_id = 0, $return_arr=false) {
        $return = $this->format_query('statistics_bi/get_warehouse_by_location', array(
                'location_id' => $city_id,
                // 'timeout'     => 3600
        ));

        if ($return_arr && $return['status'] === 0) {
            return $return['data'];
        }
        if ($return['status'] === 0) {
            $this->_return_result($return['data']);
        } else {
            $this->_return_result();
        }
    }

    /**
     * 获取sku搜索列表页
     * 
     * @author wangzejun@dachuwang.com
     */
    public function get_search_sku_lists($flag = false){
        $post                    = array();
        $common_param            = $this->get_query_params();
        $sku_param               = $this->get_sku_params();
        $post['currentPage']     = (int)$sku_param['page'];
        $post['itemsPerPage']    = (int)$sku_param['offset'];
        $post['date_mode']       = $sku_param['date_mode'];
        $post['category_id3']    = (int)$sku_param['category_id3'];
        $post['category_id2']    = (int)$sku_param['category_id2'];
        $post['category_id1']    = (int)$sku_param['category_id1'];
        $post['sort_name']       = $sku_param['sort_name'];
        $post['sort_value']      = $sku_param['sort_value'];
        $post['city_id']         = $this->input->post('city_id', true);
        $post['timeout']         = 1000;

        // 获取sku货号或sku名称
        if ($sku_param['search_key'] == 0) {
            $post['sku_number'] = $sku_param['search_value'];
        } elseif ($sku_param['search_key'] == 1) {
            $post['sku_name'] = $sku_param['search_value'];
        }

        // //获取仓库
        $post['warehouse_id'] = $sku_param['warehouse_id'];

        //依据日期类型获取sku列表（月、周、日）
        if ($sku_param['sdate'] !== '') {
            switch ($sku_param['date_mode']) {
                case self::BY_DAY:
                    $post['stime']  = $sku_param['sdate'];
                    $post['etime']  = $sku_param['sdate'];
                    break;
                case self::BY_WEEK:
                    $post['stime']  = $sku_param['sdate'];
                    $post['etime']  = $sku_param['edate'];
                    break;
                case self::BY_MONTH:
                    $post['stime']  = $sku_param['sdate'];
                    $post['etime']  = $sku_param['sdate'] . '-' . date("t", strtotime($sku_param['sdate'])) ." 23:59:59";
                    break;
                default:
                    break;
            }
        } else {
            if($flag) {
                return $this->get_sku_by_tab_id($flag);
            }
            $this->get_sku_by_tab_id($flag);
        }
        $sku_lists = $this->format_query('staticize/get_search_sku_lists', $post);
        //这里可以增加实时信息接口
        if(!empty($sku_lists['data'])){
            $sku_numbers = array_unique(array_column($sku_lists['data'], 'sku_number'));
            $sku_status = $this->MSku->get_sku_info($sku_numbers, ['sku_number', 'status']);
            $sku_status = array_column($sku_status, NULL, 'sku_number');
            foreach($sku_lists['data'] AS &$val){
                if (!array_key_exists($val['sku_number'], $sku_status)) {
                    continue;
                }
                $val['status'] = $sku_status[$val['sku_number']]['status'];//sku上下架状态
            }
        }
        if($flag) {
            return $sku_lists;
        }
        $this->_return_json(
            array(
                'sku_lists' => $sku_lists, 
                'city_id' => $common_param['city_id'], 
                'menue_id' => 6
            )
        );
    }

    /**
     * 获取sku详细信息
     * @param unknown $sku_number
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function get_sku_details(){
        $sku_number   = $this->data['sku_number'];
        $city_id      = $this->data['city_id'];
        $warehouse_id = $this->data['warehouse_id'];
        $return = $this->format_query('statistics_bi/get_sku_info', array(
            'sku_number'   => $sku_number,
            'city_id'      => $city_id,
            'warehouse_id' => $warehouse_id
        ));
        if(!empty($return['data']['spec'])){
            $return['data']['spec'] = explode(';', trim($return['data']['spec'],';'));
        }
        return $return['status'] == 0 ? $return['data'] : array();
    }
    /**
     * 获取sku列表页及详情页get参数
     * 
     * @author yuanxiaolin@dachuwang.com
     */
    private function get_sku_params(){

        $param['category_id1'] = $this->input->get_post('id1', true) ?:1;
        $param['category_id2'] = $this->input->get_post('id2', true);
        $param['category_id3'] = $this->input->get_post('id3', true);
        $param['sku_number']   = $this->input->get_post('sku_number', true);
        $param['warehouse_id'] = $this->input->get_post('warehouse_id', true)  ?: 0;
        $param['sdate']        = $this->input->get_post('sdate', true) ?: '';
        $param['edate']        = $this->input->get_post('edate',true) ?: '';
        $param['date_mode']    = $this->input->get_post('date_mode', true) ?:self::BY_DAY;
        $param['search_key']   = $this->input->get_post('search_key', true);
        $param['search_value'] = $this->input->get_post('search_value', true);
        $param['page']         = $this->input->get_post('page', true) !== false ? $this->input->get_post('page', true) : 1;
        $param['offset']       = $this->input->get_post('offset', true) !== false ? $this->input->get_post('offset', true) : 10;
        $param['sort_name']    = $this->input->post('sort_name', true) ?: 'sale_amount';
        $param['sort_value']   = $this->input->post('sort_value', true) ?: 'desc';


        if ($this->input->get('is_search') && $param['date_mode'] == self::BY_MONTH) {
            //$param['sdate']    = $param['sdate'].'-01';
            $param['edate']    = $param['sdate'].'-'.date('t',strtotime($param['sdate']));
        }
        if(!$this->input->get('is_search')){
            $param['tab_id']   = $this->input->get('tab_id')?:self::YESTERDAY;
        }else{
            $param['tab_id']   = 0;
        }

        return $param;
    }


    /**
     * 根据tab_id获取sku列表页
     * 
     * @author wangzejun@dachuwang.com
     */
    public function get_sku_by_tab_id($flag = false){
        $quik_date             = $this->get_quik_date();
        $common_param          = $this->get_query_params();
        $sku_param             = $this->get_sku_params();
        $tab_id                = $this->input->post('tab_id') ?: self::YESTERDAY;

        $post                  = array();
        $post['warehouse_id']  = $sku_param['warehouse_id']; //beijing
        $post['tab_id']        = (int)$tab_id;
        $post['currentPage']   = (int)$sku_param['page'];
        $post['itemsPerPage']  = (int)$sku_param['offset'];

        $post['sort_name']     = $sku_param['sort_name'];
        $post['sort_value']    = $sku_param['sort_value'];
        $post['city_id']       = $this->input->post('city_id', true);
        $post['timeout']       = 1000;

        // 获取sku货号或sku名称
        if ($sku_param['search_key'] == 0) {
            $post['sku_number'] = $sku_param['search_value'];
        } elseif ($sku_param['search_key'] == 1) {
            $post['sku_name'] = $sku_param['search_value'];
        }

        switch ($tab_id) {
            case self::TODAY:
                if ((int)$sku_param['category_id3']) {
                    $post['category_id'] = (int)$sku_param['category_id3'];
                } elseif ((int)$sku_param['category_id2']) {
                    $post['category_id'] = (int)$sku_param['category_id2'];
                } else {
                    $post['category_id'] = (int)$sku_param['category_id1'];
                }
                $post['by_view'] = 1;
                $sku_lists = $this->format_query('statistics_bi/get_category_sales_info', $post);
                break;

            case self::LAST_WEEK:

                $post['stime']           = $quik_date['prev_week_start'];
                $post['etime']           = $quik_date['prev_week_end'];
                $post['category_id3']    = (int)$sku_param['category_id3'];
                $post['category_id2']    = (int)$sku_param['category_id2'];
                $post['category_id1']    = (int)$sku_param['category_id1'];
                $sku_lists               = $this->format_query('staticize/get_sku_lists_by_tab_id', $post);

                break;

            case self::LAST_MONTH:
                $post['stime']           = $quik_date['prev_month_start'];
                $post['etime']           = $quik_date['prev_month_end'];
                $post['category_id3']    = (int)$sku_param['category_id3'];
                $post['category_id2']    = (int)$sku_param['category_id2'];
                $post['category_id1']    = (int)$sku_param['category_id1'];
                $sku_lists               = $this->format_query('staticize/get_sku_lists_by_tab_id', $post);
                break;

            case self::YESTERDAY:
            default:
                $post['stime']           = $quik_date['yesterday'];
                $post['etime']           = $quik_date['yesterday'];
                $post['category_id3']    = (int)$sku_param['category_id3'];
                $post['category_id2']    = (int)$sku_param['category_id2'];
                $post['category_id1']    = (int)$sku_param['category_id1'];
                $sku_lists               = $this->format_query('staticize/get_sku_lists_by_tab_id', $post); 
                break;
        }
//        $this->_return_json(array('post'=>$post, 'sku_param'=>$sku_param));
        //这里可以增加实时信息接口
        if(!empty($sku_lists['data'])){
            $sku_numbers = array_unique(array_column($sku_lists['data'], 'sku_number'));
            $sku_status = $this->MSku->get_sku_info($sku_numbers, ['sku_number', 'status']);
            $sku_status = array_column($sku_status, NULL, 'sku_number');
            foreach($sku_lists['data'] AS &$val){
                if(!isset($sku_status[$val['sku_number']])){
                    continue;
                }
                $val['status'] = $sku_status[$val['sku_number']]['status'] ?: 0;//sku上下架状态
            }
        }
        if ($flag) {
            return $sku_lists;
        }
        $this->_return_json(
            array(
                'sku_lists' => $sku_lists, 
                'city_id' => $post['city_id'], 
                'menue_id' => 6
            )
        );

    }

    /**
     * 获取今日，昨日，上周，上月开始日期及结束日期
     * @return string
     * @author yuanxiaolin@dachuwang.com
     */
    private function get_quik_date(){
        $data['today'] = date('Y-m-d');
        $data['yesterday'] = date('Y-m-d',strtotime('-1 day'));
        $week_day = date('N')+6;
        $gone_day = date('N');
        $data['prev_week_start'] = date('Y-m-d',strtotime("-$week_day day"));
        $data['prev_week_end'] = date('Y-m-d',strtotime("-$gone_day day"));
        $data['prev_month_start'] = date('Y-m',strtotime("-1 month"));
        $data['prev_month_end'] = date('Y-m',strtotime("-1 month")).'-'.date('t',strtotime('-1 month'));
        return $data;
    }

    private function get_sku_day_lists(){

        $sdate = $this->data['sdate'] ? : date('Y-m-d',strtotime('-1 day'));
        $edate = $this->data['edate'] ? : $sdate;
        if($this->input->get('from_list') == 1){
            $date = $this->tab_to_date();
            $sdate = $date['sdate'];
            $edate = $date['edate'];
        }
        $post_data = array(
            'sku_number' => $this->data['sku_number'],
            'city_id' => $this->data['city_id'],
            'warehouse_id' => $this->data['warehouse_id'],
            'sdate' => $sdate,
            'edate' => date("Y-m-d",strtotime($edate) + self::DAY_UNIXTIME),
            'date_mode' => $this->data['date_mode'],
        );

        if($this->data['tab_id'] == self::BY_DAY){
            //调用实时接口
            $data = array();
            $return = $this->format_query('statistics_bi/get_sku_sales_info',$post_data);
            if ($return['status'] == 0) {
                $data['day_lists'][] = $return['data'];
            }
            return $data;
        }else{
            $return = $this->format_query('staticize/get_sku_daylists',$post_data);
            return $return['status'] == 0 ? $return['data'] : array();
        }
    }
    
    private function tab_to_date(){
        $date = array();
        $quick_date = $this->get_quik_date();
        switch ($this->data['tab_id']) {
            case self::TODAY:
                $date['sdate'] = $date['edate'] = $quick_date['today'];
                break;
            case self::LAST_WEEK:
                $date['sdate'] = $quick_date['prev_week_start'];
                $date['edate'] = $quick_date['prev_week_end'];
                break;
            case self::LAST_MONTH:
                $date['sdate'] = $quick_date['prev_month_start'];
                $date['edate'] = $quick_date['prev_month_end'];
                break;
            case self::YESTERDAY:
            default:
                $date['sdate'] = $date['edate'] = $quick_date['yesterday'];
                break;
        }
        return $date;
    }
    
    /*
     * 将sku数据导出到excel
     * @author wangzejun@dachuwang.com
     */
    public function sku_export_excel(){
        $data = $this->get_search_sku_lists(true);
        foreach ($data['data'] as &$value) {
            unset($value['id']);
            unset($value['order_counts']);
            unset($value['order_cus_counts']);
            unset($value['total_order_cus_counts']);
            unset($value['path']);
            unset($value['inhive_date']);
            unset($value['status']);
        }
        $name = array('货号', '名称', '下单金额', '下单数量', '签收金额',
            '签收件数', '实时在库量', '实时可售量', '平均采购价', '平均销售价', '拒收件数',  '出库件数','投诉单数', 
            '退货单数', '退货件数', '仓库编号（7-北1，8-北2，9-天1，10-上1）', '城市编号（804-北京，993-上海，1206-天津）', '滞销天数', '毛利率', '客户覆盖率',
            '拒收率', '查询日期');
        array_unshift($data['data'], $name);
        $excel = new Excel_export();
        $res = $excel->export([
            $data['data']
        ],['sku'],'sku.xlsx');
//        var_dump($data[0]);
        exit;
    }
}
