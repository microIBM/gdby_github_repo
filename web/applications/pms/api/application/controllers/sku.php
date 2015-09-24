<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Sku extends MY_Controller {

    private $_page_size = 10;
    protected $_cities = array();

    public function __construct() {
        parent::__construct();
        $this->load->library(array('Cate_logic'));
        $this->_cities = C('city.open');
        $this->_get_open_city_id();
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
                    'status' => C('tips.code.op_failed'),
                    'msg'    => '查询条件不满足'
                )
            );
        }
        // 获取当前用户
        $user_info = $this->userauth->current();
        $data = $this->format_query('/sku/lists', 
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
            'id'    => intval($post['id']),
        );
        $data =  $this->format_query('/sku/set_status',array('where' => $where, 'status' => intval($post['status'])));
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
            $pattern = '/^1(\d+){6}$/';
            if(preg_match($pattern, $post['searchVal'])) {
                $post['where'] = array('sku_number' => $post['searchVal']);
            } else {
                $post['where'] = array('like' => array('name' => $post['searchVal']));
            }
        }
        if(isset($post['status'])) {
            if($post['status'] != 'all') {
                $post['where']['status'] = $post['status'];
            }
            unset($post['status']);
        }
        // 若是运营人员，那么应该可以看到所有的货物
        $data = $this->format_query('/sku/manage', $post);
        $this->_return_json($data);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 物品信息维护
     */
    public function info() {
        // $this->check_validation('product', 'edit');
        $post = $this->post;
        $set['where'] = array('id' => $post['id']);
        $data = $this->format_query('/sku/edit', $set);
        $data['info']['pictures'] = [];
        if(!empty($data['info']['pic_ids'])) {
            $ids = explode(',', $data['info']['pic_ids']);
            $where = array(
                'where' => array(
                    'in' => array('id' => $ids),
                    'status' => C('status.common.success')
                )
            );
            $pics = $this->format_query('/bucket/lists', $where);
            if($pics) {
                $data['info']['pictures'] = $pics['list'];
            }
        }
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
            $url ='/sku/create';
        } else {
            $url = '/sku/save';
        }
        // 原来就有的图
        if(empty($this->post['originImgs'])) {
            unset($this->post['originImgs']); 
        }
        if(empty($this->post['imgs'])) {
            unset($this->post['imgs']); 
        }
        $data = $this->format_query($url, $post);
        $data OR $data['msg'] = '处理发生错误';
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

    public function img_upload() {
        $this->_return_json(
            array($this->post, $_FILES)
        );
    }
}

/* End of file product.php */
/* Location: :./application/controllers/product.php */
