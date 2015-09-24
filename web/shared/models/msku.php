<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 产品sku model
 * 
 * @author : liaoxianwen@ymt360.com
 * @version : 1.0.0
 * @since : 2014-12-10
 */
class MSku extends MY_Model {
    use MemAuto;
    
    private $table = 't_sku';
    
    public function __construct () {
        parent::__construct($this->table);
    }
    
    /*
     * @description :根据sku_number查找sku名称及规格 @author: wangyang@dachuwang.com
     */
    public function get_sku_info_by_sku_num ($sku_num = array(), $where = array()) {
        $this->db->select('sku_number, name, spec, category_id')->from($this->table);
        if (! (empty($sku_num))) {
            $this->db->where_in('sku_number', $sku_num);
        }
        
        if (isset($where['like'])) {
            foreach ( $where['like'] as $k => $v ) {
                if ($k == 'sku_number' || $k == 'name') {
                    $this->db->like($k, $v);
                }
            }
            unset($where['like']);
        }
        /*
         * if(isset($category_ids)) { $this->db->where_in('category_id', $category_ids); }
         */
        $query = $this->db->get();
        return $query->result_array();
    }


    /**
     * 根据sku_num获取sku_info,支持获取特定字段和批量获取
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param int|array $sku_num 单个number或者number组成的数组
     * @param array $field 需要获取的数据字段，默认获取全部
     * @return bool|array 参数没传返回false，成功返回结果数组
     */
    public function get_sku_info($sku_num, $field = array()){
        if(empty($sku_num)) return false;
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        if(is_array($sku_num)){
            $this->db->where_in('sku_number', $sku_num);
        }else{
            $this->db->where('sku_number', $sku_num);
        }
        return $this->db->from($this->table)->get()->result_array();
    }

    /**
     * 根据品类获取品类下的所有sku，支持获取特定字段和批量获取
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param array|int $category_id 单个id或者id组成的数组
     * @param array $field 需要获取的数据字段，默认获取全部
     * @return bool|array 参数没传返回false，成功返回结果数组
     */
    public function get_sku_by_category($category_id, $field = array()){
        if(empty($category_id)) return false;
        if(is_array($category_id)){
            $this->db->where_in('category_id', $category_id);
        }else{
            $this->db->where('category_id', $category_id);
        }
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        return $this->db->from($this->table)->get()->result_array();
    }
}

/* End of file msku.php */
/* Location: :./application/models/msku.php */
