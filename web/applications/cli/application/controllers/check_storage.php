<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Check_storage extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MOrder_detail',
                'MSku',
                'MLine',
                'MStock',
            )
        );
    }

    /**
     * @description 检查计算的订单锁定库存和实际的订单锁定值是否相同，差距多少
     *              运行之前需要把已分拨的改成已出库
     */
    public function check() {
        echo date('Y-m-d H:i:s', time()) . "\n";
        //选出一个仓库对应的全部线路
        $warehouse_ids = array(
            2, //北京
            66,//上海
            72,//天津
        );

        foreach($warehouse_ids as $warehouse_id) {
            $lines = $this->MLine->get_lists(
                'id',
                array(
                    'warehouse_id' => $warehouse_id,
                    'site_src'     => C('site.dachu')
                )
            );
            if(empty($lines)) {
                echo "ERROR empty line for warehouse {$warehouse_id}\n";
                continue;
            }
            $line_ids = array_column($lines, 'id');
            $orders_in_line = $this->MOrder->get_lists(
                'id',
                array(
                    'site_src' => C('site.dachu'),
                    'in' => array(
                        'line_id' => $line_ids,
                        'status' => array(
                            C('order.status.wait_confirm.code'),
                            C('order.status.confirmed.code'),
                            C('order.status.wave_executed.code'),
                            C('order.status.picking.code'),
                            C('order.status.picked.code'),
                            C('order.status.checked.code'),
                            C('order.status.allocated.code'),
                        )
                    )
                )
            );
            $order_ids = array_column($orders_in_line, 'id');
            $order_details = $this->MOrder_detail->get_lists(
                'sku_number, quantity, name',
                array(
                    'in' => array(
                        'order_id' => $order_ids
                    )
                )
            );

            //计算这条线路上的所有sku和售卖量
            $sku_quantity_map  = [];
            foreach($order_details as $item) {
                $sku_number = $item['sku_number'];
                $quantity   = $item['quantity'];
                if(isset($sku_quantity_map[$sku_number])) {
                    $sku_quantity_map[$sku_number] += $quantity;
                } else {
                    $sku_quantity_map[$sku_number] = $quantity;
                }
            }
            $detail_skus = array_column($order_details, 'sku_number');
            $detail_name = array_column($order_details, 'name');
            $sku_to_name = array_combine($detail_skus, $detail_name);

            foreach($sku_quantity_map as $sku_number => $stock_locked_calc) {
                $stock = $this->MStock->get_one(
                    'stock_locked',
                    array(
                        'sku_number'   => $sku_number,
                        'warehouse_id' => $warehouse_id,
                    )
                );
                $name = $sku_to_name[$sku_number];

                if(!empty($stock)) {
                    $stock_locked = $stock['stock_locked'];
                    $minus = $stock_locked - $stock_locked_calc;
                    if($stock_locked_calc == $stock_locked) {
                       // echo "PASS: stock data for {$sku_number} in warehouse {$warehouse_id} matched number {$stock_locked_calc}\n";
                    } else {
                       // echo "NOT PASS: stock data for {$sku_number} in warehouse {$warehouse_id} not match db :{$stock_locked} calc {$stock_locked_calc} \n";
                        echo "{$sku_number}, {$name}, {$warehouse_id}, {$stock_locked}, {$stock_locked_calc}, {$minus}\n";
                    }
                } else {
                    echo "ERROR: stock data for {$sku_number} in warehouse {$warehouse_id} not exist\n";
                }
            }
        }


    }
}

/* End of file check_storage.php */
/* Location: ./application/controllers/check_storage.php */
