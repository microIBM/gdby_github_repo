<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Temp_export extends MY_Controller {

    private $_white_space = '';
    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
                'MProduct',
                'MOrder',
                'MSuborder',
                'MOrder_detail',
                'MUser',
                'MLocation',
                'MLine',
                'MDistribution',
            )
        );

        //导出到wms的csv文件根目录,必须带右边的/
        //$this->_csv_dir = BASEPATH . '../csv/';
        $this->_csv_dir = '/tmp/export/order_csv/';
        $this->_csv_dir_dist = '/tmp/export/distribution_orders/';
        //条形码生成目录
        $this->_barcode_dir = '/tmp/export/barcode/';

        $this->load->library(
            array(
                "Cate_logic",
                "PHPExcel",
                "order_split"
            )
        );

        $this->load->helper("file");

        //deliver的code和相应文字的对应关系
        $code_with_deliver_time = array_values(C('order.deliver_time'));
        $codes                  = array_column($code_with_deliver_time, 'code');
        $msg                    = array_column($code_with_deliver_time, 'msg');
        $this->_deliver_dict    = array_combine($codes, $msg);

        //订单状态和对应中文字典
        $code_with_cn = array_values(C('order.status'));
        $codes        = array_column($code_with_cn, 'code');
        $msg          = array_column($code_with_cn, 'msg');
        $this->_status_dict = array_combine($codes, $msg);

        //unit_id  => unit_name
        $unit_config = C('unit');
        $codes       = array_column($unit_config, 'id');
        $msg         = array_column($unit_config, 'name');
        $this->_unit_dict = array_combine($codes, $msg);
        $this->_unit_dict[0] = '无';

        //pay_type => pay_type_cn
        $pay_type_config = array_values(C('payment.type'));
        $pay_type_codes = array_column($pay_type_config, 'code');
        $pay_type_msgs = array_column($pay_type_config, 'msg');
        $this->_pay_type_dict = array_combine($pay_type_codes, $pay_type_msgs);

        //pay_status => pay_status_cn
        $pay_status_config = array_values(C('payment.status'));
        $pay_status_codes = array_column($pay_status_config, 'code');
        $pay_status_msgs = array_column($pay_status_config, 'msg');
        $this->_pay_status_dict = array_combine($pay_status_codes, $pay_status_msgs);

        $this->_status_arr = array(
            C('order.status.confirmed.code'),
            C('order.status.wave_executed.code'),
            C('order.status.picking.code'),
            C('order.status.picked.code'),
            C('order.status.checked.code'),
            C('order.status.allocated.code'),
            C('order.status.delivering.code'),
            C('order.status.loading.code'),

            C('order.status.success.code'),
            C('order.status.wait_comment.code'),
        );
    }

    private function _get_full_path($path = '') {
        if(empty($path)) {
            return '/';
        }
        $path = explode('.', $path);
        $path = array_filter($path);
        $category_arr = $this->MCategory->get_lists(
            '*',
            [
                'in' => ['id' => $path]
            ]
        );
        $category_strs = array_column($category_arr, 'name');
        $res = implode('/', $category_strs);
        $res = '/' . $res;
        return $res;
    }

    public function get_category() {
        $categories = $this->MCategory->get_lists(
            'id, path, name',
            ['is_leaf' => 0]
        );
        foreach($categories as &$item) {
            $item['path'] = $this->_get_full_path($item['path']);
        }
        unset($item);
        $title_arr = [
            'id'   => '编号',
            'name' => '分类名称',
            'path' => '分类路径'
        ];
        $this->export_csv($title_arr, $categories, 'category_list.csv');
    }

    //todo 需要先稳的接口来拿到相应的spec
    public function get_template() {
        $categories = $this->MCategory->get_lists(
            'id, path, name',
            ['is_leaf' => 1]
        );
        foreach($categories as &$item) {
            $item['spec'] = $this->Cate_logic->get_spec($item['path'], $item['id']);
            $item['path'] = $this->_get_full_path($item['path']);
        }
        unset($item);
        $title_arr = [
            'id'   => '编号',
            'name' => '分类名称',
            'path' => '分类路径',
            'spec' => '规格'
        ];
        $this->export_csv($title_arr, $categories, 'product_template.csv');
    }

    public function get_products() {
        $products = $this->MProduct->get_lists(
            'id, category_id, spec, price, market_price, title'
        );
        $category_ids = array_column($products, 'category_id');
        $categories = $this->MCategory->get_lists(
            'id, path, name',
            ['in' => ['id' => $category_ids]]
        );

        foreach($categories as &$item) {
            $item['path'] = $this->_get_full_path($item['path']);
        }
        unset($item);

        $category_ids = array_column($categories, 'id');
        $category_map = array_combine($category_ids, $categories);

        $res = [];
        foreach($products as &$item) {
            $category_id = $item['category_id'];
            $category    = isset($category_map[$category_id]) ? $category_map[$category_id] : [];
            $res[] = [
                'id'                => $item['id'],
                'template_category' => $category['path'],
                'template_name'     => $category['name'],
                'title'             => $item['title'],
                'attribute_values'  => $item['spec'],
                'price'             => $item['price'],
            ];
        }

        $title_arr = [
            'id'                => '唯一id',
            'template_category' => '类别路径',
            'template_name'     => '模版名称',
            'title'             => '产品名称',
            'attribute_values'  => '属性',
            'price'             => '价格',
        ];
        $this->export_csv($title_arr, $res, 'product_list.csv');
    }

    //把指定站点的订单直接输出到指定目录
    private function _export_site_orders($where = array(), $city = 0, $order_type = 0) {
        $deliver_date = $where['deliver_date'];
        $deliver_time = $where['deliver_time'];

        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/' . $city . '/' . $order_type . '/';
        $file_dir = "{$today_dir}/";

        //如果deliver_time不是1或者2，那么unset
        if(!is_int($deliver_time)) {
            unset($where['deliver_time']);
        }

        $line_ids = $this->MSuborder->get_lists(
            'line_id',
            $where
        );
        $line_ids = array_column($line_ids, 'line_id');
        $line_ids = array_unique($line_ids);

        //export every line loop
        foreach($line_ids as $line_id) {
            unset($where['line_id']);
            $where['line_id'] = $line_id;
            $orders_in_line = $this->MSuborder->get_lists(
                '*',
                $where
            );
            $orders_in_line = $this->_format_order_list(
                $orders_in_line
            );
            //格式化orders
            $orders_formated = [];
            foreach($orders_in_line as $item) {
                $orders_formated[] = $this->_format_single_order_to_array($item);
            }
            //还需要给每一个线路一个销库存的汇总
            $line_summary = $this->_order_product_sum($orders_in_line);
            $orders_formated[] = $line_summary;
            $sheet_titles = array_column($orders_in_line, 'id');
            $sheet_titles[] = 'all';

            //将格式化后的orders输出到excel,以线路id命名文件
            $file_path = $file_dir . $line_id . '.xlsx';
            //这里需要出条码，所以需要多传一个参数
            $barcode_arr = array_column($orders_in_line, 'order_number');
            $this->_convert_array_to_excel($orders_formated, $sheet_titles, $file_path, $barcode_arr);
        }
        return $file_dir;
        //export every line loop
        //end
    }

    private function _check_if_there_is_order_wait_confirm() {
        $where = $this->_get_deliver_info_by_req();
        $deliver_date = $where['deliver_date'];
        $get = $this->input->get(NULL, TRUE);
        $need_confirm = !empty($get['need_confirm']) ? TRUE : FALSE;
        if(!$need_confirm) {
            return FALSE;
        }

        $order_num = $this->MSuborder->get_one(
            'count(1) cnt',
            array(
                'status'       => C('order.status.wait_confirm.code'),
                'deliver_date' => $deliver_date,
            )
        );

        if(empty($order_num)) {
            return FALSE;
        }

        $order_num = $order_num['cnt'];
        if($order_num > 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function write_all_city_orders_to_tmp_dir() {
        set_time_limit(0);
        ini_set("memory_limit", "1024M");
        if($this->_check_if_there_is_order_wait_confirm()) {
            echo '请检查是否还有未审核的订单！';
            return;
        }
        $order_type_arr = $this->order_split->get_config();
        $order_type_codes = array_column($order_type_arr, 'code');

        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => C("status.common.success"),
            )
        );

        foreach($order_type_codes as $order_type) {
            foreach($cities as $item) {
                $this->write_orders_to_tmp_dir($order_type, $item['id']);
                $this->write_orders_to_tmp_dir($order_type, $item['id'], 1);
                $this->write_orders_to_tmp_dir($order_type, $item['id'], 2);
            }
        }
    }

    public function write_orders_to_tmp_dir($order_type = 0, $city_id = 0, $deliver_time = 'today') {
        $where = $this->_get_deliver_info_by_req();
        $deliver_date = $where['deliver_date'];
        if(is_numeric($deliver_time)) {
            $where['deliver_time'] = $deliver_time;
        }
        $deliver_time = $where['deliver_time'];
        $city_id = !empty($city_id) ? intval($city_id) : 0;
        // 加上城市筛选
        if(!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        if(!empty($order_type)) {
            $where['order_type'] = $order_type;
        }

        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/' . $city_id . '/' . $order_type .'/';
        $zip_path = $today_dir . 'result.zip';
        delete_files($today_dir);

        $file_dir = $this->_export_site_orders($where, $city_id, $order_type);
        echo "订单类型为{$order_type}的出库单导出完成\n<br/>";

        //打包zip
        $this->load->library(array('zip'));
        $this->zip->read_dir($today_dir, FALSE);
        $this->zip->archive($zip_path);
        $this->zip->clear_data();
        //echo 'zip file saved to ' . $zip_path . "<br/>";
        echo '压缩包存储到服务器的 ' . $zip_path . ", 可在hop中下载\n<br/>";
    }

    public function export_dist_detail() {
        //拿到dist_id
        $dist_id = $this->input->get('dist_id');
        $dist_id = !empty($dist_id) ? intval($dist_id) : 0;
        if(empty($dist_id)) {
            //报错
            exit('配送单id不正确。');
        }
                                                                                
        $map = array('id' => $dist_id);
                                                                                    
        $data = $this->MDistribution->get_one('*', $map);
        if(empty($data)) {
            exit('未找到该配送单。');
        }
        $dist_number = C('barcode.prefix.dispatch') . $data['dist_number'];

        ini_set("memory_limit", "1024M");

        $where['dist_id'] = $dist_id;

        $dist_file_name = $this->_csv_dir_dist . '/' . $dist_id . '/' . $dist_number . '.xlsx';
        $this->_export_dist_orders($where, $dist_file_name);
        
        $data = file_get_contents($dist_file_name); // 读文件内容
        $name = $dist_number . '.xlsx';
        $this->load->helper('download');
        force_download($name, $data);
    }

    //把指定配送单的订单直接输出到指定目录
     private function _export_dist_orders($where = array(), $file_name) {
         $orders_in_dist = $this->MSuborder->get_lists(
            '*',
            $where
         );
         $orders_in_dist = $this->_format_order_list(
             $orders_in_dist
         );
         //格式化orders
         $orders_formated = [];
         foreach($orders_in_dist as $item) {
             $orders_formated[] = $this->_format_single_order_to_array($item);
         }
         //还需要给每一个线路一个销库存的汇总
         $line_summary = $this->_order_product_sum($orders_in_dist);
         $orders_formated[] = $line_summary;
         $sheet_titles = array_column($orders_in_dist, 'id');
         $sheet_titles[] = 'all';
                                                                                                             //这里需要出条码，所以需要多传一个参数
                                                                                                             $barcode_arr = array_column($orders_in_dist, 'order_number');
                                                                                                             $this->_convert_array_to_excel($orders_formated, $sheet_titles, $file_name, $barcode_arr);            
                                                                                                        }

    /**
     * @author caochunhui@dachuwang.com
     * @description 将单张订单格式化成符合要求的数组
     */
    private function _format_single_order_to_array($item) {
        //抬头部分
        $csv_data = [
            ['id', $item['id'], '订单编号', "NO.{$item['order_number']}", '', '', '', '线路', $item['line'] ],
            [$item['province'], $item['city'], $item['address']],
            [$item['shop_name'], '', $item['realname'], "TEL:{$item['mobile']}", '', '下单时间', date('Y/m/d H:i:s', strtotime($item['created_time']))],
            ['销售', $item['bd'], '销售电话', "TEL:{$item['bd_mobile']}", '',  '配送时间', "{$item['deliver_date']} {$item['deliver_time']}"],
            ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
            ['货号', '产品名称', '', '', '', '订货数量', '订货单位', '结算单价', '结算单位', '小计', '实收数量', '实收金额'],
        ];

        //产品列表部分
        $details = [];
        foreach($item['detail'] as $key => $val) {
            $spec_str = $this->_format_spec($val['spec']);
            $detail   = [
                $val['sku_number'],
                    $val['name'],
                    '',
                    '',
                    '',
                    $val['quantity'],
                    //$val['unit_id'],
                    $val['unit_id'] == 0 ? $this->_unit_dict[1] : $this->_unit_dict[$val['unit_id']],
                    $val['price'] . '元',
                    $val['close_unit'] == 0 ? '/' . $this->_unit_dict[1] : '/' . $this->_unit_dict[$val['close_unit']],
                    $val['sum_price'], //小计
                    '',
                    ''
            ];

            $details[] = $detail;
        }
        //为了让尾部内容可以吸底，需要补充一些空行
        $detail_cnt = count($details);
        while($detail_cnt < 11) {
            $details[] = [];
            $detail_cnt ++;
        }

        //合并表头和列表
        $csv_data = array_merge($csv_data, $details);


        //尾部内容
        //湖南大厦ka客户的临时需求
        $line_need_pay = ['应付总价', $item['final_price']];
        if($item['mobile'] == '15084783678' || $item['mobile'] == '18618142363' || $item['mobile'] == '18612118635' || $item['mobile'] == '13520205658') {
            $line_need_pay = ['应付总价', $item['final_price'], 'ka客户月结，司机不用收款'];
        }
        //ka客户类型会收取服务费，详单比普通客户多一行
        // $line_service_fee = ['服务费', $item['service_fee']];
        $line_service_fee = [];
        /*if($item['customer_type'] == C('customer.type.normal.value')) {
            $line_service_fee = [];
        }*/

        $tail_arr = [
            ['订单备注', ' ' . $item['remarks']],
            ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
            ['订单总价', $item['total_price'] . "(" .$item['pay_status_cn'] . ")", '', '', '', '', '', '', '',  '实收总金额'],
            ['活动优惠', '-' . $item['minus_amount']],
            ['微信支付优惠', '-' . $item['pay_reduce']],
            ['运费', '+' . $item['deliver_fee']],
            $line_service_fee,
            $line_need_pay,
            ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
            ['客户签字'],
            [],
            ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '', '售后电话', 'TEL:400-8199-491']
        ];

        //大果定制需求
        // TODO 也许其他订单类型也会需要特殊处理
        if($item['order_type'] == C('order.order_type.fruit.code')) {
            $tail_arr = [
                ['订单备注', $item['remarks']],
                ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
                ['预估总价', $item['total_price'], '', '', '', '', '', '', '',  '实收总金额'],
                ['活动优惠', '- ' . $item['minus_amount']],
                ['微信支付优惠', '-' . $item['pay_reduce']],
                ['运费', '+' . $item['deliver_fee']],
                ['应付总价', $item['final_price'], '', '', '', '', '', '', '', '以实际称重为准'],
                ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
                ['客户签字'],
                [],
                ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '', '售后电话', 'TEL:400-8199-491']
            ];
        }

        $csv_data = array_merge($csv_data, $tail_arr);

        return $csv_data;
    }


    private function _get_deliver_info_by_req() {
        $request_time = $this->input->server('REQUEST_TIME');
        $request_hour = intval(date('H', $request_time));
        $deliver_date = strtotime(date('Y-m-d', $request_time));
        $deliver_time = 'today';

        if($request_hour == 23) {
            $deliver_date = $deliver_date + 86400;
        }

        $get = $this->input->get(NULL, TRUE);
        if(isset($get['deliver_date'])) {
            $deliver_date = strtotime($get['deliver_date']);
            if(isset($get['deliver_time'])) {
                $deliver_time = $get['deliver_time'];
            }
        }

        $city_id = isset($get['city_id']) ? $get['city_id'] : 0;
        $city_id = !empty($city_id) && intval($city_id) > 0 ? intval($city_id) : 0;

        $where = [
            'deliver_date' => $deliver_date,
            'deliver_time' => $deliver_time,
            'in' => array(
                'status' => $this->_status_arr
            )
        ];

        // 加上城市筛选
        if(!empty($city_id)) {
            $where['city_id'] = $city_id;
        }

        return $where;
    }

    /**
     * @description 订单汇总数据
     */
    private function _order_product_sum($orders = array()) {

        if(empty($orders)) {
            return [];
        }

        $order_ids = array_column($orders, 'id');
        if(empty($order_ids)) {
            return [];
        }

        $where = [
            'in' => [
                'suborder_id' => $order_ids
            ]
        ];
        $details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );

        $csv_data = [];
        foreach($details as $item) {
            $product_id = $item['product_id'];
            $item['spec'] = json_decode($item['spec'], TRUE);
            if(isset($csv_data[$product_id])) {
                $csv_data[$product_id]['quantity'] += $item['quantity'];
            } else {
                $csv_data[$product_id] = [
                    'id'       => $item['sku_number'],
                    'name'     => $item['name'],
                    'spec'     => $this->_format_spec($item['spec']),
                    'quantity' => $item['quantity'],
                ];
            }
        }

        //按product_id分组计算总量
        $title_arr = [
            '产品货号',
            '产品名称',
            '规格',
            '产品数量'
        ];
        array_unshift($csv_data, $title_arr);
        return $csv_data;

    }


    /**
     * @description 新的导出
     */
    public function export_orders_to_deliver() {
        $where = $this->_get_deliver_info_by_req();
        $deliver_date = $where['deliver_date'];
        $deliver_time = $where['deliver_time'];
        $city_id = !empty($where['city_id']) ? $where['city_id'] : 0;
        $order_type = !empty($this->input->get('order_type')) ? $this->input->get('order_type') : C('order.order_type.normal.code');

        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/' . $city_id . '/' . $order_type . '/';
        $zip_path  = $today_dir . 'result.zip';
        $zip_name  = date('Y-m-d', $deliver_date) . '-' . $deliver_time . '-' . $city_id . '.zip';

        if(!file_exists($zip_path)) {
            echo "没有到可以导出的时间或没有符合条件的订单！";
            return;
        }

        $data = file_get_contents($zip_path); // 读文件内容
        $name = $zip_name;
        $this->load->helper('download');

        force_download($name, $data);
    }


    private function _format_spec($spec = array()) {
        $spec_str = '';
        if(empty($spec)) {
            return $spec_str;
        }
        foreach($spec as $item) {
            if(!empty($item['name']) && $item['name'] != '描述' && !empty($item['val'])) {
                $spec_str .= $item['name'] . ':' . $item['val'] . ';';
            }
        }
        return $spec_str;
    }

    private function _get_id_to_path($category_ids = array()) {
        if(empty($category_ids)) {
            return [];
        }

        $categories = $this->MCategory->get_lists(
            'path, id',
            array(
                'in' => array(
                    'id' => $category_ids
                )
            )
        );
        if(empty($categories)) {
            return [];
        }

        //用category_id查询category_path
        $id_to_path = array_combine(
            array_column($categories, 'id'),
            array_column($categories, 'path')
        );

        foreach($categories as $item) {
            $ids = explode('.', $item['path']);
            $ids = array_filter($ids);
            $category_ids = array_merge($category_ids, $ids);
        }
        $category_ids = array_unique($category_ids);

        $categories = $this->MCategory->get_lists(
            'name, id',
            array(
                'in' => array(
                    'id' => $category_ids
                )
            )
        );
        $category_ids = array_column($categories, 'id');
        $category_names = array_column($categories, 'name');
        $id_to_name = array_combine($category_ids, $category_names);
        //获取分类done

        foreach($id_to_path as $id => $path) {
            $path_arr = array_filter(explode('.', $path));
            $path_str = '';
            foreach($path_arr as $idx) {
                if(array_key_exists($idx, $id_to_name)) {
                    $path_str .= $id_to_name[$idx] . '/';
                }
            }
            $id_to_path[$id] = $path_str;
        }

        return $id_to_path;
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 格式化订单列表
     */
    private function _format_order_list($order_list = array()) {
        if(empty($order_list)) {
            return $order_list;
        }

        //批量取出下单用户信息
        $user_ids = array_column($order_list, 'user_id');
        $user_ids = array_unique($user_ids);
        $users = $this->MCustomer->get_lists(
            '*',
            [
                'in' => [
                    'id' => $user_ids
                ]
            ]
        );
        $user_ids = array_column($users, 'id');
        $user_map = array_combine($user_ids, $users);

        $city_ids = array_column($users, 'city_id');
        $province_ids = array_column($users, 'province_id');
        $county_ids = array_column($users, 'county_id');
        $location_ids = array_merge($city_ids, $province_ids, $county_ids);
        //取出用到的city_name, province_name, county_name
        $locations = $this->MLocation->get_lists(
            '*',
            [
                'in' => array(
                    'id' => $location_ids
                )
            ]
        );
        $location_ids = array_column($locations, 'id');
        $location_map = array_combine($location_ids, $locations);

        //线路相关
        $line_ids = array_column($users, 'line_id');
        $lines = $this->MLine->get_lists(
            '*',
            [
                'in' => array(
                    'id' => $line_ids
                )
            ]
        );
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);

        //取出用到的bd
        $bd_ids = array_column($users, 'invite_id');
        $bd_ids = array_filter(array_unique($bd_ids));
        $bd_map = [];
        if(!empty($bd_ids)) {
            $bd_users = $this->MUser->get_lists(
                'id, name, mobile',
                array(
                    'in' => array(
                        'id' => $bd_ids
                    )
                )
            );
            $bd_ids = array_column($bd_users, 'id');
            $bd_map = array_combine($bd_ids, $bd_users);
        }

        //批量取出订单详情
        $order_ids = array_column($order_list, 'id');
        $where = [
            'in' => [ 'suborder_id' => $order_ids ]
        ];
        $order_details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );

        $category_ids = array_column($order_details, 'category_id');
        $id_to_path = $this->_get_id_to_path($category_ids);

        $detail_map = [];
        foreach($order_details as &$item) {
            $order_id = $item['suborder_id'];
            $item['category'] = isset($id_to_path[$item['category_id']]) ? $id_to_path[$item['category_id']] : '';
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
            $item['single_price'] /= 100;
            //$item['final_price'] /= 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $item['updated_time'] = date('Y/m/d H:i', $item['updated_time']);
            $spec = json_decode($item['spec'], TRUE);
            if(!empty($spec)) {
                foreach($spec as $idx => $spec_arr) {
                    if(empty($spec_arr['name']) || empty($spec_arr['val'])) {
                        unset($spec[$idx]);
                    }
                }
                $item['spec'] = $spec;
            } else {
                $item['spec'] = '';
            }
            if(isset($detail_map[$order_id])) {
                $detail_map[$order_id][] = $item;
            } else {
                $detail_map[$order_id] = [
                    $item
                ];
            }
        }
        unset($item);

        foreach($order_list as $idx =>&$item) {

            //价格和时间
            $item['total_price']  = $item['total_price'] / 100;
            $item['deal_price']   = $item['deal_price'] / 100;
            $item['minus_amount'] = $item['minus_amount'] / 100;
            $item['pay_reduce']   = $item['pay_reduce'] / 100;
            $item['deliver_fee']  = $item['deliver_fee'] / 100;
            $item['final_price']  = $item['final_price'] / 100;
            $item['service_fee']  = $item['service_fee'] / 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $deliver_arr          = $this->_deliver_dict;
            $item['deliver_time'] = isset($deliver_arr[$item['deliver_time']]) ?
                $deliver_arr[$item['deliver_time']] : '';
            $item['deliver_date'] = date('Y/m/d', $item['deliver_date']);

            //支付相关
            $pay_type_code = $item['pay_type'];
            $item['pay_type_cn'] = isset($this->_pay_type_dict[$pay_type_code]) ? $this->_pay_type_dict[$pay_type_code] : '';
            $pay_status_code = $item['pay_status'];
            $item['pay_status_cn'] = isset($this->_pay_status_dict[$pay_status_code]) ? $this->_pay_status_dict[$pay_status_code] : '';

            //用户相关
            $user_id           = $item['user_id'];
            $order_user        = $user_map[$user_id];

            $item['mobile']    = $order_user['mobile'];
            $item['invite_id'] = $order_user['invite_id'];
            $item['shop_name'] = $order_user['shop_name'];
            $item['realname']  = $order_user['name'];
            $item['county_id'] = $order_user['county_id'];

            //线路
            $line_id = $order_user['line_id'];
            $item['line_id'] = $line_id;
            $order_line = isset($line_map[$line_id]) ? $line_map[$line_id] : [];
            $item['line'] = !empty($order_line) ? $order_line['name'] : '';
            $item['warehouse_name'] = !empty($order_line) ? $order_line['warehouse_name'] : '';

            //地址商圈相关
            $city_id           = $order_user['city_id'];
            $province_id       = $order_user['province_id'];
            $county_id         = $order_user['county_id'];
            $item['province']  = isset($location_map[$province_id]) ? $location_map[$province_id]['name'] : '';
            $item['city']      = isset($location_map[$city_id]) ? $location_map[$city_id]['name'] : '';
            $item['county']    = isset($location_map[$county_id]) ? $location_map[$county_id]['name'] : '';
            $item['address']   = $order_user['address'];


            //bd信息
            $invite_id = $order_user['invite_id'];
            if(!isset($bd_map[$invite_id])) {
                //unset($order_list[$idx]);
                $item['bd'] = '';
                $item['bd_mobile'] = '';
            } else {
                $item['bd'] = $bd_map[$invite_id]['name'];
                $item['bd_mobile'] = $bd_map[$invite_id]['mobile'];
            }

            //订单状态
            $status            = $item['status'];
            $item['status_cn'] = $this->_status_dict[$status];
            $order_id          = $item['id'];
            $item['detail']    = isset($detail_map[$order_id]) ? $detail_map[$order_id] : [];
        }
        unset($item);
        return $order_list;
    }


    //这个函数是给everyday_order用的，用来获取特定状态类型的订单，并返回数组
    private function _get_type_orders($order_type = 0) {
        $order_type   = intval($order_type);
        $get          = $this->input->get(NULL, TRUE);
        $request_time = $this->input->server('REQUEST_TIME');
        $today        = strtotime(date('Ymd', $request_time));
        $tomorrow     = $today + 86400;
        $city_id      = !empty($get['city_id']) ? $get['city_id'] : 0;

        $where = array(
            'in' => array(
                'status' => $this->_status_arr
            )
        );

        if(isset($get['by_day']) && $get['by_day'] == 1) {
            unset($where);
            $where = array(
                'created_time >=' => $today,
                'created_time <'  => $tomorrow,
                'in' => array(
                    'status' => $this->_status_arr
                )
            );
            if(isset($get['created_date'])) {
                $today = strtotime($get['created_date']);
                $tomorrow = $today + 86400;
                $where['created_time >='] = $today;
                $where['created_time <'] = $tomorrow;
            }
        }
        if(isset($get['deliver_date']) ) {
            unset($where);
            $where = array(
                'deliver_date' => strtotime($get['deliver_date']),
                'in' => array(
                    'status' => $this->_status_arr
                )
            );

            if(isset($get['deliver_time'])) {
                switch($get['deliver_time']) {
                case "1" :
                    $where['deliver_time'] = 1;
                    break;
                case "2":
                    $where['deliver_time'] = 2;
                    break;
                default:
                    break;
                }
            }
        }

        if(!empty($city_id)) {
            $where['city_id'] = $city_id;
        }

        if(!empty($order_type)) {
            $where['order_type'] = $order_type;
        }

        $orders = $this->MSuborder->get_lists(
            '*',
            $where
        );

        if(empty($orders)) {
            //echo '没有可以导出的订单';
            return [];
        }

        $orders = $this->_format_order_list($orders);
        $counties = array_column($orders, 'county');
        $lines = array_column($orders, 'line');
        $mobiles = array_column($orders, 'mobile');
        $order_type = array_column($orders, 'order_type');
        $order_type_config = $this->order_split->get_config();
        $order_type_cn = array_column($order_type_config, "msg", "code");
        array_multisort($order_type, SORT_ASC, $lines, SORT_ASC, $counties, SORT_ASC, $mobiles, SORT_ASC, $orders);
        $csv_data = [];
        foreach($orders as $item) {
            $csv_data[] = array(
                'order_type'     => $order_type_cn[$item['order_type']],
                'shop_name'      => $item['shop_name'],
                'order_id'       => $item['id'],
                'order_number'   => 'NO.' . $item['order_number'],
                'order_status'   => $item['status_cn'],
                'user_id'        => $item['user_id'],
                'province'       => $item['province'],
                'line'           => $item['line'],
                'warehouse_name' => $item['warehouse_name'],
                'deliver_addr'   => $item['address'],
                'deliver_time'   => $item['deliver_date'] . ' ' . $item['deliver_time'],
                'realname'       => $item['realname'],
                'mobile'         => 'TEL:' . $item['mobile'],
                'pay_type'       => $item['pay_type_cn'],
                'seller'         => $item['bd'],
                'seller_mobile'  => $item['bd_mobile'],
                'category'       => '',
                'product_id'     => '',
                'product_name'   => '',
                'spec'           => '',
                'quantity'       => '',
                'unit_id'        => '',
                'price'          => '',
                'close_unit'     => '',
                'single_price'   => '',
                'total_price'    => '',
                'created_time'   => $item['created_time'],
                'remarks'        => $item['remarks'],
            );
            $first_line_idx = count($csv_data) - 1;
            foreach($item['detail'] as $idx => $val) {
                //把详情上移一行
                if($idx == 0) {
                    $csv_data[$first_line_idx]['product_id'] = $val['sku_number'];
                    $csv_data[$first_line_idx]['category'] = $val['category'];
                    $csv_data[$first_line_idx]['product_name'] = $val['name'];
                    $csv_data[$first_line_idx]['quantity'] = $val['quantity'];
                    $csv_data[$first_line_idx]['unit_id'] = $this->_unit_dict[$val['unit_id']];
                    $csv_data[$first_line_idx]['single_price'] = $val['single_price'];
                    $csv_data[$first_line_idx]['close_unit'] = $this->_unit_dict[$val['close_unit']];
                    $csv_data[$first_line_idx]['spec'] = $this->_format_spec($val['spec']);
                    $csv_data[$first_line_idx]['price'] = $val['price'];
                    $csv_data[$first_line_idx]['total_price'] = $val['sum_price'];
                    continue;
                }
                $csv_data[] = array(
                    'order_type'     => $order_type_cn[$item['order_type']],
                    'shop_name'      => $item['shop_name'],
                    'order_id'       => $item['id'],
                    'order_number'   => 'NO.' . $item['order_number'],
                    'order_status'   => $item['status_cn'],
                    'user_id'        => $item['user_id'],
                    'province'       => $item['province'],
                    'line'           => $item['line'],
                    'warehouse_name' => $item['warehouse_name'],
                    'deliver_addr'   => $item['address'],
                    'deliver_time'   => $item['deliver_date'] . ' ' . $item['deliver_time'],
                    'realname'       => $item['realname'],
                    'mobile'         => 'TEL:' . $item['mobile'],
                    'pay_type'       => $item['pay_type_cn'],
                    'seller'         => $item['bd'],
                    'seller_mobile'  => $item['bd_mobile'],
                    //type 2

                    'category'     => $val['category'],
                    'product_id'   => $val['sku_number'],
                    'product_name' => $val['name'],
                    'spec'         => $this->_format_spec($val['spec']),
                    'quantity'     => $val['quantity'],
                    'unit_id'      => $this->_unit_dict[$val['unit_id']],
                    'price'        => $val['price'],
                    'close_unit'   => $this->_unit_dict[$val['close_unit']],
                    'single_price' => $val['single_price'],
                    'total_price'  => $val['sum_price'],
                    'created_time' => $item['created_time'],
                );
            }
        }


        $title_arr = array(
            'order_type'     => '订单类型',
            'shop_name'      => '门店名称',
            'order_id'       => '订单编号',
            'order_number'   => '订单号',
            'order_status'   => '订单状态',
            'user_id'        => '门店编号',
            'province'       => '城市',
            'line'           => '线路',
            'warehouse_name' => '仓库名',
            'deliver_addr'   => '门店地址',
            'deliver_time'   => '收货时间',
            'realname'       => '联系人',
            'mobile'         => '电话',
            'pay_type'       => '付款方式',
            'seller'         => '销售员',
            'seller_mobile'  => '销售电话',
            'category'       => '产品分类',
            'product_id'     => '产品货号',
            'product_name'   => '产品名称',
            'spec'           => '规格',
            'quantity'       => '订货数量',
            'unit_id'        => '订货单位',
            'price'          => '单价',
            'close_unit'     => '结算单位',
            'single_price'   => '结算单价',
            'total_price'    => '总价',
            'created_time'   => '下单时间',
            'remarks'        => '客户备注',
        );
        array_unshift($csv_data, $title_arr);
        return $csv_data;
    }

    /**
     * @author caochunhuiQ@dachuwang.com
     * @description 每日的订单
     */
    public function everyday_order() {
        ini_set("memory_limit", "1024M");

        //取出所有订单类型，分开sheet展示
        $order_type_arr = $this->order_split->get_config();
        $order_types = array_column($order_type_arr, 'code');
        $order_type_msgs = array_column($order_type_arr, 'msg');

        $xls_data = [];
        $title_arr = [];
        foreach($order_types as $idx => $order_type) {
            $tmp_arr = [];
            $csv_data = $this->_get_type_orders($order_type);
            foreach($csv_data as $item) {
                $tmp_arr[] = array_values($item);
            }
            $xls_data[] = $tmp_arr;

            $title = $order_type_msgs[$idx];
            $title_arr[] = $title;
        }

        $path = '/tmp/export/temp.xlsx';
        $this->_convert_array_to_excel($xls_data, $title_arr, $path);

        $data = file_get_contents($path); // 读文件内容
        $name = 'default.xlsx';
        $this->load->helper('download');

        force_download($name, $data);

    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 用数组和地址直接生成excel文件
     * 每一个数组占一个sheet
     */
    private function _convert_array_to_excel($arr = array(), $sheet_titles = array(), $out_name = '', $barcode_arr = array()) {

        //下面的代码是抄的。
        //set cache
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        //open excel file
        $write_objPHPExcel = new PHPExcel();
        $write_objPHPExcel->getDefaultStyle()->getFont()
            ->setName('simsun')
            ->setSize(10);

        //下面要循环了

        $sheet_cnt = 0;
        foreach($arr as $item) {
            //用订单id.csv来命名每一个sheet
            $out_sheet = new PHPExcel_Worksheet($write_objPHPExcel, $sheet_titles[$sheet_cnt]);
            //$out_sheet->setTitle($item);

            //row index start from 1
            $row_index = 0;
            foreach ($item as $row) {
                $row_index++;
                //$cellIterator = $row->getCellIterator();
                //$cellIterator->setIterateOnlyExistingCells(false);

                //column index start from 0
                $column_index = -1;
                foreach ($row as $cell) {
                    $column_index++;
                    $out_sheet->setCellValueByColumnAndRow($column_index, $row_index, $cell);
                }
            }
            //如果条码数组不为空，那么说明需要在sheet里插入条码
            if(!empty($barcode_arr) && isset($barcode_arr[$sheet_cnt])) {
                $barcode_download_res = $this->_download_barcode($barcode_arr[$sheet_cnt]);
                if($barcode_download_res['code'] == 200) {
                    //no pic you say a jb
                    $pic_path = $barcode_download_res['file'];
                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                    $objDrawing->setName('barcode');
                    $objDrawing->setDescription('');
                    $objDrawing->setPath($pic_path);
                    $objDrawing->setHeight(50);
                    $x_index = 'F';
                    $y_index = count($item) - 3;
                    $new_position = $x_index . $y_index;
                    $objDrawing->setCoordinates($new_position);
                    //$objDrawing->setCoordinates('F26');
                    //$objDrawing->setOffsetX(10);
                    //$objDrawing->getShadow()->setVisible(true);
                    //$objDrawing->getShadow()->setDirection(36);
                    $objDrawing->setWorksheet($out_sheet);
                    //no pic you say a jb
                }
            }

            $write_objPHPExcel->addSheet($out_sheet);
            $sheet_cnt++;
        }
        $write_objPHPExcel->removeSheetByIndex(0);
        //删除第一个空sheet
        //上面要循环了
        //上面的代码是抄的

        //write excel file
        $objWriter = new PHPExcel_Writer_Excel2007($write_objPHPExcel);

        $dir_name = dirname($out_name);
        if(!is_dir($dir_name)) {
            $res = mkdir($dir_name, 0777, TRUE);
        }
        $objWriter->save($out_name);
    }

    private function _download_barcode($text) {
        //text=2015081923456&thickness=70&scale=1&source=F
        $request_arr = array(
            'text' => $text,
            'thickness' => 20,
            'scale' => 2,
        );
        $url = C('barcode.url') . '?' . http_build_query($request_arr);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        ob_start();
        curl_exec($ch);
        $pic_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $file_path = $this->_barcode_dir . $text . '.png';
        $fp= @fopen($file_path, "a");
        fwrite($fp,$pic_content);

        $res = array(
            'code' => $return_code,
            'file' => $file_path
        );
        return $res;
    }

}

/* End of file temp_export.php */
/* Location: ./application/controllers/temp_export.php */
