<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @description 库存相关的，商城侧供odoo调用的接口
 * @author caochunhui@dachuwang.com
 */

class Mall_stock extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->library(
            array(
                'Dachu_request',
                'RedisClient',
            )
        );
        $this->load->model(
            array(
                'MStock'
            )
        );
    }


    public function notice_stock_update() {
        $notice_arr = $_POST;
        $update_arr = [];
        if(!is_array($notice_arr) || empty($notice_arr)) {
            echo json_encode(
                array(
                    'error_code' => -1,
                    'msg'        => '参数不是数组，格式错误，或为空数组',
                )
            );
            return;
        }
        //检验数据格式
        foreach($notice_arr as $notice_item) {
            if(!isset($notice_item['product_code']) || !isset($notice_item['type']) || !isset($notice_item['qty'])) {
                echo json_encode(
                    array(
                        'error_code' => -1,
                        'msg'        => '数据格式错误，缺失必要数据字段',
                    )
                );
                return;
            }
            $update_arr[] = json_encode($notice_item);
        }
        //数据格式没问题，插redis

        $res = [
            'error_code' => 0,
            'msg'        => 'success',
        ];

        $lpush_res = $this->redisclient->lpush(C('storage.redis_key.wms_update_queue'), $update_arr);

        if(!$lpush_res) {
            $res = [
                'error_code' => -1,
                'msg'        => 'notice failed',
            ];
        }
        echo json_encode($res);
    }

    public function update_mall_stock() {
        //这个需要从redis的update_queue里去取
        set_time_limit(0);
        $update_queue_name = C('storage.redis_key.wms_update_queue');
        $update_arr = [];
        $update_arr = $this->redisclient->lrange(C('storage.redis_key.wms_update_queue'), 0, -1);

        if(empty($update_arr)) {
            echo json_encode(
                array(
                    'status' => 0,
                    'msg'    => 'no need to update'
                )
            );
            return;
        }

        print_r($update_arr);
        //需要先循环判断一次看看是不是outgoing，如果是的话需要把订单锁定的库存给减掉
        foreach($update_arr as &$update_item) {
            $update_item = json_decode($update_item, TRUE);
            if($update_item['type'] == 'outgoing') {
                $minus_qty = $update_item['qty'];
                $sku_number = $update_item['product_code'];
                $warehouse_id = $update_item['picking_type_id'];
                echo "-------------------\n";
                echo "updating stock_locked of {$sku_number}\n";
                /*$this->MStock->update_info(
                    array(
                        'stock_locked' => "stock_locked - {$minus_qty}"
                    ),
                    array(
                        'sku_number'     => $sku_number,
                        //'warehouse_id' => $update_arr['picking_type'],
                    )
                );*/
                $this->db->query(
                    "update t_stock "
                    . "set stock_locked = IF(stock_locked - {$minus_qty} < 0, 0, stock_locked - {$minus_qty}) "
                    . "where sku_number = {$sku_number} and warehouse_id = {$warehouse_id}"
                );
                echo $this->db->last_query() . "\n";
                echo "-------------------\n";
            }
        }
        unset($update_item);

        $sku_numbers = array_column($update_arr, 'product_code');

        //sku更新队列非空，需要请求wms来更新redis

        echo "================\n";
        echo "start to post wms ---------\n";
        $post_res = $this->dachu_request->post(
            C('service.api').'/odoo_stock/get_product_quant',
            //'http://api.test.dachuwang.com/odoo_stock/get_product_quant',
            $sku_numbers
        );
        print_r($post_res);
        echo "================\n";

        if($post_res['status'] != 0) {
            //记个错误log
            echo json_encode(
                array(
                    'status' => -1,
                    'msg'    => $post_res['msg']
                )
            );
            // modified by caiyilong 不要停止程序，不然会被重复扣减订单库存
            //return;
        }

        $result = json_decode($post_res['res'], TRUE);
        if($result['error_code'] != 0) {
            //记个错误log
            echo json_encode(
                array(
                    'status' => -1,
                    'msg'    => 'decode error'
                )
            );
            // modified by caiyilong 不要停止程序，不然会被重复扣减订单库存
            //return;
        }

        $data = $result['data'];
        //更新redis库存
        foreach($data as $sku_number => $store_arr) {
            foreach($store_arr as $warehouse_id => $store_num) {
                $key_pattern = C('storage.redis_key.main_key_pattern');
                $key = str_replace('{{warehouse_id}}', $warehouse_id, $key_pattern);
                $key = str_replace('{{sku_number}}', $sku_number, $key);
                $field = C('storage.redis_key.in_stock_key');
                $this->redisclient->hset($key, $field, $store_num);
                //还需要往数据库里插
                //check exists
                $record = $this->MStock->get_one(
                    '*',
                    array(
                        'status'       => 1,
                        'sku_number'   => $sku_number,
                        'warehouse_id' => $warehouse_id
                    )
                );
                if(empty($record)) {
                    $this->MStock->create(
                        array(
                            'sku_number'   => $sku_number,
                            'warehouse_id' => $warehouse_id,
                            'created_time' => $this->input->server('REQUEST_TIME'),
                            'updated_time' => $this->input->server('REQUEST_TIME'),
                            'in_stock'     => $store_num
                        )
                    );
                } else {
                    $this->MStock->update_info(
                        array(
                            'in_stock' => $store_num
                        ),
                        array(
                            'id'       => $record['id']
                        )
                    );
                }
            }
        }

        echo "==============\n";
        echo "start to pop redis \n";
        //更新过后删除队列中的内容
        foreach($sku_numbers as $item) {
            $sku_in_list = $this->redisclient->rpop(C('storage.redis_key.wms_update_queue'));
            $temp_arr = json_decode($sku_in_list, TRUE);
            $sku_in_redis = $temp_arr['product_code'];
            echo "sku_in_redis is {$sku_in_redis}\n";

            //防止pop的内容出错
            if(!in_array($sku_in_redis, $sku_numbers)) {
                $this->redisclient->lpush(C('storage.redis_key.wms_update_queue'), $sku_in_list);
            }
            echo "sku_numbers is " . print_r($sku_numbers);

        }
        echo "==============\n";

        echo json_encode(
            array(
                'status'           => 0,
                'msg'              => 'success',
                //'update_stock_res' => $mset_res
            )
        );
    }

    //TODO 把redis里的库存数据更新到数据库
    public function flush_redis_data_to_database() {
    }

    //TODO 在redis挂了以后把数据库里的数据恢复到redis
    public function flush_database_data_to_redis() {
    }

}

/* End of file mall_stock.php */
/* Location: ./application/controllers/mall_stock.php */
