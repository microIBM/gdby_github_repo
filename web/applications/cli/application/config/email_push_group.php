<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 大厨开放城市
$config = array(
    'mall_email_group' => array(
        'to'   => 'wangzejun@dachuwang.com,jiaodengfei@dachuwang.com,huoyang@dachuwang.com,yuanxiaolin@dachuwang.com,zhangxiao@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangjinhang@dachuwang.com',
        'cc'  => 'pm@dachuwang.com,techleader@dachuwang.com',
        'title' => '商城订单来源统计',
        'subject' => '商城订单来源统计',
        'name' => 'BI数据日报',
        'desc' => array('本组数据每日10点推送一次', '每次推送均为前一天的数据')
    ),
    'crm_cc_email_group' => array(
        'to' => 'wangzejun@dachuwang.com,dongdi@dachuwang.com,huoyang@dachuwang.com,yuanxiaolin@dachuwang.com,yugang@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com,yelongyi@dachuwang.com',
        'cc' => 'pm@dachuwang.com,techleader@dachuwang.com',
        'title' => '用户运营组核心数据指标',
        'subject' => '用户运营组核心数据指标',
        'name' => 'BI数据日报',
        'desc' => array(
            '本组数据每日10点推送一次',
            '每次推送均为前一天的数据',
            '有效客户总数=注册后下过单的客户总数',
            '订单总流水=除了关闭订单的总流水',
            '客单价=本时间段内的总流水/本时间段内的下单客户数',
            '复购率=本时间段内的复购客户数/有效客户总数',
            '复购客户=本时间段内下过单的客户',
            '客户流失率=有效客户流失总数/有效客户总数',
            '有效客户流失数=连续30天未下单，且之前下过单的客户数',
            '客户下单频率=所有有效客户下单频率的总和/总有效客户数,单个客户下单频率=单个客户有下单的天数/注册总天数',
            '总投诉单数=本时间段内投诉单总数'
            )
    ),
    'fix_workflow_log' => array(
    'to' => 'wangzejun@dachuwang.com,yuanxiaolin@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com',
    'cc' => '',
    'title' => '',
    'subject' => 'BI修复workflow_log表日报',
    'name' => 'BI修复workflow_log表日报',
    'desc' => array()
    ),
    'customer_top' => array(
        'to' => 'wangzejun@dachuwang.com,yuanxiaolin@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com',
        'cc' => '',
        'title' => '',
        'subject' => 'BI跑客户维度销售信息',
        'name' => 'BI跑客户维度销售信息',
        'desc' => array()
    ),
    'customer_top_ten' => array(
        'to' => 'wangzejun@dachuwang.com,yuanxiaolin@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com',
        'cc' => 'pm@dachuwang.com,techleader@dachuwang.com,manager@dachuwang.com',
        'title' => '',
        'name' => 'BI数据日报',
        'subject' => '每日下单客户TOP10 ' . date('Y/m/d', strtotime('yesterday')),
        'topic' => '每日下单客户TOP10报表 ' . date('Y-m-d', strtotime('yesterday')),
        'topic_desc' => array('本组数据每日9点推送一次', '每次推送均为前一天的数据,数据单位为元')
    ),
    'city_sales_reports' => array(
        'to' => 'wangzejun@dachuwang.com,yuanxiaolin@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com',
        'cc' => 'pm@dachuwang.com,techleader@dachuwang.com',
        'title' => '',
        'name' => 'BI数据日报',
        'subject' => '全国分城市核心运营数据 ' . date('Y/m/d', strtotime('yesterday')),
        'topic' => '全国分城市核心运营数据报表 ' . date('Y-m-d', strtotime('yesterday')),
        'topic_desc' => array('本组数据每日8点推送一次', '每次推送均为前一天的数据,数据单位为元')
    ),
    'category_sales_reports' => array(
        'to' => 'wangzejun@dachuwang.com,yuanxiaolin@dachuwang.com,yelongyi@dachuwang.com,zhangshaotao@dachuwang.com,zhangxiao@dachuwang.com,zhangjinhang@dachuwang.com',
        'cc' => 'pm@dachuwang.com,techleader@dachuwang.com',
        'title' => '',
        'name' => 'BI数据日报',
        'subject' => '各城市分品类基础销售数据 ' . date('Y/m/d', strtotime('yesterday')),
        'topic' => '各城市分品类基础销售数据报表 ' . date('Y-m-d', strtotime('yesterday')),
        'topic_desc' => array(
            '本组数据每日8点推送一次', 
            '每次推送均为前一天的数据,数据单位为元',
            '签收单数 ＝ 当天状态变化为已签收的订单总数（以母单计算）',
            '签收金额 ＝ 当天状态变化为已签收的订单总金额（不包含拒收金额）',
            '金额占比 ＝ 各城市当天签收金额占全国签收金额的比例',
            '签收率 ＝ 签收金额/(签收金额+拒收金额)*100%',
            '采购成本 ＝ 当天状态变化为已签收的全部订单中，被签收SKU按批次回溯到采购单，计算出的实际发生采购成本（不包含被拒收的SKU）',
            '毛利额 ＝ 签收金额-采购成本',
            '毛利率 ＝ 毛利额/签收金额*100%',
            '库存占比 ＝ 该一级分类的库存金额/该城市总库存金额*100%',
            '周转率 ＝ 采购成本/库存成本',
            '库存成本 = 所有SKU的在库成本总和',
            '交叉比率 = 毛利率*周转率',
            '动销率 = 该一级分类下，当日有被签收行为的SKU种数/该一级分类下所有已上架SKU种数*100%',
            '投诉单数 = 当日创建的投诉单总数',
            '拒收金额 = 当日被拒收的SKU总金额（以订单创建时SKU的价格计算）',
            '退货金额 = 当日被退货的SKU总金额（以订单创建时SKU的价格计算）'
        )
    )
);
