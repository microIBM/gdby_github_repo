<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: datetime
 */
class Category extends MY_Controller {
    protected $_page_size = 200;

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory'
            )
        );
        $this->load->library(array("Cate_logic", "Wms_category"));
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 获取单个分类 
     * @TODO 后续可能会需要规格等信息
     */
    public function info() {
        $cate_info = $this->MCategory->get_one(
            'weight,path,upid,name,id',
            array(
                'id' => $_POST['id']
            )
        );
        $tips = array(
            'status'    => C('tips.code.op_success'),
            'info'      => $cate_info
        );
        $this->_return_json($tips);
    }

    public function lists() {
        // backend
        if(isset($_POST['app_name'])) {
            $front = TRUE;
        } else {
            $front = FALSE;
        }
        $page = $this->get_page();
        $total = $this->MCategory->count();
       
        $data =  $this->cate_logic->lists($_POST, $front); // 缓存半小时
        extract($data);
        if(empty($top_cate) && empty($third_cate)) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg'    => C('tips.msg.cate_not_exists')
                )
            );
        }
        // 需要返回的
        $return_data = array(
            'top'   => $top_cate,
            'child' => array()
        );
        $return_data['second'] = $this->_deal_cate($top_cate, $second_cate);
        $return_data['second_child'] = $this->_deal_cate($second_cate, $third_cate);
        $return_data['third_child'] = $this->_deal_cate($third_cate, $forth_cate);
        $this->_return_json(array('status' => C('tips.code.op_success'), 'list' => $return_data,'total' => $total));
    }

    private function _deal_cate($parent_cate, $son_cate) {
        // 整合二级和三级
        $return_data = array();
        if($parent_cate && $son_cate) {
            foreach($parent_cate as $parent_val) {
                foreach($son_cate as $son_val) {
                    if ($son_val['upid'] == $parent_val['id']) {
                        $return_data[$parent_val['id']][]= $son_val;
                    }
                }
            }
        }
        return $return_data;
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 获取子分类
     */
    public function get_child_list() {
         $cate_list = $this->MCategory->get_lists(
            "id,weight,name,upid,status,updated_time", 
            $_POST['where'],
            '',
            array(),
            $this->_page_size * ($_POST['page'] - 1),
            $this->_page_size
        );
        
        if(empty($cate_list)) {
            $tips = array(
                'status' => C('tips.code.op_failed'),
                'msg'    => '此分类下已经没有其他分类了'
            );
        } else {
            foreach($cate_list as &$v) {
                $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            }
            unset($v);
            $tips = array(
                'status' => C('tips.code.op_success'),
                'list'   => $cate_list
            );
        }
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 分类的编辑以及保存
     */
    public function save() {
        $cate_info = $this->MCategory->get_one('*',
            array(
                'id'    => $_POST['edit_id']
            )
        );
        // 检测是否id合法
        if(!$cate_info) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg'    => C('tips.msg.op_fail')
                )
            );
        }
        // 检测是否upid 有变化
        if(intval($cate_info['upid']) === intval($_POST['upid'])) {
            $update_data = array(
                'weight'    => $_POST['weight'],
                'name'  => $_POST['name']
            );
            $this->MCategory->update_info(
                $update_data, array('id'    => $_POST['edit_id'])
            );
            // 提示语言
            $tips = array(
                'status'    => C('tips.code.op_success'),
                'msg'   => C('tips.msg.op_success')
            );

        } else {
            // 找到上级path
            $up_cate_info = $this->MCategory->get_one('path',
                array(
                    'id'    => $_POST['upid']
                )
            );
            // 顶级分类为FALSE
            if($up_cate_info) {
                // 更新path
                $update_data = array(
                    'weight'    => $_POST['weight'],
                    'upid'  => $_POST['upid'],
                    'path'  => '.' . trim($up_cate_info['path'], '.'). '.' . $_POST['edit_id'] . '.'
                );
                // 检测path 是不是类似.12.12 或者.12.13.14.12.
                $this->_check_valid_path($update_data['path']);
            } else {
                $update_data = array(
                    'weight'    => $_POST['weight'],
                    'upid'  => $_POST['upid'],
                    'path'  => '.' . $_POST['edit_id'] . '.'
                );
            }
            // 更新本id的path
            $this->MCategory->update_info(
                $update_data, array('id'    => $_POST['edit_id'])
            );
            // 获取其子类id
            $cate_lists = $this->MCategory->get_lists('id,path',
                array(
                    'like'  => array(
                        'path'  => $cate_info['path']
                    ),
                    'id !=' => $_POST['edit_id']
                )
            );
            // 若有子类
            if($cate_lists) {
                foreach($cate_lists as  $v) {
                    // 将改变后的path替换
                    $v['path'] = str_replace($cate_info['path'], $update_data['path'], $v['path']);
                    $this->MCategory->update_info(
                        array('path' => $v['path']),
                        array('id'  => $v['id'])
                    );
                }
            }
            // 提示语言
            $tips = array(
                'status'    => C('tips.code.op_success'),
                'msg'   => C('tips.msg.op_success')
            );
        }
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测路径是否合法
     */
    private function _check_valid_path($path) {
        $data = array_count_values(explode('.', trim($path, '.')));
        foreach($data as $v) {
            if($v > 1) {
                $this->_return_json(
                    array(
                        'status' => C('tips.code.op_failed'),
                        'msg' => '此分类下禁止进行此操作'
                    )
                );
            }
        }
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 分类添加
     */
    public function create() {
        $cate_info = $this->MCategory->get_one("id,upid,path", array('name' => trim($_POST['name'])));
        if($cate_info) {
            $this->_return_json(
                array(
                    'status'    => C('tips.code.op_failed'),
                    'msg'       => '已存在'
                )
            );
        }

        $reqtime = $this->input->server('REQUEST_TIME');
        $cate = array();
        if(!empty($_POST['upid'])) {
            $cate = $this->MCategory
               ->get_one('id,path, upid', array('id'   => $_POST['upid']));
        }
        // 更新操作
        $cateinfo = array(
            'name'  => $_POST['name'],
            'weight'=> $_POST['weight']
        );
        //
        $cateinfo['created_time'] = $reqtime;
        $cateinfo['updated_time'] = $reqtime;

        $cate_id = $this->MCategory->create($cateinfo);
        // 更新path
        $upinfo = array();
        $upinfo['path'] = '';

        if(isset($cate['path']) && $cate['path']) {
            $upinfo['upid'] = $_POST['upid'];
            $upinfo['path'] = $cate['path'] . $cate_id . '.';
            $sync_parent_path = $cate['path'];
        } else {
            $upinfo['path'] = '.' . $cate_id . '.';
            $sync_parent_path = '';
        }
        // 更新
        $this->MCategory->update_info($upinfo, array('id'  => $cate_id));
        // 同步到erp
        $sync_data = array(
            'name' => $cateinfo['name'],
            'id' => $cate_id,
            'up_path' => $sync_parent_path
        );
        $sync = $this->_sync_to_erp($sync_data);
        // 记录货号同步的结果
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg'    => '保存成功',
                'sync'  => $sync
            )
        );
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 设置禁用/启用 状态
     */
    public function set_status() {
        $res = $this->MCategory->update_info(
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
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 同步数据到ERP
     */
    private function _sync_to_erp($cate_data) {
        $path = $cate_data['up_path'];
        if(empty($path)) {
            $parent_path = '';
        } else {
            $ids = explode('.', trim($path, '.'));
            $data = $this->MCategory->get_lists('name', array('in' => array('id' => $ids)));
            $parent_path = '';
            foreach($data as $v) {
                $parent_path .= $v['name'] . ',';
            }
            $parent_path = rtrim($parent_path, ',');
        }
        $sync_erp_data = array(
            'category_name' => $cate_data['name'],
            'parent_path' => $parent_path
        );
        $return_msg = $this->wms_category->create($sync_erp_data);
        if($return_msg) {
            if(intval($return_msg['error_code']) === 0) {
                $return_msg['error_code'] = C('status.common.success');
            }
            // 更新下已经同步完成
            $up_data = array(
                'error_code' => $return_msg['error_code']
            );
            $where = array(
                'id' => $cate_data['id']
            );
            $this->MCategory->update_info($up_data, $where);
        } else {
            $return_msg['error_code'] = C('tips.code.op_failed');
        }
        return $return_msg['error_code'];
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 前端映射
     */
    public function map() {
        $new_sites = $this->get_sites();
        $data = $this->cate_logic->get_map();
        $info = array(
            'status' => C('tips.code.op_success'),
            'list' => $data,
            'sites' => $new_sites
        );
        $this->_return_json($info);
    }
}

/* End of file category.php */
/* Location: ./application/controllers/category.php */
