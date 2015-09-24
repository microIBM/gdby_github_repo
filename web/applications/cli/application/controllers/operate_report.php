<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 大厨网核心运营数据报表
 * Class operate_report
 */
class Operate_report extends MY_Controller{
    const TURNOVER_RATE_MIN = 0.03; //周转率分界点
    const GROSS_MARGIN_MIN = 0; //毛利率分界点
    const PIN_RATE_MIN = 0.1; //动销率分界点

    //发送邮件接口地址
    private $send_email_url = 'http://bi.dachuwang.com/email_report/send_email';

    public function __construct(){
        parent::__construct();
        $this->load->model(['MStatics_core_measure', 'MStatics_category_sales_reports']);
        $this->email_group = C('email_push_group');
    }

    /**
     * 发送邮件方法
     * @param string $data_date 默认跑昨日的数据
     * @return string|array 邮件发送成功或输出报错信息
     */
    public function send($data_date = 'yesterday'){
        $data_date = date("Y-m-d", strtotime($data_date));
        $this->send_city_sales_reports($data_date);
        $this->send_category_sales_reports($data_date);
    }

    /**
     * 发送分城市和类别邮件报表
     * @param string $data_date
     */
    public function send_city_sales_reports($data_date = 'yesterday'){
        $data_date = date("Y-m-d", strtotime($data_date));
        $header = ['城市', '签收单数', '签收金额', '金额占比', '采购成本', '毛利额', '毛利率', '库存成本', '周转率', '物流成本', '配送准点率', '投诉单数', '拒收金额', '退货金额'];
        //从数据库中获取数据
        $fields = ['city_name', 'sign_order_counts', 'sign_sale_amount', 'sign_amount_rate', 'buy_amount', 'margin', 'margin_rate', 'stock_total_amount', 'turnover_rate', 'transfer_total_cost', 'ontime_rate', 'complaint_order_counts', 'reject_sum', 'return_sum'];
        $where  = ['data_date' => $data_date, 'in' => ['city_id' => [C('open_cities.beijing.id'), C('open_cities.quanguo.id')]]];
        $order_by = ['city_id' => 'ASC'];
        $query_data = $this->MStatics_core_measure->get_lists($fields, $where, $order_by);
        if(empty($query_data)){
            echo $data_date . ' 从t_statics_core_measure表中没有查出任何数据';
            exit;
        }
        $returnArr = array();
        foreach($query_data as $val){
            $val['sign_amount_rate'] = ($val['sign_amount_rate'] * 100) . '%';
            $val['margin_rate'] = ($val['margin_rate'] * 100) . '%';
            $val['turnover_rate'] = ($val['turnover_rate'] * 100) . '%';
            $val['ontime_rate'] = ($val['ontime_rate'] * 100) . '%';
            $returnArr[] = array_values($val);
        }
        $table_count = 0;
        $table[$table_count] = [
            'title' => $this->email_group['city_sales_reports']['topic'],
            'header' => $header,
            'content' => $returnArr,
        ];
        /**
         * 发送报表邮件
         */
        $data = $this->format_query($this->send_email_url, array(
            'timeout'       => 60,
            'to'            => $this->email_group['city_sales_reports']['to'],
            'cc'            => $this->email_group['city_sales_reports']['cc'],
            'name'          => $this->email_group['city_sales_reports']['name'],
            'subject'       => $this->email_group['city_sales_reports']['subject'],
            'topic'         => $this->email_group['city_sales_reports']['topic'],
            'topic_desc'    => $this->email_group['city_sales_reports']['topic_desc'],
            'table'         => $table
        ), FALSE, TRUE);
        print_r($data);
    }

    /**
     * 发送分品类邮件报表
     * @param string $date_start
     */
    public function send_category_sales_reports($date_start = 'yesterday'){
        $date_start = date("Y-m-d", strtotime($date_start));
        $header = ['品类', '签收单数', '签收金额', '金额占比', '签收率', '采购成本', '毛利额', '毛利率', '库存金额', '库存占比', '周转率', '交叉比率', '动销率', '投诉单数', '拒收金额', '退货金额'];

        //从数据库中获取数据
        $fields = ['city_id', 'city_name', 'category_id', 'category_name', 'sign_orders', 'sign_amount', 'sign_amount_rate', 'sign_rate', 'purchase_amount', 'gross_profit_amount', 'gross_profit_margin', 'stock_cost', 'stock_cost_rate', 'turnover_rate', 'crossover_rate', 'pin_rate', 'complain_orders', 'rejection_amount', 'return_amount'];
        $where  = ['date_start' => $date_start, 'in' => ['city_id' => [C('open_cities.beijing.id')]]];
        $order_by = ['city_id' => 'ASC', 'category_id' => 'ASC'];
        $query_data = $this->MStatics_category_sales_reports->get_lists($fields, $where, $order_by);
        if(empty($query_data)){
            echo $date_start . ' 从t_statics_category_sales_reports表中没有查出任何数据';
            exit;
        }
        $cities_arr = array_column($query_data, 'city_name', 'city_id');
        $table_data_arr = array();
        //组成报表需要的数据格式
        foreach($query_data as $val){
            $city_id = $val['city_id'];
            $category_id = $val['category_id'];
            unset($val['city_id']);
            unset($val['city_name']);
            unset($val['category_id']);
            $val['sign_orders'] = intval($val['sign_orders']);
            $val['sign_amount_rate'] = ($val['sign_amount_rate'] * 100) . '%';
            $val['sign_rate'] = ($val['sign_rate'] * 100) . '%';
            if ($val['gross_profit_margin'] >= self::GROSS_MARGIN_MIN) {
               $val['gross_profit_margin'] = ($val['gross_profit_margin'] * 100) . '%'; 
            } else {
               $val['gross_profit_margin'] = "<font color＝'#FF0'>".($val['gross_profit_margin'] * 100) . '%</font>'; 
            }
            $val['stock_cost_rate'] = ($val['stock_cost_rate'] * 100) . '%';
            if ($val['turnover_rate'] >= self::TURNOVER_RATE_MIN) {
                $val['turnover_rate'] = ($val['turnover_rate'] * 100) . '%';
            } else {
                $val['turnover_rate'] = "<font color＝'#FF0'>".($val['turnover_rate'] * 100) . '%</font>';
            }
            $val['crossover_rate'] = ($val['crossover_rate'] * 100) . '%';
            if ($val['pin_rate'] >= self::PIN_RATE_MIN) {
                $val['pin_rate'] = ($val['pin_rate'] * 100) . '%';
            } else {
                $val['pin_rate'] = "<font color＝'#FF0'>".($val['pin_rate'] * 100) . '%</font>';
            }
            $table_data_arr[$city_id][$category_id] = $val;
        }
        //发送数据邮件
        $table = array();
        $table_count = 0;
        foreach($table_data_arr as $city_id => $sale_info){
            $city_name = $cities_arr[$city_id];
            $title = $city_name . '分品类基础销售数据';
            $table[$table_count] = [
                'title' => $title,
                'header' => $header,
                'content' => $sale_info
            ];
            $table_count++;//报表+1
        }
        /**
         * 发送报表邮件
         */
        $data = $this->format_query($this->send_email_url, array(
            'timeout'       => 60,
            'to'            => $this->email_group['category_sales_reports']['to'],
            'cc'            => $this->email_group['category_sales_reports']['cc'],
            'name'          => $this->email_group['category_sales_reports']['name'],
            'subject'       => $this->email_group['category_sales_reports']['subject'],
            'topic'         => $this->email_group['category_sales_reports']['topic'],
            'topic_desc'    => $this->email_group['category_sales_reports']['topic_desc'],
            'table'         => $table
        ), FALSE, TRUE);
        print_r($data);
    }
}