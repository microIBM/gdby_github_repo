<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author caochunhui@dachuwang.com
 * @description 波次生产
 */

class Wave extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MSuborder',
                'MOrder_detail',
                'MWave',
                'MPick_task',
                'MLine',
                'MWorkflow_log',
                'MLocation'
            )
        );
        $this->load->library(
            array(
                'skip32'
            )
        );

        $code_with_deliver_time = array_values(C('order.deliver_time'));
        $codes                  = array_column($code_with_deliver_time, 'code');
        $msg                    = array_column($code_with_deliver_time, 'msg');
        $this->_deliver_dict    = array_combine($codes, $msg);

        //订单状态和对应中文字典
        $code_with_cn = array_values(C('order.status'));
        $codes        = array_column($code_with_cn, 'code');
        $msg          = array_column($code_with_cn, 'msg');
        $this->_status_dict = array_combine($codes, $msg);

        //unit_id  => unit_name
        $unit_config = C('unit');
        $codes       = array_column($unit_config, 'id');
        $msg         = array_column($unit_config, 'name');
        $this->_unit_dict = array_combine($codes, $msg);
        $this->_unit_dict[0] = '无';
    }

    /**
     * @description 为指定配送时间的订单分配波次
     * @author caochunhui@dachuwang.com
     */
    public function create_wave() {
        $deliver_date = $_POST['deliver_date'];
        //$site_src = (isset($_POST['site_id']) && $_POST['site_id'] == C('site.daguo')) ? C('site.daguo') : C('site.dachu');
        $site_src = C('site.dachu');
        $cur = isset($_POST['cur']) ? $_POST['cur'] : NULL;
        $order_type = $_POST['order_type']; 
        //配送时间选项，参数为空选全部
        $deliver_time_dachu_options = array_column(array_values(C('order.deliver_time')), 'code');
        $deliver_time_daguo_options = array_column(array_values(C('order.deliver_time_guo')), 'code');
        $deliver_time_options = array_merge($deliver_time_daguo_options, $deliver_time_dachu_options);
        $deliver_time_options = array_unique($deliver_time_options);
        $deliver_time =
            !empty($_POST['deliver_time']) ? array(intval($_POST['deliver_time'])) : $deliver_time_options;

        //波次类型，1自动；2手动
        $wave_type = (isset($_POST['wave_type']) && $_POST['wave_type'] == C('wave.wave_type.auto.code')) ? C('wave.wave_type.auto.code') : C('wave.wave_type.manual.code');


        //城市id，不选不让创建
        $city_id = !empty($_POST['city_id']) ? intval($_POST['city_id']) : 0;


        if($city_id == 0) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '必须选择一个城市，然后再创建波次'
                )
            );
        }
        if($city_id == 804) {//针对北京北仓的临时方案
           //获取北仓的线路列表，设置not in
           $warehouse_ids = array('6', '8');
           $line = $this->MLine->get_lists(
                '*',
                array(
                    'in' => array('warehouse_id' => $warehouse_ids) 
                )
           );
           $line_ids = array_column($line, 'id'); 
        }

        $map = array(
                'city_id'      => $city_id,
                'deliver_date' => $deliver_date,
                //'site_src'     => $site_src,
                'status'       => C('order.status.confirmed.code'),
                'order_type'   => $order_type, 
                'in' => array(
                    'deliver_time' => $deliver_time
                )
        );
        if(!empty($line_ids)) {
            $map['not_in'] = array(
                'line_id' => $line_ids
            );
        }

        //创建波次时，不选择冻品订单
        $orders = $this->MSuborder->get_lists(
            'id',
            $map
        );
        if(empty($orders)) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => '没有需要生成波次的订单'
                )
            );
        }

        $order_ids = array_column($orders, 'id');
        $order_cnt = count($order_ids);
        $line_cnt = $this->MOrder_detail->get_one(
            'count(*) cnt',
            array(
                'in' => array(
                    'suborder_id' => $order_ids
                )
            )
        );
        $line_cnt = empty($line_cnt) ? 0 : $line_cnt['cnt'];
        $wave = array(
            'order_count'  => $order_cnt,
            'line_count'   => $line_cnt,
            'wave_type'    => $wave_type,
            'site_src'     => $site_src,
            'city_id'      => $city_id,
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME'),
        );
        $wave_id = $this->MWave->create(
            $wave
        );

        if(!$wave_id) {
            $this->_return_json(
                ['status' => -1, 'msg' => '创建波次失败', 'res' => "wave id is {$wave_id}"]
            );
        }

        $update_res = $this->MSuborder->update_info(
            array(
                'wave_id' => $wave_id,
                'status'  => C('order.status.wave_executed.code')
            ),
            array(
                'status'  => C('order.status.confirmed.code'),
                'in' => array(
                    'id' => $order_ids
                )
            )
        );

        $detail_update_res = $this->MOrder_detail->update_info(
            array(
                'status' => C('order.status.wave_executed.code'),
            ),
            array(
                'status'  => C('order.status.confirmed.code'),
                'in' => array(
                    'suborder_id' => $order_ids
                )
            )
        );

        //插log，波次操作、订单操作
        $this->MWorkflow_log->record_wave($wave_id, C('wave.status.valid'), $cur, '', '创建波次');
        foreach($order_ids as $order_id) {
            $this->MWorkflow_log->record_order($order_id, C('order.status.wave_executed.code'), $cur);
        }

        $this->_return_json(
            array(
                'status'  => 0,
                'msg'     => '创建波次成功',
                'res'     => "update wave id of order to {$wave_id}",
                'wave_id' => "{$wave_id}",
            )
        );
    }

    /**
     * @description 释放波次，创建分拣任务
     */
    public function create_pick_task() {
        $cur = isset($_POST['cur']) ? $_POST['cur'] : NULL;
        //波次信息合法性验证
        $wave_id = isset($_POST['wave_id']) ? intval($_POST['wave_id']) : 0;
        if(!$wave_id) {
            $this->_return_json(array('status' => -1, 'msg' => 'empty wave id'));
        }

        $wave_info = $this->MWave->get_one(
            '*',
            array(
                'id' => $wave_id
            )
        );
        if(!$wave_info) {
            $this->_return_json(array('status' => -1, 'msg' => 'empty wave id'));
        }

        $site_src = $wave_info['site_src'];
        $city_id = $wave_info['city_id'];

        $res = [];

        //获取该波次对应的所有订单
        $orders = $this->MSuborder->get_lists(
            'id, line_id',
            array(
                'wave_id' => $wave_id,
                'status'  => C('order.status.wave_executed.code')
            )
        );

        $order_ids = array_column($orders, 'id');

        //按照线路对订单分组
        $grouped_orders = [];
        foreach($orders as $item) {
            $line_id = $item['line_id'];
            if(isset($grouped_orders[$line_id])) {
                $grouped_orders[$line_id][] = $item;
            } else{
                $grouped_orders[$line_id] = array(
                    $item
                );
            }
        }

        //先把所有的详情更新，否则可能会很麻烦。
        $detail_update_res = $this->MOrder_detail->update_info(
            array(
                'status' => C('order.status.picking.code'),
            ),
            array(
                'in' => array(
                    'suborder_id' => $order_ids
                ),
                'status' => C('order.status.wave_executed.code')
            )
        );

        $pick_task_ids = [];
        //每一个线路创建一个分拣任务
        foreach($grouped_orders as $line_id => $item) {
            //$sku_count
            $order_ids = array_column($item, 'id');
            $sku_count = $this->MOrder_detail->get_one(
                'count(distinct sku_number) cnt',
                array(
                    'in' => array(
                        'suborder_id' => $order_ids
                    )
                )
            );
            $sku_count = empty($sku_count) ? 0 : $sku_count['cnt'];
            $pick_task = array(
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
                'wave_id'      => $wave_id,
                'line_id'      => $line_id,
                'sku_count'    => $sku_count,
                'site_src'     => $site_src,
                'city_id'      => $city_id
            );
            $pick_task_id = $this->MPick_task->create(
                $pick_task
            );

            if(!$pick_task_id) {
                $res['pick_task'][] = "pick task for line {$line_id} create failed";
                continue;
            }

            //成功插入分拣任务，创建分拣编号
            $pick_number = $this->skip32->get_serial_no($pick_task_id);
            $this->MPick_task->update_info(
                array(
                    'pick_number' => $pick_number
                ),
                array(
                    'id' => $pick_task_id
                )
            );

            $pick_task_ids[] = $pick_task_id;

            //pick task insert success
            $update_res = $this->MSuborder->update_info(
                array(
                    'pick_task_id' => $pick_task_id,
                    'status'       => C('order.status.picking.code')
                ),
                array(
                    'wave_id' => $wave_id,
                    'line_id' => $line_id,
                    'status'  => C('order.status.wave_executed.code')
                )
            );

            if($update_res) {
                $res['pick_task'][] = "pick task for line {$line_id} create success";
            } else {
                $res['pick_task'][] = "pick task for line {$line_id} create failed";
            }
        }

        $update_res = $this->MWave->update_info(
            array(
                'pick_task_created' => 1, //需要做个简单配置
            ),
            array(
                'id' => $wave_id
            )
        );

        if(!$update_res) {
            $res['wave_update'] = "wave {$wave_id} update failed";
        } else {
            $res['wave_update'] = "wave {$wave_id} update success";
        }

        //记log，记释放的波次，创建的分拣任务和相关的订单
        $this->MWorkflow_log->record_wave($wave_id, C('wave.task_created.created.code'), $cur, '', '释放波次');
        foreach($pick_task_ids as $pick_task_id) {
            $this->MWorkflow_log->record_pick_task($pick_task_id, C('pick_task.status.started.code'), $cur, '', '创建分拣任务');
        }

        //记下相关订单的状态更新
        $orders = $this->MSuborder->get_lists(
            'id',
            array(
                'wave_id' => $wave_id
            )
        );
        if(!empty($orders)) {
            $order_ids = array_column($orders, 'id');
            foreach($order_ids as $order_id) {
                $this->MWorkflow_log->record_order($order_id, C('order.status.picking.code'), $cur, '');
            }
        }

        $this->_return_json(
            array(
                'status'       => 0,
                'msg_internal' => "波次 {$wave_id} 创建任务成功",
                'msg'          => "已开始分拣",
                'res'          => $res
            )
        );
    }

    public function finish_task() {
        $cur = isset($_POST['cur']) ? $_POST['cur'] : NULL;
        //分拣任务id
        $pick_task_id = isset($_POST['pick_task_id']) ? intval($_POST['pick_task_id']) : 0;
        if(!$pick_task_id) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty pick task id',
                )
            );
        }
        //更新分拣任务表里的分拣任务状态为已完成
        $pick_task_update_res = $this->MPick_task->update_info(
            array(
                'status' => C('pick_task.status.finished.code'),
            ),
            array(
                'id' => $pick_task_id
            )
        );

        $order_update_res = $this->MSuborder->update_info(
            array(
                //'status' => C('order.status.picked.code')
                'status' => C('order.status.checked.code')
            ),
            array(
                'pick_task_id' => $pick_task_id,
                'status' => C('order.status.picking.code')
            )
        );

        $res = array(
            'pick_update_res'  => $pick_task_update_res,
            'order_update_res' => $order_update_res
        );

        $this->MWorkflow_log->record_pick_task($pick_task_id, C('wave.status.valid'), $cur, '', '完成分拣任务');
        //需要记订单状态
        $orders = $this->MSuborder->get_lists(
            'id',
            array(
                'pick_task_id' => $pick_task_id,
                //'status'       => C('order.status.picked.code'),
                'status'       => C('order.status.checked.code'),
            )
        );
        if(!empty($orders)) {
            //还需要改detail的状态
            $order_ids = array_column($orders, 'id');
            $this->MOrder_detail->update_info(
                array(
                    //'status' => C('order.status.picked.code')
                    'status' => C('order.status.checked.code')
                ),
                array(
                    'status' => C('order.status.picking.code'),
                    'in' => array(
                        'suborder_id' => $order_ids
                    )
                )
            );
            foreach($order_ids as $order_id) {
                $this->MWorkflow_log->record_order($order_id, C('order.status.checked.code'), $cur, '');
            }
        }

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => "分拣任务{$pick_task_id}完成",
                'res'    => $res
            )
        );
    }


    /*
     * @description
     */
    public function delete_wave() {
        $cur = isset($_POST['cur']) ? $_POST['cur'] : NULL;

        $wave_ids = (isset($_POST['wave_ids']) && is_array($_POST['wave_ids'])) ? $_POST['wave_ids'] : [];
        if(empty($wave_ids)) {
            $res = [
                'status' => -1,
                'msg'    => 'wave_ids为空'
            ];
            $this->_return_json($res);
        }

        //查一次，只删除未创建分拣任务的订单
        $waves = $this->MWave->get_lists(
            'id',
            array(
                'pick_task_created' => C('wave.task_created.pending.code'),
                'in' => array(
                    'id' => $wave_ids,
                )
            )
        );

        if(count($wave_ids) != count($waves)) {
            $res = [
                'status' => -1,
                'msg'    => '不能删除已创建分拣任务的订单'
            ];
            $this->_return_json($res);
        }

        $wave_ids = array_column($waves, 'id');

        $update_res = $this->MWave->update_info(
            array(
                'status' => C('wave.status.deleted')
            ),
            array(
                'in' => array(
                    'id' => $wave_ids
                )
            )
        );

        //删除波次log
        foreach($wave_ids as $wave_id) {
            $this->MWorkflow_log->record_wave($wave_id, C('wave.status.deleted'), $cur, '', '删除波次');
        }
        //需要记订单状态
        $orders = $this->MSuborder->get_lists(
            'id',
            array(
                'in' => array(
                    'wave_id' => $wave_ids
                )
            )
        );

        if(!empty($orders)) {
            $order_ids = array_column($orders, 'id');
            foreach($order_ids as $order_id) {
                $this->MWorkflow_log->record_order($order_id, C('order.status.confirmed.code'), $cur);
            }

            //更新detail
            $detail_update_res = $this->MOrder_detail->update_info(
                array(
                    'status' => C('order.status.confirmed.code')
                ),
                array(
                    'status' => C('order.status.wave_executed.code'),
                    'in' => array(
                        'suborder_id' => $order_ids
                    ),
                )
            );
        }

        $order_update_res = $this->MSuborder->update_info(
            array(
                'status'  => C('order.status.confirmed.code'),
                'wave_id' => 0,
            ),
            array(
                'status'  => C('order.status.wave_executed.code'),
                'in' => array(
                    'wave_id' => $wave_ids
                )
            )
        );



        $this->_return_json(
            array(
                'status'           => 0,
                'msg'              => 'success',
                'res'              => $update_res,
                'order_update_res' => $order_update_res,
            )
        );
    }


    private function _format_wave_list($wave_list = array()) {
        $res = [];
        $wave_dict = array(
            C('wave.wave_type.auto.code')   => '自动波次',
            C('wave.wave_type.manual.code') => '手动波次'
        );
        $site_dict = array(
            C('site.dachu') => '大厨',
            C('site.daguo') => '大果'
        );
        $pick_task_created_dict = array(
            C('wave.task_created.pending.code') => '未开始分拣',
            C('wave.task_created.created.code') => '已开始分拣'
        );

        $cities = $this->MLocation->get_lists(
            'id, name',
            array(
                'upid' => 0
            )
        );
        $city_ids = array_column($cities, 'id');
        $city_map = array_combine($city_ids, $cities);

        foreach($wave_list as $item) {
            $item['wave_type']    = $wave_dict[$item['wave_type']];
            $item['site_src']     = $site_dict[$item['site_src']];
            $item['task_created'] = $pick_task_created_dict[$item['pick_task_created']];
            $item['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
            $item['updated_time'] = date('Y-m-d H:i:s', $item['updated_time']);
            $city_id = $item['city_id'];
            $item['city_name'] = $city_id == 0 ? '无' : $city_map[$city_id]['name'];
            $res[] = $item;
        }


        return $res;
    }

    public function wave_list() {
        $where = [
            'status' => C('wave.status.valid')
        ];

        if(!empty($_POST['wave_type'])) {
            $where['wave_type'] = intval($_POST['wave_type']);
        }
        if(!empty($_POST['wave_id'])) {
            $where['id'] = intval($_POST['wave_id']);
        }
        if(isset($_POST['pick_task_created'])) {
            $where['pick_task_created'] = intval($_POST['pick_task_created']);
        }
        if(!empty($_POST['city_id'])) {
            $where['city_id'] = intval($_POST['city_id']);
            $city = $this->MLocation->get_one(
                'name',
                array(
                    'id' => $where['city_id']
                )
            );
            if(!empty($city)) {
                $city_name = $city['name'];
            }
        }
        $city_name = '';

        $page = $this->get_page();
        $wave_list = $this->MWave->get_lists(
            '*',
            $where,
            array('created_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $wave_list = $this->_format_wave_list($wave_list);
        $count = $this->MWave->get_one(
            'count(*) cnt',
            $where
        );
        $count = $count['cnt'];
        $this->_return_json(
            array(
                'status'    => 0,
                'msg'       => 'success',
                'city_name' => $city_name,
                'list'      => $wave_list,
                'total'     => $count
            )
        );
    }

    public function wave_info() {
        $wave_id = isset($_POST['wave_id']) ? intval($_POST['wave_id']) : 0;
        if(!$wave_id) {
            $this->_return_json(array('status' => -1, 'msg' => 'empty wave id'));
        }

        $wave_info = $this->MWave->get_one(
            '*',
            array(
                'id' => $wave_id
            )
        );
        if(!$wave_info) {
            $this->_return_json(array('status' => -1, 'msg' => 'wave info not exist'));
        }
        $wave_info = $this->_format_wave_list(
            array($wave_info)
        );
        $wave_info = $wave_info[0];

        $related_orders = $this->MSuborder->get_lists(
            '*',
            array(
                'wave_id' => $wave_id
            )
        );
        $related_orders = $this->_format_order_list($related_orders);

        $res = array(
            'status'         => 0,
            'msg'            => 'success',
            'info'           => $wave_info,
            'related_orders' => $related_orders
        );
        $this->_return_json(
            $res
        );
    }

    private function _format_pick_task_list($pick_task_list = array()) {
        $res = [];
        $site_dict = array(
            C('site.dachu') => '大厨',
            C('site.daguo') => '大果'
        );
        $status_dict = array(
            C('pick_task.status.not_created.code') => '未创建',
            C('pick_task.status.started.code')     => '未开始',
            C('pick_task.status.finished.code')    => '已完成'
        );

        if(empty($pick_task_list)) {
            return $res;
        }

        $cities = $this->MLocation->get_lists(
            'id, name',
            array(
                'upid' => 0
            )
        );
        $city_ids = array_column($cities, 'id');
        $city_map = array_combine($city_ids, $cities);

        $line_ids = array_column($pick_task_list, 'line_id');
        $lines = $this->MLine->get_lists(
            '*',
            array(
                'in' => array(
                    'id' => $line_ids
                )
            )
        );
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);
        foreach($pick_task_list as $item) {
            $item['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
            $item['updated_time'] = date('Y-m-d H:i:s', $item['updated_time']);
            $item['site_src'] = $site_dict[$item['site_src']];
            if(isset($line_map[$item['line_id']])){
                $item['line_name'] = $line_map[$item['line_id']]['name'];
                $item['warehouse_name'] = $line_map[$item['line_id']]['warehouse_name'];
            }
            $item['status_name'] = $status_dict[$item['status']];
            $item['pick_number'] = C('barcode.prefix.picking') . $item['pick_number'];
            $city_id = $item['city_id'];
            $item['city_name'] = $city_id == 0 ? '无' : $city_map[$city_id]['name'];
            $res[] = $item;
        }
        return $res;
    }

    public function pick_task_list() {
        $where = [];
        if(!empty($_POST['pick_task_id'])) {
            $where['id'] = intval($_POST['pick_task_id']);
        }
        if(isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where['status'] = intval($_POST['status']);
        }
        if(!empty($_POST['site_src'])) {
            $where['site_src'] = intval($_POST['site_src']);
        }
        if(!empty($_POST['city_id'])) {
            $where['city_id'] = intval($_POST['city_id']);
        }
        if(!empty($_POST['wave_id'])) {
            $where['wave_id'] = intval($_POST['wave_id']);
        }
        if (!empty($_POST['pick_ids'])) {
            $pick_ids = $_POST['pick_ids'];
            if(!is_array($pick_ids)) {
                $pick_ids = explode(',', $pick_ids);
                $pick_ids = array_filter($pick_ids);
            }
            $where['in'] = array('id' => $pick_ids);
        }
        if(!empty($_POST['pick_number'])) {
            $pick_number = $_POST['pick_number'];
            if($pick_number[0] == 'F') {
                $pick_number = substr($pick_number, 1);
            }
            $where['pick_number'] = $pick_number;
        }
        if (!empty($_POST['pick_numbers'])) {
            $pick_number_arr = explode(',', $this->input->post('pick_numbers', TRUE));
            $pick_number_arr = array_filter($pick_number_arr);
            foreach ($pick_number_arr as &$pick_number) {
                $pick_number = ltrim($pick_number, C('barcode.prefix.picking'));
            }
            $where['in'] = array('pick_number' => $pick_number_arr);
        }
        $where['status !='] = C("status.common.del");
        $page = $this->get_page();
        $pick_task_list = $this->MPick_task->get_lists(
            '*',
            $where,
            array('created_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $pick_task_list = $this->_format_pick_task_list(
            $pick_task_list
        );
        $count = $this->MPick_task->get_one(
            'count(*) cnt',
            $where
        );
        $count = $count['cnt'];

        $code_with_cn = array_values(C('pick_task.status'));
        $codes        = array_column($code_with_cn, 'code');
        $codes[] = -1;// 全部
        foreach($codes as $v) {
            if($v != -1) {
                $where['status'] = $v;
            }else{
                unset($where['status']);
            }
            $total[$v] = $this->MPick_task->count($where);
        }
        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'success',
                'total'  => $count,
                'list'   => $pick_task_list,
                'total_count' => $total,
            )
        );
    }

    private function _format_spec($spec = array()) {
        $spec_str = '';
        if(empty($spec)) {
            return $spec_str;
        }
        foreach($spec as $item) {
            if(!empty($item['name']) && $item['name'] != '描述' && !empty($item['val'])) {
                $spec_str .= $item['name'] . ':' . $item['val'] . ';';
            }
        }
        return $spec_str;
    }

    public function pick_task_info() {
        $pick_task_id = isset($_POST['pick_task_id']) ? intval($_POST['pick_task_id']) : 0;
        if(!$pick_task_id) {
            $this->_return_json(array('status' => -1, 'msg' => 'empty pick task id'));
        }

        $pick_task = $this->MPick_task->get_one(
            '*',
            array(
                'id' => $pick_task_id
            )
        );
        $pick_task = $this->_format_pick_task_list(
            array($pick_task)
        );
        $pick_task = $pick_task[0];

        if(!$pick_task) {
            $this->_return_json(array('status' => -1, 'msg' => 'pick task not exist'));
        }

        $related_orders = $this->MSuborder->get_lists(
            '*',
            array(
                'pick_task_id' => $pick_task_id
            )
        );
        //$related_orders = $this->_format_order_list($related_orders);
        $sku_list = $this->_order_product_sum($related_orders);

        $this->_return_json(
            array(
                'status'         => 0,
                'msg'            => 'success',
                'info'           => $pick_task,
                'sku_list'       => $sku_list,
                //'related_orders' => $related_orders
            )
        );
    }

    /**
     * @description 订单汇总数据
     */
    private function _order_product_sum($orders = array()) {

        if(empty($orders)) {
            return [];
        }

        $order_ids = array_column($orders, 'id');
        if(empty($order_ids)) {
            return [];
        }

        $where = [
            'in' => [
                'suborder_id' => $order_ids
            ]
        ];
        $details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );

        $csv_data = [];
        foreach($details as $item) {
            $product_id = $item['product_id'];
            $item['spec'] = json_decode($item['spec'], TRUE);
            if(isset($csv_data[$product_id])) {
                $csv_data[$product_id]['quantity'] += $item['quantity'];
            } else {
                $csv_data[$product_id] = [
                    'sku_number' => $item['sku_number'],
                    'name'       => $item['name'],
                    //'spec'     => $this->_format_spec($item['spec']),
                    'spec'       => $item['spec'],
                    'unit_name'  => $this->_unit_dict[$item['unit_id']],
                    'quantity'   => $item['quantity'],
                ];
            }
        }

        return $csv_data;

    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 格式化订单列表
     */
    private function _format_order_list($order_list = array()) {
        if(empty($order_list)) {
            return $order_list;
        }

        //批量取出下单用户信息
        $user_ids = array_column($order_list, 'user_id');
        $user_ids = array_unique($user_ids);
        $users = $this->MCustomer->get_lists(
            '*',
            [
                'in' => [
                    'id' => $user_ids
                ]
            ]
        );
        $user_ids = array_column($users, 'id');
        $user_map = array_combine($user_ids, $users);

        $city_ids = array_column($users, 'city_id');
        $province_ids = array_column($users, 'province_id');
        $county_ids = array_column($users, 'county_id');
        $location_ids = array_merge($city_ids, $province_ids, $county_ids);
        //取出用到的city_name, province_name, county_name
        $locations = $this->MLocation->get_lists(
            '*',
            [
                'in' => array(
                    'id' => $location_ids
                )
            ]
        );
        $location_ids = array_column($locations, 'id');
        $location_map = array_combine($location_ids, $locations);

        //线路相关
        $line_ids = array_column($users, 'line_id');
        $lines = $this->MLine->get_lists(
            '*',
            [
                'in' => array(
                    'id' => $line_ids
                )
            ]
        );
        $line_ids = array_column($lines, 'id');
        $line_map = array_combine($line_ids, $lines);

        //取出用到的bd
        $bd_ids = array_column($users, 'invite_id');
        $bd_ids = array_filter(array_unique($bd_ids));
        $bd_map = [];
        if(!empty($bd_ids)) {
            $bd_users = $this->MUser->get_lists(
                'id, name, mobile',
                array(
                    'in' => array(
                        'id' => $bd_ids
                    )
                )
            );
            $bd_ids = array_column($bd_users, 'id');
            $bd_map = array_combine($bd_ids, $bd_users);
        }

        //批量取出订单详情
        $order_ids = array_column($order_list, 'id');
        $where = [
            'in' => [ 'suborder_id' => $order_ids ]
        ];
        $order_details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );
        $detail_map = [];
        foreach($order_details as &$item) {
            $order_id = $item['suborder_id'];
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
            $item['single_price'] /= 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $item['updated_time'] = date('Y/m/d H:i', $item['updated_time']);
            $spec = json_decode($item['spec'], TRUE);
            if(!empty($spec)) {
                foreach($spec as $idx => $spec_arr) {
                    if(empty($spec_arr['name']) || empty($spec_arr['val'])) {
                        unset($spec[$idx]);
                    }
                }
                $item['spec'] = $spec;
            } else {
                $item['spec'] = '';
            }
            if(isset($detail_map[$order_id])) {
                $detail_map[$order_id][] = $item;
            } else {
                $detail_map[$order_id] = [
                    $item
                ];
            }
        }
        unset($item);

        foreach($order_list as $idx =>&$item) {

            //价格和时间
            $item['total_price']  = $item['total_price'] / 100;
            $item['deal_price']   = $item['deal_price'] / 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $deliver_arr          = $this->_deliver_dict;
            $item['deliver_time'] = isset($deliver_arr[$item['deliver_time']]) ?
                $deliver_arr[$item['deliver_time']] : '';
            $item['deliver_date'] = date('Y/m/d', $item['deliver_date']);

            //用户相关
            $user_id           = $item['user_id'];
            $order_user        = $user_map[$user_id];

            $item['mobile']    = $order_user['mobile'];
            $item['invite_id'] = $order_user['invite_id'];
            $item['shop_name'] = $order_user['shop_name'];
            $item['realname']  = $order_user['name'];
            $item['county_id'] = $order_user['county_id'];

            //线路
            $line_id = $order_user['line_id'];
            $item['line_id'] = $line_id;
            $order_line = isset($line_map[$line_id]) ? $line_map[$line_id] : [];
            $item['line'] = !empty($order_line) ? $order_line['name'] : '';

            //地址商圈相关
            $city_id           = $order_user['city_id'];
            $province_id       = $order_user['province_id'];
            $county_id         = $order_user['county_id'];
            $item['province']  = isset($location_map[$province_id]) ? $location_map[$province_id]['name'] : '';
            $item['city']      = isset($location_map[$city_id]) ? $location_map[$city_id]['name'] : '';
            $item['county']    = isset($location_map[$county_id]) ? $location_map[$county_id]['name'] : '';
            $item['address']   = $order_user['address'];

            //bd信息
            $invite_id = $order_user['invite_id'];
            if(!isset($bd_map[$invite_id])) {
                //unset($order_list[$idx]);
                $item['bd'] = '';
                $item['bd_mobile'] = '';
            } else {
                $item['bd'] = $bd_map[$invite_id]['name'];
                $item['bd_mobile'] = $bd_map[$invite_id]['mobile'];
            }

            //订单状态
            $status            = $item['status'];
            $item['status_cn'] = $this->_status_dict[$status];
            $order_id          = $item['id'];
            $item['detail']    = isset($detail_map[$order_id]) ? $detail_map[$order_id] : [];
        }
        unset($item);
        return $order_list;
    }
}

/* End of file wave.php */
/* Location: ./application/controllers/wave.php */
