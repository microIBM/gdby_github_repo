<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 首页推荐
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-06-05
 */
class Recommend extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MRecommend',
                'MLocation',
                'MProduct',
            )
        );
        $this->load->library(array('Product_lib'));
    }

   /**
     * @author: liaoxianwen@ymt360.com
     * @description 推荐列表
     */
    public function manage() {
        $page = $this->get_page();
        $where = [];
        // 格式化传入的条件
        $this->_format_search_where($where);
        $total =  $this->MRecommend->count($where);
        $where['id >'] = 1;
        $recommend_lists = $this->MRecommend->get_lists__Cache300(
            array(),
            $where,
            array('id' => 'DESC', 'updated_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $this->_format_recommend_lists($recommend_lists);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $recommend_lists,
                'total' => $total
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 推荐格式化查询条件
     */
    private function _format_search_where(& $where) {
        if(isset($_POST['status'])) {
            $where['status'] = $_POST['status'];
        }
        if(isset($_POST['site_id'])) {
            $where['site_id'] = $_POST['site_id'];
        }
        if(isset($_POST['location_id'])) {
            $where['location_id'] = $_POST['location_id'];
        }
        if(isset($_POST['customer_type'])) {
            $where['customer_type'] = $_POST['customer_type'];
        }
        // 时间范围
        if(isset($_POST['current_date'])) {
            $where['online_time <='] = intval($_POST['current_date']);
            $where['offline_time >='] = intval($_POST['current_date']);
        }
        if(isset($_POST['title'])) {
            $where['like'] = array(
                'title' => $_POST['title']
            );
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化数据
     */
    private function _format_recommend_lists(&$recommend_lists) {
        if($recommend_lists) {
            // 商品
            $product_col_ids = array_column($recommend_lists, 'product_ids');
            $product_ids = [];
            foreach($product_col_ids as $id) {
                $product_ids = array_merge($product_ids, explode(',', $id));
            }
            $product_ids = array_unique($product_ids);
            $old_products = $this->MProduct->get_lists__Cache3600('*', array('in' => array('id' => $product_ids)));
            $sku_numbers = array_column($old_products, 'sku_number');
            $product_ids = array_column($old_products, 'id');
            // 组合根据id去查询sku_number
            $product_combine_id_arr = array_combine($product_ids, $sku_numbers);
            $where = array(
                'in' => array(
                    'sku_number' => $sku_numbers
                ),
                'customer_type' => empty($_POST['customer_type']) ? C('customer.type.normal.value') : $_POST['customer_type'],
                'status' => C('status.common.success')
            );
            if(isset($_POST['location_id'])) {
                $where['location_id'] = $_POST['location_id'];
            }
            // 拿sku去查询商品
            $products = $this->MProduct->get_lists__Cache3600('*', $where);
            $this->product_lib->format_product_data($products);
            foreach($recommend_lists as &$recommend_list) {
                $recommend_list['products'] = [];
                // 推荐商品的id 按照顺序
                $product_ids = explode(',', $recommend_list['product_ids']);
                $sku_numbers = [];
                foreach($product_ids as $product_id) {
                    $sku_numbers[] = $product_combine_id_arr[$product_id];
                }
                // 组合
                foreach($products as $product) {
                    $index = array_search($product['sku_number'], $sku_numbers);
                    if(!is_bool($index)) {
                        $recommend_list['products'][$index]= $product;
                    }
                }
                ksort($recommend_list['products']);
                $recommend_list['updated_time'] = date('Y-m-d H:i', $recommend_list['updated_time']);
                $recommend_list['online_time_cn'] = date('Y-m-d', $recommend_list['online_time']) ;
                $recommend_list['offline_time_cn'] = date('Y-m-d', $recommend_list['offline_time']);
            }
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建首页推荐数据
     */
    public function create() {
        $format_data = $this->_format_recommend_post_data();
        extract($format_data);
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '创建失败'
        );
        // 判断只能为5个推荐商品
        if(!empty($recommend_data['product_ids'])) {
            $product_ids = array_unique(explode(',', $recommend_data['product_ids']));
            if(count($product_ids) === 5) {
                $recommend_id = $this->MRecommend->create($recommend_data);
                if($recommend_id) {
                    $response = array(
                        'status' => C('tips.code.op_success'),
                        'msg' => '创建成功',
                        'id' => $recommend_id
                    );
                }
            }
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化post数据
     */
    private function _format_recommend_post_data() {
        $data = array(
            'site_id'       => empty($_POST['site_id']) ? '' : $_POST['site_id'],
            'site_cn'       => $this->_get_site_name(),
            'location_id'   => empty($_POST['location_id']) ? '' : $_POST['location_id'],
            'location_cn'   => $this->_get_location_name(),
            'offline_time'  => empty($_POST['endTime']) ? '' : $_POST['endTime'],
            'online_time'   => empty($_POST['startTime']) ? '' : $_POST['startTime'],
            'status'        => $_POST['status'],
            'title'         => empty($_POST['title']) ? '' : $_POST['title'],
            'customer_type' => empty($_POST['customer_type']) ? C('customer.type.normal.value') : $_POST['customer_type'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
        );
        if(!empty($_POST['products']) && is_array($_POST['products'])) {
            $data['product_ids'] = implode(',', $_POST['products']);
        }
        return array(
            'recommend_data' => $data
        );

    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 通用函数
     * @todo 拆出去
     */
    private function _get_site_name() {
        $sites = C('app_sites');
        $site_cn = '';
       foreach($sites as $site) {
            if($site['id'] == $_POST['site_id']) {
                $site_cn = $site['name'];
            }
        }
        return $site_cn;
    }

    private function _get_location_name() {
        $location = $this->MLocation->get_one(
            'name, id',
            array(
                'id' => $_POST['location_id']
            )
        );
       return $location ? $location['name'] : '';
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 
     */
    public function set_status() {
       $updata = array(
            'status' => $_POST['status']
        );
        $this->MRecommend->update_info($updata, $_POST['where']);
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success'),
                'msg' => '设置成功'
            )
        );
    }
}

/* End of file recommend.php */
/* Location: ./application/controllers/recommend.php */
