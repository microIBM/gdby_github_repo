<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 获取开放城市列表
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-4-8
 */
class Location extends MY_Controller {
    private $_virtual_cities;

    public function __construct() {
        parent::__construct();

        $this->_virtual_cities = C('virtual_city');
    }

    public function city() {
        $data = $this->format_query('/location/get_child');
        $lists = [];
        if(!empty($data['list'])) {
            foreach($data['list'] as $key => $v) {
                $lists[] = array(
                    'id' => $v['id'],
                    'name' => $v['name']
                );
            }
        }
        $this->_deal_virtual_city($lists);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $lists
            )
        );
    }

    private function _deal_virtual_city(&$lists) {
        $cities = $this->_virtual_cities;
        if($cities) {
            foreach($cities as $v) {
                $lists[] = array(
                    'id' => C('open_cities.beijing.id'),
                    'name' => $v
                );
            }
        }
    }
}

/* End of file location.php */
/* Location: ./application/controllers/location.php */
