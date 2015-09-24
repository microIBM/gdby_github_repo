<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock_service extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->library(
            array(
                'RedisClient',
                'Dachu_request'
            )
        );
        $this->load->model(
            array(
                'MStock',
                'MOrder_detail',
                'MLine',
                'MOrder',
            )
        );
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 对库存变化通知的notice做数据格式校验
     */
    private function _validate_stock_notice($notice_arr = array()) {
        //post数据必须是数组
        if(!is_array($notice_arr) || empty($notice_arr)) {
            return array(FALSE, '参数为空或不是数组');
        }

        //post数据不能缺失字段
        if(empty($notice_arr['type'])) {
            return array(FALSE, '没有指定库存变化type');
        }
        if(empty($notice_arr['data'])) {
            return array(FALSE, '没有指定库存变化data');
        }

        switch($notice_arr['type']) {
        case "change" : //库存变动
            $data = $notice_arr['data'];
            if(empty($data['pro_code']) || !is_array($data['pro_code']) || empty($data['msg'])) {
                return array(FALSE, 'pro_code，wh_id或者msg不能为空，pro_code必须是数组');
            }
            break;
        case "out": //订单出库
            $data = $notice_arr['data'];
            if(empty($data['suborder_id']) || !is_array($data['suborder_id']) || empty($data['msg']) || empty($data['wh_id'])) {
                return array(FALSE, 'wh_id不能为空，suborder_id不能为空，msg不能为空，suborder_id必须是数组');
            }
            break;
        default:
            return array(FALSE, '不能识别的type字段');
            break;
        }

        return array(TRUE, '');
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 提供给wms的通知接口，只做插入redis操作
     * 用rpush和lpop
     */
    public function notice_stock_update() {
        //校验。
        list($validate_res, $validate_msg) = $this->_validate_stock_notice($_POST);
        //验证结果有问题，直接返回错误
        if(!$validate_res) {
            $this->_return_json(['status' => -1, 'msg' => $validate_msg]);
        }

        //notice数据没问题，插redis
        $lpush_result = $this->redisclient->lpush(C('storage.redis_key.wms_update_queue2'), json_encode($_POST));

        if($lpush_result) {
            $this->_return_json(['status' => 0, 'msg' => 'notice success!!']);
        } else {
            $this->_return_json(['status' => -1, 'msg' => 'notice fucked!!']);
        }
    }

    /**
     * @description 获取wms在库库存
     */
    private function _post_and_update_in_stock($sku_numbers = array(), $warehouse_id = 0) {
        $url = C('service.wms') . '/sku/getProductQuant';
        //简单post wms的api，得到这些sku在每一个仓库里的在库量
        //得到之后需要更新数据库
        if(empty($sku_numbers)) {
            return;
        }

        $request_param = array(
            'sku_codes' => $sku_numbers,
            'wh_id' => $warehouse_id
        );

        $response = $this->dachu_request->post($url, $request_param);
        $response = json_decode($response['res'], TRUE);
        if($response['status'] != 0) {
            //TODO 记错误日志
            echo "wms returned error status\n";
            print_r($response);
            return;
        }


        //response status为0，wms响应正常
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
                    'count(1) cnt',
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
                //TODO update_res成功失败都需要记日志，需要记录变成了多少，和时间戳
            }
        }

    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 计算并消减订单库存
     */
    private function _calc_and_minus_stock_locked($suborder_ids = array(), $warehouse_id = 0) {
        if(empty($suborder_ids)) {
            return;
        }
        //直接取这些订单的订单详情，然后计算出sku_number => 总quantity
        //然后减去订单锁定库存即可
        $order_details = $this->MOrder_detail->get_lists(
            'sku_number, quantity',
            array(
                'in' => array(
                    'id' => $suborder_ids
                )
            )
        );
        $sku_number_to_quantity = [];
        foreach($order_details as $order_detail) {
            $sku_number = $order_detail['sku_number'];
            $quantity = $order_detail['quantity'];
            if(!isset($sku_number_to_quantity[$sku_number])) { //如果没设置过
                $sku_number_to_quantity[$sku_number] = $quantity;
            } else {
                $sku_number_to_quantity[$sku_number] += $quantity;
            }
        }

        foreach($sku_number_to_quantity as $sku_number => $quantity) {
            //TODO 这里之后如果update_res异常需要打log
            $update_res = $this->MStock->update_without_escape(
                array(
                    'stock_locked' => "IF(stock_locked- {$quantity} < 0, 0 , stock_locked - {$quantity})"
                ),
                array(
                    'sku_number' => $sku_number,
                    'warehouse_id' => $warehouse_id
                )
            );
        }
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 更新在库库存
     * 一段一段地从wms_update_queue2里读取最新的需要更新库存的信息
     * 取用lrange
     */
    public function update_amount_in_stock() {
        set_time_limit(0);
        while(TRUE) {
            //有需要更新库存的数据，每次先pop再处理
            //这个后期可以改成LRS的消费者，现在没有必要。
            //处理失败就把该数据放到bad_request_queue里
            $update_item_json = $this->redisclient->lpop(C('storage.redis_key.wms_update_queue2'));
            if(empty($update_item_json)) {
                break;
            }
            $update_item = json_decode($update_item_json, TRUE);

            //验证数据是否合法
            list($validate_res, $validate_msg) = $this->_validate_stock_notice($update_item);
            //验证结果有问题，将该数据放在wms_update_bad_request_queue
            if(!$validate_res) {
                $this->redisclient->rpush(C('storage.redis_key.wms_update_bad_request_queue'), json_encode($update_item_json));
                continue;
            }

            //数据正常，走更新在库库存逻辑，update_item
            try {
                switch($update_item['type']) {
                case 'change': //普通库存变动
                    $data = $update_item['data'];
                    $prod_codes = $data['pro_code'];
                    $warehouse_id = empty($data['wh_id']) ? 0 : $data['wh_id'];
                    $this->_post_and_update_in_stock($prod_codes, $warehouse_id);
                    break;
                case 'out': //订单出库
                    $data = $update_item['data'];
                    $warehouse_id = empty($data['wh_id']) ? 0 : $data['wh_id'];
                    $this->_calc_and_minus_stock_locked($data['suborder_id'], $warehouse_id);
                    break;
                default:
                    $this->redisclient->rpush(C('storage.redis_key.wms_update_bad_request_queue'), json_encode($update_item_json));
                    break;
                }
            } catch(Exception $e) {
                $this->redisclient->rpush(C('storage.redis_key.wms_update_bad_request_queue'), $update_item_json);
                $this->_return_json(['status' => -1, 'msg' => $e->getMessage()]);
            }
        }
        $this->_return_json(['status' => 0, 'msg' => 'update stock success']);
    }



    /**
     * @description 提供给商城用的接口，根据母订单的id来锁定库存
     */
    public function incr_stock_locked() {
        if(empty($_POST['products'])) {
            $this->_return_json(['status' => -1, 'msg' => '商品不能为空']);
        }

        if(empty($_POST['line_id'])) {
            $this->_return_json(['status' => -1, 'msg' => '线路id不能为空']);
        }

        $products = $_POST['products'];
        $line_id = intval($_POST['line_id']);

        //找出warehouse_id
        $line = $this->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );

        if(empty($line)) {
            $this->_return_json(['status' => -1, 'msg' => '找不到该线路的仓库id']);
        }
        $warehouse_id = $line['warehouse_id'];

        foreach($products as $product) {
            $sku_number = $product['sku_number'];
            $quantity = $product['quantity'];
            $update_res = $this->MStock->update_without_escape(
                array(
                    'stock_locked' => "stock_locked + {$quantity}"
                ),
                array(
                    'sku_number'   => $sku_number,
                    'warehouse_id' => $warehouse_id
                )
            );
            //TODO update的结果需要记日志
        }
        $this->_return_json(['status' => 0, 'msg' => 'success']);
    }

    /**
     * @description 提供给商城用的接口，根据母订单的id来减少锁定库存
     *     一般是在订单取消的时候用
     */
    public function decr_stock_locked() {
        if(empty($_POST['order_id'])) {
            $this->_return_json(['status' => -1, 'msg' => '订单id不能为空']);
        }

        $order_id = $_POST['order_id'];
        $order = $this->MOrder->get_one(
            'line_id',
            array(
                'id' => $order_id
            )
        );
        if(empty($order)) {
            $this->_return_json(['status' => -1, 'msg' => '根本没有这样的order']);
        }

        $line_id = $order['line_id'];
        $products = $this->MOrder_detail->get_lists(
            'sku_number, quantity',
            array(
                'order_id' => $order_id
            )
        );

        //找出warehouse_id
        $line = $this->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );

        if(empty($line)) {
            $this->_return_json(['status' => -1, 'msg' => '找不到该线路的仓库id']);
        }

        $warehouse_id = $line['warehouse_id'];

        foreach($products as $product) {
            $sku_number = $product['sku_number'];
            $quantity = $product['quantity'];
            $update_res = $this->MStock->update_without_escape(
                array(
                    'stock_locked' => "IF(stock_locked - {$quantity} < 0, 0, stock_locked - {$quantity})"
                ),
                array(
                    'sku_number'   => $sku_number,
                    'warehouse_id' => $warehouse_id
                )
            );
            //TODO update的结果需要记日志
        }
        $this->_return_json(['status' => 0, 'msg' => 'success']);
    }

    /**
     * @description 设置虚拟库存
     */
    public function set_virtual_stock() {
        if(!$_POST['sku_number']|| !$_POST['warehouse_id'] || !isset($_POST['value'])) {
            $this->_return_json(['status' => 0, 'msg' => 'sku_number和warehouse_id不能为空']);
        }

        $sku_number = $_POST['sku_number'];
        $warehouse_id = $_POST['warehouse_id'];
        $value = $_POST['value'];
        $update_res = $this->MStock->update_info(
            array(
                'virtual_stock' => $value
            ),
            array(
                'sku_number'   => $sku_number,
                'warehouse_id' => $warehouse_id
            )
        );

        if($update_res) {
            $this->_return_json(['status' => 0, 'msg' => 'success']);
        }

        $this->_return_json(['status' => -1, 'msg' => 'fail']);
    }

    /**
     * @description 检查库存
     */
    public function check_storage() {
        if(empty($_POST['products']) || empty($_POST['line_id'])) {
            $this->_return_json(['status' => -1, 'msg' => 'products和line_id不能为空']);
        }

        $line_id = $_POST['line_id'];
        $line = $this->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );
        $warehouse_id = !empty($line) ? $line['warehouse_id'] : 0;

        $products = $_POST['products'];
        $sku_numbers = array_column($products, 'sku_number');
        $stock_records = $this->MStock->get_lists(
            array(
                'sku_number, in_stock, virtual_stock, stock_locked'
            ),
            array(
                'in' => array(
                    'sku_number' => $sku_numbers
                ),
                'warehouse_id' => $warehouse_id
            )
        );
        $sku_to_stock = array_column($stock_records, NULL, 'sku_number');
        foreach($products as &$product) {
            $sku_number        = $product['sku_number'];
            $quantity          = isset($product['quantity']) ? $product['quantity'] : 0;
            //列表页也会调用这个接口，但不会传quantity
            $collect_type      = $product['collect_type'];

            $stock_rec         = isset($sku_to_stock[$sku_number]) ?
                $sku_to_stock[$sku_number]
                :
                [
                    'virtual_stock' => -1,
                    'in_stock'      => 0,
                    'stock_locked'  => 0
                ];
            $virtual_stock_val = $stock_rec['virtual_stock'];
            $in_stock_val      = $stock_rec['in_stock'];
            $stock_locked_val  = $stock_rec['stock_locked'];

            //设置了虚拟库存走虚拟库存
            if($stock_rec['virtual_stock'] >= 0) {
                if($virtual_stock_val >= $quantity) {
                    $product['stock_enough_flag'] = 1;
                } else {
                    $product['stock_enough_flag'] = 0;
                }
                $product['storage'] = $virtual_stock_val - $stock_locked_val;
                //如果调用时没有设置quantity，需要把stock_enough_flag unset掉
                continue;
            }

            //没设置虚拟库存，那么以现采、预采为准来判断
            if($collect_type == C('foods_collect_type.type.pre_collect.value')) {
                if(!isset($sku_to_stock[$sku_number])) {
                    $product['stock_enough_flag'] = 0;
                }
                //预采，以实时库存为准
                if($stock_rec['in_stock'] >= $quantity) {
                    $product['stock_enough_flag'] = 1;
                } else {
                    $product['stock_enough_flag'] = 0;
                }
                $product['storage'] = $in_stock_val - $stock_locked_val;
                continue;
            }

            if($collect_type == C('foods_collect_type.type.now_collect.value')) {
                //现采，库存始终无限大
                $product['stock_enough_flag'] = 1;
                $product['storage'] = -1;
                continue;
            }

            $product['stock_enough_flag'] = 1;
        }
        unset($product);

        //如果前端没传quantity，需要把stock_enough_flag干掉
        foreach($products as &$product) {
            if(!isset($product['quantity'])) {
                unset($product['stock_enough_flag']);
            }
        }
        unset($product);

        $this->_return_json(['status' => 0, 'msg' => 'success', 'list' => $products]);
    }
}

/* End of file stock_service.php */
/* Location: ./application/controllers/stock_service.php */
