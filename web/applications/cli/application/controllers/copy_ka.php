<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 增加ka的映射分类&商品列表
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-3-25
 */
class Copy_ka extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MCategory_map',
                'MCategory',
                'MLine',
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 将product表的数据复制到sku表中，生产新的sku_number
     */
    public function product($location_id, $site_id, $tobe_site_id, $customer_type) {
        $data = $this->MProduct->get_lists("*", array('location_id' => $location_id, 'is_active' => 1, 'customer_type' => 1));
        $guo_catemaps = $this->MCategory_map->get_lists('*', array('location_id' => $location_id, 'site_id' => $site_id, 'status' => 1, 'customer_type' => 1));
        $origin_ids = array_column($guo_catemaps, 'origin_id');
        // 取出新的
        $catemaps = $this->MCategory_map->get_lists('*', array('in' => array('origin_id' => $origin_ids),'location_id' => $location_id, 'site_id' => $tobe_site_id, 'status' => 1, 'customer_type' => $customer_type));
        $not_found = [];
        if($catemaps) {
            $category_ids = array_column($catemaps, 'origin_id');
            foreach($data as $v) {
                $info = $this->MCategory->get_one('path', array('id' => $v['category_id']));
                $path_arr =  explode('.', trim($info['path'], '.'));
                if(array_intersect($path_arr, $category_ids)){
                    unset($v['id']);
                    // 更新product 的sku号
                    // 创建新的sku信息
                    $v['customer_type'] = $customer_type;
                    $this->MProduct->create($v);
                } else {
                    $not_found[] = $v;
                }
            }
        }
        var_dump($not_found);die;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 修复order detail 表
     */
    public function catemap($location_id, $site_id, $tobe_site_id, $customer_type) {
        // 先复制一级分类，然后再根据二级
        $catemaps = $this->MCategory_map->get_lists('*', array('location_id' => $location_id, 'site_id' => $site_id, 'status' => 1, 'customer_type' => 1));
        $top_catemap = $second_catemap = [];
        foreach($catemaps as $key => $v) {
            if(intval($v['upid']) === 0) {
                $top_catemap[] = $v;
            } else {
                $second_catemap[$v['upid']][] = $v;
            }
        }
        $top_ids = array_column($top_catemap, 'id');
        $top_catemap = array_combine($top_ids, $top_catemap);
        // 一级
        foreach($top_catemap as &$v) {
            $map_origin_id = $v['id'];
            unset($v['id']);
            // 创建新的sku信息
            $v['customer_type'] = $customer_type;
            $v['site_id'] = $tobe_site_id;
            $id = $this->MCategory_map->create($v);
            $v['id'] = $id;
            $v['path'] = ".$id.";
            $this->MCategory_map->update_info(array('path' => $v['path']), array('id' => $id));
        }
        unset($v);
        $not_found = [];
        foreach($second_catemap as $k => $second_val) {
            foreach($second_val as $second) {
                $upid = $second['upid'];
                if(!empty($top_catemap[$upid]['id'])) {
                    unset($second['id']);
                    $second['site_id'] = $tobe_site_id;
                    $second['customer_type'] = $customer_type;
                    $second['upid'] = $top_catemap[$upid]['id'];
                    $id = $this->MCategory_map->create($second);
                    $second['path'] = $top_catemap[$upid]['path'] . $id . '.';
                    $this->MCategory_map->update_info(array('path' => $second['path']), array('id' => $id));
                } else {
                    $not_found[] = $second;
                }
            }
        }
        var_dump($not_found);die;
    }

    public function repair_catemap() {
        $catemaps = $this->MCategory_map->get_lists('*');
        foreach($catemaps as $v) {
            if($v['upid'] == 0) {
                $path = ".{$v['id']}.";
            } else {
                $path = ".{$v['upid']}.{$v['id']}.";
            }
            $this->MCategory_map->update_info(array('path' => "$path"), array('id' => $v['id']));
        }
    }

    public function set_old_ka_invalid() {
        $where['customer_type'] = C('customer.type.KA.value');
        $where['location_id'] = C('open_cities.beijing.id');
        $this->MProduct->update_info(array('is_active' => 0, 'status' => 0), $where);
        echo 'success';
    }
}

/* End of file repair_sku.php */
/* Location: ./application/controllers/repair_sku.php */
