<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @description 客户购买行为分析
 * @author liudeen@dachuwang.com
 * @since 2015-08-13
 */
class Customer_buyaction_analysis extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model(
            [
                'MCustomer',
                'MSuborder',
                'MCategory',
                'MSku',
                'MOrder_detail',
                'MOrder'
            ]
        );
    }

    /*
     * @description 获取客户未关闭订单中所有SKU的一级分类组成，并累加金额
     * @param  POST array $customer_ids 客户id列表 [,int $start_time 查询起始时间 [,int $end_time 查询结束时间| 默认查询时间段为all
     * @return array 如['one_of_customer_ids' => ['肉类' => 10000, '蔬菜' => 2000]]
     * @author liudeen@dachuwang.com
     */
    public function get_first_category_by_customer_ids() {
        $customer_ids = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : null;
        if($customer_ids === null) {
            $this->assemble_result(C('status.req.success'), '请求成功', []);
        }
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
        $customer_suborders_map = $this->get_suborder_id_by_customer_ids($customer_ids, $start_time, $end_time);
        //[1=>[1,2,3],2=>[7,8,9]...]
        $user_map = [];
        if(!$customer_suborders_map) {
            $this->assemble_result(C('status.req.success'), '请求成功', $user_map);
        }
        $all_first_catename = $this->get_all_first_category();
        foreach($customer_suborders_map as $user_id => $suborder_ids) {
            $sku_sumprice_map = $this->get_sku_and_sumprice_by_suborder_ids($suborder_ids);
            //[sku1=>5000,sku2=>300]
            $skus = [];
            foreach($sku_sumprice_map as $sku_number => $sum_price) {
                $skus[] = $sku_number;
            }
            $sku_catename_map = $this->get_sku_and_first_category_map($skus);
            //[sku1=>'肉类',sku2=>'蔬菜',..]
            foreach($all_first_catename as $catename) {
                $result[$catename] = 0;
            }
            foreach($sku_sumprice_map as $sku_number => $sum_price) {
                $result[$sku_catename_map[$sku_number]] += floatval($sum_price);
            }
            $user_map[$user_id] = $result;
        }
        $this->assemble_result(C('status.req.success'), '请求成功', $user_map);
    }

    public function get_first_category_by_bd_ids() {
        $bd_ids = isset($_POST['bd_ids']) ? $_POST['bd_ids'] : null;
        if($bd_ids === null) {
            $this->assemble_result(C('status.req.success'), '请求成功', []);
        }
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
        $bd_suborders_map = $this->get_suborder_id_by_bd_ids($bd_ids, $start_time, $end_time);
        //[1=>[1,2,3],2=>[7,8,9]...]
        $user_map = [];
        if(!$bd_suborders_map) {
            $this->assemble_result(C('status.req.success'), '请求成功', $user_map);
        }
        $all_first_catename = $this->get_all_first_category();
        foreach($bd_suborders_map as $bd_id => $suborder_ids) {
            $sku_sumprice_map = $this->get_sku_and_sumprice_by_suborder_ids($suborder_ids);
            //[sku1=>5000,sku2=>300]
            $skus = [];
            foreach($sku_sumprice_map as $sku_number => $sum_price) {
                $skus[] = $sku_number;
            }
            $sku_catename_map = $this->get_sku_and_first_category_map($skus);
            //[sku1=>'肉类',sku2=>'蔬菜',..]
            foreach($all_first_catename as $catename) {
                $result[$catename] = 0;
            }
            foreach($sku_sumprice_map as $sku_number => $sum_price) {
                if(!isset($sku_catename_map[$sku_number])) {
                    continue;
                }
                $result[$sku_catename_map[$sku_number]] += floatval($sum_price);
            }
            $user_map[$bd_id] = $result;
        }
        $this->assemble_result(C('status.req.success'), '请求成功', $user_map);
    }

    public function get_customer_buy_action_analysis() {
        $customer_ids = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : null;
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
        if(!$customer_ids) {
            $this->assemble_result(C('status.req.success'), '缺少必要字段',[]);
        }
        $order_amount = $this->get_order_amount($customer_ids, $start_time, $end_time);
        $order_number = $this->get_order_number($customer_ids, $start_time, $end_time);
        $order_frequency = $this->get_order_frequency($customer_ids, $start_time, $end_time);
        $result = [];
        foreach($customer_ids as $customer_id) {
            $result[$customer_id] = [
                '订货金额' => $order_amount[$customer_id]/100.00,
                '母订单数' => $order_number[$customer_id],
                '订货客单价' => floatval(number_format($this->safe_divide($order_amount[$customer_id],100*$order_number[$customer_id]),2,'.','')),
                '下单频率' => floatval(number_format($order_frequency[$customer_id],2,'.',''))
            ];
        }
        $this->assemble_result(C('status.req.success'), '请求成功', $result);
    }

    private function safe_divide($bcs, $cs) {
        if($cs == 0) {
            return 0;
        }
        return $bcs/$cs;
    }

    /*
     * @description 获取用户订货金额
     * @param array $customer_ids
     * @return array 如[1=>5000,8=>10000]
     * @author liudeen@dachuwang.com
     */
    private function get_order_amount(array $customer_ids, $start_time=null, $end_time=null) {
        $fields = ['user_id', 'sum(total_price) as summ'];
        $where = [
            'status >' => C('order.status.closed.code'),
            'in' => ['user_id' => $customer_ids]
        ];
        if($start_time) {
            $where['created_time >'] = $start_time;
        }
        if($end_time) {
            $where['created_time <='] = $end_time;
        }
        $group_by = 'user_id';
        $arr = $this->MSuborder->get_lists($fields, $where, null, $group_by);
        $result = [];
        foreach($arr as $v) {
            $result[$v['user_id']] = floatval($v['summ']);
        }
        return $result;
    }

    private function get_order_number(array $customer_ids, $start_time=null, $end_time=null) {
        $fields = ['user_id', 'count(*) as summ'];
        $where = [
            'status >' => C('order.status.closed.code'),
            'in' => ['user_id' => $customer_ids]
        ];
        if($start_time) {
            $where['created_time >'] = $start_time;
        }
        if($end_time) {
            $where['created_time <='] = $end_time;
        }
        $group_by = 'user_id';
        $arr = $this->MOrder->get_lists($fields, $where, null, $group_by);
        $result = [];
        foreach($arr as $v) {
            $result[$v['user_id']] = floatval($v['summ']);
        }
        return $result;
    }

    /*
     * @description 查询客户一段时间内下单的天数（一天多次下单算一天）
     * @param array $customer_ids [, int $start_time [, int $end_time
     * @return array 如[one_of_customer_id=>8,..]
     * @author liudeen@dachuwang.com
     */
    private function get_order_days(array $customer_ids, $start_time=null, $end_time=null) {
        $fields = ['user_id','created_time'];
        $where = [
            'status >' => C('order.status.closed.code'),
            'in' => ['user_id' => $customer_ids]
        ];
        if($start_time) {
            $where['created_time >'] = $start_time;
        }
        if($end_time) {
            $where['created_time <='] = $end_time;
        }
        $arr = $this->MSuborder->get_lists($fields, $where);
        if(!$arr) {
            return null;
        }
        $result = [];
        foreach($customer_ids as $customer_id) {
            $result[$customer_id] = [];
        }
        foreach($arr as $v) {
            $day = date('Y-m-d',$v['created_time']);
            if(!in_array($day, $result[$v['user_id']])) {
                $result[$v['user_id']][] = $day;
            }
        }
        foreach($customer_ids as $customer_id) {
            $result[$customer_id] = count($result[$customer_id]);
        }
        return $result;
    }

    /*
     * @description 查询客户一段时间内的下单频率
     * @param array $customer_ids [,int $start_time [,int $end_time
     * @return array 如[1=>0.8,7=>0.42]
     * @author liudeen@dachuwang.com
     */
    private function get_order_frequency(array $customer_ids, $start_time=null, $end_time=null) {
        //指定时间内
        if($start_time && $end_time) {
            $allday = round(($end_time-$start_time)/86400+0.5);
            $order_days_map = $this->get_order_days($customer_ids, $start_time, $end_time);
            foreach($order_days_map as $customer_id => &$order_days) {
                $order_days = $this->safe_divide($order_days,$allday);
            }
            return $order_days_map;
        }
        //总时间从注册算起
        $time_arr = $this->MCustomer->get_lists(['created_time','id'], ['in'=>['id'=>$customer_ids]]);
        $all_days_map = [];
        $now = time();
        foreach($time_arr as $v) {
            //从注册到现在的天数
            $all_days_map[$v['id']] = round(($now - intval($v['created_time']))/86400+0.5);
        }
        $order_days_map = $this->get_order_days($customer_ids);
        foreach($customer_ids as $customer_id) {
            $order_days_map[$customer_id] = $order_days_map[$customer_id]/$all_days_map[$customer_id];
        }
        return $order_days_map;
    }

    /*
     * @description 获取所有一级分类
     * @return array 如[category_id=>'category_name',...]
     * @author liudeen@dachuwang.com
     */
    private function get_all_first_category() {
        $where = ['upid' => 0, 'name !=' => ''];
        $fields = ['id', 'name'];
        $arr = $this->MCategory->get_lists($fields, $where);
        $result = [];
        foreach($arr as $v) {
            $result[$v['id']] = $v['name'];
        }
        return $result;
    }

    /*
     * @description 获取客户一段时间内未关闭的子订单id
     * @param array $customer_ids [, int $start_time [, int $end_time
     * @return array 如[1=>[1,2,3], 2=>[7,8,9]] | null
     * @author liudeen@dachuwang.com
     */
    private function get_suborder_id_by_customer_ids(array $customer_ids, $start_time=null, $end_time=null) {
        $fields = ['id','user_id'];
        $where = [
            'in' => ['user_id' => $customer_ids],
            'status >' => C('order.status.closed.code')
        ];
        if($start_time) {
            $where['created_time >'] = $start_time;
        }
        if($end_time) {
            $where['created_time <='] = $end_time;
        }
        $arr = $this->MSuborder->get_lists($fields, $where);
        if(!$arr) {
            return null;
        }
        $result = [];
        foreach($arr as $v) {
            $result[$v['user_id']][] = $v['id'];
        }
        return $result;
    }

    private function get_suborder_id_by_bd_ids(array $bd_ids, $start_time=null, $end_time=null) {
        $fields = ['id','sale_id'];
        $where = [
            'in' => ['sale_id' => $bd_ids],
            'status >' => C('order.status.closed.code')
        ];
        if($start_time) {
            $where['created_time >'] = $start_time;
        }
        if($end_time) {
            $where['created_time <='] = $end_time;
        }
        $arr = $this->MSuborder->get_lists($fields, $where);
        if(!$arr) {
            return null;
        }
        $result = [];
        foreach($arr as $v) {
            $result[$v['sale_id']][] = $v['id'];
        }
        return $result;
    }

    /*
     * @description 获取一系列订单中每个SKU的购买总金额
     * @param array $order_array 如[1,2,3]
     * @return array 如[sku_num1=>2000,sku2=>1000]
     * @author liudeen@dachuwang.com
     */
    private function get_sku_and_sumprice_by_suborder_ids(array $suborders) {
        $fields = ['sku_number','sum(sum_price) as summ'];
        $where = ['in' => ['suborder_id'=>$suborders]];
        $group_by = 'sku_number';
        $arr = $this->MOrder_detail->get_lists($fields, $where, null, $group_by);
        if(!$arr) {
            return null;
        }
        $result = [];
        foreach($arr as $v) {
            $result[$v['sku_number']] = $v['summ'];
        }
        return $result;
    }

    /*
     * @description 获取SKU和其对应的一级分类名的map
     * @param array $skus
     * @return array 如[sku1=>'肉类',...]
     * @author liudeen@dachuwang.com
     */
    private function get_sku_and_first_category_map(array $skus) {
        $sku_cateid_map = $this->get_category_id_by_sku_numbers($skus);
        $cate_ids = [];
        foreach($sku_cateid_map as $v) {
            $cate_ids[] = $v;
        }
        $cateid_catename_map = $this->get_first_category_by_category_ids($cate_ids);
        foreach($sku_cateid_map as $k => $v) {
            $sku_cateid_map[$k] = $cateid_catename_map[$v];
        }
        return $sku_cateid_map;
    }

    /*
     * @description 根据sku_number获取对应的category_id
     * @param array $skus 如[1=>1,8=>5]
     * @return array
     * @author liudeen@dachuwang.com
     */
    private function get_category_id_by_sku_numbers(array $skus) {
        if(!$skus) {
            return null;
        }
        $fields = ['category_id','sku_number'];
        $where = ['in' => ['sku_number' => $skus], 'status >' => 0];
        $arr = $this->MSku->get_lists($fields, $where);
        if(!$arr) {
            return null;
        }
        $result = [];
        foreach($arr as $v) {
            $result[$v['sku_number']] = $v['category_id'];
        }
        return $result;
    }

    /*
     * @description 获取category_id和它对应的一级分类映射表
     * @param array $category_ids
     * @return array 如[1=>'肉类',8=>'蔬菜']
     * @author liudeen@dachuwang.com
     */
    private function get_first_category_by_category_ids(array $category_ids) {
        if(!$category_ids) {
            return null;
        }
        $fields = ['path', 'id'];
        $where = ['in' => ['id'=>$category_ids]];
        $arr = $this->MCategory->get_lists($fields, $where);
        if(!$arr) {
            return null;
        }
        $first_category = $this->get_all_first_category();
        $result = [];
        foreach($arr as $v) {
            if(!empty($v['path'])) {
                $route = explode('.', trim($v['path'], '.'));
                $result[$v['id']] = $first_category[$route[0]];
            }
        }
        return $result;
    }

    /*
     * @description 返回规定格式的json数据
     * @param int $status_number , string $req_info, array $list
     * @return void
     * @author liudeen@dachuwang.com
     */
    private function assemble_result($status_number, $req_info, $list) {
        $this->_return_json([
            'status' => $status_number,
            'msg' => $req_info,
            'list' => $list
        ]);
    }

}
