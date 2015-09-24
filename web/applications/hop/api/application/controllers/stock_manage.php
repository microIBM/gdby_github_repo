<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock_manage extends MY_Controller {

    private $_PAGE_SIZE = 20;

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                "MLine",
                "MOrder",
                "MSuborder",
                "MOrder_detail",
                'MMinus_stock_log'
            )
        );
        $this->load->library(
            array(
                'Wms_store',
                'order_split'
            )
        );
    }

    private function _get_deliver_info_by_req() {
        $request_time = $this->input->server('REQUEST_TIME');
        $request_hour = intval(date('H', $request_time));
        $deliver_date = strtotime(date('Y-m-d', $request_time));
        $deliver_time = 'today';

        if($request_hour == 23) {
            $deliver_date = $deliver_date + 86400;
        }

        $get = $this->input->get(NULL, TRUE);
        if(isset($get['deliver_date'])) {
            $deliver_date = strtotime($get['deliver_date']);
            if(isset($get['deliver_time'])) {
                $deliver_time = $get['deliver_time'];
            }
        }

        $where = [
            'deliver_date' => $deliver_date,
        ];
        return $where;
    }

    //销库存
    public function minus_stock($deliver_time = 0) {
        $order_types = $this->order_split->get_config();
        $order_type_codes = array_column($order_types, 'code');
        foreach($order_type_codes as $order_type) {
            $this->_minus_type_stock($order_type, $deliver_time);
        }
    }

    //销库存
    private function _minus_type_stock($order_type, $deliver_time) {
        //超时时间，防止超时
        ini_set("max_execution_time", "1800");

        $where = $this->_get_deliver_info_by_req();

        // 根据传入的条件，获取相关数据
        $request_data = $this->_order_type_res($where, $order_type, $deliver_time);
        $request_data = $this->_page_order_list($request_data, $this->_PAGE_SIZE);
        $request_data = $this->_filter_request_data($request_data);

        foreach($request_data as $item) {
            $res = $this->wms_store->create($item);
            $minus_log = array(
                'draft_date'   => $item['delivery_date'],
                'line_id'      => 0,
                'site_id'      => 0,
                'return_code'  => -111111,
                'return_msg'   => 'wms return empty',
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            );
            if(!empty($res)) {
                $minus_log = array(
                    'draft_date'   => $item['delivery_date'],
                    'line_id'      => 0,
                    'site_id'      => 0,
                    'return_code'  => $res['error_code'],
                    'return_msg'   => $res['error_message'] . '#' . $res['data'],
                    'created_time' => $this->input->server('REQUEST_TIME'),
                    'updated_time' => $this->input->server('REQUEST_TIME'),
                );
            }
            $this->MMinus_stock_log->create(
                $minus_log
            );
            sleep(1);
        }
    }

    //把没有product_list的元素unset掉
    private function _filter_request_data($request_data = array()) {
        $res = [];
        foreach($request_data as $item) {
            if(!empty($item['product_list'])) {
                $res[] = $item;
            }
        }
        return $res;
    }

    /**
     * 根据子订单类型拆分订单
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-06-30
     */
    private function _order_type_res($where = array(), $order_type = 1, $deliver_time) {

        //目前合法的线路
        $lines = $this->MLine->get_lists(
            'id, name, warehouse_id',
            array(
                'status' => C('line.status.valid')
            )
        );

        $order_type_config = $this->order_split->get_config();
        $order_type_ids = array_column($order_type_config, "code");
        $order_type_msgs = array_column($order_type_config, "msg");
        $order_type_map = array_combine($order_type_ids, $order_type_msgs);

        //特殊处理
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);

        //取订单的deliver_info
        $deliver_info = $where;
        $deliver_date = $deliver_info['deliver_date'];

        //取出在这些线路上的所有待出库的订单
        $where_query = array(
            'deliver_date' => $deliver_date,
            'order_type'   => $order_type,
            'in' => array(
                'status' => array(
                    C('order.status.confirmed.code'),
                    C('order.status.wave_executed.code'),
                    C('order.status.success.code'),
                    C('order.status.picking.code'),
                    C('order.status.picked.code'),
                    C('order.status.checked.code'),
                    C('order.status.allocated.code'),
                    C('order.status.delivering.code'),
                    C('order.status.loading.code'),
                    C('order.status.wait_comment.code'),
                    C('order.status.sales_return.code'),
                )
            )
        );
        // 如果设置了配送时间，则按照配送时间生成出库单
        if($deliver_time > 0) {
            $where_query['deliver_time'] = $deliver_time;
        }

        $final_res = array();
        foreach($line_ids as $line_id) {
            $where_query['line_id'] = $line_id;
            $orders_in_line = $this->MSuborder->get_lists('id', $where_query);
            $order_ids = array_column($orders_in_line, 'id');
            $order_details = [];
            if(!empty($order_ids)) {
                $order_details = $this->MOrder_detail->get_lists(
                    'sku_number, quantity',
                    array(
                        'in' => array(
                            'suborder_id' => $order_ids
                        )
                    ),
                    array(
                        'sku_number' => 'ASC'
                    )
                );
            }

            $product_list = $this->_group_quantity_by_sku($order_details);

            //根据仓库汇总不同订单类型的出库单
            $warehouse_id = $line_map[$line_id]['warehouse_id'];

            if(!isset($final_res[$warehouse_id])) {
                $final_res[$warehouse_id] = array(
                    'picking_type_id' => $warehouse_id,
                    'line_name'       => "{$order_type_map[$order_type]}出库单",
                    'delivery_date'   => date('Ymd', $deliver_date),
                    'delivery_time'   => $deliver_time,
                    'product_list'    => $product_list
                );
            } else {
                $products = array_merge($product_list, $final_res[$warehouse_id]['product_list']);
                $final_res[$warehouse_id]['product_list'] = $products;
            }
        }
        return array_values($final_res);
    }

    /**
     * 分页拆分出库单，每个出库单不能超过一定的行数
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-07-02
     */
    private function _page_order_list($data, $page = 200) {
        $final_res = array();
        foreach($data as $item) {
            $products = $item['product_list'];
            $total = count($products);
            $now = 0;
            while($now < $total) {
                $page_now = 0;
                $page_res = array();
                while($page_now < $page && $now < $total) {
                    $page_res[] = $products[$now];
                    $now ++;
                    $page_now ++;
                }
                $final_item = $item;
                $final_item['line_name'] .= "（" . ($now - $page_now + 1) . "-" . $now . "）";
                $final_item['product_list'] = $page_res;
                $final_res[] = $final_item;
            }
        }
        return $final_res;
    }


    //分大厨和大果站点的结果
    private function _site_res($site_id = 0, $where = array(), $order_type = 1, $deliver_time) {
        $res = [];
        $site_id = $site_id == C('site.daguo') ? C('site.daguo') : C('site.dachu');
        //目前合法的线路;
        $lines = $this->MLine->get_lists(
            'id, name, warehouse_id',
            array(
                'status' => C('line.status.valid')
            )
        );
        //特殊处理
        $lines[] = array(
            'id'           => 0,
            'warehouse_id' => 0,
            'name'         => '未分配'
        );
        //特殊处理
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);

        //有些订单为未分配线路

        //取订单的deliver_info
        $deliver_info = $where;
        $deliver_date = $deliver_info['deliver_date'];

        //取出在这些线路上的所有待出库的订单
        $where_query = array(
            'deliver_date' => $deliver_date,
            //'line_id'    => $line_id,
            'site_src'     => $site_id,
            'order_type'   => $order_type,
            'in' => array(
                'status' => array(
                    C('order.status.confirmed.code'),
                    C('order.status.wave_executed.code'),
                    C('order.status.success.code'),
                    C('order.status.picking.code'),
                    C('order.status.picked.code'),
                    C('order.status.checked.code'),
                    C('order.status.allocated.code'),
                    C('order.status.delivering.code'),
                    C('order.status.loading.code'),
                    C('order.status.wait_comment.code'),
                    C('order.status.sales_return.code'),
                )
            )
        );
        // 如果设置了配送时间，则按照配送时间生成出库单
        if($deliver_time > 0) {
            $where_query['deliver_time'] = $deliver_time;
        }
        foreach($line_ids as $line_id) {
            $where_query['line_id'] = $line_id;
            $orders_in_line = $this->MOrder->get_lists('id', $where_query);
            $order_ids = array_column($orders_in_line, 'id');
            $order_details = [];
            if(!empty($order_ids)) {
                $order_details = $this->MOrder_detail->get_lists(
                    'sku_number, quantity',
                    array(
                        'in' => array(
                            'order_id' => $order_ids
                        )
                    )
                );
            }

            $product_list = $this->_group_quantity_by_sku($order_details);
            //求和
            $res[] = array(
                'biz_type'        => $site_id,
                'line_id'         => $line_id,
                'picking_type_id' => $line_map[$line_id]['warehouse_id'],
                'line_name'       => $line_map[$line_id]['name'],
                'sku_type'        => $order_type,
                'delivery_date'   => date('Ymd', $deliver_date),
                'delivery_time'   => $deliver_time,
                'product_list'    => $product_list
            );
        }
        return $res;
    }

    //按照sku分组详情，求和
    private function _group_quantity_by_sku($order_details = array()) {
        $res = [];
        if(empty($order_details)) {
            return $res;
        }

        foreach($order_details as $item) {
            $sku_number = $item['sku_number'];
            if(isset($res[$sku_number])) {
                $qty = $res[$sku_number]['qty'] + $item['quantity'];
            } else {
                $qty = $item['quantity'];
            }

            $res[$sku_number] = array(
                'product_code' => $sku_number,
                'qty'          => $qty
            );
        }

        $res = array_values($res);
        return $res;
    }

}

/* End of file store_manage.php */
/* Location: ./application/controllers/store_manage.php */
