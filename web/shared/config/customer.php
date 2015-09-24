<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'status' => array(
        'disabled'   => array(
            'code' => -1
        ),
        'valid'   => array(
            'code' => 1
        ),
        'invalid' => array(
            'code' => 0
        ),
        'new' => array(
            'code' => 1,
            'msg'  => '已注册未下单'
        ),
        'undone' => array(
            'code' => 10,
            'msg'  => '下单未完成'
        ),
        'unallocated' => array(
            'code' => 11,
            'msg'  => '待分配'
        ),
        'allocated' => array(
            'code' => 12,
            'msg'  => '已分配'
        ),
        'no_bd' => array(
            'code' => 13,
            'msg'  => '公海完成订单但无对应BD客户'
        )
    ),
    'site_id' => array(
        'dachu' => array(
            'code' => 1
        ),
        'daguo' => array(
            'code' =>2
        )
    ),
    'direction' => array(
        'east' => array(
              'label'  => '东',
            'value' => 'east'
        ),
        'south' => array(
            'label'  => '南',
            'value' => 'south'
        ),
        'west' => array(
            'label'  => '西',
            'value' => 'west'
        ),
        'north' => array(
            'label'  => '北',
            'value' => 'west'
        ),
    ),
    'dimension' => array(
        '0-10'  => array(
            'name'  => '10平米以下',
            'value' => '0-10'
        ),
        '10-20'  => array(
            'name'  => '10-20平',
            'value' => '10-20'
        ),
        '20-50'  => array(
            'name'  => '20-50平',
            'value' => '20-50'
        ),
        '50-100'  => array(
            'name'  => '50-100平',
            'value' => '50-100'
        ),
        '100+'  => array(
            'name'  => '100平米以上',
            'value' => '100+'
        ),
    ),
    'type' => array(
        'normal'  => array(
            'name'  => '普通客户',
            'value' => '1'
        ),
        'KA'  => array(
            'name'  => 'KA客户',
            'value' => '2'
        ),
    ),
    'list_type' => array(
        'normal'  => array(
            'name'  => '普通客户',
            'value' => '1'
        ),
        'KA'  => array(
            'name'  => 'KA客户',
            'value' => '2'
        ),
        'TO_AUDIT'  => array(
            'name'  => '待审核客户',
            'value' => '3'
        ),
    ),
    'cloudmap' => array(
        'ctype' => array(
            'my_potential_customer' => array(
                'name'  => '我的潜在客户',
                'value' => '1'
            ),
            'my_customer' => array(
                'name'  => '我的注册客户',
                'value' => '2'
            ),
            'other_potential_customer' => array(
                'name'  => '其他潜在客户',
                'value' => '3'
            ),
            'other_customer' => array(
                'name'  => '其他注册客户',
                'value' => '4'
            ),
            'open_potential_customer' => array(
                'name'  => '公海潜在客户',
                'value' => '5'
            ),
            'open_customer' => array(
                'name'  => '公海注册客户',
                'value' => '6'
            ),
        ),
        'search_range' => '1',
        'max_result' => '20',
    ),
    'public_sea_code'  => -1,
    'customer_type' => array(
        'potential_customer' => array(
            'code' => 1,
            'msg'  => '潜在客户',
        ),
        'customer' => array(
            'code' => 2,
            'msg'  => '注册客户',
        ),
    ),
    'customer_visiable' => array(
        'all' => 0,
        'normal' => 1,
        'ka'  => 2,
    ),
    'order_record' => array(
        'with' => array(
            'code' => 1,
            'msg'  => '有下单记录',
        ),
        'without' => array(
            'code' => 2,
            'msg'  => '无下单记录',
        ),
    ),
    'account_type' => array(
        'parent' => array(
            'name'  => '母账号',
            'value' => 1,
        ),
        'child' => array(
            'name'  => '子账号',
            'value' => 2,
        ),
    ),
    'bank' => array(
        array(
            'name'  => '中国工商银行',
            'value' => 1,
        ),
        array(
            'name'  => '招商银行',
            'value' => 2,
        ),
        array(
            'name'  => '中国银行',
            'value' => 3,
        ),
        array(
            'name'  => '中国农业银行',
            'value' => 4,
        ),
        array(
            'name'  => '中国建设银行',
            'value' => 5,
        ),
        array(
            'name'  => '交通银行',
            'value' => 6,
        ),
        array(
            'name'  => '中国邮政储蓄银行',
            'value' => 7,
        ),
        array(
            'name'  => '中信银行',
            'value' => 8,
        ),
        array(
            'name'  => '北京银行',
            'value' => 9,
        ),
        array(
            'name'  => '中国民生银行',
            'value' => 10,
        ),
        array(
            'name'  => '光大银行',
            'value' => 11,
        ),
        array(
            'name'  => '广发银行',
            'value' => 12,
        ),
        array(
            'name'  => '华夏银行',
            'value' => 13,
        ),
        array(
            'name'  => '兴业银行',
            'value' => 14,
        ),
        array(
            'name'  => '上海银行',
            'value' => 15,
        ),
        array(
            'name'  => '渤海银行',
            'value' => 16,
        ),
        array(
            'name'  => '南京银行',
            'value' => 17,
        ),
        array(
            'name'  => '江苏银行',
            'value' => 18,
        ),
        array(
            'name'  => '宁波银行',
            'value' => 19,
        ),
        array(
            'name'  => '农村信用合作社',
            'value' => 20,
        ),
    ),
    'billing_cycle' => array(
        'none'  => array(
            'name' => '无',
            'value' => 'none',
        ),
        'day' => array(
            'name'  => '一天',
            'value' => 'day',
        ),
        'week' => array(
            'name'  => '一周',
            'value' => 'week',
        ),
        'half_month' => array(
            'name'  => '半月',
            'value' => 'half_month',
        ),
        'month' => array(
            'name'  => '一月',
            'value' => 'month',
        ),
        'offline_billing ' => array(
            'name'  => '线下挂账',
            'value' => 'offline_billing',
        ),
    ),
    'ka_date' => array(
        'day' => array(
            array(
                'name'  => '次日',
                'value' => 'tommorow',
            )
        ),
        'week' => array(
            array(
                'name'  => '周一',
                'value' => 'Monday',
            ),
            array(
                'name'  => '周二',
                'value' => 'Tuesday',
            ),
            array(
                'name'  => '周三',
                'value' => 'Wednesday',
            ),
            array(
                'name'  => '周四',
                'value' => 'Thursday',
            ),
            array(
                'name'  => '周五',
                'value' => 'Friday',
            ),
            array(
                'name'  => '周六',
                'value' => 'Saturday',
            ),
            array(
                'name'  => '周日',
                'value' => 'Sunday',
            ),
        ),
        'half_month' => array(
            'first'=>array(
                'start' => 1,
                'end' => 15,
             ),
             'next' =>array(
                 'start' => 16,
                 'end' => 28,
             )
        ),
        'month' => array(
            'start' => 1,
            'end'   => 28,
        ),
    ),
    'pay_date' => array(
        'month' => array(
            'start' => 0,
            'end'   => 30,
        )
    ),
    'estimated' => array(
        array(
            'name'  => '<500元',
            'value' => '1',
        ),
        array(
            'name'  => '500-999元',
            'value' => '2',
        ),
        array(
            'name'  => '1000-1999元',
            'value' => '3',
        ),
        array(
            'name'  => '2000-4999元',
            'value' => '4',
        ),
        array(
            'name' => '>5000',
            'value' => '5'
        )
    )
);
