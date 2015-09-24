<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Init_stock extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MSku',
                'MLine',
                'MOrder',
                'MOrder_detail',
                'MStock'
            )
        );
        $this->load->library(
            array(
                'Dachu_request'
            )
        );
    }

    //初始化订单锁定的库存
    private function _update_stock_locked() {
        $current_date = date('Ymd', $this->input->server('REQUEST_TIME'));
        $current_date = strtotime($current_date);
        $orders = $this->MOrder->get_lists(
            'id, line_id',
            array(
                'deliver_date >' => $current_date,
                'in' => array(
                    'status' => array(
                        C('order.status.wait_confirm.code'),
                        C('order.status.confirmed.code'),
                    )
                )
            )
        );

        $lines = $this->MLine->get_lists('id, warehouse_id');
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);

        foreach($orders as $order) {
            $order_details = $this->MOrder_detail->get_lists(
                'sku_number, quantity',
                array(
                    'order_id' => $order['id']
                )
            );
            $line_id = $order['line_id'];
            $warehouse_id = isset($line_map[$line_id]) ? $line_map[$line_id]['warehouse_id'] : 0;
            if($warehouse_id === 0) {
                continue;
            }
            foreach($order_details as $order_detail) {
                $quantity   = $order_detail['quantity'];
                $sku_number = $order_detail['sku_number'];
                $this->db->query(
                    "update t_stock set stock_locked = stock_locked + {$quantity} where sku_number = {$sku_number} and warehouse_id = {$warehouse_id}"
                );
            }
            echo $this->db->last_query() . "\n";
        }
    }

    public function init_sku_stock() {
        $url = C('service.wms') . '/sku/getProductQuant';
        //post wms
        $sku_numbers = $this->MSku->get_lists(
            'sku_number',
            array(
                'status' => 1
            )
        );
        $sku_numbers = array_column($sku_numbers, 'sku_number');
        foreach($sku_numbers as $sku_number) {
            $request_param = array(
                'sku_codes' => array($sku_number),
                'wh_id' => 0
            );

            echo "\nrequest {$sku_number}\n";

            $response = $this->dachu_request->post($url, $request_param);
            $response = json_decode($response['res'], TRUE);
            if(empty($response) || $response['status'] != 0) {
                //TODO wms响应异常记日志
                echo "json_decode error or wms returned error status \n";
                print_r($response);
                continue;
            }

            print_r($response);
            $response_list = $response['list'];
            /*
             * list {
             *   sku_number => {
             *      warehouse_id => {
             *          qualified_qty => '数字' 这个是合格品库存
             *          //商城端直接把后端传过来的小数抹掉，因为售卖的是成品
             *      }
             *   }
             * }
             */
            foreach($response_list as $sku_number => $data) {
                foreach($data as $warehouse_id => $val) {

                    //check exist，if not, insert
                    $stock_record = $this->MStock->get_one(
                        '*',
                        array(
                            'sku_number' => $sku_number,
                            'warehouse_id' => $warehouse_id
                        )
                    );

                    if(empty($stock_record)) {
                        $record_id = $this->MStock->create(['sku_number' => $sku_number, 'warehouse_id' => $warehouse_id]);
                    }

                    $in_stock_val = intval($val['qualified_qty']);
                    //更新在库库存
                    $update_res = $this->MStock->update_info(
                        array(
                            'in_stock' => $in_stock_val
                        ),
                        array(
                            'sku_number'   => $sku_number,
                            'warehouse_id' => $warehouse_id
                        )
                    );
                    echo $this->db->last_query();
                    echo "\n";
                    //TODO update_res成功失败都需要记日志，需要记录变成了多少，和时间戳
                }
            }
        }
        //订单锁定库存
        $this->_update_stock_locked();
    }

}

/* End of file init_stock.php */
/* Location: ./application/controllers/init_stock.php */
