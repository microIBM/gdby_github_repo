<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * description
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Ads extends MY_Controller {
   public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLocation',
                'MLine',
                'MAds_position',
                'MAds'
            )
        );
   }
   /**
    * @author: liaoxianwen@ymt360.com
    * @description 获取单个广告信息
    */
   public function info() {
       if(!empty($_POST['id'])) {
           $customer_type = empty($_POST['customer_type']) ? C('customer.type.normal.value') : intval($_POST['customer_type']);
           $data = $this->MAds->get_one('*', array('id' => $_POST['id'], 'status' => C('status.common.success'), 'customer_type' => $customer_type));
           if($data) {
               $response = array(
                   'status' => C('tips.code.op_success'),
                   'info' => $data
               );
           } else {
               $response = array(
                   'status' => C('tips.code.op_failed'),
                   'msg' => '没有此信息'
               );
           }
       } else {
           $response = array(
               'status' => C('tips.code.op_failed'),
               'msg' => '参数id必传'
           );
       }
       $this->_return_json($response);
   }
   /**
    * @author: liaoxianwen@ymt360.com
    * @description 创建广告位
    */
    public function create() {
        $this->_unique_name($_POST['title']);
        $req_time = $this->input->server('REQUEST_TIME');
        if(empty($_POST['link_url'])) {
            $_POST['link_url'] = '';
        }
        if(!empty($_POST['line_id']) && is_array($_POST['line_id'])) {
            $_POST['line_id'] = implode(',', $_POST['line_id']);
        }
        $data = array(
            'title'        => $_POST['title'],
            'pic_url'      => $_POST['pic_url'],
            'detail_img'   => isset($_POST['detail_img']) ? $_POST['detail_img'] : '',
            'link_url'     => $_POST['link_url'],
            'pos_id'       => $_POST['pos_id'],
            'site_id'      => $_POST['site_id'],
            'location_id'  => $_POST['location_id'],
            'line_ids'     => empty($_POST['line_id']) ? 0 : $_POST['line_id'],
            'need_login'   => empty($_POST['needLogin']) ? 0 : $_POST['needLogin'],
            'status'       => $_POST['status'],
            'online_time'  => $_POST['onlineTime'],
            'offline_time' => $_POST['offlineTime'],
            'created_time' => $req_time,
            'updated_time' => $req_time
        );
        if( $id = $this->MAds->create($data) ) {
            if(!empty($data['link_url'])) {
                $link_url = trim($data['link_url'], '/');
                $url_arr = explode('/', $link_url);
                if(count($url_arr) === 2) {
                    $up_data = array(
                        'link_url' => "page.$url_arr[0]({{$url_arr[0]}Id:$url_arr[1]})"
                    );
                    $where = array('id' => $id);
                    $this->MAds->update_info($up_data, $where);
                }
            }
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '广告' . $_POST['title'] . '创建成功'
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '广告创建失败'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 保存更新
     */
    public function save() {
        $up_data = array(
            'title' => $_POST['title'],
            'status' => $_POST['status'],
            'created_time' => $this->input->server('REQUEST_TIME')
        );
        $affect = $this->MAds_position->update_info($up_data, array('id' => $_POST['id']));
        if($affect) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '更新成功'
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '更新失败'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取列表信息
     */
    public function lists() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $customer_type = empty($_POST['customer_type']) ? C('customer.type.normal.value') : intval($_POST['customer_type']);
        $where['customer_type'] = $customer_type;
        $return_data = $this->_get_ads_list($where);
        extract($return_data);
        if($data) {
            $this->_deal_data($data);
            $response = array(
                'status' => C('tips.code.op_success'),
                'total' => $total,
                'list' => $data
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有数据'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取有效的广告时间
     */
    public function get_valid_ads_list() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : array('created_time' => 'DESC');
        $current = strtotime(date('Y-m-d'), $this->input->server('REQUEST_TIME'));

        $where['online_time <='] = $current;
        $where['offline_time >='] = $current;
        $return_data = $this->_get_ads_list($where);
        extract($return_data);
        if($data) {
            $this->_deal_data($data);
            $response = array(
                'status' => C('tips.code.op_success'),
                'total' => $total,
                'list' => $data
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有数据'
            );
        }
        $this->_return_json($response);

    }
    private function _get_ads_list($where) {
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : array('created_time' => 'DESC');
        $page = $this->get_page();
        $total = $this->MAds->count($where);
        $data = $this->MAds->get_lists(
            '*',
            $where,
            $orderBy,
            array(),
            $page['offset'],
            $page['page_size']
        );
        return array('data' => $data, 'total' => $total);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 处理广告需求
     */
    private function _deal_data(&$data) {
        $advs_positions = $this->MAds_position->get_lists();
        $pos_ids = array_column($advs_positions, 'id');
        $positions = array_combine($pos_ids, $advs_positions);
        // ------ 地理位置
        $locations = $this->MLocation->get_lists('*', array('upid' => 0));
        $locationIds = array_column($locations, 'id');
        $lines = $this->MLine->get_lists__Cache120(
            'name, id'
        );
        $new_lines = array_combine(array_column($lines, 'id'), $lines);

        $promo_locations = array_combine($locationIds, $locations);
        $current = strtotime(date('Y-m-d'), $this->input->server('REQUEST_TIME'));
        $status_by_time = C('ads.adv_by_time');
        foreach($data as &$v) {
            $pos_id_arr = explode(',', trim($v['pos_id'], ','));
            $v['pos_cn'] = array();
            foreach($pos_id_arr as $pv) {
                if(isset($positions[$pv])) {
                    $v['pos_cn'][]= $positions[$pv]['title'];
                }
            }
            foreach($this->_sites as $sv) {
                if($sv['id'] == $v['site_id']) {
                    $v['site_cn'] = $sv['name'];
                }
            }
            if(!empty($v['line_ids'])){
                $l_ids = explode(',', $v['line_ids']);
                $v['line_cn'] = '';
                foreach($l_ids as $l_val) {
                    $v['line_cn'] .= $new_lines[$l_val]['name'] . ';';
                }
            }
            if($v['status'] != 0) {
                if($v['offline_time'] < $current) {
                    $v['status'] = $status_by_time['exceed_time'];
                } else if($v['online_time'] > $current) {
                    $v['status'] = $status_by_time['verify'];
                }
            }
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $v['offline_time'] = date('Y-m-d H:i:s', $v['offline_time']);
            $v['online_time'] = date('Y-m-d H:i:s', $v['online_time']);
            $v['location_cn'] = $promo_locations[$v['location_id']]['name'];
        }
    }

    public function set_status() {
        $data = array(
            'status' => $_POST['status'],
            'updated_time' => $this->input->server('REQUEST_TIME')
        );
        $where = array(
            'id' => $_POST['id']
        );
        $this->MAds->update_info($data, $where);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg' => '设置成功'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 确保名称唯一
     */
    private function _unique_name($name) {
        if(!empty($name)) {
            $data = $this->MAds->get_one('id', array('title' => $name));
            if($data) {
                $response = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => $name . '广告已存在'
                );
            } else {
                $response = FALSE;
            }
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' =>  '广告名称必填'
            );

        }
        if(is_array($response)) {
            $this->_return_json($response);
        }
    }
}

/* End of file ads.php */
/* Location: ./application/controllers/ads.php */
