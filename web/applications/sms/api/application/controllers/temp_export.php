<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Temp_export extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
                'MProduct',
                'MOrder',
                'MOrder_detail',
                'MUser',
                'MLocation',
                'MLine',
            )
        );

        //导出到wms的csv文件根目录,必须带右边的/
        //$this->_csv_dir = BASEPATH . '../csv/';
        $this->_csv_dir = '/tmp/export/order_csv/';
        $this->load->library(
            array(
                "Cate_logic",
                "PHPExcel"
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
        //print_r($res);
        $this->export_csv($title_arr, $res, 'product_list.csv');
    }

    public function get_orders() {
    }


    /**
     * @author caochunhui@dachuwang.com
     * @description 保存到csv文件，不下载
     */
    private function _save_to_csv($data, $file_path = 'default.csv', $date = '') {
        set_time_limit(0);
        //ob_clean();
        $today_dir = $date;
        if(!is_dir($today_dir)) {
            $res = mkdir($today_dir, 0777, TRUE);
        }
        $temp_file_path = $today_dir . $file_path . '.csv';
        //test code
        $xls_file_path = $today_dir . $file_path . '.xlsx';
        $file = fopen($temp_file_path, 'w');
        foreach($data as $item) {
            fputcsv($file, $item);
        }
        fclose($file);

        //在win下看utf8的csv乱码
        $str = file_get_contents($temp_file_path);
        $str = iconv('UTF-8', 'GBK', $str);
        unlink($temp_file_path);
        file_put_contents($temp_file_path, $str);
    }


    //把指定站点的订单直接输出到指定目录
    private function _export_site_orders($where = array()) {
        $deliver_date = $where['deliver_date'];
        $deliver_time = $where['deliver_time'];

        $site_name = $where['site_src'] == C('site.dachu') ? 'dachu' : 'daguo';
        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/';
        $file_dir = "{$today_dir}{$site_name}/";

        //如果deliver_time不是1或者2，那么unset
        if(!is_int($deliver_time)) {
            unset($where['deliver_time']);
        }

        $line_ids = $this->MOrder->get_lists(
            'line_id',
            $where
        );
        $line_ids = array_column($line_ids, 'line_id');
        $line_ids = array_unique($line_ids);

        //export every line loop
        foreach($line_ids as $line_id) {
            unset($where['line_id']);
            $where['line_id'] = $line_id;
            $orders_in_line = $this->MOrder->get_lists(
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
            $line_summary = $this->_order_product_sum2($orders_in_line);
            $orders_formated[] = $line_summary;
            $sheet_titles = array_column($orders_in_line, 'id');
            $sheet_titles[] = 'all';

            //将格式化后的orders输出到excel,以线路id命名文件
            $file_path = $file_dir . $line_id . '.xlsx';
            $this->_convert_array_to_excel($orders_formated, $sheet_titles, $file_path);
        }
        return $file_dir;
        //export every line loop
        //end
    }

    public function write_orders_to_tmp_dir2() {
        ini_set("memory_limit", "1024M");


        $where = $this->_get_deliver_info_by_req2();
        $deliver_date = $where['deliver_date'];
        $deliver_time = $where['deliver_time'];
        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/';
        $zip_path= $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/' . 'result.zip';
        delete_files($today_dir);

        $site_ids = array_values(C('site'));
        foreach($site_ids as $site_id) {
            $site_name = $site_id == C('site.dachu') ? 'dachu' : 'daguo';
            $where['site_src'] = $site_id;
            $file_dir = $this->_export_site_orders($where);
            echo "the order of {$site_name} have been exported to {$file_dir} \n<br/>";
        }

        //打包zip
        $this->load->library(array('zip'));
        $this->zip->read_dir($today_dir, FALSE);
        $this->zip->archive($zip_path);
        echo 'zip file saved to ' . $zip_path;
    }


    /**
     * @author caochunhui@dachuwang.com
     * @description 导出每一笔订单的详情和汇总，并转换成excel文件写到指定目录中
     */
    public function write_orders_to_tmp_dir() {
        ini_set('memory_limit','1024M');

        $deliver_info = $this->_get_deliver_info_by_req();
        $deliver_date = $deliver_info['deliver_date'];
        $deliver_time = $deliver_info['deliver_time'];

        $where = [
            'deliver_date' => $deliver_date,
            'deliver_time' => $deliver_time,
            'status'       => C('order.status.confirmed.code'),
        ];

        //如果deliver_time不是1或者2，那么unset
        if(!is_int($deliver_time)) {
            unset($where['deliver_time']);
        }

        //get orders
        $orders = $this->MOrder->get_lists(
            '*',
            $where
        );

        if(empty($orders)) {
            print_r($where);
            echo '没有可以导出的订单';
            return;
        }

        //每一笔订单导出一份csv
        $orders = $this->_format_order_list($orders);
        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/';
        delete_files($today_dir);

        foreach($orders as $item) {
            $this->_write_single_order_to_csv($item, $today_dir);
        }

        //打包date目录下的所有csv
        $zip_path  = $today_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '.zip';
        $zip_name  = date('Y-m-d', $deliver_date) . '-' . $deliver_time . '.zip';
        $xlsx_path_dachu = $today_dir . '/dachu/dachu.xlsx';
        $xlsx_path_daguo = $today_dir . '/daguo/daguo.xlsx';

        $summary_dachu = $this->_order_product_sum($orders, 'dachu');
        $summary_daguo = $this->_order_product_sum($orders, 'daguo');

        //订单需采购的商品汇总
        $this->_save_to_csv($summary_dachu, 'all', $today_dir . 'dachu/');
        $this->_save_to_csv($summary_daguo, 'all', $today_dir . 'daguo/');

        //合并所有的csv到统一的xlsx
        //大厨和大果要分开
        $this->_make_excel($today_dir . 'dachu/', $xlsx_path_dachu, 'GBK');
        $this->_make_excel($today_dir . 'daguo/', $xlsx_path_daguo, 'GBK');

        //压缩对应目录
        $this->load->library(array('zip'));
        $this->zip->read_dir($today_dir, FALSE);
        $this->zip->archive($zip_path);
        ob_flush();
        echo 'zip file saved to ' . $zip_path;
        return;
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 将单张订单格式化成符合要求的数组
     */
    private function _format_single_order_to_array($item) {
        $csv_data = [
            ['商圈', $item['county'],'','','', '线路', $item['line']],
            [],
            ['id', $item['id'], $item['province'], '', $item['city']],
            [],
            ['客户名称', $item['realname'],'','','', '下单时间', date('Y年m月d日 H:i:s', strtotime($item['created_time']))],
            [],
            ['订单编号', 'NO.' . $item['order_number'] ],
            [],
            ['店铺名称', $item['shop_name'], '', '', '', '配送时间', $item['deliver_date'] . ' ' . $item['deliver_time'] ],
            [],
            ['客户地址', $item['address'],'','','','联系人', $item['realname']],
            [],
            ['联系电话', 'tel:' . $item['mobile'],'','','','售后电话', 'tel:010-58298105'],
            [],
            ['销售', $item['bd'],'','','', '销售电话', 'tel:' . $item['bd_mobile']],
            [],
            ['产品名称', '', '', '', '订货数量', '订货单价', '订货金额', '实收数量'],
            [],
        ];

        $details = [];
        foreach($item['detail'] as $key => $val) {
            $spec_str = $this->_format_spec($val['spec']);
            $detail   = [
                ($val['id'] + 1000000) .' '. $val['name'], '', '', '', $val['quantity'],
                $val['price'], $val['sum_price'], ''
            ];

            $details[] = $detail;
            $details[] = [$spec_str];
        }
        $csv_data = array_merge($csv_data, $details);

        $tail_arr = [
            [],
            ['订单总价', $item['total_price'], '', '', '', '实收金额'],
            [],
            ['客户签字','', '', '', '司机签字'],
            [],
            ['出纳签字','', '','', '库管签字'],
            [],
            ['1.存根（白联）  2.出纳（粉联） 3.库管（绿联） 4.客户（蓝联）'],
            [],
        ];

        $csv_data = array_merge($csv_data, $tail_arr);

        return $csv_data;
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 将单张订单写到指定的csv文件
     */
    private function _write_single_order_to_csv($item, $file_dir) {
        $csv_data = [
            ['商圈', $item['county'],'','','', '线路', $item['line']],
            [],
            ['id', $item['id'], $item['province'], '', $item['city']],
            [],
            ['客户名称', $item['realname'],'','','', '下单时间', date('Y年m月d日 H:i:s', strtotime($item['created_time']))],
            [],
            ['订单编号', 'NO.' . $item['order_number'] ],
            [],
            ['店铺名称', $item['shop_name'], '', '', '', '配送时间', $item['deliver_date'] . ' ' . $item['deliver_time'] ],
            [],
            ['客户地址', $item['address'],'','','','联系人', $item['realname']],
            [],
            ['联系电话', 'tel:' . $item['mobile'],'','','','售后电话', 'tel:010-58298105'],
            [],
            ['销售', $item['bd'],'','','', '销售电话', 'tel:' . $item['bd_mobile']],
            [],
            ['产品名称', '', '', '', '订货数量', '订货单价', '订货金额', '实收数量'],
            [],
        ];

        $details = [];
        foreach($item['detail'] as $key => $val) {
            $spec_str = $this->_format_spec($val['spec']);
            $detail   = [
                $val['name'], '', '', '', $val['quantity'],
                $val['price'], $val['sum_price'], ''
            ];

            $details[] = $detail;
            $details[] = [$spec_str];
        }
        $csv_data = array_merge($csv_data, $details);

        $tail_arr = [
            [],
            ['订单总价', $item['total_price'], '', '', '', '实收金额'],
            [],
            ['客户签字','', '', '', '司机签字'],
            [],
            ['出纳签字','', '','', '库管签字'],
            [],
            ['1.存根（白联）  2.出纳（粉联） 3.库管（绿联） 4.客户（蓝联）'],
            [],
        ];
        $csv_data = array_merge($csv_data, $tail_arr);
        //file_dir后面还需要一个大厨大果来分
        if($item['site_src'] == C('site.dachu')) {
            $file_dir .= 'dachu/';
        } else {
            $file_dir .= 'daguo/';
        }
        $csv_file_name = $item['line_id'] . '_' . $item['county_id'] . '_' . $item['id'];
        $this->_save_to_csv($csv_data, $csv_file_name, $file_dir);
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 将单个订单转换为可打印的数组格式
     */
    private function _convert_order_array_format($item) {
        $csv_data = [
            ['id', $item['id'], '', '市', $item['province'], '区', $item['city'], '商圈', $item['county']],
            [],
            ['客户名称', $item['realname'],'','','', '下单时间', date('Y年m月d日 H:i:s', strtotime($item['created_time']))],
            [],
            ['订单编号', 'NO.' . $item['order_number'] ],
            [],
            ['店铺名称', $item['shop_name'], '', '', '', '配送时间', $item['deliver_date'] . ' ' . $item['deliver_time'] ],
            [],
            ['客户地址', $item['address'],'','','','联系人', $item['realname']],
            [],
            ['联系电话', 'tel:' . $item['mobile'],'','','','售后电话', 'tel:010-58298105'],
            [],
            ['销售', $item['bd'],'','','', '销售电话', 'tel:' . $item['bd_mobile']],
            [],
            ['产品名称', '', '', '', '订货数量', '订货单价', '订货金额', '实收数量'],
            [],
        ];

        $details = [];
        foreach($item['detail'] as $key => $val) {
            $spec_str = $this->_format_spec($val['spec']);
            $detail   = [
                $val['name'], '', '', '', $val['quantity'],
                $val['price'], $val['sum_price'], ''
            ];

            $details[] = $detail;
            $details[] = [$spec_str];
        }
        $csv_data = array_merge($csv_data, $details);

        $tail_arr = [
            [],
            ['订单总价', $item['total_price'], '', '', '', '实收金额'],
            [],
            ['客户签字','', '', '', '司机签字'],
            [],
            ['出纳签字','', '','', '库管签字'],
            [],
            ['1.存根（白联）  2.出纳（粉联） 3.库管（绿联） 4.客户（蓝联）'],
            [],
        ];
        $csv_data = array_merge($csv_data, $tail_arr);
        //$this->_save_to_csv($csv_data, $item['id'], $file_dir);
        return $csv_data;
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 把输出目录里的csv都合成到一个统一的excel文件里
     * 每一个csv都占据一个单独的sheet，方便打印
     */
    private function _make_excel($dir = '', $out_name, $csv_enc = null) {

        //下面的代码是抄的。
        //set cache
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        //open excel file
        $write_objPHPExcel = new PHPExcel();

        //open csv file
        $objReader = new PHPExcel_Reader_CSV();
        if ($csv_enc != null)
            $objReader->setInputEncoding($csv_enc);
        //下面要循环了

        $list_arr = get_filenames($dir);
        sort($list_arr);
        $sheet_cnt = 0;
        foreach($list_arr as $idx => $item) {
            if(strpos($item, '.csv') != FALSE) {
                //用订单id.csv来命名每一个sheet
                $out_sheet = new PHPExcel_Worksheet($write_objPHPExcel, $item);
                //$out_sheet->setTitle($item);
                $read_objPHPExcel = $objReader->load($dir . $item);
                $in_sheet = $read_objPHPExcel->getActiveSheet();

                //row index start from 1
                $row_index = 0;
                foreach ($in_sheet->getRowIterator() as $row) {
                    $row_index++;
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    //column index start from 0
                    $column_index = -1;
                    foreach ($cellIterator as $cell) {
                        $column_index++;
                        $out_sheet->setCellValueByColumnAndRow($column_index, $row_index, $cell->getValue());
                    }
                }
                $write_objPHPExcel->addSheet($out_sheet);
            }
        }
        //上面要循环了
        //上面的代码是抄的

        //write excel file
        $objWriter = new PHPExcel_Writer_Excel2007($write_objPHPExcel);
        $objWriter->save($out_name);
    }

    private function _get_deliver_info_by_req2() {

        $request_time = $this->input->server('REQUEST_TIME');
        $request_hour = intval(date('H', $request_time));
        $deliver_date = strtotime(date('Y-m-d', $request_time));
        $deliver_time = 'today';
        //晚上11点到次日的9点前，都可以导出次日上午配送的订单
        //9点之后到晚上11点前都可以导出下午的订单
        //现在导出时要一次性把第二天配送的所有的订单导出来
        /*$deliver_time = 1;
        if($request_hour >= 9 && $request_hour < 23) {
            $deliver_time = 2;
        }
        if($request_hour >=23) {
            $deliver_date = $deliver_date + 86400;
        }*/

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

        $where = [
            'deliver_date' => $deliver_date,
            'deliver_time' => $deliver_time,
            'status'       => C('order.status.confirmed.code'),
        ];
        return $where;
    }

    private function _get_deliver_info_by_req() {

        $request_time = $this->input->server('REQUEST_TIME');
        $request_hour = intval(date('H', $request_time));
        $deliver_date = strtotime(date('Y-m-d', $request_time));
        $deliver_time = 'today';
        //晚上11点到次日的9点前，都可以导出次日上午配送的订单
        //9点之后到晚上11点前都可以导出下午的订单
        //现在导出时要一次性把第二天配送的所有的订单导出来
        /*$deliver_time = 1;
        if($request_hour >= 9 && $request_hour < 23) {
            $deliver_time = 2;
        }
        if($request_hour >=23) {
            $deliver_date = $deliver_date + 86400;
        }*/

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

        $res = array(
            'deliver_date' => $deliver_date,
            'deliver_time' => $deliver_time
        );

        return $res;
    }

    /**
     * @description 订单汇总数据
     */
    private function _order_product_sum2($orders = array()) {

        if(empty($orders)) {
            return [];
        }

        $order_ids = array_column($orders, 'id');
        if(empty($order_ids)) {
            return [];
        }

        $where = [
            'in' => [
                'order_id' => $order_ids
            ]
        ];
        $details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );

        $csv_data = [];
        foreach($details as $item) {
            $product_id = $item['id'];
            $item['spec'] = json_decode($item['spec'], TRUE);
            if(isset($csv_data[$product_id])) {
                $csv_data[$product_id]['quantity'] += $item['quantity'];
            } else {
                $csv_data[$product_id] = [
                    'id'       => $product_id + 1000000, // 后台需要给每个product_id加100万
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
     * @description 订单汇总数据
     * 暂时没什么用。
     */
    private function _order_product_sum($orders = array(), $site = 'dachu') {
        $site_id = C('site.' . $site);
        $order_ids = [];
        //只取符合条件的订单id
        foreach($orders as $item) {
            if($item['site_src'] == $site_id) {
                $order_ids[] = $item['id'];
            }
        }

        if(empty($order_ids)) {
            return [];
        }
        $where = [
            'in' => [
                'order_id' => $order_ids
            ]
        ];
        $details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );
        $csv_data = [];
        foreach($details as $item) {
            $product_id = $item['id'];
            $item['spec'] = json_decode($item['spec'], TRUE);
            if(isset($csv_data[$product_id])) {
                $csv_data[$product_id]['quantity'] += $item['quantity'];
            } else {
                $csv_data[$product_id] = [
                    'id'       => $product_id + 1000000, // 后台需要给每个product_id加100万
                    'name'     => $item['name'],
                    'spec'     => $this->_format_spec($item['spec']),
                    'quantity' => $item['quantity'],
                ];
            }
        }

        //按product_id分组计算总量
        $title_arr = [
            '产品id',
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
    public function export_orders_to_deliver2() {

        $deliver_info = $this->_get_deliver_info_by_req();
        $deliver_date = $deliver_info['deliver_date'];
        $deliver_time = $deliver_info['deliver_time'];

        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/';
        $zip_path  = $today_dir . 'result.zip';
        $zip_name  = date('Y-m-d', $deliver_date) . '-' . $deliver_time . '.zip';

        if(!file_exists($zip_path)) {
            echo "没有到可以导出的时间！";
            return ;
        }

        $data = file_get_contents($zip_path); // 读文件内容
        $name = $zip_name;
        $this->load->helper('download');

        force_download($name, $data);
    }

    public function export_orders_to_deliver() {
        $deliver_info = $this->_get_deliver_info_by_req();
        $deliver_date = $deliver_info['deliver_date'];
        $deliver_time = $deliver_info['deliver_time'];

        $today_dir = $this->_csv_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '/';
        $zip_path  = $today_dir . date('Y-m-d', $deliver_date) . '-' . $deliver_time . '.zip';
        $zip_name  = date('Y-m-d', $deliver_date) . '-' . $deliver_time . '.zip';

        if(!file_exists($zip_path)) {
            echo "没有到可以导出的时间！";
            return ;
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
            'in' => [ 'order_id' => $order_ids ]
        ];
        $order_details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );
        $detail_map = [];
        foreach($order_details as &$item) {
            $order_id = $item['order_id'];
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
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
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $deliver_arr          = $this->_deliver_dict;
            $item['deliver_time'] = isset($deliver_arr[$item['deliver_time']]) ?
                $deliver_arr[$item['deliver_time']] : '';
            $item['deliver_date'] = date('Y/m/d', $item['deliver_date']);

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

            //地址商圈相关
            $city_id           = $order_user['city_id'];
            $province_id       = $order_user['province_id'];
            $county_id         = $order_user['county_id'];
            $item['province']  = isset($location_map[$province_id]) ? $location_map[$province_id]['name'] : '';
            $item['city']      = isset($location_map[$city_id]) ? $location_map[$city_id]['name'] : '';
            $item['county']    = isset($location_map[$county_id]) ? $location_map[$county_id]['name'] : '';
            $item['address']   = $order_user['address'];


            //bd信息
            //print_r($item);
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


    /**
     * @author caochunhuiQ@dachuwang.com
     * @description 每日的订单
     */
    public function everyday_order() {
        $get = $this->input->get(NULL, TRUE);
        $request_time = $this->input->server('REQUEST_TIME');
        $today        = strtotime(date('Ymd', $request_time));
        $tomorrow     = $today + 86400;

        $where = array(
            'status'       => C('order.status.confirmed.code'),
        );

        if(isset($get['by_day']) && $get['by_day'] == 1) {
            unset($where);
            $where = [
                'created_time >=' => $today,
                'created_time <'  => $tomorrow,
                'status'       => C('order.status.confirmed.code'),
            ];
        }

        if(isset($get['by_created_time']) && $get['by_created_time'] == 1) {
            unset($where);
            $where = [
                'created_time >=' => $today,
                'created_time <'  => $tomorrow,
                'status'       => C('order.status.confirmed.code'),
            ];
        }

        if(isset($get['deliver_date']) ) {
            unset($where);
            $where = array(
                'deliver_date' => strtotime($get['deliver_date']),
                'status'    => C('order.status.confirmed.code')
            );
            if(isset($get['deliver_time'])) {
                switch($get['deliver_time']) {
                case "am" :
                    $where['deliver_time'] = 1;
                    break;
                case "pm":
                    $where['deliver_time'] = 2;
                    break;
                default:
                    break;
                }
            }
        }

        $orders = $this->MOrder->get_lists(
            '*',
            $where
        );
        if(empty($orders)) {
            print_r($where);
            echo '没有可以导出的订单';
            return;
        }

        $orders = $this->_format_order_list($orders);
        $counties = array_column($orders, 'county');
        $lines = array_column($orders, 'line');
        $mobiles = array_column($orders, 'mobile');
        $site = array_column($orders, 'site_src');
        array_multisort($site, SORT_ASC, $lines, SORT_ASC, $counties, SORT_ASC, $mobiles, SORT_ASC, $orders);

        $csv_data = [];
        foreach($orders as $item) {
            $item['site'] = $item['site_src'] == C('site.dachu') ? '大厨' : '大果';
            $csv_data[] = array(
                'site'          => $item['site'],
                'shop_name'     => $item['shop_name'],
                'order_id'      => $item['id'],
                'order_number'  => 'NO.' . $item['order_number'],
                'order_status'  => $item['status_cn'],
                'user_id'       => $item['user_id'],
                'province'      => $item['province'],
                'city'          => $item['city'],
                'county'        => $item['county'],
                'line'          => $item['line'],
                'deliver_addr'  => $item['address'],
                'deliver_time'  => $item['deliver_date'] . ' ' . $item['deliver_time'],
                'realname'      => $item['realname'],
                'mobile'        => 'tel:' . $item['mobile'],
                'pay_type'      => '现金',
                'seller'        => $item['bd'],
                'seller_mobile' => $item['bd_mobile'],
                'product_id'    => '',
                'product_name'  => '',
                'quantity'      => '',
                'spec'          => '',
                'single_price'  => '',
                'total_price'   => '',
                'created_time'  => $item['created_time'],
            );
            $first_line_idx = count($csv_data) - 1;
            foreach($item['detail'] as $idx => $val) {
                //把详情上移一行
                if($idx == 0) {
                    $csv_data[$first_line_idx]['product_id'] = $val['id'] + 1000000;
                    $csv_data[$first_line_idx]['product_name'] = $val['name'];
                    $csv_data[$first_line_idx]['quantity'] = $val['quantity'];
                    $csv_data[$first_line_idx]['spec'] = $this->_format_spec($val['spec']);
                    $csv_data[$first_line_idx]['single_price'] = $val['price'];
                    $csv_data[$first_line_idx]['total_price'] = $val['sum_price'];
                    continue;
                }
                $csv_data[] = array(
                    /* type 1
                    'site'       => '',
                    'shop_name'     => '',
                    'order_id'      => '',
                    'order_status'  => '',
                    'user_id'       => '',
                    'province'      => '',
                    'city'          => '',
                    'county'        => '',
                    'deliver_addr'  => '',
                    'deliver_time'  => '',
                    'realname'      => '',
                    'mobile'        => '',
                    'pay_type'      => '',
                    'seller'        => '',
                    'seller_mobile' => '',
                     */
                    //type 2
                    'site'          => $item['site'],
                    'shop_name'     => $item['shop_name'],
                    'order_id'      => $item['id'],
                    'order_number'  => 'NO.' . $item['order_number'],
                    'order_status'  => $item['status_cn'],
                    'user_id'       => $item['user_id'],
                    'province'      => $item['province'],
                    'city'          => $item['city'],
                    'county'        => $item['county'],
                    'line'          => $item['line'],
                    'deliver_addr'  => $item['address'],
                    'deliver_time'  => $item['deliver_date'] . ' ' . $item['deliver_time'],
                    'realname'      => $item['realname'],
                    'mobile'        => 'tel:' . $item['mobile'],
                    'pay_type'      => '现金',
                    'seller'        => $item['bd'],
                    'seller_mobile' => $item['bd_mobile'],
                    //type 2

                    'product_id'   => $val['id'] + 1000000,
                    'product_name' => $val['name'],
                    'quantity'     => $val['quantity'],
                    'spec'         => $this->_format_spec($val['spec']),
                    'single_price' => $val['price'],
                    'total_price'  => $val['sum_price'],
                    'created_time' => $item['created_time'],
                );
            }
        }


        $title_arr = array(
            'site'          => '下单站点',
            'shop_name'     => '门店名称',
            'order_id'      => '订单id',
            'order_number'  => '订单号',
            'order_status'  => '订单状态',
            'user_id'       => '门店id',
            'province'      => '市',
            'city'          => '区',
            'county'        => '商圈',
            'line'          => '线路',
            'deliver_addr'  => '门店地址',
            'deliver_time'  => '收货时间',
            'realname'      => '联系人',
            'mobile'        => '电话',
            'pay_type'      => '付款方式',
            'seller'        => '销售员',
            'seller_mobile' => '销售电话',
            'product_id'    => '产品货号',
            'product_name'  => '产品名称',
            'quantity'      => '数量',
            'spec'          => '规格',
            'single_price'  => '单价',
            'total_price'   => '总价',
            'created_time'  => '下单时间',
        );
        array_unshift($csv_data, $title_arr);

        $xls_data = [];
        foreach($csv_data as $item) {
            $xls_data[] = array_values($item);
        }
        $xls_data = [$xls_data];


        $path = '/tmp/export/temp.xlsx';
        $this->_convert_array_to_excel($xls_data, ['everyday'], $path);

        $data = file_get_contents($path); // 读文件内容
        $name = 'default.xlsx';
        $this->load->helper('download');

        force_download($name, $data);

    }

    /**
     * @author caochunhuiQ@dachuwang.com
     * @description 按照bd分组的订单
     * 暂时废弃
     */
    public function orders_group_by_bd() {
        $get          = $this->input->get(NULL, TRUE);
        $request_time = $this->input->server('REQUEST_TIME');
        $today        = strtotime(date('Ymd', $request_time));
        $tomorrow     = $today + 86400;
        $by_day_flag  = FALSE;
        if(!empty($get['by_day']) && $get['by_day'] == 1) {
            $by_day_flag = TRUE;
        }
        $where = [
            'status !=' => C('order.status.closed.code')
        ];
        if($by_day_flag) {
            unset($where);
            $where = [
                'created_time >=' => $today,
                'created_time <'  => $tomorrow,
                'status !='       => C('order.status.closed.code'),
            ];
        }
        $orders = $this->MOrder->get_lists(
            '*',
            $where
        );
        if(empty($orders)) {
            print_r($where);
            echo '没有可以导出的订单';
        }

        $orders = $this->_format_order_list($orders);
        $csv_data = [];
        foreach($orders as $item) {
            $spec_str = '';
            foreach($item['detail'] as $val) {
                $spec_str .= $val['name'] . '==' . $val['quantity'] . '==' . $this->_format_spec($val['spec']) . ';';
            }

            $csv_data[] = array(
                'bd'           => $item['bd'],
                'shop_name'    => $item['shop_name'],
                'id'           => $item['id'],
                'user_id'      => $item['user_id'],
                'deliver_addr' => $item['address'],
                'deliver_time' => $item['deliver_time'],
                'realname'     => $item['realname'],
                'mobile'       => 'tel:' . $item['mobile'],
                'product_name' => '',
                'quantity'     => '',
                'spec'         => $spec_str,
            );
        }

        $title_arr = array(
            'bd'           => 'BD',
            'shop_name'    => '门店名称',
            'id'           => '订单id',
            'user_id'      => '门店id',
            'deliver_addr' => '门店地址',
            'deliver_time' => '收货时间',
            'realname'     => '联系人',
            'mobile'       => '电话',
            'product_name' => '产品名称',
            'quantity'     => '数量',
            'spec'         => '规格',
        );

        $this->export_csv($title_arr, $csv_data);
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 用数组和地址直接生成excel文件
     * 每一个数组占一个sheet
     */
    private function _convert_array_to_excel($arr = array(), $sheet_titles = array(), $out_name = '') {

        //下面的代码是抄的。
        //set cache
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        //open excel file
        $write_objPHPExcel = new PHPExcel();

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
                    //var_dump($cell);
                    $out_sheet->setCellValueByColumnAndRow($column_index, $row_index, $cell);
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
}

/* End of file temp_export.php */
/* Location: ./application/controllers/temp_export.php */
