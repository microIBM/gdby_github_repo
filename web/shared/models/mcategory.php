<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的类型模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MCategory extends MY_Model {
    use MemAuto;

    private $table = 't_category';

    public function __construct() {
        parent::__construct($this->table);
    }

    public function get_children_ids($category_ids = array()) {
        $res = [];
        if(empty($category_ids)) {
            return $res;
        }
        $query = $this->db->select('id')->from('t_category');
        foreach($category_ids as $idx => $category_id) {
            if($idx == 0) {
                $query->like('path', ".{$category_id}.", 'both');
            } else {
                $query->or_like('path', ".{$category_id}.", 'both');
            }
        }
        $categories = $query->get()->result_array();
        return $categories;
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品列表
     */

    public function lists($post = array(), $return = FALSE) {
        if(!isset($post['upid'])) {
            $where['upid'] = 0;
        } else {
            $where['upid'] = $post['upid'];
        }
        if(isset($post['seccate'])) {
            $where = array();
            // $where['upid !='] = 0;
        }
        if(isset($post['name'])) {
            $where['like'] = array('name' => $post['name']);
        }
        
        $data = $this->get_lists('*', $where, array('weight'    => 'DESC'));
        
        $thirdCate = $thirdData = array();
        // 如果是二级分类，那么隐藏，显示直接三级分类
        if(isset($post['showindex'])) {
            //$this->_return_json($data);
            foreach($data as $v) {
                $path = trim($v['path'], "."); 
                if(count(explode(".", $path)) === 2) {
                    $thirdCate[] = $this->get_lists('*', 
                        array('upid'  => $v['id']),
                        array('weight' => 'DESC')
                    );
                }
            }
            if($thirdCate) {
                foreach($thirdCate as $v) {
                    foreach($v as $vv) {
                        $thirdData[] = $vv;
                    }
                }
                if($thirdData) {
                    $data = $thirdData;
                }
            }
        }
        if($return) {
            return $data;
        } else {
            $this->_return_json(array('cate'    => $data ));
        }
    }

    /**
     * 统计用户产品品类名称
     * @author yugang@ymt360.com
     * @param user_list 用户列表
     * @since 2015-01-30
     * @description 通过传入用户id和上架产品的品类id，返回用户上架产品的品类的上级品类的名称
     */
    public function count_category_name($user_list, $top = '3') {
        $result = array();
        foreach($user_list as $k => $v) {
            // 获取上级分类
            /*$query = $this->db->select('upid')
                ->from('category')
                ->where_in('id', $v)
                ->where('status', 1)
                ->get();
            if($query->num_rows() <= 0) {
                continue;
            }

            $cid_arr = array();
            foreach ($query->result_array() as $row) {
                $cid_arr[] = $row['upid'];
            }*/
            if(count($v) > $top) {
                $v = array_splice($v, 0, $top);
            }
            // 获取上级分类的名称
            $query = $this->db->select('name')
                ->from('category')
                ->where_in('id', $v)
                ->where('status', 1)
                ->get();
            if($query->num_rows() <= 0){
                continue;
            }
            $cname_arr = array();
            foreach ($query->result_array() as $row) {
                $cname_arr[] = $row['name'];
            }

            $result[$k] = implode(',', $cname_arr);
        }

        return $result;
    }

    
    /**
     * @description:通过传入的category_id，返回分类信息
     * @author: wangyang@dachuwang.com
     */
    public function get_category_info($category_id) {
        
        $this->db->select('name, upid,path')->from($this->table);
        if(is_array($category_id)){
            $this->db->where_in('id',$category_id);
        }else{
            $this->db->where(array('id' => $category_id));
        }
        $query = $this->db->get();
        $data = $query->result_array();
        return isset($data) ? $data : array();
    } 
    
    
    /**
     * @description:通过传入的分类搜索条件，得出对应的category数组
     * @author: wangyang@dachuwang.com
     */
    public function get_in_category($where = array()) {
        $query = $this->db->select('id, name, path, upid')->from($this->table);
        
        if(isset($where['like'])) {
            foreach($where['like'] as $k => $v) {
                if($k == 'category')
                $this->db->like('name', $v);
            }
            unset($where['like']);
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 根据分类id获取该分类的子分类，可以仅获取子分类，也可以获取子孙所有分类
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param int $category_id 传入的分类id
     * @param boolean $deep 是否深度检索，如果为true，则查询所有子孙分类
     * @return array 返回结果数组
     */
    public function get_category_child($category_id = 0, $deep = false){
        $this->db->select('id, name')->from($this->table);
        if($deep === true){
            if($category_id != 0){
                $this->db->like('path', ".$category_id.", 'both');
            }
            $category_list = $this->db->get()->result_array();
            //优化结果，剔除自身分类信息
//            foreach($category_list AS $key => $category){
//                if($category['id'] == $category_id){
//                    unset($category_list[$key]);
//                    break;
//                }
//            }
        }else{
            $this->db->where('upid', $category_id);
            $category_list = $this->db->get()->result_array();
        }
        return $category_list;
    }

    /**
     * 根据category获取分类path
     * @author yelongyi@dachuwang.com
     * @since 2015-07-24 15:11:37
     * @param array $category_arr
     * @return bool
     */
    public function get_path_by_categorys($category_arr = array()){
        if(empty($category_arr) || !is_array($category_arr)) return FALSE;
        $this->db->select('id, path');
        $this->db->where_in('id', $category_arr);
        $result = $this->db->from($this->table)->get()->result_array();
        $returnArr = array();
        foreach($result AS $val){
            $returnArr[$val['id']] = $val['path'];
        }
        return $returnArr;
    }

}  

/* End of file mcategory.php */
/* Location: :./application/models/mcategory.php */
