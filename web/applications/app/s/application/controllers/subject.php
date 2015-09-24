<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 专题服务
 * @author: liaoxianwen@ymt360.com
 * @version: 2.0.0
 * @since: 2015-5-12
 */
class Subject extends MY_Controller {

    public $subject_types = array();
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MSubject',
            )
        );

        $this->load->library(array('redisclient', 'product_lib'));
        $this->subject_types = C('subject_type');
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个商品信息
     */
    public function info() {
        $cur = empty($_POST['cur']) ? [] : $_POST['cur'];
        $info = $this->MSubject->get_one('*', array('id' => $_POST['id'], 'status' => C('status.common.success')));
        // 获取分类信息
        if(!empty($info['product_ids'])) {
            $product_ids = explode(',', $info['product_ids']);
            if($product_ids) {
                // 若某个商品id被禁用了
                $search_products = $this->MProduct->get_lists('id, location_id, sku_number, status', array('in' => array('id' => $product_ids)));
                $sku_numbers = array_column($search_products, 'sku_number');
                $location_id = $search_products[0]['location_id'];
                $products = $this->MProduct->get_lists__Cache120('*',
                    array(
                        'location_id' => $location_id,
                        'in' => array(
                            'sku_number' => $sku_numbers
                        ),
                        'customer_type' => empty($cur['customer_type']) ? C('customer.type.normal.value') : $cur['customer_type'],
                        'status' => C('status.common.success')
                    )
                );
                if($products) {
                    $this->product_lib->format_product_data($products);
                    $info['products'] = $products;
                }
            }
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'info'   => $info
            )
        );
    }
       // 创建专题
    public function create() {
        $resolve_data = $this->_resolve_data();
        extract($resolve_data);
        $subject_id = $this->MSubject->create($subject);
        // 判断类型是否为多品专题
        if($subject_id) {
            $return_msg = array(
                'status' => C('tips.code.op_success'),
                'msg' => '创建成功',
            );
        } else {
            $return_msg = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '创建失败'
            );
        }
        $this->_return_json($return_msg);
    }
    // 保存
    public function save() {
        $this->_return_json($_POST);
    }

    private function _resolve_data() {
        $data = array(
            'pic_url'         => empty($_POST['pic_url']) ? '' : $_POST['pic_url'],
            'banner_url'      => empty($_POST['banner_url']) ? '' : $_POST['banner_url'],
            'detail_img'      => empty($_POST['detail_img']) ? '' : $_POST['detail_img'],
            'site_id'         => empty($_POST['site_id']) ? '' : $_POST['site_id'],
            'site_cn'         => $this->_get_site_name(),
            'location_id'     => empty($_POST['location_id']) ? '' : $_POST['location_id'],
            'location_cn'     => $this->_get_location_name(),
            'offline_time'    => empty($_POST['endTime']) ? '' : $_POST['endTime'],
            'online_time'     => empty($_POST['startTime']) ? '' : $_POST['startTime'],
            'subject_type'    => empty($_POST['subjectType']) ? '' : $_POST['subjectType'],
            'subject_type_cn' => $this->_get_subject_type_cn(),
            'status'          => $_POST['status'],
            'title'           => empty($_POST['title']) ? '' : $_POST['title'],
            'created_time'    => $this->input->server('REQUEST_TIME'),
            'updated_time'    => $this->input->server('REQUEST_TIME'),
        );
        if(!empty($_POST['products']) && is_array($_POST['products'])) {
            $data['product_ids'] = implode(',', $_POST['products']);
        }
        return array(
            'subject' => $data
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取网站名称
     */
    private function _get_site_name() {
        $sites = C('app_sites');
        $site_cn = '';
        foreach($sites as $v) {
            if($v['id'] == $_POST['site_id']) {
                $site_cn = $v['name'];
            }
        }
        return $site_cn;
    }

    private function _get_location_name() {
        $location = $this->MLocation->get_one(
            'name, id',
            array(
                'id' => $_POST['location_id']
            )
        );
        return $location ? $location['name'] : 0;

    }

    private function _get_subject_type_cn() {
        $subject_types = C('subject_type');
        $subject_type_cn = '';
        foreach($subject_types as $v) {
            if($v['id'] == $_POST['subjectType']) {
                $subject_type_cn = $v['name'];
            }
        }
        return $subject_type_cn;
    }

    public function manage() {
        $page = $this->get_page();
        $where = array();
        if(!empty($_POST['where'])) {
            $where = $_POST['where'];
        }
        $total =  $this->MSubject->count($where);
        $data = $this->MSubject->get_lists(
            array(),
            $where,
            array('id' => 'DESC', 'updated_time' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $this->_resolve_subject_data($data);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data,
                'total' => $total
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化数据
     */
    private function _resolve_subject_data(&$data) {
        if($data) {
            foreach($data as &$v) {
                $v['updated_time'] = date('Y-m-d H:i', $v['updated_time']);
                $v['online_time_cn'] = date('Y-m-d', $v['online_time']) . ' 00:00';
                $v['offline_time_cn'] = date('Y-m-d', $v['offline_time']) . ' 23:00';
            }
        }
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 设置状态
     */
    public function set_status() {
        $data = array(
            'status' => $_POST['status']
        );
        if(isset($_POST['is_active'])) {
            $data['is_active'] = $_POST['is_active'];
        }
        if(!empty($_POST['updated_time'])) {
            $data['updated_time'] = $_POST['updated_time'];
        }
        $this->MSubject->update_info($data, $_POST['where']);
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success'),
                'msg' => '操作成功'
            )
        );

    }
}

/* End of file subject.php */
/* Location: ./application/controllers/subject.php */
