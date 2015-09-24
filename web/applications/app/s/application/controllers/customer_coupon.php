<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 客户模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Customer_coupon extends MY_Controller {
    private $_rules_type;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer_coupons',
                'MCustomer',
                'MOrder',
                'MLocation',
                'MCoupons',
                'MProduct',
                'MCategory_map',
                'MCategory',
                'MCoupon_rules'
            )
        );
        $this->load->helper(array('coupon_code', 'compare_minus_amount'));
        $this->_rules_type  = C('coupon_rule_type');
        $this->app_sites = array_values(C('app_sites'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 用户优惠券
     */
    public function lists() {
        $_POST['where'] = empty($_POST['where']) ? array() : $_POST['where'];
        $where = '';
        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        if(isset($_POST['status'])) {
            if(intval($_POST['status']) === 0) {
                $status = C('coupon_status.used.value') . ',' .C('coupon_status.exceed_time.value');
                $where = "(valid_time > {$current} OR invalid_time < {$current} OR status in ($status)) AND status != 0";
            } else {
                $status = C('coupon_status.valid.value') . ',' .C('coupon_status.invalid.value');
                //$status = C('coupon_status.valid.value');
                $where = "valid_time <= {$current} AND invalid_time >= {$current} and status in ($status)";
            }
        }
        if(isset($_POST['where']['customer_id'])) {
            $where .= ' AND customer_id =' . $_POST['where']['customer_id'];
        }
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : 'created_time DESC';
        $page = $this->get_page();
        $total = $this->MCustomer_coupons->count_by_sql($where);
        $customer_total_where = array();
        if(isset($_POST['where']['customer_id'])) {
            $customer_total_where = array('customer_id' => $_POST['where']['customer_id']);
        }
        $all_coupon_nums = $this->MCustomer_coupons->count($customer_total_where);
        $data = $this->MCustomer_coupons->get_lists_by_sql(
            '*',
            $where,
            'id desc',
            array(),
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $response = $this->_format_customer_coupon_data($data);
            $response['all_nums'] = $all_coupon_nums;
            $response['total'] = $total;
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '暂无优惠券'
            );
        }
        $this->_return_json($response);
        // 查出用户拥有的优惠券
        // 优惠券信息的常规信息
        // 优惠券对应的减免规则
    }


    /**
     * @author: liaoxianwen@ymt360.com
     * @description 用户优惠券
     */
    public function manage() {
        $_POST['where'] = empty($_POST['where']) ? array() : $_POST['where'];
        $where = '';
        $user_info = [];
        if(isset($_POST['where']['mobile'])) {
            $user_info = $this->MCustomer->get_lists('id', array('mobile' => $_POST['where']['mobile']));
            $_POST['where']['in']['customer_id'] = array_column($user_info, 'id');
        }

        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        if(isset($_POST['where']['status'])) {
            $_POST['status'] = $_POST['where']['status'];
            if(intval($_POST['status']) === 0 || $_POST['status'] == 3) {
                $where = "status = {$_POST['status']}";
            } else if($_POST['status'] == 1) {
                $status = C('coupon_status.valid.value') . ',' .C('coupon_status.invalid.value');
                $where = "valid_time <= {$current} AND invalid_time >= {$current} and status in ($status)";
            } else if($_POST['status'] == 2) {
                $where = "valid_time > {$current}";
                //已使用
            } else {
                $where = "invalid_time <  {$current}";
                // 过期
            }
        }
        $customer_total_where = array();
        if(isset($_POST['where']['in']['customer_id'])) {
            if($where) {
                $where .= ' AND ';
            }
            $customer_ids = implode(',', $_POST['where']['in']['customer_id']);
            $where .= 'customer_id in (' . $customer_ids . ')';
            $customer_total_where = array(
                'in' => array(
                    'customer_id' => $customer_ids
                )
            );
        }
        // 查询优惠券的有效期
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : 'created_time DESC';
        $page = $this->get_page();
        $total = $this->MCustomer_coupons->count_by_sql($where);
        $all_coupon_nums = $this->MCustomer_coupons->count($customer_total_where);
        $data = $this->MCustomer_coupons->get_lists_by_sql(
            '*',
            $where,
            'id desc',
            array(),
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $response = $this->_format_customer_coupon_data($data);
            $response['all_nums'] = $all_coupon_nums;
            $response['total'] = $total;
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '暂无优惠券'
            );
        }
        $this->_return_json($response);
        // 查出用户拥有的优惠券
        // 优惠券信息的常规信息
        // 优惠券对应的减免规则
    }

    public function count() {
        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        $where = array();
        if(isset($_POST['status'])) {
            $where['status'] = $_POST['status'];
        }
        if(isset($_POST['customer_id'])) {
            $where['customer_id'] = $_POST['customer_id'];
        }

        $where['valid_time <='] =$current;
        $where['invalid_time >='] = $current;
        $total = $this->MCustomer_coupons->count($where);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'total' => $total
            )
        );
    }

    public function valid_coupon() {
        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        $where['valid_time <='] = $current;
        $where['customer_id']   = $_POST['customer_id'];
        $where['status']        = C('coupon_status.valid.value');
        $where['invalid_time >='] = $current;
        $products = empty($_POST['products']) ? array() : $_POST['products'];

        //获取用户优惠券，在结合购买的商品去刷选购物券
        $customer_coupons = $this->MCustomer_coupons->get_lists('*', $where);
        //如果用户有优惠券格式化下
        if( ! empty($customer_coupons)) {
            $customer_coupons = $this->_format_customer_coupon_data($customer_coupons);
        }
        if (empty($customer_coupons)) {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '暂无优惠券'
            );
        } else {
            //使用用户购物车的数据去刷选，本次购物有效的优惠券
            $customer_valid_coupons    = $customer_coupons['list'];
            //通过购物车刷选过后的优惠券
            $customer_coupons_cartlist = $this->_filter_valid_coupons_by_cartlist($customer_valid_coupons, $products);
            if ($customer_coupons_cartlist) {
                $response = array(
                    'status' => C('tips.code.op_success'),
                    'list' => $customer_coupons_cartlist
                );
            } else {
                $response = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '本次没有符合条件的优惠券'
                );
            }
        }
        $this->_return_json($response);
    }

    private function _filter_valid_coupons_by_cartlist($customer_coupons, $products) {
        if (empty($customer_coupons)) {
            return array();
        }
        //商品id对应商品的价格
        $pid_price_map = [];
        //分类id对应商品价格
        $cid_price_map = [];
        //所有商品的总价格
        //购物车商品分类信息
        $category_ids = array_column($products, "category_id");
        $categories   = $this->MCategory->get_lists("id, path", array(
            'in' => array(
                'id' => $category_ids
            ),
        ));
        $path_map = array_column($categories, "path", "id");

        // 检查在分类上是否满足条件
        $all_category_in_coupon = [];
        foreach($customer_coupons as $c) {
            $category_ids_str = $c['detail']['category_ids'];
            if ($category_ids_str) {
                $all_category_in_coupon = array_merge($all_category_in_coupon, explode(',', $category_ids_str));
            }
        }
        if ($all_category_in_coupon) {
            $all_category_in_coupon = array_unique($all_category_in_coupon);
        }
        // 统计每个分类
        $category_sum = array();
        foreach($all_category_in_coupon as $cate) {
            $category_sum[$cate] = 0;
            foreach($products as $item) {
                $path = $path_map[$item['category_id']];
                if(strpos($path, ".{$cate}.") !== FALSE) {
                    $category_sum[$cate] += $item['price'] * $item['quantity'];
                    // 如果有鸡蛋，则扣掉蛋框钱
                    if( in_array($item['sku_number'], array(1000013, 100020, 100026)) ) {
                        $category_sum[$cate] -= 20 * $item['quantity'];
                    }
                }
            }
        }

        $total_price = 0;
        foreach($products as $product) {
            $single_total_price = $product['price'] * $product['quantity'];
            $total_price += $single_total_price;
            $pid_price_map[$product['id']] = $single_total_price;
        }
        //最终的优惠券
        $result_coupons = [];
        foreach($customer_coupons as $coupon) {
            $category_ids = $coupon['detail']['category_ids'];
            $product_ids  = $coupon['detail']['product_ids'];
            $require_amount = $coupon['require_amount'];
            $minus_amount   = $coupon['minus_amount'];
            //全场通用卷
            if (empty($category_ids) && empty($product_ids)) {
                if (($require_amount <= $total_price) && ($minus_amount <= $total_price)) {
                    $result_coupons[] = $coupon;
                    continue;
                }
            //特定分类的优惠券
            } else if (empty($product_ids) && $category_ids) {
                $category_ids_arr = explode(',', $category_ids);
                $total_price_of_specify_cate = 0;
                foreach($category_ids_arr as $cid) {
                    $total_price_of_specify_cate += isset($category_sum[$cid]) ? $category_sum[$cid] : 0;
                }
                if (($require_amount <= $total_price_of_specify_cate) && ($minus_amount <= $total_price_of_specify_cate)) {
                    $result_coupons[] = $coupon;
                    continue;
                }
            //特定商品的优惠券
            } else if (empty($category_ids) && $product_ids) {
                $product_ids_arr = explode(',', $product_ids);
                $total_price_of_specify = 0;
                foreach($product_ids_arr as $pid) {
                    $total_price_of_specify += isset($pid_price_map[$pid]) ? $pid_price_map[$pid] : 0;
                }
                if (($require_amount <=$total_price_of_specify) && ($minus_amount <= $total_price_of_specify)) {
                    $result_coupons[] = $coupon;
                }
            }
        }
        return $result_coupons;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化用户优惠券的数据
     */
    private function _format_customer_coupon_data($data) {
        $coupon_ids = array_column($data, 'coupon_id');
        $customer_ids = array_unique(array_column($data, 'customer_id'));
        $customers = $this->MCustomer->get_lists('shop_name, id, mobile', array('in' => array('id' => $customer_ids)));
        $customer_ids = array_column($customers, 'id');
        $new_customers = array_combine($customer_ids, $customers);
        $coupon_where = array(
            'in' => array(
                'id' => $coupon_ids
            ),
        );
        $coupons = $this->MCoupons->get_lists('*', $coupon_where);
        if($coupons) {
            $coupon_detail = [];
            $locations = $this->MLocation->get_lists__Cache120('*', array('upid' => 0));
            $new_locations = array_combine(array_column($locations, 'id'), $locations);
            $new_sites = array_combine(array_column($this->app_sites, 'id'), $this->app_sites);
            $new_rules_type = array_combine(array_column($this->_rules_type, 'id'), $this->_rules_type);
            foreach($coupons as $cou_val) {
                $rule_info = $this->MCoupon_rules->get_one('*', array('id' => $cou_val['coupon_rule_id']));
                // 支持某个分类的优惠券，或者特定商品
                if ( ! empty($cou_val['category_ids'])) {
                    $category_ids = explode(',', $cou_val['category_ids']);
                    $category_ids = array_unique($category_ids);
                    $catemaps = $this->MCategory->get_lists(
                        'name',
                        array(
                            'in' => array('id' => $category_ids)
                        )
                    );
                    $desc = $catemaps ? implode(',', array_column($catemaps, 'name')) : '';
                    $description = '仅限' . $desc . '使用';
                } else if ( ! empty($cou_val['product_ids'])) {
                    $product_ids = explode(',', $cou_val['product_ids']);
                    $product_ids = array_unique($product_ids);
                    $product_title = $this->MProduct->get_lists(
                        'title',
                        array(
                            'in' => array('id' => $product_ids)
                        )
                    );
                    $desc = $product_title ? implode(',', array_column($product_title, 'title')) : '';
                    $description = '仅限' . $desc . '使用';
                } else {
                    $description = '全品类通用';
                }
                $cou_val['site_cn'] = $new_sites[$cou_val['site_id']]['name'];
                $cou_val['location_cn'] = $new_locations[$cou_val['location_id']]['name'];

                $rule_info['rule_type_cn'] = $new_rules_type[$rule_info['rule_type']]['name'];
                $cou_val['valid_time'] = date('Y-m-d', $cou_val['valid_time']);
                $cou_val['invalid_time'] = date('Y-m-d', $cou_val['invalid_time']);
                $coupon_detail[$cou_val['id']] = array(
                    'coupon_info' => $cou_val,
                    'description' => $description,
                    'rule_info' => $rule_info,
                );
            }
            $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
            // 优惠券活动的location_id site_id
            // 遍历一个个用户优惠券，然后把不符合条件的删除掉
            foreach($data as &$v) {
                $detail = $coupon_detail[$v['coupon_id']];
                $v['updated_time'] = date('Y-m-d H:i', $v['updated_time']);
                $v['customer_info'] = empty($new_customers[$v['customer_id']]) ? array() : $new_customers[$v['customer_id']];
                $v['require_amount'] /= 100;
                $v['minus_amount'] /= 100;
                if(($v['valid_time'] > $current || $v['invalid_time'] < $current) &&  $v['status'] != 3 & $v['status'] != 0) {
                    if($v['invalid_time'] < $current) {
                        $v['status'] = C('coupon_status.exceed_time.value');
                        $v['status_cn'] = C('coupon_status.exceed_time.name');
                    } else {
                        $v['status'] = C('coupon_status.invalid.value');
                        $v['status_cn'] = C('coupon_status.invalid.name');
                    }
                } else {
                    if($v['status'] == 1) {
                        $v['status_cn'] = C('coupon_status.valid.name');
                    } else if($v['status'] == 3) {
                        $v['status_cn'] = C('coupon_status.used.name');
                    } else {
                        $v['status_cn'] = C('coupon_status.forbid.name');
                    }
                }
                if(empty($v['customer_id'])) {
                    $v['status'] = 0;
                    $v['status_cn'] = C('coupon_status.forbid.name');
                }

                $v['detail'] = array(
                    'require_amount' => $v['require_amount'],
                    'minus_amount' => $v['minus_amount'],
                    'valid_time' => $detail['coupon_info']['valid_time'],
                    'site_cn' => $detail['coupon_info']['site_cn'],
                    'location_cn' => $detail['coupon_info']['location_cn'],
                    'invalid_time' => $detail['coupon_info']['invalid_time'],
                    'rule_type_cn' => $detail['rule_info']['rule_type_cn'],
                    'description' => $detail['description'],
                    'category_ids' => $detail['coupon_info']['category_ids'],
                    'product_ids'  => $detail['coupon_info']['product_ids'],
                );
            }
            $response = array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            );
        }
        return !empty($response) ? $response : array();
    }
    public function check_coupon_valid() {
        // 若是全品类的或者全部商品优惠
        // 当前提交的优惠券
        $info = $this->MCustomer_coupons->get_one('*', array('id' => $_POST['id'], 'customer_id' => $_POST['customer_id']));
        // 检测是否为全品类
        $date_format_time = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        $where = array(
            'valid_time <=' => $date_format_time,
            'invalid_time >=' => $date_format_time,
            'require_amount <=' => $_POST['total_price'],
            'minus_amount <=' => $_POST['total_price'],
            'id' => $_POST['id'],
            'customer_id' => $_POST['customer_id'],
            'status' => C('coupon_status.valid.value')
        );
        $info = $this->MCustomer_coupons->get_one('*', $where);
        if($info) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'info' => $info
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '此券无效'
            );
        }
        $this->_return_json($response);
    }

    private function _check_coupon_valid_by_category() {
        // 检测购买的商品的分类是否在规定的优惠分类中
    }

    private function _check_coupon_valid_by_product() {
        // 检测商品中是否有在优惠中得商品
    }

    private function _check_coupon_resolve($coupon) {
        if(!empty($coupon['category_ids'])) {
            return $this->_check_coupon_valid_by_category();
        } else if(!empty($coupon['product_ids'])) {
            return $this->_check_coupon_valid_by_product();
        }
        return TRUE;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    public function create() {
        $coupon_info = $this->MCoupons->get_one('*', array('coupon_nums >' => 0, 'id' => $_POST['coupon_id']));
        if($coupon_info) {
            $rule_info = $this->MCoupon_rules->get_one('*', array('id' => $coupon_info['coupon_rule_id']));
            // 比较减免3000额度
            if($compare_info = compare_minus_amount($rule_info['minus_amount']))  {
                $this->_return_json($compare_info);
            }
            // 根据优惠券活动的site_id location_id line_ids 来确定用户数量
            $customer_where = array(
                'province_id' => $coupon_info['location_id'],
                'status !=' => C('status.common.del'),
            );
            if($coupon_info['line_ids'] != 0) {
                $line_ids = explode(',', $coupon_info['line_ids']);
                $customer_where['in'] = array('line_id' => $line_ids);
            }
            if(isset($_POST['customer_ids']) && is_array($_POST['customer_ids']) && $_POST['customer_ids']) {
                $customer_where['in']['id'] = $_POST['customer_ids'];
            }
            $customers = $this->MCustomer->get_lists('*', $customer_where);
            if($customers) {
                $coupon_codes = coupon_code_create(count($customers));
                $coupon_nums = empty($_POST['coupon_nums']) ? 1 : $_POST['coupon_nums'];
                foreach($customers as $key => $customer) {
                    //用户优惠券的创建
                    $data[] = array(
                        'coupon_id' => $_POST['coupon_id'],
                        'customer_id' => $customer['id'],
                        'coupon_rule_id' => $coupon_info['coupon_rule_id'],
                        'coupon_code' => $coupon_codes[$key],
                        'require_amount' => $rule_info['require_amount'],
                        'minus_amount' => $rule_info['minus_amount'],
                        'coupon_nums' => $coupon_nums,
                        'status' => C('status.common.success'),
                        'valid_time' => $coupon_info['valid_time'],
                        'invalid_time' => $coupon_info['invalid_time'],
                        'created_time' => $this->input->server('REQUEST_TIME'),
                        'updated_time' => $this->input->server('REQUEST_TIME')
                    );
                }
                $affect_rows = $this->MCustomer_coupons->create_batch($data);
                if($affect_rows) {
                    // 更新coupons 的coupon_nums coupon_used_nums
                    $coupon_updata = array(
                        'coupon_nums' => $coupon_info['coupon_nums'] -1,
                        'coupon_used_nums' => $coupon_info['coupon_used_nums'] + 1
                    );
                    $this->MCoupons->update_info($coupon_updata, array('id' => $coupon_info['id']));
                    $total_coupon = $affect_rows * $coupon_nums;
                    $response = array(
                        'status' => C('tips.code.op_success'),
                        'msg' => "券码分发成功,共发放{$affect_rows}个用户，发放了{$total_coupon}"
                    );
                }
            } else {
                $response = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '券活动信息有误'
                );
            }
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '券活动信息有误'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 单个的优惠券信息
     */
    public function info() {
        $coupon_info = $this->MCustomer_coupon->get_one('*', array('id' => $_POST['id']));
        $response = array('status' => C('tips.code.op_faild') , 'msg' => '没有此优惠券');
        if($coupon_info) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'info' => $coupon_info
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    public function set_status() {
        $updata = array(
            'status' => $_POST['status']
        );
        $where = array('id' => $_POST['id']);
        $this->MCustomer_coupons->update_info($updata, $where);
        $this->_return_json(array('status' => C('tips.code.op_success'), 'msg' => '设置成功'));
    }

    public function set_coupon_used_nums() {
        $where = array('id' => $_POST['id'], 'status' => C('coupon_status.valid.value'));
        $info = $this->MCustomer_coupons->get_one("*", $where);
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '无优惠券'
        );
        $order_info = $this->MOrder->get_one('*', array('customer_coupon_id' => $_POST['id']));
        if($info && $order_info) {
            if($info['coupon_nums'] >= 1) {
                $data = array(
                    'coupon_nums' => 0,
                    'coupon_used_nums' => 1,
                );
                $data['status'] = C('coupon_status.used.value');
                $affect_rows = $this->MCustomer_coupons->update_info($data, $where);
                $response = array(
                    'status' => C('tips.code.op_success')
                );
            }
        }

        $this->_return_json($response);
    }
}

/* End of file customer_coupon.php */
/* Location: ./application/controllers/customer_coupon.php */
