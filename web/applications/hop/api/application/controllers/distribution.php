<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 配送单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-04-08
 */
class Distribution extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MDistribution',
                'MOrder',
                'MSuborder',
                'MWorkflow_log',
                'MLocation',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'skip32',
                'excel_export',
                'order_split',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 配送单列表
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function lists() {
        // 权限校验
        $this->check_validation('distribution', 'list', '', FALSE);
        // 调用基础服务接口
        if(!empty($_POST['orderId'])) {
            $order_map = array('order_ids' =>array($_POST['orderId']) , 'itemsPerPage' => '1');
            $return_order = $this->format_query('/suborder/lists',$order_map);
            $map = array(); 
            if(!empty($return_order['orderlist'])) { 
                $_POST['dist_ids'] = $return_order['orderlist'][0]['dist_id'];
            }
        }
        $return = $this->format_query('/distribution/lists', $_POST);
        $dist_list = $return['list'];
        if(!empty($dist_list)) {
            $dist_ids = array_column($dist_list, 'id');
            // 查询配送单下的订单列表
            $order_by = array('dist_order' => 'ASC', 'created_time' => 'DESC');
            if(!empty($_POST['orderId'])) {
                $order_map = array('dist_ids' => $dist_ids, 'order_ids' =>array($_POST['orderId']) , 'itemsPerPage' => 'all', 'order_by' => $order_by);
            }
            else {
                $order_map = array('dist_ids' => $dist_ids, 'itemsPerPage' => 'all', 'order_by' => $order_by);
            }
            $return_order = $this->format_query('/suborder/lists',$order_map);
            $order_list = $return_order['orderlist'];
            $order_map = array();
            $line_ids = [];
            if(!empty($order_list)) {
                foreach ($order_list as $order) {
                    $line_ids[] = $order['line_id'];
                    $order_map[$order['dist_id']][] = $order;
                }
            }
            $map['line_ids'] =array_unique($line_ids);
            $map['itemsPerPage'] = count($map['line_ids']);

            $res = $this->format_query('/line/lists',$map);
            foreach($res['list'] as $val) {
                $lines[$val['id']] = $val['name'];    
            }
            // 设置配送单的订单列表
            foreach ($dist_list as &$dist) {
                $dist['orders'] = isset($order_map[$dist['id']]) ? $order_map[$dist['id']] : array();
                $order_detail_dict = array();
                $dist_lines = array();
                $user_count = [];
                foreach ($dist['orders'] as $k => $order) {
                    $dist['orders'][$k]['line_name'] = $lines[$order['line_id']]; 
                    $dist_lines[$order['line_id']] = $lines[$order['line_id']];
                    $user_count[$order['user_id']]= 1;
                    foreach ($order['detail'] as $detail) {
                        if(isset($order_detail_dict[$detail['sku_number']])) {
                            // 累加sku数量并重新设置
                            $od = $order_detail_dict[$detail['sku_number']];
                            $od['quantity'] += $detail['quantity'];
                            $order_detail_dict[$detail['sku_number']] = $od;
                        } else {
                            // 设置sku数量
                            $order_detail_dict[$detail['sku_number']] = $detail;
                        }
                    }
                }
                $dist['line_name'] = implode('/',$dist_lines);
                $dist['user_count'] = count($user_count);
                unset($user_count);
                $dist['sku_list'] = array_values($order_detail_dict);
                $dist['barcode'] = $this->config->item('api_url') . 'distribution/barcode?thickness=70&scale=1&text=' . $dist['dist_number'];
                // 配送单的配送日期设置为第一个订单的配送日期
                $dist['deliver_date'] = !empty($dist['orders']) ? $dist['orders'][0]['deliver_date'] : '';
                $map_url = C('map.api_url');
                $index = 1;
                foreach ($dist['orders'] as $order) {
                    $geo = json_decode($order['geo'], TRUE);
                    $map_url .= 'mid,,' . $index++ . ':' . $geo['lng'] . ',' . $geo['lat'] . '|';
                }
                $map_url = rtrim($map_url, '|');
                $dist['map_url'] = $map_url;
            }
            unset($dist);
            $return['list'] = $dist_list;
        }

        $this->_return_json($return);
    }

    /**
     * 列出城市列表
     * @author yugang@dachuwang.com
     * @since 2015-05-06
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('distribution', 'list', '', FALSE);
        // 调用基础服务接口
        $return['status'] = C('status.req.success');
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        $return['deliver_time'] = array(
            array(
                'name' => '全天',
                'val' => 0
            ),
            array(
                'name' => '上午',
                'val' => 1
            ),
            array(
                'name' => '下午',
                'val' => 2
            )
        );
        //$order_type = array_values(C('order.order_type'));
        $order_type = $this->order_split->get_config();
        $return['order_type'] = $order_type;

        $this->_return_json($return);
    }

    /**
     * 查看配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function view() {
        // 权限校验
        $this->check_validation('distribution', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/distribution/view', $_POST);
        // 查询配送单下的订单列表
        $return_order = $this->format_query('/suborder/lists', array('dist_id' => $_POST['id']));
        $return['list'] = $return_order['orderlist'];
        $this->_return_json($return);
    }

    /**
     * 添加配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function create() {
        // 权限校验
        // $this->check_validation('distribution', 'create', '', FALSE);
        // 调用基础服务接口
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;
        $_POST['creator'] = $cur['name'];
        $_POST['creator_id'] = $cur['id'];
        $return = $this->format_query('/distribution/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑配送单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('distribution', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/distribution/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function edit() {
        // 权限校验
        $this->check_validation('distribution', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/distribution/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 打印配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-11
     */
    public function prints() {
        // 权限校验
        $this->check_validation('distribution', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/distribution/prints', $_POST);
        $this->_return_json($return);
    }

    /**
     * 导出配送单
     * @author yugang@dachuwang.com
     * @since 2015-05-12
     */
    public function export() {
        // 权限校验
        $this->check_validation('distribution', 'list', '', FALSE);
        $_POST['dist_numbers'] = $_REQUEST['dist_numbers'];
        // 调用基础服务接口
        $return = $this->format_query('/distribution/lists', $_POST);
        $dist_list = $return['list'];
        if(!empty($dist_list)) {
            $dist_ids = array_column($dist_list, 'id');
            // 查询配送单下的订单列表
            $order_by = array('dist_order' => 'ASC', 'created_time' => 'DESC');
            $return_order = $this->format_query('/suborder/lists', array('dist_ids' => $dist_ids, 'itemsPerPage' => 'all', 'order_by' => $order_by));
            $order_list = $return_order['orderlist'];
            $order_map = array();
            if(!empty($order_list)) {
                foreach ($order_list as $order) {
                    $order_map[$order['dist_id']][] = $order;
                }
            }
            // 设置配送单的订单列表
            foreach ($dist_list as &$dist) {
                $dist['orders'] = isset($order_map[$dist['id']]) ? $order_map[$dist['id']] : array();
                $order_detail_dict = array();
                foreach ($dist['orders'] as $order) {
                    foreach ($order['detail'] as $detail) {
                        if(isset($order_detail_dict[$detail['sku_number']])) {
                            // 累加sku数量并重新设置
                            $od = $order_detail_dict[$detail['sku_number']];
                            $od['quantity'] += $detail['quantity'];
                            $order_detail_dict[$detail['sku_number']] = $od;
                        } else {
                            // 设置sku数量
                            $order_detail_dict[$detail['sku_number']] = $detail;
                        }
                    }
                }
                $dist['sku_list'] = array_values($order_detail_dict);
                $dist['barcode'] = $this->config->item('api_url') . 'distribution/barcode?thickness=70&scale=1&text=' . $dist['dist_number'];
                // 配送单的配送日期设置为第一个订单的配送日期
                $dist['deliver_date'] = !empty($dist['orders']) ? $dist['orders'][0]['deliver_date'] : '';
                $map_url = C('map.api_url');
                $index = 1;
                foreach ($dist['orders'] as $order) {
                    $geo = json_decode($order['geo'], TRUE);
                    $map_url .= 'mid,,' . $index++ . ':' . $geo['lng'] . ',' . $geo['lat'] . '|';
                }
                $map_url = rtrim($map_url, '|');
                $dist['map_url'] = $map_url;
            }
            unset($dist);
            $return['list'] = $dist_list;
        }
        // var_dump($dist_list);
        $xls_list = [];
        $sheet_titles = [];
        $title_arr = ['订单id', '订单编号', '店铺名称', '送货地址', '联系电话', '客户备注', '货品号', '产品名称', '规格', '结算单价', '结算单位',  '订货数量', '订货单位',  '单价*数量'];
        foreach ($dist_list as $dist) {
            $dist_arr = [];
            $dist_arr[] = array('配送线路单号:' . $dist['dist_number'], '', '', '', '', '', '仓库:' . $dist['warehouse_name'], '', '', '', '', '', '', '');
            $dist_arr[] = array('线路（片区）:' . $dist['line_name'], '', '', '', '', '', '发车时间:' . $dist['deliver_date'] . ($dist['deliver_time'] == 1 ? '上午' : '下午'), '', '', '', '', '', '', '');
            $dist_arr[] = array('订单数:' . count($dist['orders']), '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('订单明细', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = $title_arr;
            foreach ($dist['orders'] as $order) {
                foreach ($order['detail'] as $detail) {
                    $specs = '';
                    foreach ($detail['spec'] as $spec) {
                        if($spec['name'] != '描述') {
                            $specs .= $spec['name'] . ':' . $spec['val']; 
                        }
                    }
                    $dist_arr[] = array(
                        $order['id'],
                        $order['order_number'],
                        $order['shop_name'],
                        $order['deliver_addr'],
                        $order['mobile'],
                        $order['remarks'],
                        $detail['sku_number'],
                        $detail['name'],
                        $specs,
                        $detail['single_price'],
                        $detail['close_unit'],
                        $detail['quantity'],
                        $detail['unit_id'],
                        '',
                    );
                }

            }

            $dist_arr[] = array('汇总', '', '', '', '', '', '', '', '', '', '', $dist['sku_count'], '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品汇总', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品号', '产品名称', '订货数量', '', '', '', '', '', '', '', '', '', '', '');
            foreach ($dist['sku_list'] as $sku) {
                $dist_arr[] = array(
                    $sku['sku_number'],
                    $sku['name'],
                    $sku['quantity'],
                );
            }
            $dist_arr[] = array('汇总', '', $dist['sku_count'], '', '', '', '', '', '', '', '', '', '', '');

            $xls_list[] = $dist_arr;
            $sheet_titles[] = $dist['dist_number'];
        }
        $this->excel_export->export($xls_list, $sheet_titles);
    }

    /**
     * 删除配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function delete() {
        // 权限校验
        $this->check_validation('distribution', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/distribution/delete', $_POST);
        $this->_return_json($return);
    }

    /**
     * 生成条形码
     * @author yugang@dachuwang.com
     * @since 2015-04-20
     */
    public function barcode() {
        $data['text'] = $this->input->get('text');
        $data['thickness'] = $this->input->get('thickness');
        $data['scale'] = $this->input->get('scale');
        $query_string = http_build_query($data);
        header('Content-Type: image/png');
        echo file_get_contents($this->get_api_url($query_string));
    }

    /**
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-05-08
     */
    public function line_list() {
        // 权限校验
        $this->check_validation('distribution', 'list', '', FALSE);
        // 调用基础服务接口
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $return = $this->format_query('/line/lists', $_POST);
        $line_list = $return['list'];
        $line_ids = array_column($line_list, 'id');
        $line_count = $this->MSuborder->get_lists('count(*) cnt, line_id', array('status' => C('order.status.checked.code'), 'dist_id' => 0, 'in' => array('line_id' => $line_ids)), array(), array('line_id'));
        $line_dict = array_combine(array_column($line_count, 'line_id'), array_column($line_count, 'cnt'));
        foreach ($line_list as &$line) {
            $line['name'] .= ' (' . (isset($line_dict[$line['id']]) ? $line_dict[$line['id']] : 0) . ')';
        }
        $return['list'] = $line_list;
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        //$order_type = array_values(C('order.order_type'));
        $order_type = $this->order_split->get_config();
        $return['order_type'] = $order_type;
        $this->_return_json($return);
    }

    private function get_api_url($query_string){
        return sprintf('%s/barcode/get?%s',$this->_service_url,$query_string);
    }
}

/* End of file distribution.php */
/* Location: :./application/controllers/distribution.php */
