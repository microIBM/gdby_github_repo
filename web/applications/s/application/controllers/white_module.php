<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 白名单控制服务
 * @author wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-20
 */
class White_module extends MY_Controller {
    const ADMIN_ROLE = 1; //超级管理员

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MWhite_user', 'MWhite_module', 'MWhite_user_module', 'MUser'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 添加白名单模块信息
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function create()
    {
        // 表单校验
        $this->form_validation->set_rules('module_name', '模块名称', 'trim|required');
        $this->form_validation->set_rules('controller', '模块控制器', 'trim');
        $this->form_validation->set_rules('action', '模块方法', 'trim');
        $this->validate_form();

        // 数据处理
        $data                                      = $this->_format_data();
        $data['white_user']['created_time']        = $this->input->server("REQUEST_TIME");
        $data['white_module']['created_time']      = $this->input->server("REQUEST_TIME");
        $data['white_user_module']['created_time'] = $this->input->server("REQUEST_TIME");

        $user_info = $this->MWhite_user->get_one('id,mobile', array('mobile' => $data['white_user']['mobile']));
        if ($user_info != false || $id = $this->MWhite_user->create($data['white_user'])) {
            // 白名单模块添加成功
            $module_info = $this->MWhite_module->get_one(array('id','module_name'), array('module_name' => $data['white_module']['module_name']));
            if ($module_info != false) {
                $this->_return_json(
                    array(
                        'status' => C('status.req.failed'),
                        'msg'    => '白名单模块已存在，请更换名称'
                    )
                );
            }
            if ($module_id = $this->MWhite_module->create($data['white_module'])) {
                $data['white_user_module']['module_id'] = $module_id;
                if ($this->MWhite_user_module->create($data['white_user_module'])) {
                    $this->_return_json(
                        array(
                            'status' => C('status.req.success'),
                            'msg'    => '白名单模块添加成功',
                        )
                    );             
                }
            }
        }

        // 白名单模块添加失败
        $this->_return_json(
            array(
                'status' => C('status.req.failed'),
                'msg'    => '白名单模块添加失败'
            )
        );
    }

    /**
     * 白名单模块列表
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function lists() {
        // 参数解析&数据查询
        $where = $module_list = $user_module_list = array();
        $where['manager_id'] = $this->input->post('manager_id', TRUE);
        
        $mobile     = $this->input->post('mobile', TRUE);
        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('mobile' => $mobile));
        if (empty($user_info)) {
            $this->_return_json(array('status' => -2, 'msg' => '手机号未注册'));
        }
        
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            $module_list = $this->MWhite_module->get_lists(array('id', 'module_name', 'controller', 'action'), array(), array(), array('module_name'));
        } else {
            $user_module_list    = $this->MWhite_user_module->get_lists(array('id', 'module_id'), $where, array(), array('module_id'));
            if (! empty($user_module_list)) {
                foreach ($user_module_list as $key => $value) {
                    $where['module_id'][] = $value['module_id'];
                }

                $module_list = $this->MWhite_module->get_lists(array('id', 'module_name', 'controller', 'action'), array('in' => array('id' => $where['module_id'])), array(), array('module_name'));
            }
        }
        
        // 返回结果
        $this->_return_json($module_list);
    }

    /**
     * 删除白名单
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function set_status() {
        // 表单校验
        $this->form_validation->set_rules('module_id', '模块ID', 'required|numeric');
        $this->form_validation->set_rules('manager_id', '管理员ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $module_id  = $this->input->post('module_id', TRUE);
        $manager_id = $this->input->post('manager_id', TRUE);
        $mobile     = $this->input->post('mobile', TRUE);

        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('mobile' => $mobile));
        if (empty($user_info)) {
            $this->_return_json(array('status' => -2, 'msg' => '手机号未注册'));
        }

        $user_module_info = $this->MWhite_user_module->get_one(array('id', 'module_id', 'user_id', 'manager_id'), array('id' => $module_id));
        if (empty($user_module_info)) {
            $this->_return_json(array('status' => -2, 'msg' => '该模块不存在'));
        }
        
        $where  = array('module_id' => $user_module_info['module_id'], 'status' => 1, 'id' => $user_module_info['id']);
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            $result = $this->delete_user_module($where);
        } else {
            if (! empty($user_info) && $user_module_info['manager_id'] != $user_info['user_id']) {
                // 是否管理员在操作
                $this->_return_json(array('status' => -2, 'msg' => '没有权限'));
            }
            $result = $this->delete_user_module($where);
        }

        // 返回结果
        $this->_return_json($result);
    }
    
    /**
     * 删除模块下的白名单，删除白名单模块（该模块下没有其他用户时）
     * @author wangzejun@dachuwang.com
     * @since 2015-08-25
     */
    private function delete_user_module($data) {
        $where['status']    = $data['status'];
        $where['module_id'] = $data['module_id'];
        $this->MWhite_user_module->delete_by(array('id' => $data['id']));
        if (! $this->MWhite_user_module->count($where)) {
           $this->MWhite_module->delete_by(array('id' => $data['module_id'])); 
        }
        return true;
    }

    /**
     * 获取管理白名单用户除自己外白名单数量
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function delete_flag() {
        // 表单校验
        $this->form_validation->set_rules('manager_id', 'ID', 'required|numeric');
        $this->validate_form();
        // 数据处理
        $id     = $this->input->post('manager_id', TRUE);
        $where  = array('user_id !=' => $id, 'manager_id' => $id, 'status' => 1);
        // 假删除数据
        $result = $this->MWhite_user_module->count($where);

        // 返回结果
        $this->_return_json($result);
    }

    /**
     * 获取白名单用户拥有权限的模块
     * @author wangzejun@dachuwang.com
     * @since 2015-07-24
     */
    public function get_own_module() {
        // 表单校验
        $this->form_validation->set_rules('mobile', '手机号', 'required|numeric');
        $this->validate_form();
        // 数据处理
        $mobile           = $this->input->post('mobile', TRUE);

        $user_info        = $this->MWhite_user->get_one(array('id', 'user_id','mobile', 'user_role'), array('mobile' => $mobile));
        if (empty($user_info)) {
            $this->_return_json(array('status' => -2, 'msg' => '手机号未注册'));
        }

        $where            = array('user_id' => $user_info['user_id'], 'status' => 1);
        $user_module_info = $this->MWhite_user_module->get_lists(array('id', 'module_id'), $where);
        if (empty($user_module_info)) {
            $this->_return_json($user_module_info);
        }
        $module_ids = array();
        foreach ($user_module_info as $key => $value) {
            $module_ids[$key] = $value['module_id'];
        }

        //取出拥有白名单模块信息
        $module_info      = $this->MWhite_module->get_lists(array('id', 'module_name', 'controller', 'action'),array('in' => array('id' => $module_ids)));

        // 返回结果
        $this->_return_json($module_info);
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    private function _format_data() {
        $data = array();
        $data['white_user']['name']                 = $this->input->post('name', TRUE);
        $data['white_user']['mobile']               = $this->input->post('mobile', TRUE);
        $data['white_user']['user_id']              = $this->input->post('user_id', TRUE);
        $data['white_user']['updated_time']         = $this->input->server("REQUEST_TIME");

        $data['white_user_module']['user_id']       = $this->input->post('user_id', TRUE);
        $data['white_user_module']['manager_id']    = $this->input->post('manager_id', TRUE);
        $data['white_user_module']['updated_time']  = $this->input->server("REQUEST_TIME");

        $data['white_module']['module_name']        = $this->input->post('module_name', TRUE);
        $data['white_module']['controller']         = $this->input->post('controller', TRUE);
        $data['white_module']['action']             = $this->input->post('action', TRUE);
        $data['white_module']['updated_time']       = $this->input->server("REQUEST_TIME");

        $data['white_user']                         = array_filter($data['white_user']);
        $data['white_module']                       = array_filter($data['white_module']);
        $data['white_user_module']                  = array_filter($data['white_user_module']);

        return $data;
    }

}


/* End of file white_module.php */
/* Location: :./applications/s/application/controllers/white_module.php */