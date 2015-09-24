<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 促销活动
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-4-25
 */
class Promotion extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MCategory'));
        $this->load->library(array('order_split'));
    }

    public function save() {
        if(empty($this->post['startTime']) || empty($this->post['endTime'])) {
            $data = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '活动时间范围没有填写'
            );
            $this->_return_json($data);
        }
        $this->post['start_time'] = intval($this->post['startTime']);
        $this->post['end_time'] = intval($this->post['endTime']) + 86399; //结束时间为最后一秒
        $this->post['latest_deliver_time'] = intval($this->post['latestDeliverTime']) + 86399; //结束时间为最后一秒
        // 检查分类是否合法
        $category_names = $this->post['categoryNames'];
        $category_names = !empty($category_names) ? explode("|", trim($category_names)) : array();
        if(!empty($category_names)) {
            $categories = $this->MCategory->get_lists('id, name', array(
                'in' => array(
                    'name' => $category_names
                ),
                'status' => C("status.common.success")
            ));
            $category_ids = !empty($categories) ? implode(",", array_column($categories, "id")) : "";
            $category_real_names = !empty($categories) ? implode(",", array_column($categories, "name")) : "";
            $category_real_names .= ",";
            foreach($category_names as $name) {
                if(strpos($category_real_names, $name . ",") === FALSE) {
                    $data = array(
                        'status' => C('tips.code.op_failed'),
                        'msg' => $name . '分类不存在，请检查是否拼写有误。'
                    );
                    $this->_return_json($data);
                }
            }
        } else {
            $category_ids = "";
        }
        $this->post['category_ids'] = $category_ids;

        $category_limit_num = 0;
        if(!empty($category_ids)) {
            $category_limit_num = intval($this->post['categoryLimitNum']) > 0 ? intval($this->post['categoryLimitNum']) : 0;
        }
        $this->post['category_limit_num'] = $category_limit_num;
        $this->post['rule_type'] = intval($this->post['ruleType']) > 0 ? intval($this->post['ruleType']) : 0;
        $this->post['group_id'] = !empty($this->post['groupId']) ? $this->post['groupId'] : 1;
        $this->post['group_name'] = !empty($this->post['groupName']) ? $this->post['groupName'] : "";
        $this->post['limit_first'] = intval($this->post['isFirst']) > 0 ? 1 : 0;
        $this->post['limit_new_customer'] = intval($this->post['isNewCustomer']) > 0 ? 1 : 0;
        $data = $this->format_query('/promotion/create', $this->post);
        $this->_return_json($data);
    }

    public function input_options() {
        // 城市列表
        $locations = $this->format_query('/location/get_child');
        $data['locations'] = $locations['list'];
        $where = array(
            'where' => array(
                'upid >' => 0
            )
        );
        $data['types'] = C("promotion.items");
        $order_groups = $this->format_query('/promotion/get_groups');
        $data['order_groups'] = $order_groups['list'];
        $data['first_options'] = array(
            array(
                'id' => 0,
                'name' => "否"
            ),
            array(
                'id' => 1,
                'name' => "是"
            )
        );
        $data['new_customer_options'] = array(
            array(
                'id' => 0,
                'name' => "否"
            ),
            array(
                'id' => 1,
                'name' => "是"
            )
        );
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
    public function lists() {
        if($this->post['status'] != 'all') {
            $this->post['where']['status'] = $this->post['status'];
        }
        if(!empty($this->post['searchVal'])) {
            $this->post['where']['like'] = array('title' => $this->post['searchVal']);
        }
        $data = $this->format_query('/promotion/lists', $this->post);
        $this->_return_json($data);
    }

    public function set_status() {
        $data = $this->format_query('/promotion/set_status', $this->post);
        $this->_return_json($data);
    }

}

/* End of file promo_event.php */
/* Location: ./application/controllers/promo_event.php */
