<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 商品服务
 * @author: liaoxianwen@ymt360.com
 * @version: 2.0.0
 * @since: 2015-3-3
 */
class Sku extends MY_Controller {

    public $units = array();
    public $product_status = array();
    public function __construct() {
        parent::__construct();

        $this->load->model(
            array(
                'MCategory',
                'MSku',
                'MBucket',
                'MProperty',
                'MComplaint',
                'MComplaint_content',
                'MAbnormal_order',
                'MAbnormal_content',
            )
        );
        $this->units = C('unit');
        $this->product_status = C('product');
        $this->load->library(array('Cate_logic', 'Wms_product', 'Product_lib', 'form_validation'));
        $this->load->helper(array('format_kb'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个商品信息
     */
    public function info() {
        if(isset($_POST['where'])) {
            $info = $this->MSku->get_one('*', $_POST['where']);
            // 获取分类信息
            if($info) {
                $cate = $this->MCategory->get_one('name,path', array('id' => $info['category_id']));
                $ids = $info['path'] = explode('.', trim($cate['path'], '.'));
                array_pop($ids);
                if($ids) {
                    $path_info = $this->cate_logic->get_by_ids($ids);
                    $cate_name = implode('-->', array_column($path_info, 'name'));
                    $cate_name .= '-->';
                    $info['cate_name'] = $cate_name . $cate['name'];
                } else {
                    $info['cate_name'] = $cate['name'];
                }
                if($info['spec']) {
                    $info['spec'] = json_decode($info['spec'], TRUE);
                    $new_info = array();
                    if($info['spec']) {
                        $new_info = $this->_deal_spec($info['spec']);
                    }
                    $info['spec'] = $new_info;

                }
                $info['units'] = $this->units;
                $info['product_status'] = $this->product_status;
                $data = array(
                    'status' => C('tips.code.op_success'),
                    'info'   => $info
                );
            } else {
                $data = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '货号不存在'
                );
            }
        } else {
            $data = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '缺少where参数'
            );
        }
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 处理之前的异常规格属性
     */
    private function _deal_spec($spec) {
        $new_info = array();
        foreach($spec as $v_spec) {
            if(isset($v_spec['name']) && $v_spec['name'] != '单价' && isset($v_spec['val'])) {
                $new_info[]= array(
                    'name' => $v_spec['name'] ,
                    'val' =>  $v_spec['val']
                );
            }
        }
        return $new_info;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取列表
     */
    public function lists() {
        $products = array();
        $cate_ids= explode(',',rtrim($_POST['upid'], ','));
        $childs = $this->cate_logic->get_child($cate_ids);
        $category_ids = array_column($childs, "id");
        foreach($cate_ids as $v) {
            $category_ids[] = $v;
        }
        $page_size = isset($_POST['page_size']) ? intval($_POST['page_size']) : 100;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $lists = $this->MSku->get_lists('*',array(
                'in' => array(
                    'category_id' => $category_ids
                    //'user_id' =>
                ),
                'status' => C('status.product.up'),
            ),
            array('updated_time' => 'desc'),
            array(),
            $page_size * ($page -1),
            $page_size
        );
        $units = C('unit');
        // 修改
        if(!empty($lists)) {
            foreach($lists as &$v) {
                $v['spec'] = json_decode($v['spec'], TRUE);
                $v['spec'] = $this->_check_unique_spec($v['spec']);
                $v['price'] = sprintf("%.2f", ($v['price'] / 100));
                $v['market_price'] = sprintf("%.2f", ($v['market_price'] / 100));
                $v['has_img_cn'] = $v['pic_ids'] ? '已上传' : '暂无图片';
                foreach($units as $unit_val) {
                    if($unit_val['id'] == $v['unit_id']) {
                        $v['unit'] = $unit_val['name'];
                    }
                }
            }
        }

        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $lists
            )
        );
    }
    // 确保spec 唯一
    private function _check_unique_spec($spec) {
        $name_arr = $new_spec = array();
        foreach($spec as $v) {
            if(!in_array($v['name'], $name_arr)) {
                $new_spec[] = $v;
            }
            $name_arr[] = $v['name'];
        }
        return $new_spec;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品创建
     */
    public function create(){
        $format_data = $this->_format_data();
        extract($format_data);
        $lock_name = 'create__Lock30';
        $product_id = $this->MSku->$lock_name($product);
        if(is_bool($product_id)) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '不要点击太快了，或许也有伙伴也在操作一样的信息'
                )
            );
        }

        $sync_status = -1;
        if($product_id) {
            $this->MSku->create_unlock($lock_name, $product);
            $sync_data['id'] = $product_id;
            $sync_data['code'] = set_sku($product_id);
            $sync_data['cate_id'] = $product['category_id'];
            $sync_data['name'] = $product['name'];
            // 更新sku货号
            $update_data = array(
                'sku_number' => $sync_data['code']
            );
            $where = array(
                'id' => $product_id
            );
            $this->MSku->update_info($update_data, $where);

            $sync_status = $this->_sync_to_wms($sync_data);
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg' => '保存成功',
                'sync' => $sync_status
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 编辑货号
     */
    public function edit() {
        $info = array();
        if(isset($_POST['where'])) {
            $info = $this->MSku->get_one('*', $_POST['where']);
            $cate_info = $this->MCategory->get_one('path', array('id' => $info['category_id']));
            $info['spec'] = json_decode($info['spec'], TRUE);
            // 获取规格属性
            $path = trim($cate_info['path'], '.');
            $ids = explode('.', $path);
            $properties = array();
            // 获取属性
            foreach($ids as $v) {
                $where['category_id'] = $v;
                $property = $this->MProperty->get_lists("*", $where);
                if($property) {
                    $properties = $property;
                }
            }

        }
        // 规格属性
        if($properties) {
            foreach($properties as $key => &$v) {
                if($v['name'] == '单价') {
                    unset($properties[$key]);
                }
            }
            $info['properties']  = $properties;
        }
        $info['path_arr']  = $ids;
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'info'   => $info
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 编辑后保存
     */
    public function save() {
        $format_data = $this->_format_data();
        $info = $this->MSku->get_one('id, sku_number', array('id' => $_POST['id']));
        if($info) {
            extract($format_data);
            $this->MSku->update_info($product, array('id' =>$_POST['id']));
            $sync_data['id'] = $info['id'];
            $sync_data['code'] = $info['sku_number'];
            $sync_data['cate_id'] = $product['category_id'];
            $sync_data['name'] = $product['name'];
            $code = $this->_sync_to_wms($sync_data, FALSE, TRUE);
            $msg =  array(
                'status' => C('tips.code.op_success'),
                'msg' => '保存成功',
                'error_code' => $code
            );
        } else {
            $msg =  array(
                'status' => C('tips.code.op_failed'),
                'msg' => '该货号没有找到'
            );
        }
        $this->_return_json($msg);
    }

    public function manage() {
        $page = $this->get_page();
        $where = array();
        if(!empty($_POST['where'])) {
            $where = $_POST['where'];
        }
        $total =  $this->MSku->count($where);
        $data = $this->MSku->get_lists(
            '*',
            $where,
            array('updated_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        if(!empty($data)) {
            $sku_product_category_ids = array_column($data, 'category_id');
            $return_categories = $this->_get_category_info($sku_product_category_ids);
            foreach($data as &$v) {
                $v['spec'] = json_decode($v['spec'], TRUE);
                $v['category_info'] = $return_categories[$v['category_id']];
                $v['description'] = '';
                if(is_array($v['spec']) && $v['spec']) {
                    $v['description'] = $this->_deal_spec($v['spec']);
                }
                if($v['pic_ids']) {
                    $v['pic_ids_count'] = substr_count($v['pic_ids'], ',') + 1;
                }
                $v['has_img_cn'] = $v['pic_ids'] ? '已上传' : '暂无图片';
                $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            }
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data,
                'total' => $total
            )
        );
    }

    /**
     * 统计指定sku的投诉单和退货退款单
     * @author yugang@dachuwang.com
     * @since 2015-07-21
     */
    public function count_abnormal() {
        // 表单校验
        $this->form_validation->set_rules('sku_numbers', 'sku', 'required') ;
        $this->form_validation->set_rules('stime', '开始时间', 'required') ;
        $this->form_validation->set_rules('etime', '结束时间', 'required') ;
        $this->validate_form();

        $sku_numbers = $_POST['sku_numbers'];
        $stime = $_POST['stime'];
        $etime = $_POST['etime'];

        $complaint_order_counts = 0;
        $return_order_counts = 0;
        $return_sku_counts = 0;
        $product_list = $this->MProduct->get_lists('*', ['in' => ['sku_number' => $sku_numbers]]);
        $product_ids = array_column($product_list, 'id');
        if(empty($product_ids)){
            $this->_return_json(
                array(
                    'status'    => C('status.req.success'),
                    'list'   => [],
                    'total'  => []
                )
            );
        }
        $product_sku_dict = array_combine($product_ids, array_column($product_list, 'sku_number'));
        // 只查询统计质量问题的投诉
        $quality_complaints = $this->MComplaint->get_lists('id', ['ctype' => C('complaint.ctype.quality.code'), 'created_time >=' => $stime, 'created_time <=' => $etime]);
        $quality_complaint_ids = array_column($quality_complaints, 'id');
        if (empty($quality_complaint_ids)) {
            $quality_complaint_ids = [-1];
        }
        $sku_complaint_dict = [];
        $complaints = $this->MComplaint_content->get_lists('*', ['in' => ['product_id' => $product_ids, 'cid' => $quality_complaint_ids], 'created_time >=' => $stime, 'created_time <=' => $etime]);
        foreach ($complaints as $complaint) {
            $sku_number = $product_sku_dict[$complaint['product_id']];
            if (isset($sku_complaint_dict[$sku_number])) {
                $sku_complaint_dict[$sku_number] += 1;
            } else {
                $sku_complaint_dict[$sku_number] = 1;
            }
            $complaint_order_counts++;
        }

        // 只查询统计退货退款单的异常单
        $return_abnormals = $this->MAbnormal_order->get_lists('id', ['otype' => C('abnormal_order.otype.return.val'), 'created_time >=' => $stime, 'created_time <=' => $etime]);
        $return_abnormals_ids = array_column($return_abnormals, 'id');
        if (empty($return_abnormals_ids)) {
            $return_abnormals_ids = [-1];
        }
        $sku_return_order_dict = [];
        $return_orders = $this->MAbnormal_content->get_lists('*', ['in' => ['product_id' => $product_ids, 'aid' => $return_abnormals_ids], 'created_time >=' => $stime, 'created_time <=' => $etime]);
        foreach ($return_orders as $order) {
            $sku_number = $product_sku_dict[$order['product_id']];
            if (isset($sku_return_order_dict[$sku_number])) {
                $sku_return_order_dict[$sku_number]['order_count'] += 1;
                $sku_return_order_dict[$sku_number]['sku_count'] += $order['quantity'];
            } else {
                $sku_return_order_dict[$sku_number]['order_count'] = 1;
                $sku_return_order_dict[$sku_number]['sku_count'] = intval($order['quantity']);
            }
            $return_order_counts++;
            $return_sku_counts += $order['quantity'];
        }

        $return_list = [];
        foreach ($sku_numbers as $sku_number) {
            $sku_data = [];
            $sku_data['sku_number'] = $sku_number;
            $sku_data['complaint_order_counts'] = isset($sku_complaint_dict[$sku_number]) ? $sku_complaint_dict[$sku_number] : 0;
            $sku_data['return_order_counts'] = isset($sku_return_order_dict[$sku_number]['order_count']) ? $sku_return_order_dict[$sku_number]['order_count'] : 0;
            $sku_data['return_order_counts'] = isset($sku_return_order_dict[$sku_number]['order_count']) ? $sku_return_order_dict[$sku_number]['order_count'] : 0;
            $sku_data['return_sku_counts'] = isset($sku_return_order_dict[$sku_number]['sku_count']) ? $sku_return_order_dict[$sku_number]['sku_count'] : 0;
            $return_list[] = $sku_data;
        }

        $this->_return_json([
            'status' => C('status.req.success'),
            'list'   => $return_list,
            'total'  => [
                'complaint_order_counts' => $complaint_order_counts,
                'return_order_counts'    => $return_order_counts,
                'return_sku_counts' => $return_sku_counts,
            ]
        ]);
    }

    /*
     * @author: liaoxianwen@ymt360.com
     * @description 获取sku的分类信息
     */
    private function _get_category_info($category_ids) {
        $sku_category_info = $this->MCategory->get_lists('path,id,upid,name', array('in' => array('id' => $category_ids)));
        $sku_paths = array_column($sku_category_info, 'path');
        $category_ids_arr = [];
        foreach($sku_paths as $sku_path) {
            $path = trim($sku_path, '.');
            $category_ids_arr = array_merge(explode('.', $path), $category_ids_arr);
        }
        $category_ids_arr = array_unique($category_ids_arr);
        $categories = $this->MCategory->get_lists('id, name, path', array('in' => array('id' => $category_ids_arr)));
        $return_category_arr = [];
        foreach($sku_category_info as $sku_category) {
            foreach($categories as $category) {
               $pos = is_bool(strpos($sku_category['path'], $category['path'])) ? FALSE : TRUE;
               $point_nums = substr_count($category['path'], '.');
               if($pos) {
                   switch($point_nums) {
                   case 2:
                       $return_category_key = 'top';
                       break;
                   case 3:
                       $return_category_key = 'second';
                       break;
                   case 4:
                       $return_category_key = 'third';
                       break;
                   case 5:
                       $return_category_key = 'fourth';
                       break;
                   }
                   $return_category_arr[$sku_category['id']][$return_category_key][] = $category;
               }
            }
        }
        return $return_category_arr;
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建商品组合数据
     */
    private function _format_data() {
        $spec = $sync_data = array();
        $deal_data = $this->_deal_property();
        extract($deal_data);
        $req_time = $this->input->server('REQUEST_TIME');
        $pic_ids = '';
        if(isset($_POST['imgs'])) {
            foreach($_POST['imgs'] as $v) {
                $bucket = array(
                    'pic_url' => $v['dataUrl'],
                    'mime_type' => empty($v['type']) ? '' : $v['type'],
                    'file_size' => empty($v['size']) ? 0 :format_kb($v['size']),
                    'status' => C('status.common.success'),
                    'created_time' => $req_time,
                    'updated_time' => $req_time
                );

                $create_id= $this->MBucket->create($bucket);
                $pic_ids .= $create_id . ',';
            }
        }
        // 若是原图没有删掉就继续使用
        if(isset($_POST['originImgs'])) {
            foreach($_POST['originImgs'] as $img) {
                $pic_ids .= $img['id'] . ',';
            }
        }
        $pic_ids = rtrim($pic_ids, ',');
        $unit_name = '';
        $product = array(
            'name'             => $_POST['title'],
            'category_id'      => $_POST['category_id'],
            'status'           => C('status.common.success'),
            'created_time'     => $req_time,
            'updated_time'     => $req_time,
            'spec'             => json_encode($spec),
            'pic_ids'          => $pic_ids,
            'guarantee_period' => isset($_POST['guarantee_period']) ? $_POST['guarantee_period'] : '',
            'effect_stage'     => isset($_POST['effect_stage']) ? $_POST['effect_stage'] : '',
            'code'             => isset($_POST['code']) ? $_POST['code'] : '',
            'net_weight'       => isset($_POST['net_weight']) ? $_POST['net_weight'] : '',
            'min_safe_storage' => isset($_POST['min_safe_storage']) ? $_POST['min_safe_storage'] : '',
            'max_safe_storage' => isset($_POST['max_safe_storage']) ? $_POST['max_safe_storage'] : '',
            'unit_id'          => isset($_POST['unit_id']) ? $_POST['unit_id'] : 0,
        );
        if(!empty($product['unit_id'])) {
            $product['unit_name'] = $this->product_lib->get_unit_name($product['unit_id']);
        }
        return array(
            'product' => $product,
            'sync_data' => $sync_data
        );
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 处理规格属性数据
     */
    private function _deal_property() {
        $spec = [];
        $sync_data = [];
        // 将规格属性拼接到sub_title
        if(!empty($_POST['spec'])) {
            foreach($_POST['spec'] as $v) {
                if(is_array($v)) {
                    // 有一个property_id
                    $property = $this->MProperty->get_one(
                        '*',
                        array(
                            'id'    => $v['id']
                        )
                    );
                    if($property) {
                        $v['val'] = trim($v['val']);
                        if($v['val'] !== '') {
                            $spec[] = array(
                                'name' => $v['name'],
                                'id' => $v['id'],
                                'val' => $v['val']
                            );
                            $sync_data['attributes'][] = array(
                                $v['name'], $v['val']
                            );
                        }
                    }
                }
            }
        }
        return array('spec' => $spec, 'sync_data' => $sync_data);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description
     */
    public function set_status() {
        $status = $this->MSku->update_info(
            array(
                'status'   => $_POST['status']
            ),
            $_POST['where']
        );
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success')
            )
        );

    }

    public function units() {
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $this->units
            )
        );
    }

    private function _get_unit_id($name) {
        $id = $this->units[0]['id'];
        foreach($this->units as $v) {
            if($v['name'] == $name) {
                $id = $v['id'];
            }
        }
        return $id;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 同步到wms中去
     */
    private function _sync_to_wms($sku_info, $re_sync = FALSE, $update = FALSE) {
        if(empty($sku_info['attributes'])) {
            $sku_info['attributes'] = array();
        }
        $id = $sku_info['cate_id'];
        $info = $this->MCategory->get_one('path', array('id' => $id));
        $ids = explode('.', trim($info['path'], '.'));
        $data = $this->MCategory->get_lists('id, name', array('in' => array('id' => $ids)));
        $data_map = array_column($data, "name", "id");
        $category_path = '';
        foreach($ids as $item) {
            $category_path .= $data_map[$item] . ',';
        }
        $category_path = rtrim($category_path, ',');
        // 规格属性
        $sync_data = array(
            'category' => $category_path,
            'name' => $sku_info['name'],
            'attributes' => $sku_info['attributes'],
            'code'  => $sku_info['code'],
            'type' => 'product'
        );
        // 分类层级
        // 内部名称
        // 库存货号
        // 产品类型 --product
        if(!$update) {
            $return_msg = $this->wms_product->create($sync_data);
        } else {
            $return_msg = $this->wms_product->update($sync_data);
        }
        if($return_msg) {
            if(intval($return_msg['error_code']) === 0) {
                $return_msg['error_code'] = C('status.common.success');
            }
            // 更新下已经同步完成
            $up_data = array(
                'error_code' => $return_msg['error_code']
            );
            $where = array(
                'id' => $sku_info['id']
            );
            $this->MSku->update_info($up_data, $where);
        } else {
            $return_msg['error_code'] = C('tips.code.op_failed');
        }
        return $return_msg;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 同步失败后，然后继续同步，查明原因
     */
    public function re_sync() {
        $id = $_POST['id'];
        $sku_info = array(
            'id' => $_POST['id']
        );
        $info = $this->MSku->get_one('category_id, title, code', array('id' => $id));
        $spec = json_encode($info['spec'], TRUE);
        if($spec) {
            foreach($spec as $v) {
                $sku_info['attributes'][] = array(
                    $v['name'], $v['val']
                );
            }
        }
        $sku_info['cate_id'] = $info['category_id'];
        $sku_info['code'] = $info['code'];
        $sku_info['name'] = $info['title'];
        $code = $this->_sync_to_wms($sku_info, TRUE);
        if(intval($code) === 0) {
            $msg = array(
                'status' => C('tips.code.op_success'),
                'msg' => '同步成功'
            );
        } else {
            $msg = array(
                'status' => C('tips.code.op_success'),
                'msg' => '同步失败, 返回错误码' . $code
            );
        }
        $this->_return_json($msg);
    }
}

/* End of file product.php */
/* Location: ./application/controllers/product.php */
