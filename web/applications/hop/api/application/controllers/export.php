<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Export extends MY_Controller {

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

        $this->load->helper(
            array('sku_helper')
        );

        //导出到wms的csv文件根目录,必须带右边的/
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
            $line_summary = $this->_order_product_sum($orders_in_line);
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

    public function write_orders_to_tmp_dir() {

        $where = $this->_get_deliver_info_by_req();
        $deliver_date = $where['deliver_date'];
        $deliver_time = $where['deliver_time'];

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

        $where = [
            'deliver_date' => $deliver_date,
            'deliver_time' => $deliver_time,
            'status'       => C('order.status.confirmed.code'),
        ];
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
                'order_id' => $order_ids
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
                    'id'       => set_sku($product_id), // 后台需要给每个product_id加100万
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
            $item['sku_id'] = set_sku($item['product_id']);
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
            $item['spec_str'] = $this->_format_spec($item['spec']);
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
            $invite_id = $order_user['invite_id'];
            if(!isset($bd_map[$invite_id])) {
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
            $arr = array(
                'status' => -1,
                'msg'    => '没有可以导出的订单'
            );
            $this->_return_json($arr);
        }

        $orders = $this->_format_order_list($orders);
        $counties = array_column($orders, 'county');
        $lines = array_column($orders, 'line');
        $mobiles = array_column($orders, 'mobile');
        $site = array_column($orders, 'site_src');
        array_multisort($site, SORT_ASC, $lines, SORT_ASC, $counties, SORT_ASC, $mobiles, SORT_ASC, $orders);

        $arr = array(
            'status' => 0,
            'msg'    => 'fetch order success',
            'lists'  => $orders
        );
        $this->_return_json($orders);
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
