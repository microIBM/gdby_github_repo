<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_collect_type extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MCategory',
                'MLocation'
            )
        );
    }

    /*
     * 第一行的描述一定要有
     *要求先把excel弄成：地点，skuID，名称
     */
    public function fix($csvpath = null) {
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        $data = eval('return ' . iconv('gbk','utf-8', var_export($data, true)) . ';');
        $desc = array_shift($data);
        $city_index = array_search('城市', $desc);
        $sku_index = array_search('货号', $desc);
        $desc_index = array_search('APP线上名称', $desc);
        $city_index OR $sku_index OR $desc_index OR die('没有找到城市、货号、app线上名称');
        $data_need_change = array();
        foreach($data as $row) {
            if (trim($row[$city_index]) && trim($row[$sku_index])) {
                $data_need_change[] = array(
                    'location_name' => trim($row[$city_index]),
                    'sku_number' => trim($row[$sku_index]),
                    'msg' => $row[$desc_index]);
            } else {
                echo '传人信息错误：城市 ->' . ($row[$city_index] ? $row[$city_index] : '空') . ',skuID ->' . ($row[$sku_index] ? $row[$sku_index] : '空') . "\r\n";
            }
        }
        $location_names = array_unique(array_column($data_need_change, 'location_name'));
        $location_infos = $this->MLocation->get_lists('name,id', array(
            'in' => array('name' => $location_names)
        ));
        $location_name_map_id = array();
        foreach($location_infos as $info) {
            $location_name_map_id[$info['name']] = $info['id'];
        }

        $update_count = 0;
        $failed_ids = array();
        foreach($data_need_change as $element) {
            $location_id = isset($location_name_map_id[$element['location_name']]) ? $location_name_map_id[$element['location_name']] : 0;
            if($location_id && $element['sku_number']) {
                $update_info = $this->MProduct->update_info(
                    array('collect_type' => C('foods_collect_type.type.now_collect.value')),
                    array('location_id' => $location_id, 'sku_number'  => $element['sku_number'], 'collect_type' => C('foods_collect_type.type.pre_collect.value'))
                );
                if ($update_info) {
                    $update_count += 1;
                } else {
                    $failed_ids[] = $id['id'];
                }
            } else {
                echo '数据更新错误：城市->' . ($location_id ? $location_id : '空') . ',sku_nume ->' . ($element['sku_number'] ? $element['sku_number'] : '空') . "\r\n";
            }
        }
        echo '成功更新' . $update_count . '条数据!';
        echo $failed_ids ? '更新失败' . count($failed_ids) . '条' : '';
    }
}

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
