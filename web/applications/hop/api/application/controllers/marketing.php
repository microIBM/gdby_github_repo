<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @description 这个类是临时的，给运营导出17-21号参与活动的订单数据
 */
class Marketing extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MOrder_detail',
                'MLine',
                'MLocation'
            )
        );
        $this->load->library(
            array(
                "PHPExcel"
            )
        );
        //unit_id  => unit_name
        $unit_config = C('unit');
        $codes       = array_column($unit_config, 'id');
        $msg         = array_column($unit_config, 'name');
        $this->_unit_dict = array_combine($codes, $msg);
        $this->_unit_dict[0] = '无';

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

    public function export() {
        ini_set("memory_limit", "1024M");

        $where = array(
            'status !='       => 0,
            'created_time >=' => strtotime('20150417'),
            'created_time <'  => strtotime('20150422'),
            'total_price >='  => 19900,
            'site_src'        => C('site.dachu')
        );

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
        //print_r($orders);die();
        $customer_ids = array_column($orders, 'user_id');
        $counties = array_column($orders, 'county');
        $lines = array_column($orders, 'line');
        $mobiles = array_column($orders, 'mobile');
        $site = array_column($orders, 'site_src');
        array_multisort($customer_ids, SORT_ASC, $site, SORT_ASC, $lines, SORT_ASC, $counties, SORT_ASC, $mobiles, SORT_ASC, $orders);

        $csv_data = [];
        foreach($orders as $item) {
            $item['site'] = $item['site_src'] == C('site.dachu') ? '大厨' : '大果';
            $order_info = array(
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
                'seller'        => $item['bd'],
                'seller_mobile' => $item['bd_mobile'],
                'product_info'  => '',
                'total_price'   => $item['total_price'],
                'created_time'  => $item['created_time'],
                'remarks'       => $item['remarks'],
            );

            //填充详情
            foreach($item['detail'] as $detail) {
                $order_info['product_info'] .= $detail['name'] . ' ' . $detail['quantity'] . $this->_unit_dict[$detail['unit_id']] . "\n";
            }

            $csv_data[] = $order_info;
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
            'seller'        => '销售员',
            'seller_mobile' => '销售电话',
            'product_info'  => '商品信息',
            'total_price'   => '总价',
            'created_time'  => '下单时间',
            'remarks'       => '客户备注',
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

        $category_ids = array_column($order_details, 'category_id');

        $detail_map = [];
        foreach($order_details as &$item) {
            $order_id = $item['order_id'];
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
            $item['single_price'] /= 100;
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
            $item['status_cn'] = isset($this->_status_dict[$status]) ? $this->_status_dict[$status] : '';
            $order_id          = $item['id'];
            $item['detail']    = isset($detail_map[$order_id]) ? $detail_map[$order_id] : [];
        }
        unset($item);
        return $order_list;
    }

    private function _convert_array_to_excel($arr = array(), $sheet_titles = array(), $out_name = '') {

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

/* End of file marketing.php */
/* Location: ./application/controllers/marketing.php */
