<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mline extends MY_Model {
    use MemAuto;
    private $_table = 't_line';
    public function __construct() {
        parent::__construct($this->_table);
    }

    /**
     * 根据路线id得到路线详细信息
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param array $line_ids_arr 路线id数组
     * @param array $field 需要取的字段
     * @return bool|array 参数没传返回false，成功返回结果数组
     */
    public function get_line_by_lineIds($line_ids_arr, $field = array()){
        if(empty($line_ids_arr)) return false;
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        $this->db->where_in('id', $line_ids_arr);
        return $this->db->from($this->_table)->get()->result_array();
    }

    /**
     * 根据城市id获取仓库信息
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param null|int $location_id 不传默认获取所有仓库
     * @param array $field 获取的字段
     * @return array 结果数组
     */
    public function get_line_by_locationId($location_id, $field = array('location_id', 'warehouse_name', 'warehouse_id')){
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        //不传location_id，则返回所有
        if(!empty($location_id)){
            $this->db->where('location_id', $location_id);
        }
        $this->db->group_by('warehouse_id');
        return $this->db->from($this->_table)->get()->result_array();
    }
}

/* End of file mline.php */
/* Location: :./application/models/mline.php */
