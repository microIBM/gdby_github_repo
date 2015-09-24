<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 每日下单客户TOP10报表需求
 * Class Customer_top_ten
 */
class Customer_top_ten extends MY_Controller{
    private $start_time;//开始时间
    private $end_time;//结束时间
    //发送邮件接口地址
    private $send_email_url = 'http://bi.dachuwang.com/email_report/send_email';

    public function __construct(){
        parent::__construct();
        $this->load->model(['MOrder', 'MOrder_detail', 'MCustomer', 'MCategory']);
        $this->email_group = C('email_push_group');
    }

    /**
     * 发送邮件方法
     * @param string $start_date 默认跑昨日的数据
     * @return string|array 邮件发送成功或输出报错信息
     */
    public function send($start_date = 'yesterday'){
        $this->start_time = strtotime($start_date);
        $this->end_time = $this->start_time + 86400 - 1;
        $order_detail_arr = $this->MOrder_detail->get_order_by_time($this->start_time, $this->end_time, ['order_id', 'sum_price', 'category_id', 'city_id']);
        if(empty($order_detail_arr)){
            echo $start_date . ' 从order_detail表中没有查出任何数据';
            exit;
        }
        //根据订单id获取用户详情
        $order_ids = array_unique(array_column($order_detail_arr, 'order_id'));
        $user_ids = $this->MOrder->get_orderInfo_by_orderIds($order_ids, ['id', 'user_id']);
        if(empty($user_ids)){
            echo $start_date . ' 没有任何用户';
            exit;
        }
        $user_ids = array_column($user_ids, NULL, 'id');
        $user_info_arr= $this->MCustomer->lists_by_uids(array_column($user_ids, 'user_id'));

        //根据订单当前分类获取顶级分类
        $category_ids= array_unique(array_column($order_detail_arr, 'category_id'));
        $path_arr = $this->MCategory->get_path_by_categorys($category_ids);
        foreach($path_arr AS &$path){
            $path = explode('.', $path)[1];
        }

        //给order_detail数组加上user_id和top_category_id,便于计算
        $ana_arr = array();
        foreach($order_detail_arr AS &$detail){
            $detail['user_id'] = $user_ids[$detail['order_id']]['user_id'];
            $detail['top_category'] = $path_arr[$detail['category_id']];
            $ana_arr[$detail['city_id']][$detail['user_id']]['user_id'] = $detail['user_id'];
            //得到每个顶级分类的总下单金额
            if(isset($ana_arr[$detail['city_id']][$detail['user_id']][$detail['top_category']])){
                $ana_arr[$detail['city_id']][$detail['user_id']][$detail['top_category']] += $detail['sum_price']/100;
            }else{
                $ana_arr[$detail['city_id']][$detail['user_id']][$detail['top_category']] = $detail['sum_price']/100;
            }
            //得到肉和蔬菜的总下单金额
            if(in_array($detail['top_category'], [326, 269])){
                if(isset($ana_arr[$detail['city_id']][$detail['user_id']]['meat_greens'])){
                    $ana_arr[$detail['city_id']][$detail['user_id']]['meat_greens'] += $detail['sum_price']/100;
                }else{
                    $ana_arr[$detail['city_id']][$detail['user_id']]['meat_greens'] = $detail['sum_price']/100;
                }
            }
            //得到所有分类总下单金额
            if(isset($ana_arr[$detail['city_id']][$detail['user_id']]['total'])){
                $ana_arr[$detail['city_id']][$detail['user_id']]['total'] += $detail['sum_price']/100;
            }else{
                $ana_arr[$detail['city_id']][$detail['user_id']]['total'] = $detail['sum_price']/100;
            }
        }
        ksort($ana_arr);//按城市id进行排序
        //ok,发送邮件
        //组装表格header
        $top_category_list = $this->MCategory->get_category_child();
        $top_category_list = array_column($top_category_list, NULL, 'id');
        //unset($top_category_list[43]);//去除水果分类
        $header = ['排名', '客户店名', '客户姓名', '手机号', '总下单金额', '蔬菜+肉金额'];
        foreach($top_category_list AS $category){
            array_push($header, $category['name']);
        }
        //获取所有开放城市
        $cities_id = $this->get_cities();
        //对报表表格进行初始化
        $table = array();
        $table_count = 0;
        /**
         * 循环order_detail表数据进行数据计算处理
         */
        foreach($ana_arr AS $city_id => $sale_info){
            //获取总订单额TOP10
            $total_arr = $this->array_sort($sale_info, 'total');
            $total_arr = array_slice($total_arr, 0, 10, TRUE);

            //如果city_id不在配置文件中,那么不处理此城市
            if(!isset($cities_id[$city_id]['name'])){
                unset($ana_arr[$city_id]);
            }
            $city_name = $cities_id[$city_id]['name'];
            $title = $city_name . '总下单金额TOP10';
            $content = array();
            $rank_id = 1;
            /**
             * 按总下单金额排序组成报表数据
             */
            foreach($total_arr AS $user_id => $info){
                $shop_name = $user_info_arr[$user_id]['shop_name'];
                $customer_name = $user_info_arr[$user_id]['name'];
                $tel = $user_info_arr[$user_id]['mobile'];
                $total = $info['total'];
                if(isset($info['meat_greens'])){
                    $meat_greens = $info['meat_greens'];
                }else{
                    $meat_greens = 0;
                }
                $content[$rank_id] = [$rank_id, $shop_name, $customer_name, $tel, $total, $meat_greens];
                foreach($top_category_list AS $category){
                    $category_id = $category['id'];
                    if(isset($info[$category_id])){
                        array_push($content[$rank_id], $info[$category_id]);
                    }else{
                        array_push($content[$rank_id], 0);
                    }
                }
                $rank_id++;
            }
            $table[$table_count] = [
                'title' => $title,
                'header' => $header,
                'content' => $content
            ];
            $table_count++;//报表+1

            //获取蔬菜+肉订单额TOP10
            $meat_greens_arr = $this->array_sort($sale_info, 'meat_greens');
            $meat_greens_arr = array_slice($meat_greens_arr, 0, 10, TRUE);
            $title = $city_name . '蔬菜+肉下单金额TOP10';
            $rank_id = 1;
            /**
             * 按蔬菜+肉下单金额排序组成报表数据
             */
            foreach($meat_greens_arr AS $user_id => $info){
                $shop_name = $user_info_arr[$user_id]['shop_name'];
                $customer_name = $user_info_arr[$user_id]['name'];
                $tel = $user_info_arr[$user_id]['mobile'];
                $total = $info['total'];
                if(isset($info['meat_greens'])){
                    $meat_greens = $info['meat_greens'];
                }else{
                    $meat_greens = 0;
                }
                $content[$rank_id] = [$rank_id, $shop_name, $customer_name, $tel, $total, $meat_greens];
                foreach($top_category_list AS $category){
                    $category_id = $category['id'];
                    if(isset($info[$category_id])){
                        array_push($content[$rank_id], $info[$category_id]);
                    }else{
                        array_push($content[$rank_id], 0);
                    }
                }
                $rank_id++;
            }
            $table[$table_count] = [
                'title' => $title,
                'header' => $header,
                'content' => $content
            ];
            $table_count++;//报表+1
        }

        /**
         * 发送报表邮件
         */
        $data = $this->format_query($this->send_email_url, array(
            'to'      => $this->email_group['customer_top_ten']['to'],
            'cc'      => $this->email_group['customer_top_ten']['cc'],
            'name'      => $this->email_group['customer_top_ten']['name'],
            'subject' => $this->email_group['customer_top_ten']['subject'],
            'topic'   => $this->email_group['customer_top_ten']['topic'],
            'topic_desc'    => $this->email_group['customer_top_ten']['topic_desc'],
            'table' => $table
        ), FALSE, TRUE);
        print_r($data);
    }

    /**
     * 获取当前开放城市
     * @return array
     */
    private function get_cities(){
        $cities_arr = C('open_cities');
        unset($cities_arr['quanguo']);//去除全国
        return array_column($cities_arr, NULL, 'id');
    }

    /**
     * 按指定键对数组进行排序
     * @param $arr
     * @param $keys
     * @param string $type
     * @return array
     */
    private function array_sort($arr, $keys, $type='desc'){
        $keys_value = $new_array = array();
        foreach ($arr as $k=>$v){
            if(!isset($v[$keys])){
                $v[$keys] = 0;
            }
            $keys_value[$k] = $v[$keys];
        }
        if($type == 'asc'){
            asort($keys_value);
        }else{
            arsort($keys_value);
        }
        reset($keys_value);
        foreach ($keys_value as $k=>$v){
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

}