<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户购买详情脚本数据
 * Class Customer_top
 */
class Customer_top extends MY_Controller{

    private $start_time; //开始时间
    private $end_time; //结束时间
    private $data_date; //记录时间

    private $email_group;//邮件发送配置

    public function __construct(){
        parent::__construct();
        $this->email_group = C('email_push_group');
        $this->load->model(['MOrder', 'MOrder_detail', 'MCategory', 'MStatics_customer_cate']);
    }

    /**
     * 今日数据不断累计更新
     */
    public function index(){
        $this->start_time = strtotime(date("Y-m-d 00:00:00"));
        $this->end_time = time();
        $send_str = '';
        $send_str  .= $this->data_date = date("Y-m-d", $this->start_time);
        $insert_result = $this->statics_customer_cate();
        if(empty($insert_result)){
            echo $send_str .= '<br>执行成功，从order_detail表中没有查到销售数据，插入条数为0';
        }else{
            echo $send_str .= '<br>插入条数' . $insert_result['insert_counts'] . "<br>\n";
            echo $send_str .= '更新条数' . $insert_result['update_counts'] . "<br>\n";
        }
        $this->send_email($send_str);
    }

    /**
     * 跑历史数据 命令示例: php index.php customer_top history '2015-09-09' '2015-09-09'
     * @param string $start_date
     */
    public function history($start_date = 'yesterday', $end_date = ''){
        $this->start_time = strtotime(date("Y-m-d 00:00:00", strtotime($start_date)));
        if(empty($end_date)){
            $end_time = $this->start_time + 86400 - 1;
        }else{
            $end_time = strtotime($end_date);
        }
        $send_str = '';
        for(;$this->start_time <= $end_time; $this->start_time += 86400) {
            $this->end_time = $this->start_time + 86400 - 1;
            $send_str  .= $this->data_date = date("Y-m-d", $this->start_time);
            $insert_result = $this->statics_customer_cate();
            if(empty($insert_result)){
                echo $send_str .= '<br>执行成功，从order_detail表中没有查到销售数据，插入条数为0';
            }else{
                echo $send_str .= '<br>插入条数' . $insert_result['insert_counts'] . "<br>\n";
                echo $send_str .= '更新条数' . $insert_result['update_counts'] . "<br>\n";
            }
        }
        $this->send_email($send_str);
    }

    /**
     * 发送邮件方法
     * @param $send_str
     */
    private function send_email($send_str){
            $this->format_query('email_report/send', array(
            'timeout' => 60,
            'to'      => $this->email_group['customer_top']['to'],
            'cc'      => $this->email_group['customer_top']['cc'],
            'name'    => $this->email_group['customer_top']['name'],
            'subject' => $this->email_group['customer_top']['subject'],
            'title'   => ' ',
            'desc'    => [$send_str],
        ));
    }

    /**
     * 计算用户购买详情主方法
     * @return mixed
     */
    private function statics_customer_cate(){
        //获取下单和签收订单信息
        $order_detail_arr = $this->get_orders();
        if (empty($order_detail_arr)) {
            return 0;
        }
        //根据订单id获取用户详情
        $order_ids = array_unique(array_column($order_detail_arr, 'order_id'));
        $user_ids = $this->MOrder->get_orderInfo_by_orderIds($order_ids, ['id', 'user_id', 'line_id']);
        if (empty($user_ids)) {
            return 0;
        }
        $user_ids = array_column($user_ids, null, 'id');
        $fields = ['id', 'customer_type'];
        $where = [
            'in' => ['id' => array_column($user_ids, 'user_id')],
        ];
        $user_info_arr = $this->MCustomer->get_lists($fields, $where);
        $user_info_arr = array_column($user_info_arr, null, 'id');

        //根据订单当前分类获取所有上级分类
        $category_ids = array_unique(array_column($order_detail_arr, 'category_id'));
        $path_arr = $this->MCategory->get_path_by_categorys($category_ids);
        foreach ($path_arr as &$path) {
            $path = explode('.', trim($path, '.'));
        }
        //集合数据进行数据分析
        $ana_arr = array();
        foreach ($order_detail_arr AS $detail) {
            $user_id = $user_ids[$detail['order_id']]['user_id'];
            $ana_arr[$user_id]['line_id'] = $user_ids[$detail['order_id']]['line_id'];
            $ana_arr[$user_id]['user_id'] = $user_id;
            $ana_arr[$user_id]['city_id'] = $detail['city_id'];
            $ana_arr[$user_id]['user_type'] = $user_info_arr[$user_id]['customer_type'];
            //计算下单或签收金额
            $ana_count_type = 'order_count';
            $ana_sale_type = 'sale_amount';
            if ($detail['sign_status']) {
                $ana_count_type = 'sign_count';
                $ana_sale_type = 'sign_amount';
            }
            //依次计算底级至顶级分类
            $path_count_arr = [];
            foreach ($path_arr[$detail['category_id']] as $key => $category) {
                array_push($path_count_arr, $path_arr[$detail['category_id']][$key]);
                $ana_arr[$user_id][$category]['path'] = '.' . implode('.', $path_count_arr) . '.';
                if (isset($ana_arr[$user_id][$category][$ana_sale_type])) {
                    $ana_arr[$user_id][$category][$ana_sale_type] += $detail['sum_price'] / 100;
                } else {
                    $ana_arr[$user_id][$category][$ana_sale_type] = $detail['sum_price'] / 100;
                }
                $ana_arr[$user_id][$category][$ana_count_type][] = $detail['order_id'];
            }
        }
        //组成可以直接插入数据库的数组
        $insert_data_arr = $this->create_insert_data($ana_arr);
        return $this->MStatics_customer_cate->replace_into($insert_data_arr);
    }

    /**
     * 获取订单数据(包括已下单和已签收)
     * @return array
     */
    private function get_orders(){
        $fields = ['order_id', 'sum_price', 'category_id', 'city_id'];
        $order_detail_arr = $this->MOrder_detail->get_order_by_time($this->start_time, $this->end_time, $fields);
        //把签收订单拿过来一起计算
        $where = [
            'modify_time >=' => $this->start_time,
            'modify_time <=' => $this->end_time,
            'order_status' => C('order.status.wait_comment.code'),
        ];
        $sign_orders = [];
        //如果需要加入签收数据，可以在这里处理
//        $sign_orders = $this->MOrder_update->get_lists(['order_id'], $where);
//        if(!empty($sign_orders)){
//            $sign_orders = array_column($sign_orders, 'order_id');
//            $where = [
//                'in' => ['order_id' => $sign_orders],
//            ];
//            $sign_order_detail_arr = $this->MOrder_detail->get_lists($fields, $where);
//            $order_detail_arr = array_merge($order_detail_arr, $sign_order_detail_arr);
//        }
        //签收订单和下单订单进行区分
        foreach ($order_detail_arr as &$val) {
            if (in_array($val['order_id'], $sign_orders)) {
                $val['sign_status'] = 1;
            } else {
                $val['sign_status'] = 0;
            }
        }
        return $order_detail_arr;
    }

    /**
     * 组成可直接插入到数据库的数组
     * @param $ana_arr array 待分析的数组
     * @return array
     */
    private function create_insert_data(array $ana_arr){
        //组成可以向数据库插入的数组
        $insert_data_arr = array();
        $now_time = time();
        $i = 0;
        foreach ($ana_arr as $val) {
            foreach ($val as $key => $value) {
                $insert_data_arr[$i]['city_id'] = $val['city_id'];
                $insert_data_arr[$i]['line_id'] = $val['line_id'];
                $insert_data_arr[$i]['customer_id'] = $val['user_id'];
                $insert_data_arr[$i]['customer_type'] = $val['user_type'];
                $insert_data_arr[$i]['data_date'] = $this->data_date;
                $insert_data_arr[$i]['updated_time'] = $now_time;
                //如果是分类数组,那么生成新的一条数据库记录
                if (is_int($key) && is_array($value)) {
                    $insert_data_arr[$i]['category_id'] = $key;
                    $insert_data_arr[$i]['path'] = $value['path'];
                    //下单数
                    if (isset($value['order_count'])) {
                        $insert_data_arr[$i]['order_count'] = count(array_unique($value['order_count']));
                    } else {
                        $insert_data_arr[$i]['order_count'] = 0;
                    }
                    //下单金额
                    if (isset($value['sale_amount'])) {
                        $insert_data_arr[$i]['sale_amount'] = $value['sale_amount'];
                    } else {
                        $insert_data_arr[$i]['sale_amount'] = 0;
                    }
                    //签收数
                    if (isset($value['sign_count'])) {
                        $insert_data_arr[$i]['sign_count'] = count(array_unique($value['sign_count']));
                    } else {
                        $insert_data_arr[$i]['sign_count'] = 0;
                    }
                    //签收金额
                    if (isset($value['sign_amount'])) {
                        $insert_data_arr[$i]['sign_amount'] = $value['sign_amount'];
                    } else {
                        $insert_data_arr[$i]['sign_amount'] = 0;
                    }
                    $i++;
                }
            }
        }
        return $insert_data_arr;
    }
}