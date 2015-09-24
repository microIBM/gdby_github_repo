<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_sku_unit_name extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MSku'
            )
        );
        // $this->load->library(array('Product_lib'));
    }

    /*
     * 第一行的描述一定要有
     *要求先把excel弄成：地点，skuID，名称
     */
    public function import_csv_name1($csvpath = null) {
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        array_shift($data);
        foreach($data as $val) {
            $unit_name = iconv('', 'utf-8//ignore', $val['6']);
            $this->MSku->update_by('sku_number', $val['1'], array('unit_name' => $unit_name));
        }
        echo count($data);exit;
    }

    public function import_csv_name2($csvpath = null) {
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        
        print_r($data);exit;
        array_shift($data);
        foreach($data as $val) {
            $unit_name = iconv('', 'utf-8//ignore', $val['6']);
            $this->MSku->update_by('sku_number', $val['1'], array('unit_name' => $unit_name));
        }
        echo count($data);exit;
    }

    public function import_csv_name($csvpath = null) {
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        array_shift($data);
        foreach($data as $val) {
            $unit_name = iconv('', 'utf-8//ignore', $val['1']);
            $this->MSku->update_by('sku_number', $val['0'], array('unit_name' => $unit_name));
        }
        echo count($data);exit;
    }
    
    public function import_csv_name_clone($csvpath = null) {
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        array_shift($data);

        $this->MSku->get_one('sku_number');
        foreach($data as $val) {
            //$unit_name = iconv('', 'utf-8//ignore', $val['5']);
            //$this->MSku->update_by('sku_number', $val['3'], array('unit_name' => $unit_name));
        }
        echo count($data);exit;
    }
}

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
