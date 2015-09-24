<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 权限基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-03
 */
class Privilege extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MPrivilege'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看权限
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MPrivilege->get_one('*', array('id' => $this->input->post('id', TRUE)));

        //返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'data'   => $data,
            )
        );
    }

    /**
     * 权限列表
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('status.common.success');
        if(isset($_POST['status']) && -1 != $_POST['status']) {
            $where['status'] = $_POST['status'];
        }
        if(!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if(!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if(!empty($_POST['searchValue'])) {
            $where['like'] = array('name' => $_POST['searchValue']); 
        }
        $list = $this->MPrivilege->get_lists('*', $where, array('path' => 'asc'), array(), $page['offset'], $page['page_size']);
        $list = $this->_format_list($list);
        $total = $this->MPrivilege->count($where);
        $arr = array(
            'status'     => C('status.req.success'),
            'list'       => $list,
            'total'      => $total,
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加权限页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => C('status.common.success'));
        $order = array('path' => 'asc');
        $list = $this->MPrivilege->get_lists('*', $where, $order, array());
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
     * 添加权限
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('name', '权限名称', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        // 权限添加，入库
        if($insert_id = $this->MPrivilege->create($data)) {
            // 更新新添加权限path和level
            if(!empty($data['parent_id'])) {
                $parent = $this->MPrivilege->get_one('*', array('id' => $data['parent_id']));
                $path = $parent['path'] . $insert_id . '.';
                $level = $parent['level'] + 1;
            } else {
                $path = '.' . $insert_id . '.';
                $level = 0;
            }
            $this->MPrivilege->update_by('id', $insert_id, array('path' => $path, 'level' => $level));
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '权限添加成功',
                )
            );
        } else {
            // 权限添加入库失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '权限添加失败'
                )
            );
        }
    }


    /**
     * 修改权限页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MPrivilege->get_lists('*', $where, $order, array());
        $list = $this->_format_list($list);
        $data = $this->MPrivilege->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $filter_list = array();
        // 过滤掉当前权限及其下级权限,不能设置某个权限的上级权限为其自身或其下级权限
        foreach ($list as $v) {
            if(strpos($v['path'], $data['path']) === FALSE) {
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
     * 修改权限
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', '权限名称', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data(); 
        $id = $this->input->post('id', TRUE);
        // 根据上级部门设置当前部门的path和level
        if (!empty($data['parent_id'])) {
            $parent = $this->MPrivilege->get_one('*', array('id' => $data['parent_id']));
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
        $result = $this->MPrivilege->update_by('id', $id, $data);
        // 更新子部门的path和level
        $this->MPrivilege->update_children($id);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除权限
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
        $del_ids = $this->MPrivilege->get_children($del_id);
        $del_ids[] = $del_id;
        $where = array('in' => array('id' => $del_ids));
        // 假删除数据
        $result = $this->MPrivilege->false_delete($where);

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
        $data['module'] = $this->input->post('module', TRUE);
        $data['resource'] = $this->input->post('resource', TRUE);
        $data['operation'] = $this->input->post('operation', TRUE);
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

/* End of file privilege.php */
/* Location: :./application/controllers/privilege.php */
