<?php
if( !defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * @description BI系统，对品类和sku销量数据进行分析的类
 * @author yelongyi@dachuwang.com
 * @since 2015-07-19
 * @version 2015-07-22 17:47:01
 */
class Statistics_bi extends MY_Controller{
    //WMS接口
    private $wms_req_url;
    //CC接口
    private $crm_cc_url = 'sku/count_abnormal';

    public function __construct(){
        ini_set('memory_limit', '1024M');
        parent::__construct();
        $this->load->model(['MSku', 'MCategory', 'MOrder_detail', 'MOrder', 'MLine', 'MComplaint_content', 'MAbnormal_content', 'MProduct', 'MStock','MBucket']);
        $this->wms_req_url = C('wms_sku_url.api');
    }

    /**
     * 接口:通过品类id，获取该品类下的所有sku的销售信息
     * #param int category_id 品类id，必传
     * #param int stime 开始时间戳，不传默认今天的开始时间
     * #param int etime 结束时间戳，不传默认今天的结束时间
     * #param int city_id 城市id，不传默认北京
     * #param int warehouse_id 仓库id
     * @return json json格式信息
     */
    public function get_category_sales_info(){
        try{
            //参数初始化
            $category_id = $this->input->post('category_id');
            $warehouse_id = $this->input->post('warehouse_id');
            if(!is_numeric($category_id) || !is_numeric($warehouse_id)){
                throw new Exception('没有传参数category_id或warehouse_id');
            }
            $stime = $this->input->post('stime') ?: strtotime(date('Y-m-d 00:00:00'));
            $etime = $this->input->post('etime') ?: strtotime(date('Y-m-d 23:59:59'));
            $city_id = $this->input->post('city_id') ?: C('open_cities.beijing.id');
            $by_view = $this->input->post('by_view');//是否是通过前端页面调用,如果为1,返回sku上下架状态
            $cross = FALSE;//是否开启全域名http请求
            if(empty($by_view)){
              $this->crm_cc_url = C('service.cli') . '/' . $this->crm_cc_url;
                $cross = TRUE;
            }
            /**
             * 获取WMS数据.平均采购价 => average_buy_price | sku出库总数 => out_warehouse_sku_counts | 拒收sku数 => reject_sku_counts | 实际销售额 => actual_sale_amount | 实际销售件数 => actual_sale_count
             * @todo 分类需要传三个参数，这里暂时只写一个
             */
            $wms_data_arr = $this->format_query($this->wms_req_url, array('timeout' => 60, 'category_id1' => $category_id, 'stime' => $stime, 'etime' =>  $etime, 'warehouse_id' => $warehouse_id), FALSE, TRUE);
            //数据只有异常才会返回空,这时候可以认为请求失败了.
            if(empty($wms_data_arr)){
                $message = 'statistics_bi请求接口失败,接口地址:' . $this->wms_req_url.';参数为:category_id1:'. $category_id . '-warehouse_id:' . $warehouse_id . '-stime:' . $stime . '-etime' . $etime.';返回结果:' . print_r($wms_data_arr);
                log_message('error', $message);//做个记错误日志的处理.
                throw new Exception('statistics_bi请求接口失败');
            }
            if(isset($wms_data_arr['list']) && !empty($wms_data_arr['list'])){
                $wms_data_arr = array_column($wms_data_arr['list'], NULL, 'sku_number');
            }else {
                $this->success(['data' => array()]);//接口取得的数据为空,返回空结果
            }

            $sku_numbers_arr = array_keys($wms_data_arr);
            $sku_info = $this->MSku->get_sku_info($sku_numbers_arr, ['sku_number', 'name', 'category_id', 'status',]);
            $sku_info = array_column($sku_info, NULL, 'sku_number');
            $sku_category_arr = array_unique(array_column($sku_info,'category_id'));
            $skus_path = $this->MCategory->get_path_by_categorys($sku_category_arr);

            //下单顾客数 总数 get
            $total_order_cus_counts = $this->MOrder->get_total_ordered_customer(array('stime' => $stime, 'etime' => $etime), TRUE);
            /**
             * sku库存数据. 实时在库量 => in_stock | 实时可售量 => (in_stock - stock_locked)
            */
            $stock_arr = $this->MStock->get_in_warehouse($sku_numbers_arr, $warehouse_id);

            /**
             * 获取前端数据.销售件数 => sale_quantity | 下单顾客数 => order_cus_counts | 已完成订单数 => order_counts |
             */
            $this->db->reconnect();//防止MySQL超时
            $ana_sku_Arr = $this->get_sale_info($sku_numbers_arr, $stime, $etime, $city_id, $warehouse_id);
            //流水 => sale_amount  滞销天数 => unsalable_day_counts
            $ana_sku_amount_arr = $this->get_sale_amount($sku_numbers_arr, $stime, $etime, $city_id, $warehouse_id);
            /**
             * 获取cc支持数据. sku的投诉单数 => complaint_order_counts | sku的已处理退货退款单数 => return_order_counts
             */
            $count_abnormal_arr = $this->format_query($this->crm_cc_url, array('timeout' => 1000, 'sku_numbers' => $sku_numbers_arr, 'stime' => $stime, 'etime' =>  $etime), FALSE, $cross);
            if(isset($count_abnormal_arr['list']) && $count_abnormal_arr['status'] == 0){
                $count_abnormal_arr = array_column($count_abnormal_arr['list'], NULL, 'sku_number');
            }else{
                $message = 'statistics_bi请求接口失败,接口地址:' . $this->crm_cc_url.';参数为:sku_numbers:'. print_r($sku_numbers_arr) .'-stime:' . $stime . '-etime' . $etime.';返回结果:' . print_r($count_abnormal_arr);
                log_message('error', $message);//做个记错误日志的处理.
            }
            //3. 组装结果数组并返回json结果
            $i = 0;
            $returnArr = array();
            $unsalable_day_counts = round(($etime - $stime) / 86400);//滞销天数初始化
            foreach($wms_data_arr AS $key => $val){
                $sku_number               = $key;
                $sku_name                 = !empty($sku_info[$key]['name']) ? $sku_info[$key]['name'] : '';
                $sale_amount              = isset($ana_sku_amount_arr[$key]['sale_amount']) ? $ana_sku_amount_arr[$key]['sale_amount'] : 0;
                $order_sku_counts         = isset($ana_sku_amount_arr[$key]['order_sku_counts']) ? $ana_sku_amount_arr[$key]['order_sku_counts'] : 0;
                $sale_quantity            = isset($ana_sku_Arr[$key]['sale_quantity']) ? $ana_sku_Arr[$key]['sale_quantity'] : 0;
                $order_counts             = isset($ana_sku_Arr[$key]['order_counts']) ? $ana_sku_Arr[$key]['order_counts'] : 0;
                $order_cus_counts         = isset($ana_sku_amount_arr[$key]['order_cus_counts']) ? $ana_sku_amount_arr[$key]['order_cus_counts'] : 0;
                $total_order_cus_counts   = $total_order_cus_counts ? $total_order_cus_counts : 0;
                //$actual_sale_count        = isset($val['actual_sale_count']) ? $val['actual_sale_count'] : 0;
                //$actual_sale_amount       = isset($val['actual_sale_amount']) ? $val['actual_sale_amount'] : 0;
                $actual_sale_amount       = isset($ana_sku_Arr[$key]['actual_sale_amount']) ? $ana_sku_Arr[$key]['actual_sale_amount'] : 0;//tms数据不准确,这里采用前端数据
                $quantity_inwarehouse     = isset($stock_arr[$key]['in_stock']) ? $stock_arr[$key]['in_stock'] : 0;
                $quantity_salable         = isset($stock_arr[$key]['stock_locked']) ? ($quantity_inwarehouse - $stock_arr[$key]['stock_locked']) : 0;
                $average_buy_price        = isset($val['average_buy_price']) ? $val['average_buy_price'] : 0;
                if(!empty($sale_quantity) && ($sale_quantity != 0)){
                    $average_sale_price   = round($actual_sale_amount / $sale_quantity, 2);
                }else{
                    $average_sale_price   = 0.00;
                }
                $reject_sku_counts        = isset($val['reject_sku_counts']) ? $val['reject_sku_counts'] : 0;
                $out_warehouse_sku_counts = isset($val['out_warehouse_sku_counts']) ? $val['out_warehouse_sku_counts'] : 0;
                $complaint_order_counts   = isset($count_abnormal_arr[$key]['complaint_order_counts']) ? $count_abnormal_arr[$key]['complaint_order_counts'] : 0;
                $return_order_counts      = isset($count_abnormal_arr[$key]['return_order_counts']) ? $count_abnormal_arr[$key]['return_order_counts'] : 0;
                $return_sku_counts        = isset($count_abnormal_arr[$key]['return_sku_counts']) ? $count_abnormal_arr[$key]['return_sku_counts'] : 0;
                $path                     = $skus_path[$sku_info[$key]['category_id']];
                $unsalable_day_counts     = isset($ana_sku_amount_arr[$key]['unsalable_day_counts']) ? $ana_sku_amount_arr[$key]['unsalable_day_counts'] : $unsalable_day_counts;
                if(!empty($average_sale_price) && ($average_sale_price != 0)){
                    $margin_rate = round((1 - $average_buy_price / $average_sale_price), 4);
                }else{
                    $margin_rate = 0.0000;
                }
                if(!empty($total_order_cus_counts) && ($total_order_cus_counts != 0)){
                    $cover_rate = round($order_cus_counts / $total_order_cus_counts, 4);
                }else{
                    $cover_rate = 0.0000;
                }
                if(!empty($out_warehouse_sku_counts) && ($out_warehouse_sku_counts != 0)){
                    $reject_rate = round($reject_sku_counts / $out_warehouse_sku_counts, 4);
                }else{
                    $reject_rate = 0.0000;
                }

                $returnArr['data'][$i]['sku_number']                 = $sku_number;                 //货号
                $returnArr['data'][$i]['sku_name']                   = $sku_name;                   //名称
                $returnArr['data'][$i]['sale_amount']                = $sale_amount;                //流水
                $returnArr['data'][$i]['order_sku_counts']           = intval($order_sku_counts);           //下单数量 所有已确认订单中该sku的数量总和
                $returnArr['data'][$i]['sale_quantity']              = intval($sale_quantity);              //销售数量
                $returnArr['data'][$i]['order_counts']               = intval($order_counts);               //已完成订单数
                $returnArr['data'][$i]['order_cus_counts']           = intval($order_cus_counts);           //下单顾客数
                $returnArr['data'][$i]['total_order_cus_counts']     = intval($total_order_cus_counts);     //下单顾客总数
                $returnArr['data'][$i]['actual_sale_amount']         = $actual_sale_amount;         //实际销售额
                $returnArr['data'][$i]['quantity_inwarehouse']       = intval($quantity_inwarehouse);       //实时在库量
                $returnArr['data'][$i]['quantity_salable']           = intval($quantity_salable);           //实时可售量
                $returnArr['data'][$i]['average_buy_price']          = $average_buy_price;          //平均采购价
                $returnArr['data'][$i]['average_sale_price']         = $average_sale_price;         //平均销售价
                $returnArr['data'][$i]['reject_sku_counts']          = intval($reject_sku_counts);          //拒收sku数
                $returnArr['data'][$i]['out_warehouse_sku_counts']   = intval($out_warehouse_sku_counts);   //sku出库销售总数
                $returnArr['data'][$i]['complaint_order_counts']     = intval($complaint_order_counts);     //sku的质量问题的投诉单数
                $returnArr['data'][$i]['return_order_counts']        = intval($return_order_counts);        //sku的已处理退货退款单数
                $returnArr['data'][$i]['return_sku_counts']          = intval($return_sku_counts);          //sku的已处理退货件数
                $returnArr['data'][$i]['path']                       = $path;                       //sku的category的path
                $returnArr['data'][$i]['unsalable_day_counts']       = intval($unsalable_day_counts);       //滞销天数
                $returnArr['data'][$i]['margin_rate']                = $margin_rate;                //毛利率
                $returnArr['data'][$i]['cover_rate']                 = $cover_rate;                 //客户覆盖率
                $returnArr['data'][$i]['reject_rate']                = $reject_rate;                //拒收率
                $returnArr['data'][$i]['data_date']                  = $stime;                      //该条记录所指代的时间
                $returnArr['data'][$i]['city_id']                    = $city_id;                    //城市id
                $returnArr['data'][$i]['warehouse_id']               = $warehouse_id;               //仓库id
                $i++;
            }
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 获取sku详情页今日信息
     */
    public function get_sku_sales_info(){
        try{
            //参数初始化
            $category_id = 0;
            $warehouse_id = $this->input->post('warehouse_id');
            $city_id = $this->input->post('city_id') ?: C('open_cities.beijing.id');
            if(!is_numeric($category_id) || !is_numeric($warehouse_id)){
                throw new Exception('没有传参数category_id 和 warehouse_id');
            }
            $stime = strtotime(date('Y-m-d 00:00:00'));
            $etime = strtotime(date('Y-m-d 23:59:59'));
            $sku_number = $this->input->post('sku_number');
            $sku_info = $this->MSku->get_sku_info($sku_number, ['category_id', 'name', 'status']);
            if(empty($sku_info)){
                throw new Exception('传入的sku不存在');
            }
            $sku_category = $sku_info[0]['category_id'];
            //sku name get
            $sku_name = $sku_info[0]['name'];
            //sku 上下架状态
            $sku_status = $sku_info[0]['status'];

            $sku_path = $this->MCategory->get_path_by_categorys(array($sku_category));
            //sku_path get
            $sku_path = $sku_path[$sku_category];

            //下单顾客数 总数 get
            $total_order_cus_counts = $this->MOrder->get_total_ordered_customer(array('stime' => $stime, 'etime' => $etime), TRUE);

            $stock_arr = $this->MStock->get_in_warehouse(array($sku_number), $warehouse_id);
            //实时在库量 get
            $quantity_inwarehouse = $stock_arr[$sku_number]['in_stock'];
            //实时可售量 get
            $quantity_salable = $quantity_inwarehouse - $stock_arr[$sku_number]['stock_locked'];


            /**
             * 获取前端数据
             * 销售件数 => sale_quantity
             * 下单顾客数 => order_cus_counts
             * 已完成订单数 => order_counts
             * 滞销天数 => unsalable_day_counts
             */
            $ana_sku_Arr = $this->get_sale_info(array($sku_number), $stime, $etime, $city_id, $warehouse_id);
            //单独获取流水 => sale_amount
            $ana_sku_amount_arr = $this->get_sale_amount(array($sku_number), $stime, $etime, $city_id, $warehouse_id);
            $sale_amount            = isset($ana_sku_amount_arr[$sku_number]['sale_amount']) ? $ana_sku_amount_arr[$sku_number]['sale_amount'] : 0;
            $actual_sale_amount     = isset($ana_sku_Arr[$sku_number]['actual_sale_amount']) ? $ana_sku_Arr[$sku_number]['actual_sale_amount'] : 0;
            $sale_quantity          = isset($ana_sku_Arr[$sku_number]['sale_quantity']) ? $ana_sku_Arr[$sku_number]['sale_quantity'] : 0;
            $order_sku_counts       = isset($ana_sku_amount_arr[$sku_number]['order_sku_counts']) ? $ana_sku_amount_arr[$sku_number]['order_sku_counts'] : 0;
            $order_cus_counts       = isset($ana_sku_amount_arr[$sku_number]['order_cus_counts']) ? $ana_sku_amount_arr[$sku_number]['order_cus_counts'] : 0;
            $order_counts           = isset($ana_sku_Arr[$sku_number]['order_counts']) ? $ana_sku_Arr[$sku_number]['order_counts'] : 0;
            $unsalable_day_counts   = isset($ana_sku_Arr[$sku_number]['unsalable_day_counts']) ? $ana_sku_Arr[$sku_number]['unsalable_day_counts'] : 0;

            $count_abnormal_tmp = $this->format_query($this->crm_cc_url, array('timeout' => 1000, 'sku_numbers' => array($sku_number), 'stime' => $stime, 'etime' =>  $etime));
            if(isset($count_abnormal_tmp['status'])){
                //sku的投诉单数 get
                $complaint_order_counts = $count_abnormal_tmp['list'][0]['complaint_order_counts'];
                //sku的已处理退货退款单数 get
                $return_order_counts = $count_abnormal_tmp['list'][0]['return_order_counts'];
                $return_sku_counts = $count_abnormal_tmp['list'][0]['return_sku_counts'];
            }else{
                $complaint_order_counts = 0;
                $return_order_counts = 0;
                $return_sku_counts = 0;
            }


            /**
             * 获取WMS数据
             * 平均采购价 => average_buy_price
             * sku出库总数 => out_warehouse_sku_counts
             * 拒收sku数 => reject_sku_counts
             * 实际销售额 => actual_sale_amount
             * 实际销售件数 => actual_sale_count
             * @todo 分类需要传三个参数，这里只需要写一个
             */
            //这里只传sku_number stime etime warehouse即可
            $wms_data_tmp = $this->format_query($this->wms_req_url, array('timeout' => 1000, 'sku_number' => $sku_number, 'category_id1' => 1, 'stime' => $stime, 'etime' =>  $etime, 'warehouse_id' => $warehouse_id), FALSE, TRUE);
            $average_buy_price          = isset($wms_data_tmp['list'][0]['average_buy_price']) ? $wms_data_tmp['list'][0]['average_buy_price']            : 0;
            $out_warehouse_sku_counts   = isset($wms_data_tmp['list'][0]['out_warehouse_sku_counts']) ? $wms_data_tmp['list'][0]['out_warehouse_sku_counts']: 0;
            $reject_sku_counts          = isset($wms_data_tmp['list'][0]['reject_sku_counts'])  ? $wms_data_tmp['list'][0]['reject_sku_counts']             : 0;
            //$actual_sale_amount         = isset($wms_data_tmp['list'][0]['actual_sale_amount']) ? $wms_data_tmp['list'][0]['actual_sale_amount']            : 0;
            //$actual_sale_count          = isset($wms_data_tmp['list'][0]['actual_sale_count'])  ? $wms_data_tmp['list'][0]['actual_sale_count']             : 0;
            $average_sale_price         = (empty($sale_quantity) || empty($actual_sale_amount)) ? 0 : $sale_quantity/$actual_sale_amount;//平均销售价
            if(!empty($average_buy_price) && $average_buy_price != 0){
                $margin_rate = round(($average_sale_price / $average_buy_price - 1), 4);
            }else{
                $margin_rate = 0.0000;
            }
            if(!empty($total_order_cus_counts) && $total_order_cus_counts != 0){
                $cover_rate = round($order_cus_counts / $total_order_cus_counts, 4);
            }else{
                $cover_rate = 0.0000;
            }
            if(!empty($out_warehouse_sku_counts) && $out_warehouse_sku_counts != 0){
                $reject_rate = round($reject_sku_counts / $out_warehouse_sku_counts, 4);
            }else{
                $reject_rate = 0.0000;
            }
            //3. 组装结果数组并返回json结果
            $returnArr = array();
                $returnArr['data']['sku_number']                 = $sku_number;//货号
                $returnArr['data']['sku_name']                   = $sku_name;//名称
                $returnArr['data']['sale_amount']                = $sale_amount;//流水
                $returnArr['data']['order_sku_counts']           = $order_sku_counts;//下单数量 所有已确认订单中该sku的数量总和
                $returnArr['data']['sale_quantity']              = $sale_quantity;//销售数量
                $returnArr['data']['order_counts']               = $order_counts;//已完成订单数
                $returnArr['data']['order_cus_counts']           = $order_cus_counts;//下单顾客数
                $returnArr['data']['total_order_cus_counts']     = $total_order_cus_counts;//下单顾客总数
                $returnArr['data']['actual_sale_amount']         = $actual_sale_amount;//实际销售额
                $returnArr['data']['quantity_inwarehouse']       = $quantity_inwarehouse;//实时在库量
                $returnArr['data']['quantity_salable']           = $quantity_salable;//实时可售量
                $returnArr['data']['average_buy_price']         = $average_buy_price;//平均采购价
                $returnArr['data']['average_sale_price']         = (empty($actual_sale_count) || empty($actual_sale_amount)) ? 0 : $actual_sale_count/$actual_sale_amount;//平均销售价
                $returnArr['data']['reject_sku_counts']          = $reject_sku_counts;//拒收sku数
                $returnArr['data']['out_warehouse_sku_counts']   = $out_warehouse_sku_counts;//sku出库销售总数
                $returnArr['data']['complaint_order_counts']     = $complaint_order_counts;//sku的质量问题的投诉单数
                $returnArr['data']['return_order_counts']        = $return_order_counts;//sku的已处理退货退款单数
                $returnArr['data']['return_sku_counts']          = $return_sku_counts;//sku的已处理退货退款件数
                $returnArr['data']['margin_rate']                = $margin_rate;                //毛利率
                $returnArr['data']['cover_rate']                 = $cover_rate;                 //客户覆盖率
                $returnArr['data']['reject_rate']                = $reject_rate;                //拒收率
                $returnArr['data']['city_id']                    = $city_id;//城市id
                $returnArr['data']['warehouse_id']               = $warehouse_id;//仓库id
                $returnArr['data']['data_date']                  = $stime;//sku的category的path
                $returnArr['data']['path']                       = $sku_path;//sku的category的path
                $returnArr['data']['unsalable_day_counts']       = $unsalable_day_counts;//滞销天数
                $returnArr['data']['status']                     = intval($sku_status);//上下架状态
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 接口:获取单个sku的基本信息
     * #param sku_number 通过post方式传过来的sku_number
     * @return json 返回json格式的信息
     */
    public function get_sku_info(){
        try{
            $sku_num = $this->input->post('sku_number');
            $city_id = $this->input->post('city_id');
            $warehouse_id = $this->input->post('warehouse_id');
            if(!is_numeric($sku_num)){
                throw new Exception('没有传入sku_number');
            }
            if(!is_numeric($city_id)){
                throw new Exception('没有传入city_id');
            }
            if(!is_numeric($warehouse_id)){
                throw new Exception('没有传入warehouse_id');
            }
            $sku_info_arr = $this->MSku->get_sku_info($sku_num);
            if(empty($sku_info_arr)){
                throw new Exception('没有任何数据能够被返回');
            }
            $returnArr = array();
            $returnArr['data'] = $sku_info_arr[0];
            $oldSpec = json_decode($sku_info_arr[0]['spec']);
            $newSpec = '';
            foreach($oldSpec AS $val){
                $newSpec .= $val->name . ':' . $val->val . ';';
            }
            $returnArr['data']['spec'] = $newSpec;
            $returnArr['data']['cats_info'] = array();
            
            //sku分类信息
            if ($sku_info_arr[0]['category_id']) {
                $cats = $this->MCategory->get_category_info($sku_info_arr[0]['category_id']);
                if ($cats[0]['path']) {
                    $paths = explode('.', $cats[0]['path']);
                    array_pop($paths);
                    array_shift($paths);
                    $returnArr['data']['cats_info'] = $this->MCategory->get_category_info($paths);
                }
            }
            //sku实时销售价和采集信息
            $sku_sale_price = $this->MProduct->get_sku_sale_price($sku_num, $city_id, $field = ['price', 'collect_type']);
            foreach($sku_sale_price AS $val){
                if($val['collect_type'] == C('foods_collect_type.type.pre_collect.value')){
                    $val['collect_type'] = C('foods_collect_type.type.pre_collect.name');
                }elseif($val['collect_type'] == C('foods_collect_type.type.now_collect.value')){
                    $val['collect_type'] = C('foods_collect_type.type.now_collect.name');
                }
                $temp[] = implode(',',$val);
            }
            $temp = array_unique($temp);
            foreach($temp AS $k => $v){
                $temp[$k] = explode(",", $v);
            }
            $returnArr['data']['sale_price'] = $temp;

            //sku图片信息
            if(!empty($sku_info_arr[0]['pic_ids'])){
                $returnArr['data']['pics'] = $this->get_image_info($sku_info_arr[0]['pic_ids']);
            }
            //在库量和可售量
            $stock_arr = $this->MStock->get_in_warehouse(array($sku_num), $warehouse_id);
            $returnArr['data']['inwarehouse'] = $stock_arr[$sku_num]['in_stock'];
            $returnArr['data']['salable'] = $stock_arr[$sku_num]['in_stock'] - $stock_arr[$sku_num]['stock_locked'];
            $returnArr['data']['stock_locked'] = $stock_arr[$sku_num]['stock_locked'];
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }

    }
    
    /**
     * 接口:根据分类id获取该分类的下一级分类信息
     * @author yelongyi@yelongyi.com
     * @sice 2015-07-22 18:28:19
     * #param int|void 分类id或者不传任何参数，不传参数默认返回所有一级分类
     * @return json 返回json格式的信息
     */
    public function get_category_child(){
        try{
            $category_id = $this->input->post('category_id') ?: 0;
            $category_list = $this->MCategory->get_category_child($category_id);
            if(empty($category_list)){
                throw new Exception('没有任何数据能够被返回');
            }
            $returnArr = array();
            $i = 0;
            foreach($category_list AS $category){
                if($category['id'] == $category_id){
                    continue;//去除本身
                }
                $returnArr['data'][$i]['category_id'] = $category['id'];
                $returnArr['data'][$i]['category_name'] = $category['name'];
                $i++;
            }
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 接口:根据城市id获取下面的仓库信息
     * @author yelongyi@yelongyi.com
     * @sice 2015-07-22 18:28:19
     * #param int|void location_id 城市id，0获取所有仓库
     * @return json 返回json格式的信息
     */
    public function get_warehouse_by_location(){
        try{
            $location_id = $this->input->post('location_id');
            if(!is_numeric($location_id)){
                throw new Exception('缺少必要参数location_id');
            }
            $warehouseArr = $this->MLine->get_line_by_locationId($location_id);
            if(empty($warehouseArr)){
                throw new Exception('该地点没有任何仓库信息');
            }
            $returnArr = array();
            foreach ($warehouseArr as $key => $val) {
                $returnArr['data'][$key]['location_id'] = $val['location_id'];
                $returnArr['data'][$key]['warehouse_id'] = $val['warehouse_id'];
                $returnArr['data'][$key]['warehouse_name'] = $val['warehouse_name'];
            }
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 根据sku_number获取销售信息,按照订单完成时间计算
     * @param array $sku_numbers_arr sku数组
     * @param int $stime 开始时间
     * @param int $etime 结束时间
     * @param int $city_id 城市id
     * @param string $warehouse_id 仓库id
     * @return array 结果数组
     */
    private function get_sale_info($sku_numbers_arr, $stime, $etime, $city_id, $warehouse_id){
        //根据时间和城市进行第一次筛选
        $order_detail_arr = $this->MOrder_detail->get_order_by_skus($sku_numbers_arr, $stime, $etime, $city_id, 'complete_time', array('sku_number', 'order_id', 'quantity', 'sum_price'));
        $returnArr = array();
        //如果为空，不进行其他操作，直接返回空数组
        if(!empty($order_detail_arr)){
            //拿到order_id数组到order表中查询数据
            $order_ids_arr = array_unique(array_column($order_detail_arr, 'order_id'));
            $order_arr = $this->MOrder->get_orderInfo_by_orderIds($order_ids_arr, array('id', 'user_id', 'status', 'complete_time', 'line_id', 'final_price'));
            $order_arr = array_column($order_arr, NULL, 'id');
            $line_ids_arr = array_unique(array_column($order_arr, 'line_id'));

            $line_arr = $this->MLine->get_line_by_lineIds($line_ids_arr, array('id AS line_id', 'warehouse_id'));
            //把线路数组转成: 线路=>仓库id的这种形式
            $warehouse_arr = array_column($line_arr, 'warehouse_id', 'line_id');
            //如果传入了仓库id，按仓库进行二次筛选
            if(!empty($warehouse_id)){
                //剔除order表中不是指定仓库的订单
                foreach($order_arr AS $key => $val){
                    //线路id都没有,不计算
                    if(empty($val['line_id'])){
                        continue;
                    }
                    if($warehouse_arr[$val['line_id']] != $warehouse_id) {
                        $tmp_order_ids[] = $val['id'];
                        unset($order_arr[$key]);
                    }
                }
                //剔除order_detail中不是指定仓库的订单
                if(!empty($tmp_order_ids)){
                    foreach($order_detail_arr AS $key => $val){
                        if(in_array($val['order_id'], $tmp_order_ids)){
                            unset($order_detail_arr[$key]);
                        }
                    }
                }
            }
            //按sku_number为索引把sku_number集合一下.
            $new_order_detail_arr = array();
            foreach($order_detail_arr AS $val){
                $new_order_detail_arr[$val['sku_number']][] = $val;
            }
            unset($order_detail_arr);

            //开始分析得出数据
            //计算所有sku的数据
            foreach($new_order_detail_arr AS $key => $val){
                //数据初始化
                $actual_sale_amount = 0;//实际销售额
                $sale_quantity = 0;//销售件数
                $order_counts_arr = array();

                //计算单个sku的数据
                foreach($val AS $value){
                    //先和订单表中取出的数据关联起来
                    $order_info = $order_arr[$value['order_id']];
                    //计算已完成订单和下单顾客数只计算已签收和已完成的，
                    if( $order_info['status'] == C('order.status.success.code') ||  $order_info['status'] == C('order.status.wait_comment.code')){
                        //销售件数 get
                        $sale_quantity += $value['quantity'];
                        //销售额 get
                        $actual_sale_amount += $value['sum_price'];
                        //已完成订单，订单id数组,需要去重得出已完成订单
                        $order_counts_arr[] = $value['order_id'];
                    }
                }
                //实际销售额 get
                $returnArr[$key]['actual_sale_amount']   = round($actual_sale_amount/100, 2);
                //销售件数 get
                $returnArr[$key]['sale_quantity']        = $sale_quantity;
                //已完成订单数 get
                $returnArr[$key]['order_counts']         = count(array_unique($order_counts_arr));
            }
        }
        return $returnArr;
    }

    /**
     * 以已确认订单的维度时间来计算,单写一个方法是因为流水要按照订单创建时间算.
     * @param $sku_numbers_arr
     * @param $stime
     * @param $etime
     * @param $city_id
     * @param $warehouse_id
     * @return array
     */
    private function get_sale_amount($sku_numbers_arr, $stime, $etime, $city_id, $warehouse_id){
        //根据时间和城市进行第一次筛选
        $order_detail_arr = $this->MOrder_detail->get_order_by_skus($sku_numbers_arr, $stime, $etime, $city_id, 'created_time', array('sku_number', 'order_id', 'quantity', 'sum_price'));
        $returnArr = array();
        //如果为空，不进行其他操作，直接返回空数组
        if(!empty($order_detail_arr)){
            //拿到order_id数组到order表中查询数据
            $order_ids_arr = array_unique(array_column($order_detail_arr, 'order_id'));
            $order_arr = $this->MOrder->get_orderInfo_by_orderIds($order_ids_arr, array('id', 'user_id', 'status', 'created_time', 'line_id', 'final_price'));
            $order_arr = array_column($order_arr, NULL, 'id');
            $line_ids_arr = array_unique(array_column($order_arr, 'line_id'));

            $line_arr = $this->MLine->get_line_by_lineIds($line_ids_arr, array('id AS line_id', 'warehouse_id'));
            //把线路数组转成: 线路=>仓库id的这种形式
            $warehouse_arr = array_column($line_arr, 'warehouse_id', 'line_id');
            //如果传入了仓库id，按仓库进行二次筛选
            if(!empty($warehouse_id)){
                //剔除order表中不是指定仓库的订单
                foreach($order_arr AS $key => $val){
                    //线路id都没有,不计算
                    if(empty($val['line_id'])){
                        continue;
                    }
                    if($warehouse_arr[$val['line_id']] != $warehouse_id) {
                        $tmp_order_ids[] = $val['id'];
                        unset($order_arr[$key]);
                    }
                }
                //剔除order_detail中不是指定仓库的订单
                if(!empty($tmp_order_ids)){
                    foreach($order_detail_arr AS $key => $val){
                        if(in_array($val['order_id'], $tmp_order_ids)){
                            unset($order_detail_arr[$key]);
                        }
                    }
                }
            }
            //按sku_number为索引把sku_number集合一下.
            $new_order_detail_arr = array();
            foreach($order_detail_arr AS $val){
                $new_order_detail_arr[$val['sku_number']][] = $val;
            }
            unset($order_detail_arr);

            //开始分析得出数据
            $unsalable_day_counts = round(($etime - $stime) / 86400);//滞销天数初始化
            foreach($new_order_detail_arr AS $key => $val){
                //数据初始化
                $sale_amount = 0;//流水
                $unsalable_arr = array();//滞销天数
                $order_cus_arr = array();//下单顾客数
                $order_sku_counts = 0;//下单数量

                //计算单个sku的数据
                foreach($val AS $value){
                    //先和订单表中取出的数据关联起来
                    $order_info = $order_arr[$value['order_id']];
                    //计算流水剔除状态为0的
                    if( $order_info['status'] != C('order.status.closed.code') ){
                        //流水
                        $sale_amount += $value['sum_price'];
                        //下单数量 order_sku_counts
                        $order_sku_counts += $value['quantity'];
                        //已销售天数，组成数组
                        $unsalable_arr[] = date("Ymd", $order_info['created_time']);
                        //所有下单顾客数组
                        $order_cus_arr[] = $order_info['user_id'];
                    }
                }
                //流水 get
                $returnArr[$key]['sale_amount']          = round($sale_amount/100, 2);
                //下单顾客数 get
                $returnArr[$key]['order_cus_counts']     = count(array_unique($order_cus_arr));
                //下单数量
                $returnArr[$key]['order_sku_counts']     = $order_sku_counts;
                //滞销天数 get
                $returnArr[$key]['unsalable_day_counts'] = $unsalable_day_counts - count(array_unique($unsalable_arr));
            }
        }
        return $returnArr;
    }


    /*
     * @param $pic_ids为xxx,xxx 形式的ID字串
     * @description 根据pic_ids,返回这个图片的原始图片和缩略
    */
    private function get_image_info($pic_ids, $zoom = '-30-') {
        $this->load->helper('img_zoom');
        $images = array();
        if ( ! $pic_ids) {
            return array('raw_image' => array(), 'thumbnail' => array());
        }
        $pic_ids_array = explode(',', $pic_ids);
    
        $pic_urls_info = $this->MBucket->get_lists(
                '*',
                array(
                    'in' => array('id' => $pic_ids_array)
                )
        );
        
        if ($pic_urls_info) {
            $images['raw_image'] = $pic_urls_info;
            $images['thumbnail'] = img_zoom($pic_urls_info, $zoom);
        } else {
            $images['raw_image'] = array();
            $images['thumbnail'] = array();
        }
        return $images;
    }

    /**
     * 接口调用失败返回数据
     * @param string $message
     * @return json json格式的错误信息
     */
    private function failed($message = '接口调用失败'){
        $this->_return_json(
            [
                'status'  => C('status.req.failed'),
                'msg' => $message,
            ]
        );
    }

    /**
     * 接口调用成功返回数据
     * @param array $data 传入数组格式的数据
     * @return json 返回json格式的数据信息
     */
    private function success(array $data = array()){
        $data['status'] = C('status.req.success');
        $this->_return_json($data);
    }
}


/* End of file statistics_bi.php */
/* Location: ./application/controllers/statistics_bi.php */
