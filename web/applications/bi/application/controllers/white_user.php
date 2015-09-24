<?php if (! defined('BASEPATH')) exit('No direct script acess allowed');
/**
 * 白名单控制服务
 * @author wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-17
 */
class White_user extends MY_Controller {
    const WHITE_USER_MODULE = 7; //white白名单模块ID
    
    public function __construct() {
        parent::__construct();

        $this->load->helper('date', 'url');
        $this->load->library('pagination');
        $this->load->helper('pagination');
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }
    
    /**
     * 白名单展示
     * @author wangzejun@dachuwang.com
     * @since 2015-07-18
     */
    public function index()
    {
        $data                   = $this->data;
        $city_id                = $this->input->get('city_id');
        $data['tab_id']         = $this->input->get('tab_id');
        $data['menue_id']       = $this->input->get('menue_id');
        $data['city_id']        = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_USER_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
        
            $offest                 = $this->input->get('offset');
            $page                   = $this->input->get('page');
            $data['searchKey']      = $this->input->get('searchKey');
            $data['searchValue']    = $this->input->get('searchValue');
            $data['searchModule']   = $this->input->get('searchModule');
            
            $post['mobile']         = $data['user_info']['mobile'];
            $post['manager_id']     = $data['user_info']['id'];
            $post['currentPage']    = $page ? $page : 1;
            $post['itemsPerPage']   = $offest ? $offest : 10;
            $data['manage_module']  = $this->format_query('white_module/lists', $post); //获取当前用户可管理的白名单模块
            $data['btn_class']      = empty($data['manage_module']) ? 'disabled' : 'active';

            //搜索
            $post['searchKey']      = $data['searchKey'];
            $post['searchValue']    = $data['searchValue'];
            $post['searchModule']   = $data['searchModule'];
            //分页
            $data['page']           = $page ? $page : 1; // 默认显示第一页
            $data['offset']         = $offest ? $offest : 10; // 默认每页10条纪录
            $return                 = $this->format_query('white_user/lists', $post);
            $data['white_users']    = $return['data'];
            $data['total_records']  = $return['total'];
            $data['pagination']     = $this->get_pagination_tags($data); // 创建分页
            
            $data['white_info']     = $this->format_query('white_user/get_white_info', array('mobile' => $this->data['user_info']['mobile']));
            $data['load_js'] = ['white_user.js'];
            $this->load->view('white_user', $data);
        }
    }

    /**
     * 添加白名单用户
     * @author wangzejun@dachuwang.com
     * @since 2015-07-20
     */
    public function create()
    {
        $data                 = $this->data;
        $city_id              = $this->input->get_post('city_id');
        $data['menue_id']     = $this->input->get_post('menue_id');
        $data['city_id']      = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京

        $post                 = array();
        $post['name']         = $this->input->get_post('user_name');
        $post['mobile']       = $this->input->get_post('user_mobile');
        $post['module_id']    = $this->input->get_post('select_module');
        $post['manager_id']   = $data['user_info']['id'];
        // 调用基础服务接口
        $return = $this->format_query('white_user/create', $post);
        $this->_return_json($return);
    }

    /**
     * 白名单编辑
     * @author wangzejun@dachuwang.com
     * @since 2015-07-23
     */
    public function edit()
    {
        $data                 = $this->data;
        $city_id              = $this->input->get_post('city_id');
        $data['tab_id']       = $this->input->get_post('tab_id');
        $data['menue_id']     = $this->input->get_post('menue_id');
        $data['city_id']      = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京

        $post = array();
        $post['name']         = $this->input->post('edit_user_name', TRUE);
        $post['user_id']      = $this->input->post('edit_user_id', TRUE);
        $post['mobile']       = $this->input->post('edit_user_mobile', TRUE);
        $post['module_id']    = $this->input->post('edit_select_module', TRUE);
        $post['manager_id']   = $this->data['user_info']['id'];
        $post['user_role']    = $this->input->post('edit_user_role', TRUE);
        // 调用基础服务接口
        $return = $this->format_query('white_user/edit', $post);
        header("Location:{$data['base_url']}/white_user/index?city_id={$data['city_id']}&menue_id={$data['menue_id']}&tab_id={$data['tab_id']}");
        exit;
    }

    /**
     * 白名单编辑
     * @author wangzejun@dachuwang.com
     * @since 2015-07-23
     */
    public function get_white_user_info()
    {
        $data               = $this->data;
        $post['manager_id'] = $data['user_info']['id'];
        $post['user_id']    = $this->input->get('user_id');
        $return = $this->format_query('white_user/get_white_user_info', $post);
        
        $this->_return_json($return);
    }

    /**
     * 创建分页链接
     *
     * @author wangzejun@dachuwang.com
     */
    private function get_pagination_tags($data)
    {
        $config['base_url']             = $this->data['base_url'] . '/white_user/index?menue_id='.$data['menue_id'].'&tab_id=' . $data['tab_id'] . '&offset=' . $data['offset'] . '&city_id=' . $data['city_id'] . '&searchKey=' . $data['searchKey'] . '&searchValue=' . $data['searchValue'];
        $config['total_rows']           = $data['total_records'];
        $config['per_page']             = $data['offset'];
        $config['num_links']            = 4;
        $config['use_page_numbers']     = TRUE;
        $config['page_query_string']    = TRUE;
        $config['query_string_segment'] = 'page';
        $config['first_link']           = "首页";
        $config['last_link']            = "末页";
        $config['full_tag_open']        = '<ul class="pagination">';
        $config['full_tag_close']       = '</ul>';
        $config['first_tag_open']       = '<li>';
        $config['first_tag_close']      = '</li>';
        $config['last_tag_open']        = '<li>';
        $config['last_tag_close']       = '</li>';
        $config['next_tag_open']        = '<li>';
        $config['next_tag_close']       = '</li>';
        $config['prev_tag_open']        = '<li>';
        $config['prev_tag_close']       = '</li>';
        $config['cur_tag_open']         = '<li class="active"><a>';
        $config['cur_tag_close']        = '</li></a>';
        $config['num_tag_open']         = '<li>';
        $config['num_tag_close']        = '</li>';
        $this->pagination->initialize($config);
        $links = $this->pagination->create_links();
        return $links;
    }

}


/* End of file white_user.php */
/* Location: :./applications/bi/application/controllers/white_user.php */