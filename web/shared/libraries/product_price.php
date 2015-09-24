<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 价格服务
 * @author yugang@dachuwang.com
 * @since 2015-08-10
 * @description 提供价格的计算与校验
 */
class Product_price
{
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MCategory',
                'MProduct',
                'MMember_rebate',
                'MCustomer',
            )
        );
    }

    /**
     * 获取商品折扣后的价格
     * @author yugang@dachuwang.com
     * @since 2015-08-10
     * @param $products  array 产品列表
     * @param $customer_id int 客户ID
     * @param $format boolean 是否格式化价格单位为元，即是否将价格除100
     * @return 返回计算完折扣后的商品列表，修改每个商品的price字段为折扣后的价格，
     * 增加origin_price字段，记录商品折扣前价格，增加rebate字段，记录商品折扣
     */
    public function get_rebate_price($products, $customer_id, $format = true)
    {
        // 获取当前客户母账号的id
        if (!empty($customer_id)) {
            $customer_id = $this->CI->MCustomer->get_parent_id($customer_id);
        }
        // 从数据库中获取最新的价格
        $product_ids = array_column($products, 'id');
        $db_products = $this->CI->MProduct->get_lists('*', ['in' => ['id' => $product_ids]]);
        $db_products_dict = array_combine(array_column($db_products, 'id'), $db_products);

        // 获取当前客户对于所有一级品类的折扣,没有设置则为100%
        $top_categories = $this->CI->MCategory->get_lists('id, name, path', ['upid' => 0, 'status' => C('status.common.normal')]);
        $rebates = $this->CI->MMember_rebate->get_lists('*', ['customer_id' => $customer_id, 'status' => C('status.common.normal')]);
        $rebate_dict = array_column($rebates, 'rebate', 'category_id');
        foreach ($top_categories as &$top_category) {
            if (isset($rebate_dict[$top_category['id']])) {
                $top_category['rebate'] = intval($rebate_dict[$top_category['id']]);
            } else {
                $top_category['rebate'] = 100;
            }
        }
        unset($top_category);

        // 获取当前产品的所属分类的折扣并计算出产品最终售卖价格
        $category_ids = array_column($products, 'category_id');
        $categories = $this->CI->MCategory->get_lists('id, path', ['in' => ['id' => $category_ids]]);
        $category_map = array_column($categories, 'path', 'id');
        foreach ($products as &$product) {
            $product['category_path'] = isset($category_map[$product['category_id']]) ? $category_map[$product['category_id']] : '';
            foreach ($top_categories as $top_cate) {
                if (strpos($product['category_path'], $top_cate['path']) === 0) {
                    $product['rebate'] = $top_cate['rebate'];
                    $db_price = isset($db_products_dict[$product['id']]) ? $db_products_dict[$product['id']]['price'] / 100 : 0;
                    $product['origin_price'] = $db_price;
                    // 折扣价格保留到小数点后一位：角
                    $product['price'] = intval($db_price * $product['rebate'] / 10) / 10;
                    // 如果价格不需要格式化，则返回价格单位为分
                    if (!$format) {
                        $product['origin_price'] *= 100;
                        $product['price'] *= 100;
                    }
                }
            }
        }
        unset($product);

        return $products;
    }


}
/* End of file  product_price.php*/
/* Location: :./application/libraries/product_price.php */
