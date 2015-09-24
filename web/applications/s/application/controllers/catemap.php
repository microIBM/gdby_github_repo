<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 分类映射
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-3-12
 */
class Catemap extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array('MCategory_map', 'MLocation', 'MProduct')
        );
        $this->load->library(array('Cate_logic'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 取单个信息
     */
    public function info() {
        $data = $this->MCategory_map->get_one(
            '*',
            array('id' => $_POST['id'])
        );
        if($data) {
            if($data['upid']) {
                $up_info = $this->MCategory_map->get_one('origin_id', array('id' => $data['upid']));
                $data['origin_upid'] = $up_info['origin_id'];
            } else {
                $data['origin_upid'] = 0;
            }
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'info' => $data
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 前端商城调用的lists
     * @since 15-8-26
     */
    public function lists() {
        $condition = $this->_fill_map_where_condition();
        extract($condition);
        $method_name = 'get_lists__Cache30';
        $catemaps = $this->MCategory_map->$method_name('*', $where, array('weight' => 'DESC','updated_time' => 'DESC'));
        $catemaps = $this->_get_final_catemaps($catemaps);
        // 查出子类
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $catemaps
            )
        );
    }

    private function _get_final_catemaps($catemaps) {
        foreach($catemaps as $key =>&$catemap) {
            if(empty($catemap['product_nums'])) {
                unset($catemaps[$key]);
            }
        }
        unset($catemap);

        return $catemaps;
    }

    public function set_product_nums() {
        $product = $_POST['product'];
        // 分类映射的查询条件
        $catemap_where_condition = [
            'location_id' => $product['location_id'],
            'customer_type' => $product['customer_type']
        ];
        $category_condition = array('id' => $product['category_id']);
        // 分类映射
        $catemaps = $this->MCategory_map->get_lists('id, origin_id', $catemap_where_condition);
        // 分类真实信息
        $category_info = $this->MCategory->get_one('path, id', $category_condition);
        $product['path'] = $category_info['path'];
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '更新数量失败'
        );
        if($is_update = $this->_set_catemap_product_nums($product, $catemaps)) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '更新数量成功'
            );
        }
        $this->_return_json($response);
    }

    private function _set_catemap_product_nums($product, $catemaps) {
        switch($product['status']) {
        case 1:
            $incr_nums = 1;
            break;
        case 2:
            $incr_nums = 0;
            break;
        case 0:
            $incr_nums = -1;
        }
        foreach($catemaps as &$map) {
            if(!is_bool(strpos($product['path'], ".{$map['origin_id']}."))) {
                $map['product_nums'] += $product['incr_nums'];
            }
        }
        unset($map);
        return $this->db->update_batch('t_catemap', $catemaps, 'id');
    }

    private function _fill_map_where_condition() {
        $where = array(
            'site_id' => $_POST['site_id']
        );
        $where['customer_type'] = empty($_POST['customer_type']) ? C('customer.type.normal.value') : intval($_POST['customer_type']);
        // 判断所属城市
        if(!isset($_POST['location_id'])) {
            // 默认北京
            $_POST['location_id'] = C('open_cities.beijing.id');
        }
        $where['location_id'] = $_POST['location_id'];
        // 状态筛选
        if(!empty($_POST['status'])) {
            $where['status'] = $_POST['status'];
        }
        return array('where' => $where);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取所有
     */
    public function get_all() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $all_catemaps = $this->MCategory_map->get_lists('*', $where);
        if($all_catemaps) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'list' => $all_catemaps
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
     * @description 后台获取数据接口
     */
    public function backend_list() {
        $page = $this->get_page();
        extract($page);
        $where = array();
        if(!empty($_POST['site_id'])) {
            $where = array(
                'site_id' => $_POST['site_id']
            );
        }
        // 判断所属城市
        if(!isset($_POST['location_id'])) {
            $default = $this->MLocation->get_one('id, name', array('upid' => 0), 'id ASC');
            // 默认北京
            $_POST['location_id'] = $default['id'];
        } else {
            $default = $this->MLocation->get_one('id, name', array('id' => $_POST['location_id']));
        }
        $where['location_id'] = $_POST['location_id'];
        // 状态筛选
        $where['status'] = isset($_POST['status']) ? $_POST['status'] : 1;
        $method_name = 'get_lists__Cache30';
        if(isset($_POST['no_cache'])) {
            $method_name = rtrim($method_name, '__Cache30');
        }

        $where['customer_type'] = empty($_POST['customer_type']) ? C('customer.type.normal.value') : intval($_POST['customer_type']);
        $total_catemaps = $this->MCategory_map->count($where);
        $lists = $this->MCategory_map->$method_name('*', $where,
            array('weight' => 'DESC','updated_time' => 'DESC'),
            array(),
            $page_size * ($page -1),
            $page_size
        );
        $this->_format_catemap_list_data($lists);
        // 查出子类
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $lists,
                'total' => $total_catemaps
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化catemap_list 的数据
     */

    private function _format_catemap_list_data(&$lists) {
        $customer_type = array_values(C('customer.type'));
        $customer_type_values = array_column($customer_type, 'value');
        $customer_type_combine = array_combine($customer_type_values, $customer_type);
        foreach($lists as &$v) {
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $customer_type_cn = isset($customer_type_combine[$v['customer_type']]) ? $customer_type_combine[$v['customer_type']]['name'] : C('customer.type.normal.name');

            $v['customer_type_cn'] = $customer_type_cn;
        }
        unset($v);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建分类映射
     */
    public function create() {

        $this->_check_map_name(
            array(
                'name' => $_POST['name'],
                'location_id' => $_POST['location_id'],
            )
        );
        $up_info = array();
        if(!empty($_POST['initUpid'])) {
            $up_info = $this->_check_upid();
        }

        $data = $this->_format_catemap_post_data();
        $create_id = $this->MCategory_map->create($data);
        if($create_id) {
            if($up_info) {
                $path = $up_info['path'] . $create_id . '.';
                $upid = $up_info['id'];
            } else {
                $path = ".$create_id.";
                $upid = 0;
            }
            // 更新
            $up_data = array(
                'path' => $path,
                'upid' => $upid
            );
            $this->MCategory_map->update_info(
                $up_data,
                array('id' => $create_id)
            );
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'id' => $create_id,
                'msg' => '添加成功'
            )
        );
    }
    public function child($id) {
        $childs = $this->MCategory_map->get_lists(
            '*',
            array(
                'upid' => $id
            ),
            array('weight' => 'DESC', 'updated_time' => 'DESC')
        );
        return $childs;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化分类映射数据
     */
    private function _format_catemap_post_data() {
        $req_time = $this->input->server('REQUEST_TIME');
        $data = array(
            'name'          => $_POST['name'],
            'upid'          => $_POST['initUpid'],
            'site_id'       => $_POST['siteId'],
            'location_id'   => $_POST['location_id'],
            'customer_type' => empty($_POST['customerType']) ? C('customer.type.normal.value') : intval($_POST['customerType']) ,
            'origin_id'     => $_POST['initId'],
            'weight'        => $_POST['weight'],
            'origin_name'   => $_POST['initName'],
            'updated_time'  => $req_time,
            'status'        => C('status.common.success')
        );
        return $data;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 保存信息
     */
    public function save() {
        $up_info = array();
        if(!empty($_POST['initUpid'])) {
            $up_info = $this->_check_upid();
        }
        $this->_check_map_name(
            array(
                'name' => $_POST['name'],
                'location_id' => $_POST['location_id']
            ),
            $_POST['id']
        );
        $data = $this->_format_catemap_post_data();
        $this->MCategory_map->update_info($data, array('id' => $_POST['id']));
        if($up_info) {
            $path = $up_info['path'] . $_POST['id'] . '.';
            $upid = $up_info['id'];
        } else {
            $path = "." . $_POST['id'] . ".";
            $upid = 0;
        }
        // 更新
        $up_data = array(
            'path' => $path,
            'upid' => $upid
        );
        $this->MCategory_map->update_info(
            $up_data,
            array('id' => $_POST['id'])
        );
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'id' => $_POST['id'],
                'msg' => '保存成功'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测下上级id的信息
     */
    private function _check_upid() {
        $customer_type = empty($_POST['customerType']) ? C('customer.type.normal.value') : intval($_POST['customerType']);
        $up_info = $this->MCategory_map->get_one(
            'path,id',
            array(
                'origin_id' => $_POST['initUpid'],
                'customer_type' => $customer_type,
                'location_id' => $_POST['location_id'],
                'site_id' => empty($_POST['siteId']) ? 1 : $_POST['siteId']
            )
        );
        if(!$up_info) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '一级分类映射尚不存在'
                )
            );
        }
        return $up_info;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测下map名称的重复
     */
    private function _check_map_name($where, $id = array()) {
        $customer_type = empty($_POST['customerType']) ? C('customer.type.normal.value') : intval($_POST['customerType']);
        $where['customer_type'] = $customer_type;
        if(empty($id)) {
            $info = $this->MCategory_map->get_one('id', $where);
        } else {
            $info = $this->MCategory_map->get_one(
                'id',
                array_merge(
                    array('not_in' => array('id' => $id)), $where
                )
            );
        }
        extract($where);
        $name = trim($name);
        return FALSE;
        if($info || !$name) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '映射名称已存在'
                )
            );
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 根据名称搜素
     */
    public function search() {
        $data = $this->MCategory_map->get_lists('*', $_POST['where']);
        $this->_format_catemap_list_data($data);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 设置禁用/启用 状态
     */
    public function set_status() {
        $res = $this->MCategory_map->update_info(
            array(
                'status'    => $_POST['status']
            ),
            $_POST['where']
        );
        $info = array(
            'status' => C('tips.code.op_success'),
            'msg' => C('tips.msg.op_success')
        );
        $this->_return_json($info);
    }
}

/* End of file catemap.php */
/* Location: ./application/controllers/catemap.php */
