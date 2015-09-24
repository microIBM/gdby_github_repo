<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * description
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Property extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProperty',
                'MOptions',
                'MCategory'
            )
        );
        $this->load->library(array('Cate_logic'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 规格属性列表
     */
    public function lists() {
        $page = $this->get_page();
        $total = $this->MProperty->count();
        $data = $this->MProperty->get_lists(
            '*',
            array(),
            array('updated_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $category_ids = array_values(array_unique(array_column($data, 'category_id')));
            $categories = $this->cate_logic->get_by_ids($category_ids);
            foreach($data as &$v) {
                foreach($categories as $cate_val) {
                    $cate_name = '';
                    if($v['category_id'] === $cate_val['id']) {
                        $ids = explode(".", trim($cate_val['path'], '.'));
                        array_pop($ids);
                        if($ids) {
                            $path_info = $this->cate_logic->get_by_ids($ids);
                            $cate_name = implode('-->', array_column($path_info, 'name'));
                            $cate_name .= '-->';
                        }
                        $v['cate_name'] = $cate_name . $cate_val['name'];
                    }
                }
                $v['updated_time']  = date('Y-m-d H:i:s', $v['updated_time']);
            }
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list'   => $data,
                'total'  => $total
            )
        );
    }
   /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建规格
     */
    public function create() {
        $req_time = $this->input->server('REQUEST_TIME');
        $property_opt = array();
        if(isset($_POST['options'])) {
        $property_opt = $_POST['options'];
        unset($_POST['options']);
        }
        $saveData = $_POST;
        $saveData['created_time'] = $req_time;
        $saveData['updated_time'] = $req_time; 

        if(!$_POST['id']) {
            unset($_POST['id']);
            $property_id = $this->MProperty->create($saveData);
        } else {
            // update
            $where = array('id' => $saveData['id']);
            unset($saveData['id']);
            //$this->MProperty->update_info($saveData, $where);
            $property_id = $where['id'];
            $this->MOptions->delete_by(array('property_id'  => $where['id']));
        }
        if($property_opt) {
            foreach($property_opt as $v) {
                $saveOpt = array(
                    'property_id'   => $property_id,
                    'name'          => $v,
                    'created_time'  => $req_time,
                    'updated_time'  => $req_time
                );
                $this->MOptions->create($saveOpt);
            }
        }
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success'),
                'msg'       => C('tips.msg.add_success')
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取分类规格属性
     */
    public function get_cate_properties() {
        $where['status'] = intval(C('status.common.success'));
        $properties = array();
        if(is_array($_POST['id'])) {
            foreach($_POST['id'] as $v) {
                $where['in'] = array('category_id' => $v);
                $property = $this->MProperty->get_lists("*", $where);
                if($property) {
                    $properties = $property;
                }
            }
        } else {
            $where['category_id'] = $_POST['id'];

            $properties = $this->MProperty->get_lists(
                "*",
                $where
            );

        }
        if($properties) {
            $this->_return_json(
                array(
                    'status'    => C('tips.code.op_success'),
                    'list'      => $properties,
                    'where' => $where
                )
            );
        } else {

            $this->_return_json(
                array(
                    'status'    => C('tips.code.op_failed'),
                    'msg'       => C('tips.msg.op_fail')
                )
            );
        }

    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置状态
     */
    public function set_status() {
        $req_time = $this->input->server('REQUEST_TIME');
        $this->MProperty->update_info(
            array(
                'status'        => $_POST['status'],
                'updated_time'  => $req_time
            ),
            array(
                'id'    => $_POST['id']
            )
        );
        // 更改options
        $this->MOptions->update_info(
            array(
                'status'        => $_POST['status'],
                'updated_time'  => $req_time
            ),
            array(
                'property_id'    => $_POST['id']
            )
        );
        $tips = array(
            'status'    => C('tips.code.op_success'),
            'msg'       => C('tips.msg.reuse_success')
        );
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 继承覆盖
     */
    public function get_last_properties() {
        $cate = $this->cate_logic->lists();
        $this->_return_json(array('list' => $cate));
    }
}

/* End of file property.php */
/* Location: ./application/controllers/property.php */
