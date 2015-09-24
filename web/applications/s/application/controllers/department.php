<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 部门基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-03
 */
class Department extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MDepartment', 'MLocation', 'MUser'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看部门
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MDepartment->get_one('*', array('id' => $this->input->post('id', TRUE)));
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 部门列表
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('status.common.success');
        if (!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if (!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if (!empty($_POST['searchValue'])) {
            $where['like'] = array('name' => $_POST['searchValue']); 
        }
        // 根据path排序，无需使用递归
        $order = array('path' => 'asc');
        $list = $this->MDepartment->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MDepartment->count($where);
        $list = $this->_format_list($list);
        $arr = array(
            'status'     => C('status.req.success'),
            'list'       => $list,
            'total'      => $total,
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加部门页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MDepartment->get_lists('*', $where, $order, array());
        $list = $this->_format_list($list);

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'list'   => $list,
            )
        );
    }

    /**
     * 添加部门
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('name', '部门名称', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        // 部门添加，入库
        if ($insert_id = $this->MDepartment->create($data)) {
            // 更新新添加部门path
            if (!empty($data['parent_id'])) {
                $parent = $this->MDepartment->get_one('*', array('id' => $data['parent_id']));
                if (!$parent) {
                    $this->_return(FALSE, '', '上级部门不存在！');
                }
                $path = $parent['path'] . $insert_id . '.';
                $level = $parent['level'] + 1;
            } else {
                $path = '.' . $insert_id . '.';
                $level = 0;
            }
            $this->MDepartment->update_by('id', $insert_id, array('path' => $path, 'level' => $level));
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '部门添加成功',
                )
            );
        } else {
            // 部门添加入库失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '部门添加失败'
                )
            );
        }
    }


    /**
     * 修改部门页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MDepartment->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MDepartment->get_lists('*', $where, $order, array());
        $list = $this->_format_list($list);
        $filter_list = array();
        // 过滤掉当前部门及其下级部门,不能设置某个部门的上级部门为其自身或其下级部门
        foreach ($list as $v) {
            if (strpos($v['path'], $data['path']) === FALSE) {
                $filter_list[] = $v;
            }
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
                'list'   => $filter_list,
            )
        );
    }

    /**
     * 修改部门
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', '部门名称', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $id = $this->input->post('id', TRUE);
        // 根据上级部门设置当前部门的path和level
        if (!empty($data['parent_id'])) {
            $parent = $this->MDepartment->get_one('*', array('id' => $data['parent_id']));
            if (!$parent) {
                $this->_return(FALSE, '', '上级部门不存在！');
            }
            $data['path'] = $parent['path'] . $id . '.';
            $data['level'] = $parent['level'] + 1;
        } else {
            $data['path'] = '.' . $id . '.';
            $data['level'] = 0;
        }
        // 部门修改，入库
        $result = $this->MDepartment->update_by('id', $id, $data);
        // 更新子部门的path和level
        $this->MDepartment->update_children($id);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除部门
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        // 获取当前部门以及所有下级部门
        $del_ids = $this->MDepartment->get_children($del_id);
        $del_ids[] = $del_id;
        $where = array('in' => array('id' => $del_ids));
        // 假删除数据
        $result = $this->MDepartment->false_delete($where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    private function _format_data() {
        $data = array();
        $data['name'] = $this->input->post('name', TRUE);
        $data['parent_id'] = $this->input->post('parentId', TRUE);
        $data['description'] = $this->input->post('description', TRUE);
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data = array_filter($data);
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    private function _format_list($list) {
        $result = array();
        foreach ($list as $k => $v) {
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $v['pre_name'] = str_repeat('----', $v['level']) . $v['name'];
            $result[] = $v;
        }

        return $result;
    }

}

/* End of file department.php */
/* Location: :./application/controllers/department.php */
