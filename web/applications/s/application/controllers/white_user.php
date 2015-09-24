<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 白名单控制服务
 * @author wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-17
 */
class White_user extends MY_Controller {
    const ADMIN_ROLE = 1; //超级管理员
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('MWhite_user', 'MWhite_module', 'MWhite_user_module', 'MUser'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 添加白名单用户
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function create()
    {
        // 表单校验
        $this->form_validation->set_rules('name', '用户姓名', 'trim|required');
        $this->form_validation->set_rules('mobile', '用户手机号', 'trim|required|exact_length[11]|numeric');
        $this->validate_form();

        // 数据处理
        $data                                      = $this->_format_data();
        $data['white_user']['created_time']        = $this->input->server("REQUEST_TIME");
        $data['white_user_module']['created_time'] = $this->input->server("REQUEST_TIME");

        if ($user_id = $this->MUser->get_one('id', array('mobile' => $data['white_user']['mobile'], 'status' => 1))) {
            $data['white_user']['user_id']         = isset($user_id['id']) ? $user_id['id'] : 0;
            $data['white_user_module']['user_id']  = isset($user_id['id']) ? $user_id['id'] : 0;
        } elseif (empty($user_id)) {
            // 此手机号还未注册
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '此手机号还未注册'
                )
            );
        }
        $admin_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('user_id' => $data['white_user_module']['manager_id']));
        $user_info = $this->MWhite_user->get_one('id,mobile', array('mobile' => $data['white_user']['mobile']));

        if ($user_info != false || $insert_id = $this->MWhite_user->create($data['white_user'])) {
            // 白名单模块添加成功
            $data['white_user_modules'] = array();
            if (is_array($data['white_user_module']['module_ids'])) {
                foreach ($data['white_user_module']['module_ids'] as $key => $value) {
                    if (! empty($admin_info) && $admin_info['user_role'] == self::ADMIN_ROLE) {
                        //超级管理员操作
                        if ($user_module_info = $this->MWhite_user_module->get_one(array('id', 'module_id'), array('module_id' => $value, 'user_id' => $data['white_user_module']['user_id']))) {
                            $this->MWhite_user_module->update($user_module_info['id'], array('status' => 1));
                            continue;
                        }
                    } else {
                        if ($user_module_info = $this->MWhite_user_module->get_one(array('id', 'module_id'), array('module_id' => $value, 'user_id' => $data['white_user_module']['user_id'],'manager_id' => $data['white_user_module']['manager_id']))) {
                            $this->MWhite_user_module->update($user_module_info['id'], array('status' => 1));
                            continue;
                        }
                    }
                    $data['white_user_modules'][$key]['module_id']    = $value;
                    $data['white_user_modules'][$key]['manager_id']   = $data['white_user_module']['manager_id'];
                    $data['white_user_modules'][$key]['user_id']      = $data['white_user_module']['user_id'];
                    $data['white_user_modules'][$key]['created_time'] = $data['white_user_module']['created_time'];
                    $data['white_user_modules'][$key]['updated_time'] = $data['white_user_module']['updated_time'];
                }
            }
            if (! empty($data['white_user_modules']) && ! $this->MWhite_user_module->create_batch($data['white_user_modules'])) {
                $this->_return_json(
                    array(
                        'status' => C('status.req.failed'),
                        'msg'    => '白名单添加失败'
                    )
                );
            }
        } 

        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '白名单添加成功',
            )
        );
    }

    /**
     * 编辑白名单用户
     * @author wangzejun@dachuwang.com
     * @since 2015-07-23
     */
    public function edit()
    {
        // 表单校验
        $this->form_validation->set_rules('name', '用户姓名', 'trim|required');
        $this->form_validation->set_rules('mobile', '用户手机号', 'trim|required|exact_length[11]|numeric');
        $this->form_validation->set_rules('user_id', '用户id', 'trim|required|numeric');
        $this->validate_form();

        // 数据处理
        $data                                      = $this->_format_data();
        $data['white_user']['created_time']        = $this->input->server("REQUEST_TIME");
        $data['white_user_module']['created_time'] = $this->input->server("REQUEST_TIME");
        $data['white_user_module']['user_id']      = $this->input->post('user_id', TRUE);
        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('user_id' => $data['white_user_module']['manager_id']));
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            $this->MWhite_user_module->update_by('user_id', $data['white_user_module']['user_id'], array('status' => 0));
            $user_role = $this->input->post('user_role', TRUE);
            if ($user_role) {
                $this->MWhite_user->update_by('mobile', $data['white_user']['mobile'], array('user_role' => $user_role));
            }
        } else {
            $this->MWhite_user_module->update_info(array('status' => 0), array('user_id' => $data['white_user_module']['user_id'], 'manager_id' => $data['white_user_module']['manager_id']));
        }
        $data['white_user_modules'] = array();
        if (is_array($data['white_user_module']['module_ids'])) {
            foreach ($data['white_user_module']['module_ids'] as $key => $value) {
                if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
                    //超级管理员操作
                    if ($user_module_info = $this->MWhite_user_module->get_one(array('id', 'module_id'), array('module_id' => $value, 'user_id' => $data['white_user_module']['user_id']))) {
                        $this->MWhite_user_module->update($user_module_info['id'], array('status' => 1));
                        continue;
                    }
                } else {
                    if ($user_module_info = $this->MWhite_user_module->get_one(array('id', 'module_id'), array('module_id' => $value, 'user_id' => $data['white_user_module']['user_id'],'manager_id' => $data['white_user_module']['manager_id']))) {
                        $this->MWhite_user_module->update($user_module_info['id'], array('status' => 1));
                        continue;
                    }
                }
                $data['white_user_modules'][$key]['module_id']    = $value;
                $data['white_user_modules'][$key]['manager_id']   = $data['white_user_module']['manager_id'];
                $data['white_user_modules'][$key]['user_id']      = $data['white_user_module']['user_id'];
                $data['white_user_modules'][$key]['created_time'] = $data['white_user_module']['created_time'];
                $data['white_user_modules'][$key]['updated_time'] = $data['white_user_module']['updated_time'];
            }
        }
        if (! empty($data['white_user_modules']) && ! $this->MWhite_user_module->create_batch($data['white_user_modules'])) {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '白名单编辑失败'
                )
            );
        }

        // 白名单编辑成功
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '白名单编辑成功',
            )
        );
    }

    /**
     * 白名单列表
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function lists() {
        // 参数解析&数据查询
        $page        = $this->get_page();
        $manager_id  = $this->input->post('manager_id', TRUE);  //当前用户的ID
        $user_where  = $module_where = array();
        $where               = array();
        $where['manager_id'] = $manager_id;
        $mobile     = $this->input->post('mobile', TRUE);
        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('mobile' => $mobile));
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            unset($where['manager_id']);
        } 
        //搜索
        if ($search_module_id = $this->input->post('searchModule')) {
            $user_module = $this->MWhite_user_module->get_one(array('id', 'module_id'), array('module_id' => $search_module_id));
            if (isset($user_module['module_id'])) {
                $where['module_id']  = $user_module['module_id'];
            }
        }
        
        if (($search_key = $this->input->post('searchKey', TRUE)) && ($search_value = $this->input->post('searchValue', TRUE))) {
            if ($search_key == 1) {
                $user_where['like'] = array('name' => $search_value);
            }else if ($search_key == 2) {
                $user_where['mobile'] = $search_value;
            }

            $user_list   = $this->MWhite_user->get_lists(array('id', 'mobile', 'name', 'user_id'), $user_where);
            $user_id = !empty($user_list) ? array_column($user_list, 'user_id') : array(0);
            $where['in'] = array('user_id' => $user_id);
        }
        $user_module_list = $this->MWhite_user_module->get_lists(array('id', 'module_id', 'user_id', 'manager_id', 'status'), $where, array('id' => 'DESC'), array(), $page['offset'], $page['page_size']);
        if (empty($user_module_list)) {
            $this->_return_json(array('data' => array(), 'total' => 0));
        }
        $module_list      = $this->MWhite_module->get_lists(array('id', 'module_name', 'controller', 'action'), $module_where);
        $user_list        = isset($user_list) ? $user_list : $this->MWhite_user->get_lists(array('id', 'mobile', 'name', 'user_id'), $user_where);
        $data             = $this->format_list($user_module_list, $module_list, $user_list);
        $total            = $this->MWhite_user_module->count($where);
        // 返回结果
        $this->_return_json(
            array(
                'data'  => $data,
                'total' => $total
            )
        );
    }

    /**
     * 白名单编辑时获取信息
     * @author wangzejun@dachuwang.com
     * @since 2015-07-24
     */
    public function get_white_user_info()
    {
        $user_id     = $this->input->post('user_id', TRUE);  //当前用户的ID
        $manager_id  = $this->input->post('manager_id', TRUE);  //当前管理员的ID

        $user_list   = $this->MWhite_user->get_one(array('user_id', 'name', 'mobile', 'user_role'), array('user_id' => $user_id));
        if (empty($user_list)) {
            $this->_return_json(array('status' => 0, 'msg' => '没有权限'));
        }

        $where['status']  = 1;
        $where['user_id'] = $user_id;
        $user_module_list = $this->MWhite_user_module->get_lists(array('id', 'module_id', 'user_id', 'manager_id', 'status'), $where, array(), array('module_id'));
        if (empty($user_module_list)) {
            $this->_return_json(array('status' => 0, 'msg' => '没有权限'));
        }

        $module_ids = array();
        foreach ($user_module_list as $key => $value) {
            $module_ids[$key] = $value['module_id'];
        }
        $user_list['module_ids'] = $module_ids;
        $user_role   = 0;  //默认值
        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('user_id' => $manager_id));
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            $user_role = $user_list['user_role'];
        } 
        $this->_return_json(array('status' => 1, 'user_info' => $user_list, 'user_role' => $user_role));
    }

    /**
     * 白名单访问模块验证接口
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function check_white_user() {

        $module_id   = $this->input->post('module_id', TRUE);  //白名单模块ID
        $mobile      = $this->input->post('mobile', TRUE);     //白名单用户手机

        $user_info   = $this->MWhite_user->get_one(array('id', 'mobile', 'name', 'user_id'), array('mobile' => $mobile));

        if (!empty($user_info) && isset($user_info['user_id'])) {
            $where['status']      = 1;
            $where['user_id']     = $user_info['user_id'];
            $where['module_id']   = $module_id;
            $module_list = $this->MWhite_user_module->get_lists(array('id', 'module_id', 'user_id'), $where);

            if (!empty($module_list)) {
                $this->_return_json(
                    array(
                        'status' => C('status.req.success'),
                        'msg'    => '用户拥有白名单权限'
                    )
                );
            }
        }

        $this->_return_json(
            array(
                'status' => C('status.req.failed'),
                'msg'    => '用户没有白名单权限'
            )
        );
    }
    
    /**
     * 获取白名单实用信息。包括模块创建人，用户数量，模块名称
     * @author wangzejun@dachuwang.com
     * @since 2015-08-27
     */
    public function get_white_info() {
        $mobile      = $this->input->post('mobile', TRUE);
        $white_info  = array();
        $user_info   = $this->MWhite_user->get_one(array('id', 'user_id', 'user_role'), array('mobile' => $mobile));
        if (! empty($user_info) && $user_info['user_role'] == self::ADMIN_ROLE) {
            //超级管理员操作
            $white_user_module = $this->MWhite_user_module->get_lists(array('manager_id', 'module_id', 'count(`user_id`) as user_num'), array('status !=' => 0), array(), array('module_id'));
            if (!empty($white_user_module)) {
                $white_user        = $this->MWhite_user->get_lists(array('user_id', 'name'), array('in' => array('user_id' => array_column($white_user_module, 'manager_id'))));
                $white_module      = $this->MWhite_module->get_lists(array('id', 'module_name'), array('in' => array('id' => array_column($white_user_module, 'module_id'))));
                $white_user = array_column($white_user, 'name', 'user_id');
                $white_module = array_column($white_module, 'module_name', 'id');
                foreach($white_user_module as $key => $value) {
                    $white_info[$key]['module_id']   = $value['module_id'];
                    $white_info[$key]['user_num']    = $value['user_num'];
                    $white_info[$key]['name']        = $white_user[$value['manager_id']];
                    $white_info[$key]['module_name'] = $white_module[$value['module_id']];
                }
            }
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'data'   => $white_info
                )
            );
        }

        $this->_return_json(
            array(
                'status' => C('status.req.failed'),
                'msg'    => '只对超级管理员开放'
            )
        );
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    private function _format_data() {
        $data = array();
        $data['white_user']['name']                 = $this->input->post('name', TRUE);
        $data['white_user']['mobile']               = $this->input->post('mobile', TRUE);
        $data['white_user']['updated_time']         = $this->input->server("REQUEST_TIME");

        $data['white_user_module']['manager_id']    = $this->input->post('manager_id', TRUE);
        $data['white_user_module']['module_ids']    = $this->input->post('module_id', TRUE);
        $data['white_user_module']['updated_time']  = $this->input->server("REQUEST_TIME");

        $data['white_user']                         = array_filter($data['white_user']);
        $data['white_user_module']                  = array_filter($data['white_user_module']);
        return $data;
    }

    /**
     * 处理数据
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    private function format_list($user_module_list, $module_list, $user_list)
    {
        if (empty($user_module_list)) {
            return $module_list;
        }
        $data = array();
        foreach ($user_module_list as $key => $value) {
            foreach ($user_list as $k => $v) {
                if ($value['user_id'] == $v['user_id']) {
                    $value['mobile'] = $v['mobile'];
                    $value['name']   = $v['name'];
                    foreach ($module_list as $kk => $val) {
                        if ($value['module_id'] == $val['id']) {
                            $value['module_name'] = $val['module_name'];
                            $value['controller']  = $val['controller'];
                            $value['action']      = $val['action'];
                            $data[] = $value;
                        }
                    }
                }
            }
        }
        return $data;
    }

}


/* End of file white_user.php */
/* Location: :./applications/s/application/controllers/white_user.php */