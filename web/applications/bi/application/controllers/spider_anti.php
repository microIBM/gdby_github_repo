<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 友商竞品信息接口类
 * @author zhangxiao@dachuwang.com
 * @since 2015-08-18
 */
class Spider_anti extends MY_Controller {
    const WHITE_ANTI_MODULE = 11; //竞品分析
    const ADD_SKU_MODULE    = 13; //关联操作
    public function __construct() {
        parent::__construct();
        $this->load->library('pagination');
        $this->load->helper('pagination');
    }

    public function index(){
        
        $data                   = $this->data;
        $data['friend_id']      = $this->input->get('friend_id') !== FALSE ? $this->input->get('friend_id') : C('anti_sites.meicai.id');
        $data['city_id']        = $this->input->get('city_id') !== FALSE ? $this->input->get('city_id') : C('open_cities.beijing.id');
        $data['cate_name']      = $this->input->get('cate_name');
        $data['search_key']     = $this->input->get('search_key');
        $data['search_value']   = $this->input->get('search_value');
        $offest                 = $this->input->get('offset');
        $page                   = $this->input->get('page');
        
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_ANTI_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            //站点列表
            $data['friend_list']    = $this->get_names();
            //城市列表
            $data['city_list']      = $this->get_city_names();
            //分类列表
            $data['cate_list']      = $this->get_cate_names($data['friend_id'], $data['city_id']);

            $data['page']           = $page ? $page : 1; // 默认显示第一页
            $data['offset']         = $offest ? $offest : 10; // 默认每页10条纪录
            $data['pagesize']       = ($data['page'] - 1 ) * $data['offset'];
            $return                 = $this->get_list($data['friend_id'], $data['city_id'], $data['search_key'], $data['search_value'], $data['cate_name'], $data['pagesize'], $data['offset']);
            $data['total_records']  = $return['total'];
            $data['data_list']      = $return['data'];
            $data['pagination']     = $this->get_pagination_tags($data); // 创建分页
            $data['load_js']        = ['anti_products.js'];

            $this->load->view('friend/index', $data);
        }
    }

    /**
     * 获取竞品信息
     * @author zhangxiao@dachuwang.com
     */
    public function get_list($friend_id = null, $city_id = null, $key, $key_word = null, $cate_name = null, $offset = 0, $page_size = 10 ) {
        $key       = $key ?: $this->input->get_post('search_key', TRUE);
        $key_word  = $key_word ?: $this->input->get_post('search_value', TRUE);
        $cate_name = $cate_name ?: $this->input->get_post('cate_name', TRUE);
        $friend_id = $friend_id ?: $this->input->get_post('friend_id', TRUE);
        $city_id   = $city_id ?: $this->input->get_post('city_id', TRUE);
        //使用sphinx
        $s = new SphinxClient();
        $s->setServer(C('service.spider'), 9312);
        $s->setMatchMode(SPH_MATCH_EXTENDED2);
        $s->setLimits($offset, $page_size, 100000);
        $s->setMaxQueryTime(30);
        //筛选友商城市
        if($city_id != C('open_cities.quanguo.id')) {
            $s->setFilter('city_id', array($city_id));
        }
        //筛选友商站点
        if($friend_id != C('anti_sites.all.id')) {
            $s->setFilter('site_id', array($friend_id));
        }
        $s->SetSortMode (SPH_SORT_EXTENDED, "product_id asc");
        $fields = '';
        //筛选关键字
        if ($key_word) {
            if($key == 'product_name'){
                $fields .= '@title "'.$key_word.'" ';
            }elseif($key == 'product_id'){
                $s->setFilter('product_id', array($key_word));
            }elseif($key == 'sku_number'){
                $auto_ids = $this->_get_product_by_sku_num($key_word);
                if($auto_ids) {
                    $s->setFilter('auto_id', $auto_ids);
                } else {
                    return array(
                        'total' => 0,
                        'data'  => [],
                    );
                }
            }
        }
        //筛选友商品类名称
        if ($cate_name) {
            $fields .= '@category_name "'.$cate_name.'" ';
        }
        $result = $s->query($fields, 'anti_products');
        if(isset($result['matches'])) {
            $list = array_column($result['matches'], 'attrs');
        } else {
        	$list = array();
        }
        $final_list = $this->_assemble_sku_num($list);
        $return = array(
        	'total' => $result['total'],
        	'data'  => $final_list
        );
        return $return;
    }

    /**
     * 获取友商商品和大厨网关联的sku_number
     * @author zhangxiao@dachuwang.com
     */
    private function _assemble_sku_num($list){
        $auto_ids = array();
        if($list) {
            foreach($list as $value) {
                array_push($auto_ids, $value['auto_id']);
            }
        }
        $post_data = array(
            'auto_ids' => $auto_ids
        );
        $return = $this->format_query("anti_products/get_sku_by_prod", $post_data);
        if($return['status'] == 0) {
            foreach($list as $key => &$value) {
                $value['sku_numbers'] = $return['data'][$value['auto_id']][0];
            }
        }
        return $list;
    }

    /**
     * 获取与大厨网sku_number有关联的友商的product_ids
     * @author zhangxiao@dachuwang.com
     */
    private function _get_product_by_sku_num($key_word) {
        $post = array(
            'sku_number' => $key_word,
        );
        $return = $this->format_query("anti_products/get_prod_by_sku", $post);
        if($return['status'] == 0) {
            return $return['data'];
        } else {
            return array();
        }
    }

    /**
     * 获取城市名称列表
     * @author zhangxiao@dachuwang.com
     */
    public function get_city_names() {
        $cities = array_column(C('open_cities'), 'name', 'id');
        return $cities;
    }

    /**
     * 获取商家名称列表
     * @author zhangxiao@dachuwang.com
     */
    public function get_names() {
        $result = $this->format_query('anti_site/get_info');
        if(isset($result['status']) && $result['status'] == C('status.req.success')) {
            return array(C('anti_sites.all.id') => C('anti_sites.all.name')) + $result['data'];
        } else {
            return array();
        }
    }

    /**
     * 根据城市ID和站点ID获取分类名称
     * @author zhangxiao@dachuwang.com
     */
    public function get_cate_names($friend_id = null, $city_id = null) {
        $friend_id = $friend_id ?: $this->input->get_post('friend_id', TRUE);
        $city_id = $city_id ?: $this->input->get_post('city_id', TRUE);
        $result = $this->format_query('anti_products/get_cate_info', array(
            'city_id' => $city_id,
            'site_id' => $friend_id,
        ));

        if(isset($result['status']) && $result['status'] == C('status.req.success')) {
            return $result['data'];
        } else {
            return array();
        }
    }

    /**
     * 批量关联prod_id和sku_number
     * @author wangzejun@dachuwang.com
     */
    public function add_sku(){
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::ADD_SKU_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            $this->_return_json(array(
                'status' => -1,
                'msg'    => '您的账号没有权限操作哦',
            ));
        }
        $prod_sku_lists = $this->input->get_post('prod_sku_lists');
        $return = $this->format_query("anti_products/add_sku", array('prod_sku_lists' => $prod_sku_lists));
        $this->_return_json($return);
    }

    /**
     * 删除sku
     * @author wangzejun@dachuwang.com
     */
    public function delete_sku(){
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::ADD_SKU_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            $this->_return_json(array(
                'status' => -1,
                'msg'    => '您的账号没有权限操作哦',
            ));
        }

        $delete_sku_list = $this->input->get_post('delete_sku_list');
        $return = $this->format_query("anti_products/delete_sku", array('delete_sku_list' => $delete_sku_list));
        $this->_return_json($return);
    }

    /**
     * 通过sku获取prod
     * @author wangzejun@dachuwang.com
     */
    public function get_prod_by_sku(){
        $post['sku_number'] = $this->input->get_post('sku_number');
        $post['site_id']    = $this->input->get_post('site_id', TRUE);
        $post['city_id']    =  $this->input->get_post('city_id', TRUE);
        $return = $this->format_query("anti_products/get_prod_by_sku", $post);
        $this->_return_json($return);
    }

    /**
     * 创建分页链接
     *
     * @author wangzejun@dachuwang.com
     */
    private function get_pagination_tags($data)
    {
        $config['base_url']             = $this->data['base_url'] . '/spider_anti/index?menue_id=8' . '&offset=' . $data['offset'] . '&city_id=' . $data['city_id'] . '&search_key='. $data['search_key'] .'&search_value=' . $data['search_value'] . '&friend_id=' . $data['friend_id'] . '&cate_name=' . $data['cate_name'];
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

/* End of file spider_anti.php */
/* Location: ./application/controllers/spider_anti.php.php */
