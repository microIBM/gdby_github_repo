<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Temp_sms extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MCustomer',
                'MPotential_customer',
                'MTicket',
            )
        );
        $this->load->library(
            array(
                'http'
            )
        );
    }

    public function send_sms_20150417() {
        $customer_ids = $this->MOrder->get_lists(
            'user_id',
            array(
                'created_time >=' => strtotime('2015-04-17'),
                'created_time <' => strtotime('2015-04-21 23:00'),
                'site_src' => C('site.dachu'),
                'status !=' => 0,
            )
        );

        $customer_ids = array_column($customer_ids, "user_id");
        $customer_ids = array_values(array_unique($customer_ids));

        $registered_customers = $this->MCustomer->get_lists(
            'id, mobile, name',
            array(
                'status !='      => 0,
                'site_id'        => C('site.dachu'),
                'province_id'    => 804,
                'created_time <' => strtotime('2015-04-21 23:00'),
                'not_in' => array(
                    'id' => $customer_ids
                )
            )
        );

        $registered_customer_ids = array_column($registered_customers, 'id');
        $customer_map = array_combine($registered_customer_ids, $registered_customers);

        //$customer_with_no_order_ids = array_diff($registered_customer_ids, $customer_ids);
        //$customer_with_no_order_ids = array('574');
        $customer_with_no_order_ids = $registered_customer_ids;
        foreach($customer_with_no_order_ids as $customer_id) {
            $customer = $customer_map[$customer_id];
            $name = $customer['name'];
            $first_name = mb_substr($name, 0, 1);
            $mobile = $customer['mobile'];
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '五一热潮挡不住！五一满立减，活动期间每天最高立减60元！微信关注“大厨网”，点击“大厨首页”查看更多活动内容！如有问题，请拨打大厨网全国统一服务热线：400-8199-491。',
                    'mobile' => array($mobile),
                    'site' => C('site.dachu')
                )
            );
            var_dump($sms_return);
            echo "\n";
        }

    }

    public function format_query($uri_string, $data = array()) {
        $url = C('service.s') . '/' . $uri_string;
        $return_data = $this->http->query($url, $data);
        return json_decode($return_data, TRUE);
    }

    /**
     * @description 17-20号连续下单的用户发送短信提示再下单
     */
    public function send_sms_20150421() {
        $date_arr = array(
            strtotime('20150417'),
            strtotime('20150418'),
            strtotime('20150419'),
            strtotime('20150420'),
        );

        $customer_res_arr = [];
        foreach($date_arr as $date) {
            $customers = [];
            $customers = $this->MOrder->get_lists(
                'user_id',
                array(
                    'site_src'        => C('site.dachu'),
                    'created_time >=' => $date,
                    'created_time <'  => $date + 86400,
                    'total_price >='  => 19900,
                    'status !='       => 0
                )
            );
            $customer_ids = array_column($customers, 'user_id');
            if(empty($customer_res_arr)) {
                $customer_res_arr = array_unique($customer_ids);
            } else {
                $customer_res_arr = array_intersect($customer_res_arr, $customer_ids);
            }
        }

        foreach($customer_res_arr as $customer_id) {
            $customer = $this->MCustomer->get_one(
                'mobile, name, site_id',
                array(
                    'status !=' => 0,
                    'site_id'   => C('site.dachu'),
                    'id'        => $customer_id
                )
            );
            if(empty($customer)) {
                continue;
            }
            $mobile = $customer['mobile'];
            $first_name = !empty($customer['name']) ? mb_substr($customer['name'], 0, 1) : '';
            $site_id = $customer['site_id'];
            $sms_return = $this->format_query('/sms/send_captcha',
                array(
                    'content' => $first_name . '老板您好，减免活动最后1天，再下1单(200元以上)就有机会获得减免100元，更有全网最低价产品等您来采！搜索“大厨网”微信公众号了解更多信息，有疑问请拨打4008199491',
                    'mobile'  => array($mobile),
                    'site'    => $site_id
                )
            );
            var_dump($sms_return);
            echo "\n";
        }

    }

    function make_ticket_20150423($money = 0, $type = 0) {
        $type = !empty($type) ? intval($type) : 0;
        $content = file_get_contents("customer_{$money}.csv", "r");
        $customers = explode("\r", $content);
        foreach($customers as $item) {
            echo $item;
            $item = trim($item);
            if(empty($item)) {
                echo "RES:\tFALSE\n";
                continue;
            }
            list($customer_id, $mobile) = explode(";", $item);
            $new_customer_id = $customer_id + 10000;
            $rand_number = rand(100, 999);
            $ticket_number = "{$new_customer_id}{$rand_number}";
            $info = $this->MCustomer->get_one(
                "name, shop_name, mobile",
                array(
                    'id' => $customer_id,
                    'status !=' => 0
                )
            );
            if(empty($info)) {
                echo "RES:INVALID USER\n";
                continue;
            }
            $ticket_info = array(
                'customer_id'  => $customer_id,
                'type_id'      => 1,
                'number'       => $ticket_number,
                'money'        => $money * 100,
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            );
            $this->MTicket->create($ticket_info);
            echo "RES:\tDONE\n";
        }
    }

    function send_ticket_20150423($type = 0) {
        $type = !empty($type) ? intval($type) : 0;
        $tickets = $this->MTicket->get_lists("*",
            array(
                'status !='  => 0,
                'type_id' => $type
            )
        );
        var_dump($tickets);
        foreach($tickets as $item) {
            echo "{$item['customer_id']}";
            $info = $this->MCustomer->get_one(
                "name, mobile",
                array(
                    'id' => $item['customer_id'],
                    'status !=' => 0
                )
            );
            if(empty($info)) {
                echo "RES:INVALID USER\n";
                continue;
            }
            $first_name = mb_substr($info['name'], 0, 1);
            $sms_return = "FALSE";
            $money = $item['money'] / 100;
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '恭喜' . $first_name . '老板，您在本次活动中获得减免'.$money.'元的奖励，券号为：'.$item['number'].'，您可使用于4月24日-4月26日23：00下的任意1个订单，限使用1次，逾期作废！快来下单吧！有疑问请拨打4008199491',
                    'mobile'  => array($info['mobile']),
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150515() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id' => C('site.dachu'),
                'province_id' => 804,
                'status !=' => 0
            )
        );

        foreach($customers as $customer) {
            if(!empty($customer['mobile'])) {
                $mobile = $customer['mobile'];
                $first_name = !empty($customer['name']) ? mb_substr($customer['name'], 0, 1) : '';
                $sms_return = $this->format_query('/sms/send_notice',
                    array(
                        'content' => '减最直接，初夏热潮挡不住！活动期间调味品和厨房日用品每天最高可立减45元，水产冻品每天最高可立减60元！点击“大厨首页”查看更多活动内容。如有问题，请拨打大厨网全国统一服务热线：400-8199-491',
                        'mobile'  => array($mobile),
                        'site'    => 1
                    )
                );
                var_dump($sms_return);
            }
        }
    }

    function send_ticket_20150518() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.dachu'),
                'province_id' => 804,
                'status !='   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");
        $now = 0;
        $mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！好消息，大厨网蔬菜开卖啦！黄瓜、苦瓜、西葫芦，茄子、萝卜、西红柿，洋葱、土豆、葱姜蒜等近30种新鲜菜品！大小袋分装，方便又健康；平均每袋10元出头，经济实惠任您挑选！想买放心菜，快来大厨网！点击打开大厨网微信公众号，进入“大厨首页”，查看更多产品。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150522() {
        $mobiles = $this->_get_no_order_customers();

        $now = 0;
        //$mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！初夏新体验，天天有惊喜！5月23日-5月27日首次下单的用户，可享受此活动期间每天第1笔订单满199元减20元的优惠，品类齐全，价格实惠，老板们做好准备下单哟！进入微信公众号“大厨网”，点击“大厨首页”查看更多产品信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150523() {
        $mobiles = $this->_get_no_order_customers();

        $now = 0;
        //$mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！满减活动开始啦！5月23日-5月27日首次下单的用户，可享受此活动期间每天第1笔订单满199元减20元的优惠，品类齐全，价格实惠，老板们快来下单哟！进入微信公众号“大厨网”，点击“大厨首页”查看详细活动信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150525() {
        $mobiles = $this->_get_no_order_customers();

        $now = 0;
        //$mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！满减活动第3天！5月23日-5月27日首次下单的用户，可享受此活动期间每天第1笔订单满199元减20元的优惠，品类齐全，价格实惠，老板们快来下单哟！进入微信公众号“大厨网”，点击“大厨首页”查看详细活动信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150527() {
        $mobiles = $this->_get_no_order_customers();

        $now = 0;
        //$mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！满减活动最后一天！5月23日-5月27日首次下单的用户，可享受此活动期间每天第1笔订单满199元减20元的优惠，品类齐全，价格实惠，老板们快来下单哟！进入微信公众号“大厨网”，点击“大厨首页”查看详细活动信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => 1
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150526() {
        $mobiles = $this->_get_beijing_customers();

        $now = 0;
        //$mobiles = array('15810540851', '18612188241', '15810955007');
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！山东京欣西瓜促销价0.99元/斤，全城最低价！5月26日-5月31日开始抢购，每人每天限购100个！点击大果网微信，进入“大果首页”查看活动产品，如有问题，请拨打大果网全国统一服务热线：400-8199-491。感谢您的支持！退订回T或N',
                    'mobile'  => $page,
                    'site'    => C("site.daguo")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150528() {
        $mobiles = $this->_get_all_customers();

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！月末满减优惠升级，每天首单最高立减200元！5月28日-5月31日大厨网所有用户每天首单最高可立减200元，品类齐全，价格实惠，老板们做好准备下单哟！点击“大厨首页”查看更多产品信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。退订回T或N',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150531() {
        $mobiles = $this->_get_all_customers();

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：您好！满减活动最后1天！5月28日-5月31日大厨网所有用户每天首单最高可立减200元，品类齐全，价格实惠！点击“大厨首页”查看详细活动信息。如有问题，请拨打大厨网全国统一服务热线：400-8199-491。退订回T或N',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150529() {
        $mobiles = $this->_get_tianjin_customers();

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的用户：大果网特色瓜果商品底价盛放！帮你解暑降温，冰爽一夏！退订回T或N',
                    'mobile'  => $page,
                    'site'    => C("site.daguo")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150604() {
        $mobiles = $this->_get_beijing_customers();

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '价格跳水！小白菜7毛/斤，五得利特精粉85.5元/袋，还有油和肉类哟！数量有限15:30准时开抢！',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150702() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.daguo'),
                'province_id' => 804,
                'status !='   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '尊敬的客户，大果网开生鲜标准化。即日起，线上售卖水果均以标准重量售卖，结算总价以订单为准。省去到货按斤称重结算的环节，宁可我们吃亏也不会让您吃亏，足斤足量。若有疑问，请拨打客服专线：400—8199—491。',
                    'mobile'  => $page,
                    'site'    => C("site.daguo")
                )
            );
            var_dump($sms_return);
        }
    }

    function send_ticket_20150715() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.dachu'),
                'province_id' => 804,
                'status >'   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '每日直降第1波：7月15日蔬菜特惠专场最高直降12%，仅限24小时！每日首笔微信支付再减5元！详情进入微信查看！',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
        // 天津
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.dachu'),
                'province_id' => 1206,
                'status >'   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '每日直降第1波：7月15日蔬菜特惠专场最高直降42%，仅限24小时！每日首笔微信支付再减5元！详情进入微信查看！',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
        // 上海
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.dachu'),
                'province_id' => 993,
                'status >'   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '每日直降第1波：7月15日蔬菜特惠专场疯狂特价（土豆1.15元/斤），仅限24小时！每日首笔微信支付再减5元！详情进入微信查看！',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }

    }

    function _get_tianjin_customers() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.daguo'),
                'province_id' => 1206,
                'status !='   => 0,
            )
        );
        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        // 获取潜在客户列表
        $potentials = $this->MPotential_customer->get_lists(
            "mobile",
            array(
                'status !='   => 0,
                'site_id'     => C("site.daguo"),
                'province_id' => 1206,
                'mobile !='   => ''
            )
        );
        $potential_mobiles = array_column($potentials, "mobile");

        if(!empty($potential_mobiles)) {
            $mobiles = array_merge($mobiles, $potential_mobiles);
        }
        return array_values(array_unique($mobiles));
    }

    function send_ticket_2015071502() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'province_id' => 804,
                'status >'   => 0
            )
        );

        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        $now = 0;
        $len = count($mobiles);
        echo $len . "\r\n";
        while($now < $len) {
            $page = [];
            $cur_now = 0;
            while($cur_now < 200 && $now < $len) {
                $page[] = $mobiles[$now];
                $cur_now ++;
                $now ++;
            }
            $sms_return = $this->format_query('/sms/send_notice',
                array(
                    'content' => '河北京红水蜜桃，产地直采，个头均匀，形美味甜，比市面的桃耐储存，价格实惠！当日首笔微信支付再减5元！详情请进入微信查看！',
                    'mobile'  => $page,
                    'site'    => C("site.dachu")
                )
            );
            var_dump($sms_return);
        }
    }

    function _get_beijing_customers() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'     => C('site.dachu'),
                'province_id' => 804,
                'status !='   => 0,
            )
        );
        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        // 获取潜在客户列表
        $potentials = $this->MPotential_customer->get_lists(
            "mobile",
            array(
                'status !='   => 0,
                'site_id'     => C("site.dachu"),
                'province_id' => 804,
                'mobile !='   => ''
            )
        );
        $potential_mobiles = array_column($potentials, "mobile");

        if(!empty($potential_mobiles)) {
            $mobiles = array_merge($mobiles, $potential_mobiles);
        }
        return array_values(array_unique($mobiles));
    }

    function _get_all_customers() {
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id'   => C('site.dachu'),
                'status !=' => 0,
            )
        );
        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        // 获取潜在客户列表
        $potentials = $this->MPotential_customer->get_lists(
            "mobile",
            array(
                'status !=' => 0,
                'site_id'   => C("site.dachu"),
                'mobile !=' => ''
            )
        );
        $potential_mobiles = array_column($potentials, "mobile");

        if(!empty($potential_mobiles)) {
            $mobiles = array_merge($mobiles, $potential_mobiles);
        }
        return array_values(array_unique($mobiles));
    }

    function _get_no_order_customers() {
        $order_customers = $this->MOrder->get_lists(
            'user_id',
            array(
                'status !=' => 0,
                'created_time <' => strtotime('2015-05-23')
            )
        );
        $order_customer_ids = array_column($order_customers, "user_id");
        $order_customer_ids = array_values(array_unique($order_customer_ids));
        $customers = $this->MCustomer->get_lists(
            '*',
            array(
                'site_id' => C('site.dachu'),
                //'province_id' => 804,
                'status !=' => 0,
                'not_in'    => array(
                    'id' => $order_customer_ids
                )
            )
        );
        // 每组两百个发送短信
        $mobiles = array_column($customers, "mobile");

        // 获取潜在客户列表
        $potentials = $this->MPotential_customer->get_lists(
            "mobile",
            array(
                'status !=' => 0,
                'site_id'   => 1,
                'mobile !=' => ''
            )
        );
        $potential_mobiles = array_column($potentials, "mobile");

        if(!empty($potential_mobiles)) {
            $mobiles = array_merge($mobiles, $potential_mobiles);
        }
        return array_values(array_unique($mobiles));
    }
}

/* End of file repair_sku.php */
/* Location: ./application/controllers/repair_sku.php */
