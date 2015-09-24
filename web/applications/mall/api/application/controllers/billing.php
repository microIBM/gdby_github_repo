<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 账单管理
 *
 * @author : liaoxianwen@ymt360.com
 * @version : 1.0.0
 * @since : 2015-07-20
 */
class Billing extends MY_Controller
{

    public static $user_info = [];

    public function __construct()
    {
        parent::__construct();
        $cur = $this->userauth->current(TRUE);
        if (empty($cur)) {
            $this->_return_json(array(
                'status' => C('status.auth.login_timeout'),
                'msg' => '登录超时，请重新登录'
            ));
        } else {
            self::$user_info = $cur;
        }
        $this->load->library(
            array(
                'form_validation',
                'excel_export'
            )
        );
    }

    public function search_options()
    {
        $conditions = $this->format_query('/billing/get_condition');
        $condition_response = array(
            'status' => $conditions['status'],
            'msg' => '账单列表搜索条件获取成功',
            'list' => $conditions['billing_status']
        );
        $this->_return_json($condition_response);
    }

    public function lists()
    {
        $this->form_validation->set_rules('currentPage', '当前页', 'required');
        $this->form_validation->set_rules('itemsPerPage', '每页显示数', 'required');
        $this->form_validation->set_rules('status', '账单列表状态', 'required');
        $this->validate_form();

        $user = self::$user_info;
        $page = $this->get_page();
        $list_search_post_data = array(
            'customer_id' => $user['id']
        );
        if (! empty($this->post['startTime'])) {
            $list_search_post_data['start_time'] = $this->post['startTime'];
        }
        if (! empty($this->post['endTime'])) {
            $list_search_post_data['end_time'] = $this->post['endTime'];
        }
        if (isset($this->post['status'])) {
            $list_search_post_data['status'] = (int) $this->post['status'];
        }
        $list_search_post_data['currentPage'] = $page['page'];
        $list_search_post_data['itemsPerPage'] = $page['page_size'];
        $billing_lists_response = $this->format_query('/billing/shop_billing_list', $list_search_post_data);
        $this->_format_billing_lists($billing_lists_response);
        $this->_return_json($billing_lists_response);
    }

    private function _get_order_status($status)
    {
        $order_status_finish = "待收货";
        switch (intval($status)) {
            case C('order.status.success.code'):
                $order_status_finish = "已完成";
                break;
            case C('order.status.wait_comment.code'):
                $order_status_finish = "已完成";
                break;
            case C('order.status.sales_return.code'):
                $order_status_finish = "已完成";
                break;
        }
        return $order_status_finish;
    }

    private function _format_billing_lists(&$billing_lists_response)
    {
        $status_cn_info = array_values(C('order.status'));
        $order_status_cn = array_combine(array_column($status_cn_info, 'code'), $status_cn_info);
        if (! empty($billing_lists_response['list'])) {
//             foreach ($billing_lists_response['list'] as &$billing_list) {
//                 if (! empty($billing_list['order'])) {
//                     foreach ($billing_list['order'] as &$billing_order) {
//                         if (! empty($billing_order['order_list'])) {
//                             foreach ($billing_order['order_list'] as &$order) {
//                                 $order['status_cn'] = empty($order_status_cn[$order['status']]['msg']) ? '异常订单，状态值不存在' : $order_status_cn[$order['status']]['msg'];
//                                 if  ($order['status'] != C('order.status.closed.code')) {
//                                     $order['status_pool'] =  $this->_get_order_status($order['status']);
//                                 }
//                             }
//                         }
//                     }
//                 }
//             }
            unset($billing_list);
            unset($billing_order);
            unset($order);
        }
    }

    public function export_billing_csv()
    {
        $id = empty($this->uri->segment(3)) ? die('缺少id') : $this->uri->segment(3);
        $this->_check_billing_customer($id);
        $billing_info = $this->format_query('/billing/export_billing', array('id' => $id));
        if(empty($billing_info)) {
            echo '当前账单无效';exit;
        }
        $this->_export(array('账单'), $billing_info['orders']);
        echo '导出成功';
    }

    private function _check_billing_customer($id) {
        $user = self::$user_info;
        $check_info = $this->format_query('/billing/check_billing_customer', array(
            'id' => $id,
            'customer_id' => $user['id']
        ));
        if (intval($check_info['status']) !== 0) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '当前账单有异常'
            ));
        }
    }

    public function shop_agree_pay()
    {
        $this->form_validation->set_rules('id', '账单id', 'required');
        $this->_check_billing_customer($this->post['id']);
        $response = $this->format_query('/billing/shop_agree_pay', array(
            'id' => $this->post['id']
        ));
        $this->_return_json($response);
    }

    private function _export($title_arr, $bill_info) {
                // 在公共的方法中使用导出时间不限制
        // 以防止数据表太大造成csv文件过大,造成超时
        foreach($bill_info as $key => &$item) {
            $item[] = [
                '总价', $item['deal_price'], '配送日期', $item['deliver_date']
            ];
        }
        $bill_info_title = [
            '订单编号',
            '订单状态',
            '订货金额',
            '拒收金额',
            '收货金额',
            '运费',
            '优惠',
            '应付金额'
        ];
        $new_bill_info[] = $bill_info_title;
        foreach($bill_info as &$bill) {
            foreach($bill as $bill_detail) {
                if(is_array($bill_detail)) {
                    $is_bottom = FALSE;
                    foreach($bill_detail as $bill_detail_info) {
                        if(is_array($bill_detail_info)) {
                            $new_bill_info[] = array_values($bill_detail_info);
                            $is_bottom = TRUE;
                        }
                    }
                    if(!$is_bottom) {
                        $new_bill_info[] = $bill_detail;
                    }
                }
            }
        }

        $bill_info = array($new_bill_info);
        $this->excel_export->export($bill_info, $title_arr, 'bill.xls');
   }

    /**
     * ription 支付凭证
     *
     * @author : liaoxianwen@ymt360.com
     */
    public function payment_evidence()
    {
        $this->form_validation->set_rules('id', '账单id', 'required');
        $this->form_validation->set_rules('evidence', '账单图片地址', 'required');
        $this->_check_billing_customer($this->post['id']);
        $response = $this->format_query('/billing/payment_evidence', $this->post);
        $this->_return_json($response);
    }
}

/* End of file billing.php */
/* Location: ./application/controllers/billing.php */
