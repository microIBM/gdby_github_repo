<?php if (! defined('BASEPATH')) exit('No direct script acess allowed');
/**
 * 白名单控制服务
 * @author wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-17
 */
class White_module extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 添加白名单模块
     * @author wangzejun@dachuwang.com
     * @since 2015-07-18
     */
    public function create()
    {
        // $this->check_validation('white_module', 'create', 'bi', FALSE);

        $data                 = $this->data;
        $city_id              = $this->input->get_post('city_id');
        $data['menue_id']     = $this->input->get_post('menue_id');
        $data['city_id']      = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京

        $post = array();
        $post['module_name']  = $this->input->post('module', TRUE);
        $post['controller']   = $this->input->post('controller', TRUE);
        $post['action']       = $this->input->post('action', TRUE);
        $post['user_id']      = $data['user_info']['id'];
        $post['manager_id']   = $data['user_info']['id'];
        $post['mobile']       = $data['user_info']['mobile'];
        $post['name']         = $data['user_info']['name'];
        // 调用基础服务接口
        $return = $this->format_query('white_module/create', $post);
        $this->_return_json($return);
    }

    /**
     * 删除白名单模块
     * @author wangzejun@dachuwang.com
     * @since 2015-07-18
     */
    public function set_status()
    {
        $data                 = $this->data;
        $city_id              = $this->input->get('city_id');
        $data['tab_id']       = $this->input->get('tab_id');
        $data['menue_id']     = $this->input->get('menue_id');
        $data['city_id']      = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京

        $post = array();
        $post['module_id']    = $this->input->get('module_id');
        $post['manager_id']   = $data['user_info']['id'];
        $post['mobile']       = $data['user_info']['mobile'];
        // 调用基础服务接口
        $return = $this->format_query('white_module/set_status', $post);
        header("Location:{$data['base_url']}/white_user/index?city_id={$data['city_id']}&menue_id={$data['menue_id']}&tab_id={$data['tab_id']}");
        exit;
    }
}


/* End of file white_user.php */
/* Location: :./applications/bi/application/controllers/white_user.php */