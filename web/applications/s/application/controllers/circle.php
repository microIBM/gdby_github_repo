<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * service
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Circle extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(array('location'));
    }

    public function lists() {
        // 商家的类别
        $shop_type = C('customer_type');
        // 系统类别
        $sites = array(C('app_sites.chu'), C('app_sites.guo'));
        $data = $this->location->index();
        $top_ids = array_column($data, 'id');
        $second = $this->location->children($top_ids);
        $return_data['top'] = $data;
        foreach($top_ids as $id) {
            foreach($second as $v) {
                if($v['upid'] == $id) {
                    $return_data['second'][$id][] = $v;
                }
            }
        }
        $second_ids = array_column($second, 'id');
        $third = $this->location->children($second_ids);
        foreach($second_ids as $id) {
            foreach($third as $v) {
                if($v['upid'] == $id) {
                    $return_data['third'][$id][] = $v;
                }
            }
        }

        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $return_data,
                'sites' => $sites,
                'shop_type' => $shop_type
            )
        );
    }
}

/* End of file location.php */
/* Location: ./application/controllers/location.php */
