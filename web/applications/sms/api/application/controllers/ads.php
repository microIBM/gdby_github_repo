<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 广告接口
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-4-24
 */
class Ads extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function lists() {
        if($this->post['status'] != 'all') {
            $this->post['where']['status'] = $this->post['status'];
        }
        if(!empty($this->post['searchVal'])) {
            $this->post['where']['like'] = array('title' => $this->post['searchVal']);
        }
        $data = $this->format_query('/ads/lists', $this->post);
        $this->_return_json($data);
    }

    public function save() {
        if(isset($this->post['id'])) {
            $data = $this->format_query('/ads/save', $this->post);
        } else {
            $this->post['status'] = C('status.common.del');
            $data = $this->format_query('/ads/create', $this->post);
        }
        $this->_return_json($data);
    }

    public function set_status() {
        $data = $this->format_query('/ads/set_status', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 添加的选项
     */
    public function input_options() {
        $data['adv_status'] = C('ads.options.advs.status');
        // 广告位
        $position_data = $this->format_query('/ads_position/lists', array('getAll' => TRUE));
        if(isset($position_data['list'])) {
            $data['positions'] = $position_data['list'];
        }
        // 城市列表
        $locations = $this->format_query('/location/get_child');
        $data['locations'] = $locations['list'];
        $where = array(
            'where' => array(
                'upid >' => 0
            )
        );
        $catemaps = $this->format_query('/catemap/get_all');
        foreach($catemaps['list'] as $v) {
            if(intval($v['upid']) > 0 && $v['location_id']) {
                $data['catemaps'][$v['site_id']][$v['location_id']][] = $v;
            }
        }
        $sites = C('app_sites');
        foreach($sites as $sv) {
            $data['sites'][] = $sv;
        }
        $lines = $this->format_query('/line/get_all');
        $new_lines = array();
        foreach($lines['list'] as $v) {
            $new_lines[$v['location_id']][] = $v;
        }
        $data['line_options'] = $new_lines;
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
}

/* End of file ads.php */
/* Location: ./application/controllers/ads.php */
