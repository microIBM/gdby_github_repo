<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的类型控制器
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Category extends MY_Controller {
    private $_page_size = 10;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
            )
        );
    }
    public function lists() {
        $return_data = $this->_get_lists();
        extract($return_data);
        $current_date = strtotime(date("Y-m-d", $this->input->server('REQUEST_TIME')));
        $recommends = $this->format_query('/recommend/manage', array('customer_type' => $customer_type, 'status' => C('status.common.success'), 'location_id' => $location_id, 'site_id' => $site_id, 'current_date' => $current_date));
        $data['recommends'] = [];
        if(isset($recommends['list'])) {
            $data['recommends'] = $recommends['list'];
        }
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取分类映射列表
     */
    private function _get_lists() {
        $post = $this->post;
        $site_id = $post['site_id'] = C('app_sites.guo.id');
        $post['status'] = C('status.common.success');
        // 查询所属城市
        if(!empty($post['locationId'])) {
            $location_id = $post['location_id'] = intval($post['locationId']);
        } else {
            $location_id = $post['location_id'] =  C('open_cities.beijing.id');
        }
        // 检测用户是否已经登录,
        // 登录用户不允许切换城市
        // 优先取登录用户的所在城市
        $cur = $this->userauth->current(TRUE);
        $user_info = array();
        $customer_type = C('customer.type.normal.value');
        if($cur) {
            $location_id = $post['location_id'] = $cur['province_id'];
            $local_info = $this->format_query('/location/info', array('where' => array('id' => $cur['province_id'])));
            // 所在城市info信息
            if(intval($local_info['status']) === 0) {
                $user_info = array(
                    'location_id' => $cur['province_id'],
                    'name' => $local_info['info']['name']
                );
                $post['line_id'] = $cur['line_id'];
            }
            if(!empty($cur['customer_type'])){
                $customer_type = $post['customer_type'] = $cur['customer_type'];
            }
        }

        $return_data = $this->format_query('/catemap/lists', $post);
        $data = $this->_deal_catemap_data($return_data, $user_info, $post);
        return array('data' => $data, 'customer_type' => $customer_type, 'location_id' => $location_id, 'site_id' => $site_id);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 处理列表数据
     */
    private function _deal_catemap_data($return_data, $user_info) {
        $data = array(
            'list' => array(
                'top' => [],
                'second' => [],
                'user_info' => $user_info
            ),
            'adv_switch_index' => FALSE,
            'status' => C('tips.code.op_success')
        );
        if($return_data) {
            $top = $second = array();
            foreach($return_data['list'] as $v) {
                if($v['upid']) {
                    $v['map_id'] = $v['id'];
                    $v['id'] = $v['origin_id'];
                }
                if($v['upid'] == 0) {
                    $top[] = $v;
                } else {
                    $second[$v['upid']][] = $v;
                }
            }

            $adv_switch = C('advs.index');
            $data = array(
                'list' => array(
                    'top' => $top,
                    'second' => $second,
                    'user_info' => $user_info
                ),
                'adv_switch_index' => $adv_switch,
                'status' => $return_data['status']
            );
        }
        return $data;
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 查看子分类
     */
    public function get_category_list() {
        $page = empty($this->post['page']) ? 1 : $this->post['page'];
        if(isset($this->post['upid'])) {
            $this->post['upid'] = intval($this->post['upid']);
        } else {
            $this->post['upid'] = 0;
        }
        if(isset($this->post['name'])) {
            $where['like'] = array('name'   => $this->post['name']);
        }
        if(!isset($this->post['seccate'])) {
            $where['upid'] = $this->post['upid'];
        }
        $tips = $this->format_query('/category/get_child_list', array('where' => $where, 'page' => $this->post['page']));
        $this->_return_json($tips);
    }
}
/* End of file category.php */
/* Location: :./application/controllers/category.php */
