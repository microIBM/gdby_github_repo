<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MProduct extends MY_Model {
    use MemAuto;

    private $table = 't_product';

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 统计用户产品品类
     * @author yugang@ymt360.com
     * @param uids 逗号分隔的用户id列表
     * @param top 用户产品排行前几的品类
     * @since 2015-01-29
     */
    public function count_category_by_uids($uids, $top = 3) {
        $result = array();
        $query = $this->db->select('user_id as uid, category_id, count(*) as count')
                    ->from('product')
                    ->where_in('user_id', $uids)
                    ->where('status', 1)
                    ->group_by('user_id, category_id')
                    ->order_by('uid asc, category_id asc, count desc')
                    ->get();
        if($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $result[$row['uid']][] = $row['category_id'];
            }
        }

        return $result;
    }

    /**
     * 通过城市和sku_number获取实时销售单价
     * @param $sku_number
     * @param $city_id
     * @param array $field
     * @return array
     */
    public function get_sku_sale_price($sku_number, $city_id, $field = array()){
        $returnArr = [];
        if(empty($sku_number) || empty($city_id)){
            return $returnArr;
        }
        if(is_array($sku_number)){
            $this->db->where_in('sku_number', $sku_number);
        }else{
            $this->db->where('sku_number', $sku_number);
        }
        $this->db->where('location_id', $city_id);
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        $this->db->where('status', 1);
        return $this->db->from($this->table)->get()->result_array();
    }
}

/* End of file mproduct.php */
/* Location: :./application/models/mproduct.php */
