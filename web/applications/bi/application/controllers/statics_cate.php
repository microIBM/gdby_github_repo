<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * statics for category
 * @author zhangxiao@dachuwang.com
 * @version 1.0.0
 * @since 2015-08-10
 */

class Statics_cate extends MY_Controller {

    const YESTERDAY             = 2;  // 昨日
    const LAST_WEEK             = 3;  // 上周
    const LAST_MONTH            = 4;  // 上月

    const BY_DAY                = 1; // 按日统计,按自然日
    const BY_WEEK               = 3; // 按周统计,按自然周
    const BY_MONTH              = 2; // 按月统计,按自然月
    const WHITE_CATE_MODULE    = 12; //品类统计分析

    public function __construct() {
        parent::__construct();
        $this->load->Model(array(
            'MStatics_cate_day',
            'MStatics_cate_week',
            'MStatics_cate_month'
        ));
        $this->load->library(['excel_export']);
    }

    public function index() {
        
        $params = $this->_get_req_data();
        $this->data   = array_merge($this->data, $params);
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_CATE_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $this->data);
        } else {
            $this->data['load_js'] = ['cate_staticize.js', 'cate_lists_ajax.js'];
            $this->load->view('statics_cate', $this->data);
        }
    }

    /**
     * 获取品类分析数据
     * @author zhangxiao@dachuwang.com
     */
    public function get_lists($flag = false) {
        //将小数转化为百分数的字段
        $format_cloumn = array('sale_sku_kinds_margin', 'gross_margin', 'order_cus_margin', 'complaint_order_margin');
        
        $fields     = array('*');
        $sql_params = $this->_get_sql_params();
        $where      = $sql_params['where'];
        if (!isset($where['in'])) {
            $this->_return_json(array('status' => C('status.req.failed'), 'total' => 0, 'msg' => '失败', 'data' => array()));
        }
        $order_by   = $sql_params['order_by'];
        $offset     = $sql_params['offset'] ?: 0;
        $pagesize   = $sql_params['pagesize'] ?: 0;
        $model_name = $sql_params['model_name'];
        
//        print_r($sql_params);
        $data = $this->$model_name->get_lists($fields, $where, $order_by, array(), $pagesize, $offset);
        if($flag) {
            return $data;
        }
        $data = $this->_get_format_data($data, $format_cloumn);
        $count_fields = array('COUNT(*) as total');
        $count = $this->$model_name->get_lists($count_fields, $where);

        $results['status'] = C('status.req.success');
        $results['total']  = $count[0]['total'];
        $results['msg']    = '成功';
        $results['data']   = $data;

        return $this->_return_json($results);
    }

    /**
     * 获取数据库查询参数
     * @author zhangxiao@dachuwang.com
     */
    private function _get_sql_params($flag = true) {
        $return['where']    = array();
        $return['order_by'] = array();
        $return['offset']   = array();
        $return['pagesize'] = array();
        $params = $this->_get_req_data($flag);
        $return['model_name'] = $params['model_name'];

        if(isset($params['city_id'])) {
            $return['where']['city_id'] =  $params['city_id'];
        }
        if(isset($params['warehouse_id'])) {
            $return['where']['warehouse_id'] = $params['warehouse_id'];
        }
        if(isset($params['date_start >='])) {
            $return['where']['date_start >='] = $params['date_start >='];
        }
        if(isset($params['date_start <='])) {
            $return['where']['date_start <='] = $params['date_start <='];
        }
        if(isset($params['like'])) {
            $return['where']['like'] = $params['like'];
        }
        if(isset($params['page']) && isset($params['offset'])) {
            $return['pagesize'] = ($params['page'] - 1) * $params['offset'];
            $return['offset'] = $params['offset'];
        }
//         if(isset($params['path'])) {
//             $return['where'] = array_merge($return['where'], $params['path']);
//         }
        if(isset($params['in'])) {
        	$return['where']['in'] = $params['in'];
        }
        if(isset($params['sort_name']) && isset($params['sort_value'])) {
            $return['order_by'] = array($params['sort_name'] => $params['sort_value']);
        }
        //print_r($return);exit;
        return $return;
    }

    /**
     * 获取请求参数
     * @author zhangxiao@dachuwang.com
     */
    private function _get_req_data($flag = true) {

        $params['city_id']      = $this->input->get_post('city_id', true) ?: C('open_cities.beijing.id');
        $params['warehouse_id'] = $this->input->get_post('warehouse_id', true);
        $params['menue_id']     = $this->input->get_post('menue_id', true) ?: 7;
        $params['category_id']  = $this->input->get_post('category_id', true);
        $crumbs                 = $this->input->get_post('catenames', true);
        $catids                 = $this->input->get_post('cateids', true);

        if ($crumbs) {
            $params['crumbs'] = explode('-', trim($crumbs, '-'));
        }
        if ($catids) {
            $params['catids'] = explode('-', trim($catids, '-'));
        }
        $params['model_name']   = 'MStatics_cate_day';
        $tab_id = $this->input->get_post('tab_id', true);
        //如果是快捷时间
        if($tab_id) {
            switch ($tab_id) {
                case self::LAST_WEEK:
                    $params['model_name'] = 'MStatics_cate_week';
                    $params['date_start >='] = $this->get_quik_date()['prev_week_start'];
                    $params['date_start <='] = $this->get_quik_date()['prev_week_end'];
                    break;
                case self::LAST_MONTH:
                    $params['model_name'] = 'MStatics_cate_month';
                    $params['date_start >='] = $this->get_quik_date()['prev_month_start'];
                    $params['date_start <='] = $this->get_quik_date()['prev_month_end'];
                    break;
                case self::YESTERDAY:
                default:
                    $params['model_name'] = 'MStatics_cate_day';
                    $params['date_start >='] = $this->get_quik_date()['yesterday'];
                    $params['date_start <='] = $this->get_quik_date()['yesterday'];
            }
        }
        //如果是时间选择器
        $sdate = $this->input->get_post('sdate', true);
        $edate = $this->input->get_post('edate',true);
        if ($sdate) {
            $params['date_start >='] = $sdate;
            $date_mode = $this->input->get_post('date_mode', true);
            switch ($date_mode) {
                case self::BY_WEEK:
                    $params['date_start <='] = $edate;
                    $params['model_name'] = 'MStatics_cate_week';
                    break;
                case self::BY_MONTH:
                    $params['date_start <='] = date("Y-m", strtotime($sdate)).'-'.date('t',strtotime($sdate));
                    $params['model_name'] = 'MStatics_cate_month';
                    break;
                case self::BY_DAY:
                default:
                    $params['date_start <='] = $sdate;
                    $params['model_name'] = 'MStatics_cate_day';
            }
        }

        $search_key = $this->input->get_post('search_key', true);
        $search_value = $this->input->get_post('search_value', true);
        
        if($search_key == 'cate_id') {
            $params['like'] = array('category_id' => $search_value);
        } elseif ($search_key == 'cate_name' && $search_value) {
            $params['like'] = array('category_name' => $search_value);
        }

        $params['page']         = $this->input->get_post('page', true) !== false ? $this->input->get_post('page', true) : 1;
        $params['offset']       = $this->input->get_post('offset', true) !== false ? $this->input->get_post('offset', true) : 10;
        $params['sort_name']    = $this->input->post('sort_name', true) ?: 'sale_amount';
        $params['sort_value']   = $this->input->post('sort_value', true) ?: 'desc';
        
        $params['id3'] = $this->input->get_post("id3", true);
        $params['id2'] = $this->input->get_post("id2", true);
        $params['id1'] = $this->input->get_post("id1", true) ?: 0;
        
        if ($flag) {
            if($params['id3']) {
                    $params['in'] = array_column($this->get_category_info(TRUE, $params['id3']), 'category_id');
            } elseif ($params['id2']) {
                    $params['in'] = array_column($this->get_category_info(TRUE, $params['id2']), 'category_id');
            } else {
                    $params['in'] = array_column($this->get_category_info(TRUE, $params['id1']), 'category_id');
            }
            if (!empty($params['in'])) {
                $params['in'] = array('category_id' => $params['in']);
            }
        }

        $params = array_filter($params);
        return $params;
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

    /**
     * 返回品类信息接口
     * @author zhangxiao@dachuwang.com
     */
    public function get_category_info($return_array = FALSE, $category_id = NULL) {
        if (!$return_array) {
    		$category_id = $this->input->post('category_id') ? $this->input->post('category_id') : NULL;
        }
        $return = $this->format_query('statistics_bi/get_category_child', array(
            'category_id' => $category_id,
        ));
        if ($return['status'] === 0) {
            if($return_array) {
                return $return['data'];
            }
            $this->_return_result($return['data']);
        } else {
            if($return_array) {
                return array();
            }
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
     * 品类分析详情接口
     * @access public
     * @author wangzejun@dachuwang.com
     */
    public function detail(){
        $params = $this->_get_req_data();
        $this->data   = array_merge($this->data, $params);
        $this->data['load_js'] = ['cate_detail.js'];
        $this->load->view('statics_cate_detail', $this->data);
    }
    
    /**
     * 
     * @author wangzejun@dachuwang.com
     */
    public function get_cate_day_lists(){
        //将小数转化为百分数的字段
        $format_cloumn = array('sale_sku_kinds_margin', 'gross_margin', 'order_cus_margin', 'complaint_order_margin');
        //在页面显示table时一次对应的字段
        $cloumn = array('date_start', 'sale_amount', 'actual_sale_amount', 'buy_amount', 'sale_quantity', 'online_sku_counts', 'sale_sku_kinds_margin', 'gross_margin', 'order_cus_margin', 'complaint_order_margin', 'return_goods_orders', 'rejection_orders');
        //html table中显示的内容
        $tbody_html = '';
        $params = $this->_get_req_data();
        $fields     = array('*');
        $sql_params = $this->_get_sql_params(false);
        $where      = $sql_params['where'];
        $where['category_id'] = $params['category_id'];
        $data['day_lists'] = $this->MStatics_cate_day->get_lists($fields, $where, array('date_start' => 'DESC'));
        $data['day_lists'] = $this->_get_format_data($data['day_lists'], $format_cloumn);
        $date_mode = $this->input->get_post('date_mode', true);
        //按阶段统计总的量
        if ($date_mode == 3) {//按周
            $data['period_lists'] = $this->MStatics_cate_week->get_lists($fields, $where);
        } else if ($date_mode == 2) {//按月
            $data['period_lists'] = $this->MStatics_cate_month->get_lists($fields, $where);
        }
        if (isset($data['period_lists'])) {
            $data['period_lists'] = $this->_get_format_data($data['period_lists'], $format_cloumn);
            if (!empty($data['period_lists'])) {
                $data['period_lists'][0]['date_start'] = '总计';
            }
            $tbody_html .= $this->_assemble_table_list($data['period_lists'], $cloumn);
        }
        $tbody_html .= $this->_assemble_table_list($data['day_lists'], $cloumn, false);
        $this->_return_json($tbody_html);
    }
    
    /**
     * 将小数转为百分数
     * @author wangzejun@dachuwang.com
     * @param array $data 
     * @param type $cloumn 要转化的列
     */
    private function _get_format_data($data, $cloumn) {
        if (empty($cloumn)) {
            return $data;
        }
        foreach ($data as $key => &$val) {
            foreach ($cloumn as $k => $v) {
                $val[$v] = number_format($val[$v] * 100, 2) . '%';
            }
        }
        return $data;
    }

    /**
     * 
     * @author wangzejun@dachuwang.com
     * @param type $data array
     * @param type $cloumn 
     */
    private function _assemble_table_list($data, $cloumn, $flag = 1) {
        $params = $this->_get_req_data();
        $table_html = '';
        if (empty($cloumn)) {
            return $data;
        }
        if (empty($data)) {
            return $table_html;
        }
        foreach ($data as $key => $val) {
            $table_html .= "<tr>";
            foreach ($cloumn as $k => $v) {
               $table_html .= "<td>";
               $table_html .= $val[$v];
               $table_html .= "</td>";
            }
            if ($flag) {
               $table_html .= "<td>--</td>";
            } else {

                $url = $this->data['base_url'] . '/statics_sku?menue_id=6';
                if (isset($params['city_id'])) {
                    $url = $url . '&city_id=' . $params['city_id'];
                }
                if (isset($params['tab_id'])) {
                    $url = $url . '&tab_id=' . $params['tab_id'];
                }
                if ($date_mode = $this->input->get_post('date_mode', true)) {
                    $url = $url . '&date_mode=' . $date_mode;
                    if (isset($params['date_start >='])) {
                        $url = $url . '&sdate=' . $params['date_start >='];
                    }
                    if (isset($params['date_start <=']) && $date_mode == self::BY_WEEK) {
                        $url = $url . '&edate=' . $params['date_start <='];
                    } else if (isset($params['date_start >=']) && $date_mode == self::BY_DAY) {
                        $url = $url . '&edate=' . $params['date_start >='];
                    } else if (isset($params['date_start >=']) && $date_mode == self::BY_MONTH) {
                        $url = $url . '&edate=' . $params['date_start >='];
                    }
                }
                if (isset($params['warehouse_id'])) {
                    $url = $url . '&warehouse_id=' . $params['warehouse_id'];
                }
                if (isset($params['catids'])) {
                    if (count($params['catids']) == 3 || count($params['catids']) == 4) {
                        $url = $url . '&id1=' . $params['catids'][0] . '&id2='.$params['catids'][1] . '&id3='.$params['catids'][2];
                    } else if (count($params['catids']) == 2) {
                        $url = $url . '&id1=' . $params['catids'][0] . '&id2='.$params['catids'][1];
                    } else if (count($params['catids']) == 1) {
                        $url = $url . '&id1=' . $params['catids'][0];
                    }
                }
                $table_html = $table_html . '<td><a target="_blank" href="'. $url .'" class="btn btn-info">查看</a></td>';
            }
            $table_html .= "</tr>";
        }
        return $table_html;
    }
    
    /*
     * 将品类数据导出到excel
     * @author wangzejun@dachuwang.com
     */
    public function cate_export_excel(){
        $data = $this->get_lists(true);
        foreach ($data as &$value) {
            unset($value['id']);
            unset($value['category_id']);
            unset($value['path']);
            unset($value['out_stock_qty']);
            unset($value['inhive_date']);
            unset($value['return_order_margin']);
            unset($value['reject_sku_margin']);
        }
        $name = array('品类名称', '城市编号（804-北京，993-上海，1206-天津）', '仓库编号（7-北1，8-北2，9-天1，10-上1）', '下单金额', '签收金额',
            '采购成本', '签收件数', '在线sku数量', '动销的sku数量', 'sku总数', '动销率', '毛利率', '下该品类的客户数', '下单客户总数', '客户覆盖率', '下单总数', '投诉单数',
            '投诉率', '退货单数', '拒收单数', '查询起始日期', '查询截止日期');
        array_unshift($data, $name);
        $excel = new Excel_export();
        $res = $excel->export([
            $data
        ],['sku'],'sku.xlsx');
        exit;
    }
}
