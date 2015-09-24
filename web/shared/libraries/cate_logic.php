<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cate_logic {

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array("MCategory", "MProperty", "MCategory_map")
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取顶级分类，然后将其中的所有的分类都取出来
     */

    public function lists($post = array(), $front = TRUE) {
        if($front) {
            $app_name = $post['app_name'];
            $cates = $this->_category_map($app_name);
        } else {
            $cates = $this->_backend_category();
        }
        extract($cates);
        // 三级分类
        $second_ids = array_column($second_cate, 'id');
        $third_cate = $this->get_child($second_ids);
        $third_ids = array_column($third_cate, 'id');
        $forth_cate = $this->get_child($third_ids);
        return array(
            'top_cate'   => $top_cate,
            'second_cate'=> $second_cate,
            'third_cate' => $third_cate,
            'forth_cate' => $forth_cate
        );
    }
    /*
     * 后台分类显示
     *
     */
    private function _backend_category() {
        $where = array(
            'upid'  => 0,
            'status'=> intval(C('status.common.success'))
        );
        $top_cate = $this->CI->MCategory->get_lists(
            'path, name, id',
            $where,
            array('weight' => 'DESC', 'updated_time' => 'DESC')
        );
        // 如果是二级分类，那么隐藏，显示直接三级分类
        $top_ids = array_column($top_cate, 'id');
        $second_cate = $this->get_child($top_ids);
        return array(
            'top_cate' => $top_cate,
            'second_cate' => $second_cate
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 前台商品分类映射
     */
    private function _category_map($app_name) {
        // top
        $site_default = $app_name;
        $top_categories = C('categories.' . $site_default  . '.top');
        // 二级类
        $second_categories = C('categories.' . $site_default .'.second');
        // 根据映射表
        $top_cate = $this->_get_by_name($top_categories);
        //二级分类
        $top_ids = array_column($top_cate, 'id');
        $second_arr = array_combine($top_ids, $second_categories);
        // 获取二级类
        $second_categories = $this->_deal_arr_category($second_categories);
        $second_cate = array();
        // 将二级拼接
        foreach($second_arr as $second_val) {
            $second = $this->_get_by_name($second_val);
            foreach($second as $v) {
                $second_cate[] = $v;
            }
        }
        return array(
            'top_cate' => $top_cate,
            'second_cate' => $second_cate
        );
    }
    private function _deal_arr_category($multi_arr_cate) {
        $new_cate_arr = array();
        foreach($multi_arr_cate as $mcate_val) {
            foreach($mcate_val as $v) {
                $new_cate_arr[] = $v;
            }
        }
        return $new_cate_arr;
    }

    private function _get_by_name($names) {
         $where = array(
            'in' => array('name' => $names),
            'status'=> intval(C('status.common.success'))
        );
        // top
        $cates = $this->CI->MCategory
            ->get_lists(
                'path, name, id',
                $where,
                array('weight' => 'DESC')
            );
        return $cates;
    }

    public function get_child($up_ids) {
        $childs = $this->_get_child_by_ids($up_ids);
        return $this->_merge_childs($childs);
    }

    private function _merge_childs($childs) {
        $third_childs = $last_childs= array();
        if($childs) {
            $child_ids = array_column($childs, 'id');
            $third_childs = $this->_get_child_by_ids($child_ids);
            if($third_childs) {
                $third_child_ids = array_column($third_childs, 'id');
                $last_childs = $this->_get_child_by_ids($third_child_ids);
            }
        }
        $childs = array_merge($childs, $third_childs, $last_childs);
        return $childs;
    }

    //把实际的id做一个映射
    //源分类id和分类id映射
    //源id --> map_id
    private function _cate_map($cids) {
        if (empty($cids)) {
            return array();
        }
        $cids = is_array($cids) ? $cids : array($cids);
        $cid_map_infos = $this->CI->MCategory_map->get_lists(
            'id,origin_id',
            array(
                'in' => array('origin_id' => $cids)
            )
        );
        if (empty($cid_map_infos)) {
            return array();
        }
        return array_column($cid_map_infos, 'id', 'origin_id');
    }
    //根据父类id获取子类中的id
    private function _get_map_cate_child($upid) {
        if (empty($upid)) {
            return array();
        }
        $childs = $this->CI->MCategory_map->get_lists(
            'origin_id,name',
            array(
                'upid' => $upid,
                'status' => 1,
            )
        );
        return $childs ? $childs : array();
    }
    //获取列表页的头两级分类
    /*
     *@param $upid传入父类id
     */
    private function _get_top_two_cate($category_id) {
        if (empty($category_id)) {
            return array();
        }
        $cid_map_mid = $this->_cate_map($category_id);
        if (empty($cid_map_mid)) {
            return array($category_id => array());
        }
        //映射后的id
        $mcid = $cid_map_mid[$category_id];
        //获取子映射后的子id
        $upid = $this->_get_map_upid($mcid);
        $childs = $this->_get_map_cate_child($upid);
        $result[$category_id] = $this->_cate_have_prods($childs);
        return $result;
    }
    //因为能够在前端显示肯定是排除了那些父类没有商品的分类
    //既然父类的分类必然有商品，那么这里只要去判断下子类中有没有商品即可
    //origin_id,name
    private function _cate_have_prods($cate_ids) {
        if (empty($cate_ids)) {
            return array();
        }
        $cids = array_column($cate_ids, 'origin_id');
        $cate_have_prods = $this->CI->MProduct->get_lists(
            'category_id',
            array(
                'in' => array('category_id' => $cids),
                'status' => 1
            )
        );
        $cate_have_prods = $cate_have_prods ? array_column($cate_have_prods, 'category_id') : array();
        $ret = array();
        foreach($cate_ids as $cate_item) {
            if (in_array($cate_item['origin_id'], $cate_have_prods)) {
                $ret[] = $cate_item;
            }
        }
        return $ret;
    }
    private function _get_map_upid($category_id) {
        if (empty($category_id)) {
            return 0;
        }
        $upid = $this->CI->MCategory_map->get_one(
            'upid',
            array(
                'id' => $category_id
            )
        );
        return empty($upid) ? 0 : $upid['upid'];
    }

    private function _get_upid($category_id) {
        if (empty($category_id)) {
            return 0;
        }
        $upid = $this->CI->MCategory->get_one(
            'upid',
            array(
                'id' => $category_id
            )
        );
        return empty($upid) ? 0 : $upid['upid'];
    }

    //由调用着保证数据非空
    public function get_top_two_cate($category_id, $is_upid = FALSE) {
        if ($is_upid) {
            return $this->_get_top_two_cate($category_id);
        } else {
            $upid = $this->_get_upid($category_id);
            return $this->_get_top_two_cate($upid);
        }
    }

    private function _get_child_by_ids($up_ids) {
        $childs = array();
        if(!empty($up_ids)) {
            $childs = $this->CI->MCategory->get_lists__Cache3600("path, name, id, upid",
                array(
                    'in' => array(
                        'upid'  => $up_ids
                    ),
                    'status'    => intval(C('status.common.success'))
                ),
                array('weight'  => 'DESC')
            );
        }
        return $childs;
    }
    public function get_spec($path, $id) {
        $path = trim($path, '.');
        $path_arr = explode('.', $path);
        $property = $this->_get_specs($path_arr);
        $new_arr = array_combine($path_arr, $property);
        $spec = [];
        foreach($new_arrr as $v) {
            if($v) {
                $spec = $v;
            }
        }
        return $spec;
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 只要传递过来一个分类路径字串，得到名称关联数组
     *@param $path 传入一个.分割的分类名称字符串
     *@return 返回一个数组，这个数组是分类id-->name的关联数组
     */
    public function get_category_path_by($path) {
        if ( ! $path) {
            return array();
        }
        $path_array = explode('.', trim($path, '.'));
        $this->cid_map_sort = array_flip($path_array);
        $path_info  = $this->CI->MCategory->get_lists(
            'id,name',
            array(
                'in' => array('id' => $path_array)
            )
        );
        if ( ! $path_info) {
            return array();
        }
        usort($path_info, array($this, 'cate_sort'));
        return $path_info;
    }

    private function cate_sort($first, $second) {
        return $this->cid_map_sort[$first['id']] > $this->cid_map_sort[$second['id']];
    }

    private function _get_specs($ids) {
        $property = $this->CI->MProperty->get_lists(
            "id,name",
            array(
                'in' => array(
                    'category_id' => $ids
                )
            )
        );
        return $property;
    }

    public function get_by_ids($category_ids) {
        $categories = $this->CI->MCategory->get_lists('id, name, path',
            array(
                'in'    => array(
                    'id'    => $category_ids
                )
            )
        );
        return $categories;
    }

    public function get_map() {
        $data = $this->CI->MCategory->get_lists('id,name,path,upid');
        $top = $second = array();
        // 一级、二级
        foreach($data as $v) {
            $path = trim($v['path'], '.');
            $path_arr = explode('.', $path);
            $nums = count($path_arr);
            if( $nums === 1) {
                // 可能是一级
                $top[] = $v;
            } else if($nums === 2 ) {
                $second[] = $v;
            }
        }

        $top_child = $this->_get_map_child($top);
        $second_child = $this->_get_map_child($second);
        return array(
            'top' => $top,
            'second' => $second,
            'top_child' => $top_child,
            'second_child' => $second_child
        );
    }

    private function _get_map_child($top) {
        $childs = array();
        foreach($top as $v) {
            $data = $this->CI->MCategory->get_lists(
                'id,name,path,upid',
                array(
                    'id !=' => $v['id'],
                    'like' => array('path' => $v['path'])
                )
            );
            if($data) {
                foreach($data as $v) {
                    $childs[] = $v;
                }
            }
        }
        $ids = $new_child = array();
        foreach($childs as $v) {
            if(!in_array($v['id'], $ids)) {
                $new_child[] = $v;
            }
            $ids[] = $v['id'];
        }
        return $new_child;
    }
}

/* End of file  cate_logic.php*/
/* Location: :./application/libraries/cate_logic.php/ */
