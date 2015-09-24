<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 角色基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-03
 */
class Role extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MRole', 'MLocation', 'MPrivilege'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看角色
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MRole->get_one('*', array('id' => $this->input->post('id', TRUE)));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 角色列表
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('status.common.success');
        if(isset($_POST['status']) && 'all' != $_POST['status']) {
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
        $list = $this->MRole->get_lists('*', $where, array(), array(), $page['offset'], $page['page_size']);
        $total = $this->MRole->count($where);

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
     * 添加角色页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MPrivilege->get_lists('*', $where, $order, array());
        foreach ($list as &$v) {
            $v['pre_name'] = str_repeat('--------', $v['level']) . $v['name'];
            $v['checked'] = FALSE;
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'list'   => $list,
            )
        );
    }

    /**
     * 添加角色
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('name', '角色名称', 'trim|required');
        $this->form_validation->set_rules('dataset', '数据集权限', 'required');
        $this->form_validation->set_rules('pri_id', '操作权限', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        if ($insert_id = $this->MRole->create($data)) {
            // 角色添加成功
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '角色添加成功',
                )
            );
        } else {
            // 角色添加失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '角色添加失败'
                )
            );
        }
    }

    /**
     * 修改角色页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MRole->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MPrivilege->get_lists('*', $where, $order, array());
        foreach ($list as &$v) {
            $v['pre_name'] = str_repeat('--------', $v['level']) . $v['name'];
            if(strpos(',' . $data['pri_id'] . ',', ',' . $v['id'] . ',') !== FALSE) {
                $v['checked'] = TRUE;
            } else {
                $v['checked'] = FALSE;
            }
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
                'list'   => $list,
            )
        );
    }

    /**
     * 修改角色
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', '角色名称', 'required');
        $this->form_validation->set_rules('dataset', '数据集权限', 'required');
        $this->form_validation->set_rules('pri_id', '操作权限', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $id = $this->input->post('id', TRUE);
        // 角色修改，入库
        $result = $this->MRole->update_by('id', $id, $data);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除角色
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MRole->false_delete($where);

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
        $data['dataset'] = $this->input->post('dataset', TRUE);
        $data['pri_id'] = $this->input->post('pri_id', TRUE);
        $data['pri_id'] = implode(',', $data['pri_id']);
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
        $pri_list = $this->MPrivilege->get_lists('*');
        foreach ($list as $k => $v) {
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            // 通过权限id列表获取到权限名称列表
            $pri_ids = explode(',', $v['pri_id']);
            $pri_names = array();
            foreach ($pri_ids as $pri_id) {
                foreach ($pri_list as $pri) {
                    if($pri['id'] == $pri_id){
                        $pri_names[] = $pri['name'];
                        break;
                    }
                }
            }
            $v['pri_names'] = implode(',', $pri_names);
            $result[] = $v;
        }

        return $result;
    }
}

/* End of file role.php */
/* Location: :./application/controllers/role.php */
