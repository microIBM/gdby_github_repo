<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MStock extends MY_Model {

    private $_table = 't_stock';
    public function __construct() {
        parent::__construct($this->_table);
    }

    public function update_without_escape($data = array(), $where = array()) {
        if(!$data || !$where) {
            return FALSE;
        }

        foreach($data as $key => $val) {
            $this->db->set($key, $val, FALSE);
        }

        foreach($where as $key => $val) {
            $this->db->where($key, $val);
        }
        return $this->db->update($this->_table);
    }

    public function check_and_insert($stock_record = []) {
        if(empty($stock_record) || empty($stock_record['sku_number']) || empty($stock_record['warehouse_id'])) {

            return FALSE;
        }

        $rec = $this->get_one(
            'id',
            array(
                'warehouse_id' => $stock_record['warehouse_id'],
                'sku_number'   => $stock_record['sku_number']
            )
        );

        if(empty($rec)) {
            return $this->create($stock_record);
        } else {
            $data = [];
            if(!empty($stock_record['in_stock'])) {
                $data['in_stock'] = $stock_record['in_stock'];
            }
            if(!empty($stock_record['stock_locked'])) {
                $data['stock_locked'] = $stock_record['stock_locked'];
            }

            $this->update_info(
                $data,
                array(
                    'id' => $rec['id']
                )
            );

            return $rec['id'];
        }
    }

    /**
     * 根据sku_numbers和仓库id获取库存信息
     * @author yelongyi@dachuwang.com
     * @since 2015-07-25 10:27:40
     * @param array $sku_numbers sku_numbers
     * @param string $warehouse 仓库id，可以是字符串
     * @return array|bool 结果数组，建名为sku_number，键值为sku库存信息
     */
    public function get_in_warehouse($sku_numbers, $warehouse){
        if(!is_array($sku_numbers) || empty($warehouse)) return FALSE;
        $result = $this->db->select('sku_number, in_stock, stock_locked')
            ->from($this->_table)
            ->where_in('sku_number', $sku_numbers)
            ->where('warehouse_id', $warehouse)
            ->get()->result_array();
        //对结果转变一下
        $returnArr = array();
        foreach($result AS $val){
            $returnArr[$val['sku_number']] = $val;
        }
        unset($result);
        return $returnArr;
    }

}

/* End of file mstock.php */
/* Location: :./application/models/mstock.php */
