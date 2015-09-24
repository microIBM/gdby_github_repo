<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Product extends MY_Controller {

    private $_page_size = 100;
    protected $_cities = array();

    public function __construct() {
        parent::__construct();
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 增加搜索，根据名称搜索
     */
    public function search() {
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '暂无信息'
        );
        $page = $this->get_page();
        $cur = $this->userauth->current(TRUE);
        $location_id = C('open_cities.beijing.id');
        // 查询所属城市
        if(!empty($this->post['locationId'])) {
            $location_id = intval($this->post['locationId']);
        }
        if($cur) {
            $location_id =$cur['province_id'];
        }
        $site_id = C('app_sites.guo.id');
        $customer_type = empty($cur) ? C('customer.type.normal.value') : $cur['customer_type'];
        if(!empty($this->post['searchVal'])) {
            $response = $this->format_query('/product/manage',
                array(
                    'where' => array(
                        'like' => array(
                            'title' => $this->post['searchVal']
                        ),
                        'customer_type' => $customer_type,
                        'location_id' => $location_id ,
                        'status' => C('status.product.up')
                    ),
                    'currentPage' => $page['page'],
                    'itemsPerPage' => $page['page_size']
                )
            );
        }
        $this->_return_json($response);
    }
   /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品列表
     */
    public function lists() {
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
        // 查询所属城市
        if(!empty($post['locationId'])) {
            $post['location_id'] = intval($post['locationId']);
        }
        // 检测用户是否已经登录,
        // 登录用户不允许切换城市
        // 优先取登录用户的所在城市
        $cur = $this->userauth->current(TRUE);
        $user_info = array();
        if($cur) {
            $post['location_id'] = $cur['province_id'];
            $local_info = $this->format_query('/location/info', array('where' => array('id' => $cur['province_id'])));
            // 所在城市info信息
            if(intval($local_info['status']) === 0) {
                $user_info = array(
                    'location_id' => $cur['province_id'],
                    'line_id' => $cur['line_id'],
                    'name' => $local_info['info']['name']
                );
            }
        }
        $data = $this->format_query('/product/lists',
            array(
                'upid' => $post['upid'],
                'page' => $page,
                'location_id' => $post['location_id'],
                'page_size' => $this->_page_size,
                'user_info'   => $user_info
            )
        );
        if(!empty($data['list'])) {
            $this->_format_data_by_line_id($cur, $data['list']);
        }
        $this->_return_json($data);
    }

    private function _format_data_by_line_id($cur, &$lists) {
        $is_login = $cur ? TRUE : FALSE;
        if($is_login) {
            $line_ids = array($cur['line_id']);
        } else {
            $line_ids = array(0);
        }
        foreach($lists as $key => &$v) {
            $ori_lines = explode(',', $v['line_id']);
            if($v['line_id'] != 0) {
                if(!$inter = array_intersect($ori_lines, $line_ids)) {
                    unset($lists[$key]);
                    continue;
                }
            }
        }
    }
  }
/* End of file product.php */
/* Location: :./application/controllers/product.php */
