<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_shop_type extends MY_Controller {

    //id为5的分类是新的不用修复数据
    private $_map = [
        1 => [1,9,11,14,19,20,21,27,28],
        2 => [18,23,24,25,26],
        3 => [3],
        4 => [8,13,15,16],
        6 => [22],
        7 => [4],
        8 => [12],
        9 => [5,7,10,17],
        10 => [2,29,30,31,32,33,34]
    ];

    public function __construct () {
        parent::__construct();
        $this->load->model(
            ['MCustomer','MPotential_customer']
        );
    }

    private function _update_type($who, $new_type, $ordarr) {
        $where = [
            'in' => ['shop_type' => $ordarr]
        ];
        $data = ['shop_type' => $new_type];
        return $this->$who->update_info($data, $where);
    }

    private function _check_repeat() {
        $exist = [];
        foreach($this->_map as $key => $arr) {
            foreach($arr as $val) {
                if(!in_array($val, $exist)) {
                    $exist[] = $val;
                } else {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    public function do_task() {
        if(!$this->_check_repeat()) {
            echo '程序内硬编码的映射关系($_map)数据冗余，请检查后再执行'."\n";
            return;
        }
        echo '正在进行数据修复...'."\n";
        foreach($this->_map as $new => $oldarr) {
            $this->_update_type('MCustomer', $new, $oldarr);
            $this->_update_type('MPotential_customer', $new, $oldarr);
        }
        echo "Success!\n";
    }
}
/* End of file fix_customer.php */
/* Location: ./application/controllers/fix_customer.php */
