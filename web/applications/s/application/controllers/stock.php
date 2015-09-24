<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MSku',
                'MLine',
                'MStock',
                'MWarehouse',
                'MSku'
            )
        );
        $this->load->library(
            array(
                'RedisClient'
            )
        );
    }

    private function _get_warehouses() {
        $warehouse_list = $this->MLine->get_lists(
            'warehouse_id, warehouse_name',
            array(
                'status' => 1,
            )
        );
        //print_r($warehouse_list);
        return $warehouse_list;
    }

    private function _format_stock_list($stock_list = array()) {
        //填充仓库名
        $warehouse_list = $this->_get_warehouses();
        $warehouse_ids = array_column($warehouse_list, 'warehouse_id');
        $warehouse_names = array_column($warehouse_list, 'warehouse_name');
        $warehouse_map = array_combine($warehouse_ids, $warehouse_names);

        $sku_numbers = array_column($stock_list, 'sku_number');
        $skus = $this->MSku->get_lists(
            '*',
            array(
                'in' => array(
                    'sku_number' => $sku_numbers
                )
            )
        );

        $sku_numbers = array_column($skus, 'sku_number');
        $sku_map = array_combine($sku_numbers, $skus);

        //print_r($warehouse_map);
        foreach($stock_list as &$item) {
            $warehouse_id = $item['warehouse_id'];
            $item['warehouse_name'] = isset($warehouse_map[$warehouse_id]) ? $warehouse_map[$warehouse_id] : '';
            //可售库存
            $item['sellable'] = $item['in_stock'] - $item['stock_locked'];

            $sku_number = $item['sku_number'];
            $item['sku_name'] = $sku_map[$sku_number]['name'];
            //print_r($warehouse_map[$warehouse_id]);
            //
            //限购值需要去redis取
        }
        unset($item);
        return $stock_list;
    }

    public function lists() {
        //还是需要存数据库
        $page = $this->get_page();
        $where = array(
            'status' => 1
        );

        $sql = 'select * from t_stock  where status = 1 ';
        $count_sql = 'select count(1) cnt from t_stock where status = 1';
        if(!empty($_POST['warehouse_id'])) {
            $where['warehouse_id'] = $_POST['warehouse_id'];
            $warehouse_id = $_POST['warehouse_id'];
            $sql .= "and warehouse_id = {$warehouse_id} ";
            $count_sql .= "and warehouse_id = {$warehouse_id} ";
        }

        if(!empty($_POST['sku_number'])) {
            $where['sku_number'] = intval($_POST['sku_number']);
            $sku_number = intval($_POST['sku_number']);
            $sql .= " and sku_number = {$sku_number} ";
            $count_sql .= " and sku_number = {$sku_number} ";
        }

        //$sql .= ' and (stock_locked != 0 or in_stock != 0 or virtual_stock > 0) ';
        //$count_sql .= ' and (stock_locked != 0 or in_stock != 0 or virtual_stock > 0) ';

        $sql .= " order by sku_number ASC, warehouse_id ASC ";
        $count_sql .= " order by sku_number ASC, warehouse_id ASC ";
        $sql .= " limit " . $page['offset'] . ", " . $page['page_size'];
        //$count_sql .= " limit " . $page['offset'] . ", " . $page['page_size'];

        $stock_list = $this->db->query($sql)->result_array();
        $total_count = $this->db->query($count_sql)->result_array();
        //print_r($count_sql);
        $total_count = empty($total_count) ? 0 : $total_count[0]['cnt'];

        /*
        $stock_list = $this->MStock->get_lists(
            '*',
            $where,
            array('sku_number' => 'ASC', 'warehouse_id' => 'ASC'),//order_by
            array(),//group by
            $page['offset'],
            $page['page_size']
        );
        */
        $total = count($stock_list);
        $stock_list = $this->_format_stock_list($stock_list);
        $stock_list = $this->_filter_stock_list($stock_list);
        $res = array(
            'status'      => 0,
            'total_count' => $total_count,
            'total'       => $total,
            'list'        => $stock_list,
        );
        $this->_return_json($res);
    }

    private function _filter_stock_list($stock_list) {
        $res = [];
        foreach($stock_list as $stock) {
            if(!empty($stock['warehouse_name'])) {
                $res[] = $stock;
            }
        }
        return $res;
    }

    //修改虚拟库存和可超卖额
    public function update() {
        $warehouse_id = !empty($_POST['warehouse_id']) ? $_POST['warehouse_id'] : 0;
        $sku_number   = !empty($_POST['sku_number']) ? $_POST['sku_number'] : 0;

        $key_pattern = C('storage.redis_key.main_key_pattern');
        $key = str_replace('{{sku_number}}', $sku_number, $key_pattern);
        $key = str_replace('{{warehouse_id}}', $warehouse_id, $key);

        $update_info = [];
        if(isset($_POST['virtual_stock'])) {
            $update_info['virtual_stock'] = intval($_POST['virtual_stock']);
            $this->redisclient->hset($key, C('storage.redis_key.virtual_stock_key'), intval($_POST['virtual_stock']));
        }
        if(isset($_POST['exceed_limit'])) {
            $update_info['exceed_limit'] = intval($_POST['exceed_limit']);
            $this->redisclient->hset($key, C('storage.redis_key.exceed_limit_key'), intval($_POST['virtual_stock']));
        }

        if(empty($update_info)) {
            $this->_return_json(
                array(
                    'status'     => 0,
                    'msg'        => '更新库存成功',
                    'update_res' => 'no need to update',
                )
            );
        }
        //还需要更新数据库表
        $update_res = $this->MStock->update_info(
            $update_info,
            array(
                'sku_number'   => $sku_number,
                'warehouse_id' => $warehouse_id
            )
        );

        $this->_return_json(
            array(
                'status'     => 0,
                'msg'        => '更新库存成功',
                'update_res' => $update_res
            )
        );
    }

    /**
     * @description 下单时锁定一部分库存
     */
    public function inc_order_locked() {
        //参数，仓库id，sku数组，quantity
        $sku_list = isset($_POST['sku_list']) ? $_POST['sku_list'] : [];
        $warehouse_id = isset($_POST['warehouse_id']) ? $_POST['warehouse_id'] : 0;
        if(empty($warehouse_id) || empty($sku_list)) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '仓库id和sku列表不能为空'
                )
            );
        }
        foreach($sku_list as $sku_number => $quantity) {
            $this->MStock->update_info(
                array(
                    'stock_locked' => "stock_locked + {$quantity}"
                ),
                array(
                    'sku_number'   => $sku_number,
                    'warehouse_id' => $warehouse_id,
                )
            );
        }

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => '订单库存更新成功'
            )
        );
    }

    /**
     * @description 订单开始配送之后减掉锁定库存
     */
    public function decr_order_locked() {
        //参数，仓库id，sku数组，quantity
        $sku_list = isset($_POST['sku_list']) ? $_POST['sku_list'] : [];
        $warehouse_id = isset($_POST['warehouse_id']) ? $_POST['warehouse_id'] : 0;
        if(empty($warehouse_id) || empty($sku_list)) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '仓库id和sku列表不能为空'
                )
            );
        }
        foreach($sku_list as $sku_number => $quantity) {
            $this->MStock->update_info(
                array(
                    'stock_locked' => "stock_locked - {$quantity}"
                ),
                array(
                    'sku_number'   => $sku_number,
                    'warehouse_id' => $warehouse_id,
                )
            );
        }
        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => '订单库存回滚成功'
            )
        );
    }
}

/* End of file storage.php */
/* Location: ./application/controllers/storage.php */
