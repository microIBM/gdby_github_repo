<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @description 库存lib 2.0，以后会独立成为一个service
 */

class Stock {

    public function __construct () {
        parent::__construct();
        $this->CI = &get_instance();
        $this->CI->load->library(
            array(
                'Dachu_request',
                'RedisClient',
            )
        );
        $this->CI->load->model(
            array(
                'MStock',
                'MSku'
            )
        );
    }

    /*
     * @description 初始化订单库存和在库库存
     */
    public function init_stock() {
        //先读取所有的sku_number
        $skus = $this->CI->MSku->get_lists(
            'sku_number',
            array()
        );

        foreach($skus as $sku) {
            $sku_number = $sku['sku_number'];
            //TODO post wms
            $wms_stock_response = [];
            $stock_record = array(
                'sku_number' => '',
                'warehouse_id' => '',
            );
            $this->CI->MSku->check_and_insert(
                $stock_record
            );
        }
    }

    /**
     * @description 修改订单锁定库存值
     * num为变化量，可以为正数或负数 +或者-
     */
    public function change_stock_locked($warehouse_id = '', $sku_number = '', $num) {
        if(empty($warehouse_id) || empty($sku_number)) {
            return;
        }

        $stock_record = $this->CI->MStock->get_one(
            '*',
            array(
                'warehouse_id' => $warehouse_id,
                'sku_number' => $sku_number
            )
        );

        if(empty($stock_record)) {
            return;
        }

        $original_in_stock = $stock_record['stock_locked'];
        $new_in_stock = ($original_in_stock + $num) < 0 ? 0 : $original_in_stock + $num;

        $update_res = $this->CI->MStock->update_info(
            array(
                'stock_locked' => $in_stock
            ),
            array(
                'id' => $stock_record['id']
            )
        );
        return;
    }

    /**
     * @description 设置虚拟库存
     */
    public function set_virtual_stock($warehouse_id = '', $sku_number = '', $num) {
        if(empty($warehouse_id) || empty($sku_number)) {
            return;
        }

        $num = doubleval($num);
        $update_res = $this->CI->MStock->update_info(
            array(
                'num' => $num
            ),
            array(
                'warehouse_id' => $warehouse_id,
                'sku_number' => $sku_number
            )
        );
        return;
    }

    /**
     * @description 查询wms某sku的库存值
     */
    public function query_wms_stock($sku_number = '') {
        if(empty($sku_number)) {
            return [];
        }

        //TODO query wms stock
    }

    /**
     * @description 根据wms接口查询wms库存值
     */
    public function update_wms_stock_by_queue() {
        //这个需要从redis的update_queue里去取
        echo "success";
    }

    /**
     * @description 校准在库库存接口
     * 暂时还不需要
     */
    public function calibate_wms_stock($sku_number = '') {
    }

}

/* End of file stock.php */
/* Location: ./application/controllers/stock.php */
