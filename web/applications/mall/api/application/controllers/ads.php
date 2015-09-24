<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 广告信息接口
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 14-4-24
 */
class Ads extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('img_zoom'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个信息
     */
    public function info() {
        $data = $this->format_query('/ads/info', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 广告列表
     */
    public function lists() {
        $site_id = C('app_sites.chu.id');
        $status = C('status.common.success');
        // 查询所属城市
        if(!empty($this->post['pos_id'])) {
            $pos_id = intval($this->post['pos_id']);
        } else {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '广告位id没有标注'
                )
            );
        }
        if(!isset($this->post['locationId'])) {
            $location_id = C('open_cities.beijing.id');
        } else {
            $location_id = $this->post['locationId'];
        }
        // 检测用户是否已经登录,
        // 登录用户不允许切换城市
        // 优先取登录用户的所在城市
        $cur = $this->userauth->current(TRUE);
        // 增加是否需要登录的来判断
        $need_login = array(0);
        if($cur) {
            $location_id = $cur['province_id'];
            $need_login = array(0, 1);
        }
        $post = array(
            'where' => array(
                'status'      => $status,
                'location_id' => $location_id,
                'site_id'     => $site_id,
                'in' => array(
                    'need_login' => $need_login
                ),
                'customer_type' => !empty($cur['customer_type']) ? $cur['customer_type'] : C('customer.type.normal.value'),
            )
        );
        $data = $this->format_query('/ads/get_valid_ads_list', $post);
        $status = empty($data['list']) ? FALSE : TRUE;
        if($status) {
            $data['list'] = $this->_deal_by_pos_id($data, $pos_id, $cur);
            $data['list'] = img_zoom($data['list'], '-950-500');
        }
        if($status && isset($data['list'])){
            $response = $data;
        } else {
            $response = array(
                'status' => C('tips.code.op_success'),
                'list' => array(),
                'msg' => '没有广告'
            );
        }
        $this->_return_json($response);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 根据pos_id来筛选
     */
    private function _deal_by_pos_id($data, $pos_id, $cur) {
        $new_data = array();
        $is_login = $cur ? TRUE : FALSE;
        foreach($data['list'] as $v) {
            $path = explode(',', trim($v['pos_id'], ','));
            if(in_array($pos_id, $path)) {
                // line_ids
                if($is_login) {
                    if($v['line_ids'] == 0) {
                        $new_data[] = $v;
                    } else {
                        $line_ids = explode(',', $v['line_ids']);
                        if(array_intersect(array($cur['line_id']), $line_ids)) {
                            $new_data[] = $v;
                        }
                    }
                } else {
                    $new_data[] = $v;
                }
            }
        }
        return $new_data;
    }
}

/* End of file ads.php */
/* Location: ./application/controllers/ads.php */
