<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_sku extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MSku'
            )
        );
        $this->load->library(array('Product_lib'));
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
        //转化格式
        $data = eval('return ' . iconv('gbk','utf-8//ignore', var_export($data, true)) . ';');
        $desc = array_shift($data);
        $sku_index = array_search('编码', $desc) OR $sku_index = array_search("商品编号", $desc);
        $expire_index = array_search('保质期', $desc);
        $left_index = array_search('近效期', $desc);
        $code_index = array_search('录入条码', $desc);
        $sku_index OR $expire_index OR $left_index OR $code_index OR die('没有找到城市、货号、app线上名称');

        $data_need_change = array();
        foreach($data as $row) {
            if (trim($row[$sku_index])) {
                if (!$row[$expire_index] && !$row[$left_index] && (!$row[$code_index] || $row[$code_index] == '空')) {
                    echo "$row[$sku_index]保质期，近效期，录入码为空\r\n";
                    continue;
                }
                $data_need_change[$row[$sku_index]] = implode(',', array($row[$expire_index], $row[$left_index], $row[$code_index]));
            } else {
                echo "sku_number为空其他信息为" . implode(',', $row) . "\r\n";
            }
        }
        $count = 0;
        foreach($data_need_change as $skuid => $update) {
            list($expire,$left,$code) = explode(',', $update);
            $cond = array();
            $expire AND $cond['guarantee_period'] = $expire;
            $left AND $cond['effect_stage'] = $left;
            ($code && !in_array($code, array("空", "待确认", "无")))  AND $cond['code'] = $code;
            $update_info = $this->MSku->update_info(
                $cond,
                array('sku_number' => $skuid)
            );
            if (!$update_info) {
                echo "$sku：更新失败\r\n";
            } else {
                $count += 1;
            }
        }
        echo "更新完毕,成功更新 $count 条数据\r\n";
        exit;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 修复单位
     */
    public function fix_unit() {
        $sql = 'update t_sku left join t_product on t_product.sku_number = t_sku.sku_number set t_sku.unit_id = t_product.unit_id where t_sku.id > 0 and t_product.status != 0;';
        $this->db->query($sql);
        // 要更新unit_name
        $this->units = C('unit');
        $sku_data = $this->MSku->get_lists('unit_id, id');
        foreach($sku_data as $sku) {
            $update_data['unit_name'] = $this->Product_lib->get_unit_name($sku['unit_id']);
            $this->MSku->update_info($update_data, array('id' => $sku['id']));
        }
    }
}

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
