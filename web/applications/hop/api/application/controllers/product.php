<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Product extends MY_Controller {

    private $_page_size = 10;
    protected $_cities = array();

    public function __construct() {
        parent::__construct();
        $this->load->library(array('Cate_logic'));
        $this->_cities = C('city.open');
        $this->_get_open_city_id();
    }
    //店铺产品列表
    public function shop_lists() {
        $post = $this->post;
        //添加访问历史
        $shop_history = array();
        $uinfo = $this->userauth->current();
        if(!empty($uinfo) && !empty($post['store_number'])) {
            $shop_history = $this->MVisited_store->get_one('*',
                array(
                    'uid'          => $uinfo['id'],
                    'store_number' => $post['store_number'],
                    'status'       => 1
                )
            );
            if(empty($shop_history)) {
                $shop_result = $this->MVisited_store->create(
                    array(
                        'uid'          => $uinfo['user_id'],
                        'store_number' => $post['store_number'],
                        'created_time' => $this->input->server('REQUEST_TIME'),
                        'updated_time' => $this->input->server('REQUEST_TIME'),
                    )
                );
            }
        }
        $shop_where = array('store_number' => $post['store_number']);
        $supplier_info = $this->_get_supplier_info($shop_where);
        if(empty($supplier_info)) {
            $data = array(
                'status' => TRUE,
                'list'   => array()
            );
            $this->_return_json($data);
        }
        $page = isset($post['page']) && intval($post['page']) >= 1 ? intval($post['page']) : 1;
        $childs = $this->cate_logic->lists($post);
        $products = array();
        $arr_municipality = C('status.municipality');

        $lists = $this->MProduct->query(
            array(
                'user_id' => $supplier_info['id'],
                'status'  => C('status.product.up')
            ),
            $this->_page_size * ($page - 1),
            $this->_page_size,
            array(
                'updated_time' => "DESC"
            )
        );
        // 修改
        if(!empty($lists)) {
            foreach($lists as &$v) {
                $v['min_price'] = sprintf("%.2f", ($v['min_price'] / 100));
            }
        }
        // 查出最近供货商后，显示
        $data = array(
            'status'    => TRUE,
            'list'  => $lists,
            'user' => array(
                'name' => $supplier_info['name'],
                'market' => $supplier_info['market'],
            ),
        );
        $this->_return_json($data);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品列表
     */
    public function lists($city_id = 1) {
        $post = $this->post;
        $page = isset($post['page']) && intval($post['page']) >= 1 ? intval($post['page']) : 1;
        $ip_address = '';// 当前id地址
        if(empty($post['upid'])) {
            $this->_return_json(
                array(
                    'status'    => C('tips.code.op_failed'),
                    'msg'   => '查询条件不满足'
                )
            );
        }
        // 获取当前用户
        $user_info = $this->userauth->current();
        $data = $this->format_query('/product/lists', 
            array(
                'upid' => $post['upid'],
                'page' => $page,
                'page_size' => $this->_page_size,
                'user_info'   => $user_info
            )
        );

        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置货物状态
     */
    public function set_status() {
        // $this->check_validation('product', 'edit');
        $post = $this->post;
        $where = array(
            'id'    => $post['id'],
        );
        $data =  $this->format_query('/product/set_status',array('where' => $where, 'status' => $post['status']));
        $this->_return_json($data);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 管理货物
     */
    public function manage() {
        // $this->check_validation('product', 'list');
        $post  = $this->post;
        $post['where'] = array();
        if(!empty($post['searchVal'])) {
            $post['where'] = array('like' => array('title' => $post['searchVal']));
        }
        if(isset($post['status'])) {
            if($post['status'] != 'all') {
                $post['where']['status'] = $post['status'];
            }
            unset($post['status']);
        }
        $uinfo = $this->userauth->current();
        // 若是运营人员，那么应该可以看到所有的货物
        $data = $this->format_query('/product/manage', $post);
        $this->_return_json($data);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 物品信息维护
     */
    public function edit() {
        // $this->check_validation('product', 'edit');
        $post = $this->post;
        $data = $this->format_query('/product/info', $post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 根据类别搜索
     */
    public function search() {
        $post = $this->post;
        $category_id = $post['category_id'];

        $data = $this->MProduct
            ->get_lists(
                array(), 
                array(
                    'category_id' => $category_id
                )
            );
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 增加货物
     */
    public function save() {
        // $this->check_validation('product', 'create');
        $post = $this->post;
        if(empty($post['id'])) {
            $url ='/product/create';
        } else {
            $url = '/product/save';
        }
        $data = $this->format_query($url, $post);
        $uinfo = $this->userauth->current();
        $this->_return_json($data);
    }
    /**
     * @modify author: liaoxianwen@ymt360.com
     * @author wuxuan
     * @date  2015-01-14 
     */
    private function _get_supplier_info($condition) {
        if($condition) {
            $data = $this->MUser->get_one('market_id, name, store_number, detail_address,brand,id', $condition);
            if($data) {
                $where['id'] = $data['market_id'];
                $market = $this->MMarket_info->get_one('*', $where);
                $return_data['market'] = $market['alias_name'].$data['detail_address'] . " " . $data['brand'];
                $return_data['boss'] = $data['name'];
                $return_data['id'] = $data['id'];
                $return_data['name'] = $data['name'];
                return $return_data;
            }
        }
        return array();
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取ip信息，没有登录的用户就判断
     */
    protected function _get_ip_info() {
        $ip = $this->input->server("REMOTE_ADDR");
        // 淘宝api
        $ipinfo = ip2info($ip);
        $direct_city = FALSE;
        if(!$ipinfo) {
            $province_id = C('status.municipality.BeiJing');
            $loc = $this->MLocation->get_one('name', array('id'    => $province_id));
            $ipinfo['province'] = $loc['name'];
            $direct_city = TRUE;
        } else {
            // 查出省份信息
            if(is_direct_city($ipinfo['province_id'])) {
                $direct_city = TRUE;
                $province_id = $ipinfo['province_id'];
                // 直辖市
            } else {
                $direct_city = FALSE;
                $city_id = $ipinfo['city_id'];
            }
        }
        // 直辖市
        if($direct_city) {
            return array(
                'province_id' => $province_id,
                'address'    => $ipinfo['province']
            );
        } else {
            return array(
                'city_id'  => $city_id,
                'address'  => $ipinfo['province'] . $ipinfo['city']
            );
        }
    }

    protected function _get_city_supply($city_id = 1, $market_id = 415) {
        if($city_id) {
            $this->_ids = array($city_id); 
        }
        $ids = $this->MUser->get_lists('id,market_id', 
            array( 
                'market_id' => $market_id,
                'type'  => C('user.normaluser.supply.type')
            )
        );
        // 获取地址
        $city = $this->MLocation->get_one('id, name', array('id'    => $city_id));
        $market = $this->MMarket_info->get_one('id, alias_name', array('id'  => $market_id));
        return array(
            'ids'     => array_column($ids, 'id'),
            'address' => $city['name'] . $market['alias_name'],
            'city'    => $city,
            'market'  => $market
        );
    }

    private function _get_open_city_id() {
        foreach($this->_cities as $v) {
            $this->_ids[] = $v;
        }
    }
    // 获取同一城市的供应商
    protected function _get_supply($user_info) {

        $suggest_msg = ''; // 推荐提示信息
        if(is_direct_city($user_info['province_id'])) {
            $ids = $this->MUser->get_lists( 'id,market_id', 
                array(
                    'province_id' => $user_info['province_id'],
                    'type'        => C('user.normaluser.supply.type')
                )
            );
        } else {
            $ids = $this->MUser->get_lists('id, market_id',
                array(
                    'city_id' => $user_info['city_id'],
                    'type'    => C('user.normaluser.supply.type')
                )
            );
        }
        return array(
            'ids'         => array_column($ids, 'id'),
            'suggest_msg' => $suggest_msg
        );
    }

    public function units() {
        $data = $this->format_query('product/units');
        $this->_return_json($data);
    }
}

/* End of file product.php */
/* Location: :./application/controllers/product.php */
