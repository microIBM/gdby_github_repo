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
        $this->load->model(array('MLine', 'MStock', 'MProduct', 'MLocation', 'MSku', 'MBucket', 'MFollow_with_interest'));
        $this->load->library(array('Cate_logic', 'product_lib', 'check_storage', 'product_price'));

        //慢加载，快执行
        $this->op_failed  = C('tips.code.op_failed');
        $this->op_success = C('tips.code.op_success');

        //关注状态
        $this->followed   = 1;
        $this->unfollowed = 0;

    }
   /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品列表
     */
    public function lists() {
        $post = $this->post;
        $page = $this->get_page();
        $show_page = empty($_POST['itemsPerPage']) ? FALSE : TRUE;
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
        $customer_type = !empty($cur['customer_type']) ? $cur['customer_type'] : C('customer.type.normal.value');
        $response = $this->format_query('/product/lists',
            array(
                'upid' => $post['upid'],
                'offset' => $page['offset'],
                'location_id' => $post['location_id'],
                'page_size' => $show_page ? $page['page_size'] : 0,
                'user_info'   => $user_info,
                'customer_type' => $customer_type,
            )
        );
        if(!empty($response['list'])) {
            $response = $this->_format_product_lists($cur, $response);
        }
        $this->_return_json($response);
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
        $site_id = C('app_sites.chu.id');
        $normal_customer = C('customer.type.normal.value');
        $customer_type = empty($cur) ? $normal_customer : $cur['customer_type'];
        $visiable_arr = C('customer_visiable');
        if($customer_type == $normal_customer) {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['normal']['value']);
        } else {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['ka']['value']);
        }
        if(!empty($this->post['searchVal'])) {
            $fruit_category_id = empty($cur) || $cur['customer_type'] == $normal_customer ? C("category.category_type.fruit.code") : 0 ;
            $where = array(
                'where' => array(
                    'like' => array(
                        'title' => $this->post['searchVal']
                    ),
                    'in' => array(
                        'customer_visiable' => $visiable_condition,
                    ),
                    'customer_type' => C('customer.type.normal.value'),
                    'location_id' => $location_id ,
                    'status' => C('status.product.up')
                ),
                'currentPage' => $page['page'],
                // 是否查询水果相关的产品
                'fruit_category_id' => $fruit_category_id,
                'itemsPerPage' => $page['page_size']
            );
            $response = $this->format_query('/product/manage', $where);
        }
        if(!empty($response['list'])) {
            $response = $this->_format_product_lists($cur, $response);
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 通用的一些方法（格式化商品信息列表）
     */
    private function _format_product_lists($cur, $products) {
        if(isset($cur['id'])) {
            $this->product_lib->format_data_by_line_id($cur, $products['list']);
            $products['list'] = $this->product_price->get_rebate_price($products['list'], $cur['id'], FALSE);
            $product_list = $this->product_lib->set_product_fields($products['list']);
            // 使用新的库存服务
            $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
            $this->product_lib->set_default_check_storage_list($check_storage_info, $products['list']);
            $this->_add_follow_status_info($cur, $products['list']);
        }
        $products['list'] = $this->product_lib->format_shop_product_list($products['list']);
        return $products;
    }
    //给商品信息列表添加关注信息
    private function _add_follow_status_info($cur, &$products) {
        //如果用户没有登录，那么所有的商品都是未关注
        if ($cur) {
            $user_id        = $cur['id'];
            $product_id_arr = array_column($products, 'id');
            $follow_info    = $this->MFollow_with_interest->get_lists(
                'product_id,status',
                array(
                    'user_id' => $user_id,
                    'in'      => array('product_id' => $product_id_arr)
                )
            );
            $pid_map_status = array_column($follow_info, 'status', 'product_id');
            foreach($products as &$product) {
                $product['follow_status'] = array_key_exists($product['id'], $pid_map_status) ? $pid_map_status[$product['id']] : $this->unfollowed;
            }
        } else {
            foreach($products as &$product) {
                $product['follow_status'] = $this->unfollowed;
            }
        }
    }

    //id,category_id,user_id,...
    private function _get_follow_status(&$search_result) {
        $cur = $this->userauth->current(TRUE);
        $pids_str_arr = array_column($search_result, 'id');
        $pids = $pids_str_arr ? array_unique($pids_str_arr) : array(0);
        $pid_map_status = $this->MFollow_with_interest->get_lists(
            'product_id,status',
            array(
                'user_id' => empty($cur['id']) ? 0 : $cur['id'],
                'in'      => array('product_id' => $pids),
                'status'  => 1
            )
        );
        $product_id_map_status = array_column($pid_map_status, 'status', 'product_id');
        foreach($search_result as &$search_item) {
            $search_item['follow_status'] = isset($product_id_map_status[$search_item['id']]) ? $product_id_map_status[$search_item['id']] : 0;
        }
    }
    /*
     * @author longlijian@dachuwang.com
     * @description 获取商品描述
     * @return $show_product_detail 返回商品详情和状态码,返回登录和未登录的价格数据
     */
    public function get_product_detail() {
        $show_product_detail = array();
        empty($_POST['product_id']) AND $this->_return_failed('缺少商品id信息');
        $product_id = $_POST['product_id'];

        //如果没有登录显示普通信息
        $cur = $this->userauth->current(TRUE);
        $customer_type = ! empty($cur['customer_type']) ? $cur['customer_type'] : C('customer.type.normal.value');

        $product_info = $this->MProduct->get_lists(
            '*',
            array(
                'id'            => $product_id,
            )
        );
        empty($product_info) AND $this->_return_failed('未能在数据库中找到符合商品id,用户类型的商品');
        $format_product_info = array('list' => $product_info);
        $format_product_info = $this->_format_product_lists($cur, $format_product_info);
        $product_info = $format_product_info['list'];
        $product_info = end($product_info);
        $this->_get_photos($product_info);

        $category_info = $this->MCategory->get_one(
            'path',
            array(
                'id' => $product_info['category_id']
            )
        );

        if ($category_info) {
            $category_path = end($category_info);
            $path_id_map_name = $this->cate_logic->get_category_path_by($category_path);
            $show_product_detail['cate_spec'] = $path_id_map_name ? $path_id_map_name : array();
        } else {
            $show_product_detail['cate_spec'] = array();
        }
        $show_product_detail['id']          = $product_info['id'];
        $show_product_detail['title']       = $product_info['title'];
        $show_product_detail['adv_words']   = $product_info['adv_words'];
        $show_product_detail['category_id'] = $product_info['category_id'];
        $show_product_detail['top_two_cid'] = $this->cate_logic->get_top_two_cate($product_info['category_id']);
        $show_product_detail['storage']     = $product_info['storage'];
        $show_product_detail['unit']        = $this->product_lib->get_unit_name($product_info['unit_id']);
        $show_product_detail['user_id']     = $product_info['user_id'];
        $show_product_detail['sku_number']  = $product_info['sku_number'];
        $show_product_detail['location_id'] = $product_info['location_id'];
        //获取该商品的关注信息
        //对于未登录的都取未关注的信息
        if ($cur) {
            $follow_info = $this->MFollow_with_interest->get_one(
                'status',
                array(
                    'user_id'    => empty($cur['id']) ? 0 : $cur['id'],
                    'product_id' => $product_id,
                    'status'     => $this->followed
                )
            );
            $show_product_detail['follow_status'] = $follow_info ? $this->followed : $this->unfollowed;
        } else {
            $show_product_detail['follow_status'] = $this->unfollowed;
        }

        //商品图片信息
        $show_product_detail['pictures'] = $this->_get_image_info($product_info['pic_ids']);

        //获取商品的售卖地区信息
        $location_info = $this->MLocation->get_one(
            'name',
            array(
                'id' => $product_info['location_id']
            )
        );
        $produce_area = isset($location_info['name']) ? $location_info['name'] : '';
        $show_product_detail['produce_area'] = $produce_area;

        //获取商品的包装规则
        //获取商品等级
        $sku_spec = $this->MSku->get_one(
            'spec, sku_number, net_weight',
            array(
                'sku_number' => $product_info['sku_number']
            )
        );
        $sku_spec AND $spec = $sku_spec['spec'];
        $show_product_detail['spec'] = $spec ? $this->_get_sku_spec($spec) : array();

        //商品价格，结算价格
        //添加净重单价
        $net_weight = $sku_spec ? $sku_spec['net_weight'] : 0;
        list($price, $signle_price, $unit_price, $unit_name, $close_unit_name) = $this->_get_product_price($product_info);
        $show_product_detail['price']        = $price;
        $show_product_detail['single_price'] = $signle_price;
        $show_product_detail['unit_price']   = $unit_price;
        $show_product_detail['net_weight_price'] = empty($net_weight) ? '' : sprintf('%.2f', (($product_info['price']) / $net_weight)) . '元/斤';
        //售卖单位
        $show_product_detail['unit_name']    = $unit_name;
        //结算单位
        $show_product_detail['close_unit_name'] = $close_unit_name;

        //查询商品的限购情况
        $show_product_detail['buy_limit'] = $product_info['buy_limit'];

        $this->_return_success('获取商品详情成功', $show_product_detail);
    }

    /*
     *@param $spec 字符串类型的json
     *@todo 规则排序规则
     */
    private function _get_sku_spec($spec) {
        if ( ! $spec) {
            return array();
        }
        $spec = json_decode($spec);
        if ( ! $spec) {
            return array();
        }
        $spec_decode  = array_filter($spec, array($this, '_sku_spec_not_include'));
        $must_at_head = array('描述');
        $must_at_end  = array('规格', '包装规格');
        $head   = array();
        $middle = array();
        $foot   = array();
        //稳定排序
        $middle_filter = array_merge($must_at_head, $must_at_end);
        foreach($spec_decode as $spec_item) {
            in_array($spec_item->name, $must_at_head)    AND $head[] = $spec_item;
            in_array($spec_item->name, $must_at_end)     AND $foot[] = $spec_item;
            ! in_array($spec_item->name, $middle_filter) AND $middle[] = $spec_item;
        }
        return array_merge($head, $middle, $foot);
    }

    private function _get_photos(&$product) {
        if (empty($product['pic_ids'])) {
            $sku_number = $product['sku_number'];
            $pic_ids = $this->MSku->get_one(
                'pic_ids',
                array('sku_number' => $sku_number)
            );
            $pic_ids = $pic_ids ? $pic_ids['pic_ids'] : '';
            $product['pic_ids'] = $pic_ids;
        }
    }

    private function _return_failed($msg = '') {
        $this->_return_json(
            array(
                'status' => $this->op_failed,
                'msg'    => $msg
            )
        );
    }

    private function _return_success($msg = '', $info = array()) {
        $this->_return_json(
            array(
                'status' => $this->op_success,
                'msg'    => $msg,
                'info'   => $info
            )
        );
    }

    /*
     *@param $sku_spec('name','id','val')
     *@description 排除那些指定的字段
     */
    private function _sku_spec_not_include($sku_spec) {
        $spec_not_show_config = array('单价');
        return $sku_spec ? ! in_array($sku_spec->name, $spec_not_show_config) : FALSE;
    }

    private function _get_product_price($product_info) {
        $price_detail = array();
        $unit_id_map_name = array_column(C('unit'),'name','id');

        //售卖单位
        $unit_name = isset($unit_id_map_name[$product_info['unit_id']]) ? $unit_id_map_name[$product_info['unit_id']] : '';

        //结算单位
        $close_unit_name = isset($unit_id_map_name[$product_info['close_unit']]) ? $unit_id_map_name[$product_info['close_unit']] : '';

        $price        = ($product_info['price']);
        $single_price = ($product_info['single_price']) . '元/' . $close_unit_name;
        $unit_price   = $product_info['single_price'];

        return array($price, $single_price, $unit_price, $unit_name, $close_unit_name);
    }

    /*
     *@param $pic_ids为xxx,xxx 形式的ID字串
     *@description 根据pic_ids,返回这个图片的原始图片和缩略
     */
    private function _get_image_info($pic_ids, $zoom = 100) {
        $images = array();
        if ( ! $pic_ids) {
            return array('raw_image' => array(), 'thumbnail' => array());
        }
        $pic_ids_array = explode(',', $pic_ids);

        $pic_urls_info = $this->MBucket->get_lists(
            '*',
            array(
                'in' => array('id' => $pic_ids_array)
            )
        );

        if ($pic_urls_info) {
            $images['raw_image'] = $pic_urls_info;
            $images['thumbnail'] = img_zoom($pic_urls_info, '-30-');
        } else {
            $images['raw_image'] = array();
            $images['thumbnail'] = array();
        }

        return $images;
    }


  }
/* End of file product.php */
/* Location: :./application/controllers/product.php */
