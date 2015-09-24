<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @description
 */
$config = array(
    'deliver_time' => array(
        'early' => array(
            'code' => 1,
            'msg' => '08:00至10:30'
        ),
        'late' => array(
            'code' => 2,
            'msg' => '14:00至16:30'
        )
    ),
    'deliver_time_guo' => array(
        'early' => array(
            'code' => 1,
            'msg' => '08:00至12:00'
        ),
        'shanghai_early' => array(
            'code' => 1,
            'msg' => '07:00至10:00'
        )
    ),
    'audit_time' => array(
        'early' => 10,
        'late'  => 23,
    ),
    'order_type' => array(
        'normal' => array(
            'code' => 1,
            'msg'  => '大厨普通订单'
        ),
        'frozen' => array(
            'code' => 2,
            'msg'  => '冻品订单'
        ),
        'top_selling' => array(
            'code' => 3,
            'msg' => '水果爆款订单'
        ),
        'fruit' => array(
            'code' => 4,
            'msg' => '水果订单'
        )
    ),
    'customer_side_status' => array(
        'wait_confirm' => array(
            'code'  => 1,
            'msg'   => '待审核',
            'class' => 'label-primary',
        ),
        'wait_receive' => array(
            'code'  => 11,
            'msg'   => '待收货',
            'class' => 'label-primary',
        ),
        'success' => array(
            'code'  => 21,
            'msg'   => '已收货',
            'class' => 'label-primary',
        ),
        'closed' => array(
            'code'  => 31,
            'msg'   => '已取消',
            'class' => 'label-primary',
        ),
    ),
    'status' => array(
        'all' => array(
            'code'  => -1,
            'msg'   => '全部',
            'class' => 'label-default',
        ),
        'closed' => array(
            'code'  => 0,
            'msg'   => '已关闭',
            'class' => 'label-default',
        ),
        'success' => array(
            'code'  => 1,
            'msg'   => '已完成',
            'class' => 'label-success',
        ),
        'wait_confirm' => array(
            'code'  => 2,
            'msg'   => '待审核',
            'class' => 'label-primary',
        ),
        'confirmed' => array(
            'code'  => 3,
            'msg'   => '待生产',
            'class' => 'label-info',
        ),
        'wave_executed' => array(
            'code'  => 11,
            'msg'   => '波次中',
            'class' => 'label-info'
        ),
        'picking' => array(
            'code'  => 12,
            'msg'   => '待分拣',
            'class' => 'label-warning'
        ),
        'picked' => array(
            'code'  => 4,
            'msg'   => '已分拣',
            'class' => 'label-warning',
        ),
        'checked' => array(
            'code'  => 13,
            'msg'   => '已复核',
            'class' => 'label-info'
        ),
        'allocated' => array(
            'code'  => 14,
            'msg'   => '已分拨',
            'class' => 'label-info'
        ),
        'delivering' => array(
            'code'  => 5,
            'msg'   => '已出库',
            'class' => 'label-danger',
        ),
        'loading' => array(
            'code'  => 8,
            'msg'   => '已装车',
            'class' => 'label-info',
        ),
        'wait_comment' => array(
            'code'  => 6,
            'msg'   => '已签收',
            'class' => 'label-danger',
        ),
        'sales_return' => array(
            'code'  => 7,
            'msg'   => '已拒收',
            'class' => 'label-default',
        ),
        'wait_deliver' => array(
            'code'  => 100,
            'msg'   => '待收货', // 包括仓库已确认，仓库已分拣，已出库运送中三种状态
            'class' => 'label-info',
        )
    ),
    'comment'  => array(
        'code' => -1,
        'msg'  => '内部备注'
    ),
    'cancel_reason' => array(
        array(
            'code' => 1,
            'msg'  => 'BD反馈(重复下单)'
        ),
        array(
            'code' => 2,
            'msg'  => 'BD反馈(订单内容有误)'
        ),
        array(
            'code' => 3,
            'msg'  => '客户要求取消订单(重复下单)'
        ),
        array(
            'code' => 4,
            'msg'  => '客户要求取消订单(下错单)'
        ),
        array(
            'code' => 5,
            'msg'  => '客户要求取消订单(价格贵)'
        ),
        array(
            'code' => 6,
            'msg'  => '其他'
        ),
    ),
    'resource' => array(
        'ios' => array(
            'code' => 1
        ),
        'android' => array(
            'code' => 2
        ),
        'chu' => array(
            'code' => 3
        ),
        'mall' => array(
            'code' => 4
        )
    )
);
