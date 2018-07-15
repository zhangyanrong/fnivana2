<?php
namespace bll\wap;
include_once("wap.php");

/**
 * 周期购业务
 */
class Subscription extends wap
{
    function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 用户订单确认相关数据
     */
    public function orderConfirm($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 套餐编号参数判断 */
        $pid = isset($params['pid']) ? $params['pid'] : 0;
        if (!$pid) {
            return array('code' => 300, 'msg' => '套餐错误，请选择其他套餐');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $aid = isset($params['aid']) ? $params['aid'] : 0; //用户新地址编号
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderConfirm($uid, $pid, $aid);
        $address = $result['address'];
        $package = $result['package'];

        /* 判断套餐的有效期 */
        $date_now = date('Y-m-d H:i:s');
        $start_date = $package['start_date'];
        $end_date = $package['end_date'];
        if ($start_date && $end_date) {
            if (($start_date > $date_now) OR ($end_date < $date_now)) {
                return array('code' => 300, 'msg' => '套餐错误，请选择其他套餐');
            }
        }

        /* 判断用户已有的收获地址是否是套餐可售地区 */
        $province = $address['province'];
        $region = $package['region'];
        if ($province && ($province != $region)) {
            $result['code'] = 301;
            $result['msg'] = '收货地址非当前套餐可售区域';
        }

        return $result;
    }

    /**
     * 生成订单
     */
    public function createOrder($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        $aid = isset($params['aid']) ? intval($params['aid']) : 0; //收货地址编号
        if (!$aid) {
            return array('code' => 3001, 'msg' => '请选择收货地址');
        }

        $pid = isset($params['pid']) ? intval($params['pid']) : 0; //套餐编号
        if (!$pid) {
            return array('code' => 3003, 'msg' => '套餐错误，请选择其他套餐');
        }

        /* 获取套餐和收货地址 */
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderConfirm($uid, $pid, $aid);

        //判断收货地址是否已入库
        $address = $result['address'];
        if (!$address) {
            return array('code' => 3001, 'msg' => '请选择收货地址');
        }

        //判断套餐是否真实存在
        $package = $result['package'];
        if (!$package) {
            return array('code' => 3003, 'msg' => '套餐错误，请选择其他套餐');
        }

        //续订活动
        $renew = $result['renew'];

        $week_count = $package['week_count']; //一周送几次：1/2
        $week_day = isset($params['week_day']) ? $params['week_day'] : ''; //配送周期
        $allowed_weeks = [1, 2, 3, 4, 5, 6, 7];
        if ($week_count == 2) {
            if (!$week_day OR (strpos($week_day, ',') === false)) {
                return array('code' => 3002, 'msg' => '请选择配送日');
            }
            $weekArr = explode(',', $week_day);
            if (count($weekArr) != 2) {
                return array('code' => 3002, 'msg' => '请选择配送日');
            }
            foreach ($weekArr as $w) {
                if (!in_array($w, $allowed_weeks)) {
                    return array('code' => 3002, 'msg' => '请选择配送日');
                    break;
                }
            }

            //判断两个配送日期是否符合间隔3-4天
            if (!in_array((max($weekArr) - min($weekArr)), [3, 4])) {
                return array('code' => 3002, 'msg' => '请重新选择配送日');
            }
        } else {
            if (!in_array($week_day, $allowed_weeks)) {
                return array('code' => 3002, 'msg' => '请选择配送日');
            }
            $weekArr[] = $week_day;
        }

        /* 判断套餐的有效期 */
        $date_now = date('Y-m-d H:i:s');
        $start_date = $package['start_date'];
        $end_date = $package['end_date'];
        if ($start_date && $end_date) {
            if (($start_date > $date_now) OR ($end_date < $date_now)) {
                return array('code' => 3003, 'msg' => '套餐错误，请选择其他套餐');
            }
        }

        /* 判断用户已有的收获地址是否是套餐可售地区 */
        $province = $address['province'];
        $region = $package['region'];
        if ($province && ($province != $region)) {
            return array('code' => 3004, 'msg' => '收货地址非当前套餐可售区域');
        }

        //券卡支付校验
        $card = array();
        if(!empty($params['card_number']) && !empty($params['card_passwd'])){
            $card = ['card_number'=> $params['card_number'], 'card_passwd'=>$params['card_passwd']];
            $this->ci->load->bll('cardchange');
            $card_res = $this->ci->bll_cardchange->checkExchange($card);
            if($card_res['code'] != 200){
                return $card_res;
            }
            if($card_res['pro_info'][0] != $params['pid']){
                return array('code' => 300, 'msg' => '该券卡不能用于此套餐');
            }
        }

        //执行生成订单
        $insert_id = 0;
        $i = 0;
        $this->ci->load->bll('subscription');
        do {
            $order_name = $this->generate_order_number(); //生成订单编号
            $order_data = [
                'uid' => $uid,
                'address_id' => $address['id'],
                'order_name' => $order_name,
                'status' => 0,
                'combo_id' => $package['id'],
                'count' => $package['count'],
                'valid_count' => $package['count'],
                'week_count' => $package['week_count'],
                'money' => $package['price'],
                'time' => date('Y-m-d H:i:s'),
                'type' => 1
            ];

            //券卡支付
            if(!empty($card)){
                $order_data['status'] = 1;
                $order_data['pay_name'] = "券卡支付-在线提货券支付";
                $order_data['pay_parent_id'] = 6;
                $order_data['pay_id'] = 1;
                $order_data['pay_status'] = 1;
                $order_data['update_pay_time'] = date('Y-m-d H:i:s');
            }

            $order_address_data = [
                'position' => $address['position'] > 0 ? strval($address['position']) : '',
                'address' => $address['address'],
                'name' => $address['name'],
                'email' => $address['email'],
                'telephone' => $address['telephone'],
                'mobile' => $address['mobile'],
                'province' => intval($address['province']),
                'city' => intval($address['city']),
                'area' => intval($address['area']),
                'flag' => $address['flag']
            ];
            $insert_id = $this->ci->bll_subscription->createOrder($order_data, $order_address_data, $weekArr, $card, $renew);
            if (!$insert_id) $i++;
        } while (!$insert_id && ($i < 3)); //允许订单自动提交3次
        if ($insert_id) {
            return !empty($card) ? $insert_id : $order_name;
        } else {
            return array('code' => 3005, 'msg' => '提交订单失败');
        }
    }

    /**
     * 生成订单编号
     */
    private function generate_order_number() {
        $number = '';
        for ($i = 0; $i < 4 ; $i++) {
            $number .= rand(0, 9);
        }
        return 'G'. date("ymdi"). $number;
    }

    /**
     * 用户订单列表
     */
    public function orderList($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderList($uid);
        return $result;
    }

    /**
     * 取消订单
     */
    public function orderCancel($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderCancel($uid, $order_id);
        if ($result) {
            return array('code' => 200, 'msg' => '订单成功取消');
        } else {
            return array('code' => 300, 'msg' => '订单取消失败');
        }
    }

    /**
     * 获取子订单列表
     */
    public function orderSub($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderSub($order_id);
        return $result;
    }

    /**
     * 统计指定用户的订单总数
     */
    public function orderTotal($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderTotal($uid);
        return $result;
    }

    /**
     * 定购订单详情
     */
    public function orderDetail($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单参数判断 */
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0; //订单编号
        $order_name = isset($params['order_name']) ? $params['order_name'] : ''; //订单编码
        if (!$order_id && !$order_name) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderDetail($uid, $order_id, $order_name);
        return $result;
    }

    /**
     * 定购配送订单详情
     */
    public function orderSubDetail($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数判断 */
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderSubDetail($params);
        return $result;
    }

    /**
     * 定购订单日历
     */
    public function orderCycle($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数判断 */
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderCycle($uid, $order_id);
        return $result;
    }

    /**
     * 暂停订单
     */
    public function orderSuspend($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderSuspend($uid, $order_id);
        return $result;
    }

    /**
     * 恢复暂停订单
     */
    public function orderRegain($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderRegain($uid, $order_id);
        return $result;
    }

    /**
     * 获取暂停订单生效日
     */
    public function orderEffectiveDate($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderEffectiveDate($uid, $order_id);
        return $result;
    }

    /**
     * 选菜确认页
     */
    public function choose($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->choose($uid, $order_id);
        return $result;
    }

    /**
     * 用户换菜操作页
     */
    public function chooseDetail($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        /* 菜品类编号参数验证 */
        $cate_id = isset($params['cate_id']) ? intval($params['cate_id']) : 0;
        if (!$cate_id) {
            return array('code' => 300, 'msg' => '菜品错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->chooseDetail($uid, $order_id, $cate_id);
        return $result;
    }

    /**
     * 周期购订单配送地址修改
     */
    public function orderUpdateAddress($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        /* 订单配送地址编号参数验证 */
        $address_id = isset($params['address_id']) ? intval($params['address_id']) : 0;
        if (!$address_id) {
            return array('code' => 301, 'msg' => '地址错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');

        /* 获取订单和套餐明细 */
        $orderDetail = $this->ci->bll_subscription->getOrderDetail($uid, $order_id);
        $comboDetail = $this->ci->bll_subscription->getComboDetail($orderDetail['combo_id']);

        /* 获取收货地址 */
        $addressDetail = $this->ci->bll_subscription->getAddressDetail($address_id);

        //订单、套餐和配送地址，只要有一个不存在，都退出
        if (!$orderDetail OR !$comboDetail OR !$addressDetail) {
            return array('code' => 302, 'msg' => '数据错误');
        }

        /* 判断用户重新修改的配送地址是否是套餐可售地区 */
        $province = $addressDetail['province'];
        $region = $comboDetail['region'];
        if ($province && ($province != $region)) {
            return array('code' => 303, 'msg' => '新的配送地址非当前套餐可售区域');
        }

        //执行地址修改
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderUpdateAddress($uid, $order_id, $address_id);
        return $result;
    }

    /**
     * 保存用户的选菜数据
     */
    public function saveChooseDish($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 重新选择菜品数据的判断 */
        $foods = isset($params['foods']) ? json_decode($params['foods'], true) : [];
        if (!$foods) {
            return array('code' => 300, 'msg' => '未选择菜品');
        }

        /* 订单编号参数验证 */
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        /* 菜品类编号参数验证 */
        $cate_id = isset($params['cate_id']) ? intval($params['cate_id']) : 0;
        if (!$cate_id) {
            return array('code' => 300, 'msg' => '菜品错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->saveChooseDish($uid, $order_id, $cate_id, $foods);
        return $result;
    }

    /**
     * 定购订单物流信息
     */
    public function orderLogistics($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单编码参数验证 */
        $order_name = isset($params['order_name']) ? intval($params['order_name']) : '';
        if (!$order_name) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //调用配送订单物流接口
        $this->ci->load->bll('order');
        $result = $this->ci->bll_order->logisticTrace($params);
        //暂时提供一份测试数据
        //$result = json_decode('{"type":0,"driver_name":"\u6682\u65e0","driver_phone":"","logistic_company":"\u5929\u5929\u679c\u56ed","logistic_logo":"http:\/\/cdn.fruitday.com\/assets\/logistic\/ic_fruitday@2x.png","logistic_order":"","logistic_trace":[{"trace_desc":"\u60a8\u7684\u8ba2\u5355\u5df2\u7ecf\u62e3\u8d27\u5b8c\u6210,\u51c6\u5907\u53d1\u8d27","trace_time":"2016-10-28 15:12:20"},{"trace_desc":"\u8ba2\u5355\u5df2\u786e\u8ba4\uff0c\u9884\u8ba110\u670829\u65e59\u70b9-18\u70b9\u9001\u8fbe\u60a8\u624b\u4e2d","trace_time":"2016-10-28 10:13:33"},{"trace_desc":"\u60a8\u63d0\u4ea4\u4e86\u8ba2\u5355\u3001\u8bf7\u7b49\u5f85\u7cfb\u7edf\u786e\u8ba4","trace_time":"2016-10-28 10:13:17"}]}', true);
        return $result;
    }

    /**
     * 周期购订单支付完成
     */
    public function orderPaySucc($params) {
        /* 登录判断 */
        if (empty($params['connect_id'])) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期，请重新登录');
        }

        /* 订单参数判断 */
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0; //订单编号
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        //获取登录用户uid
        $uid = $this->ci->login->get_uid();
        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderPaySucc($uid, $order_id, $params['unpaid']);
        return $result;
    }

    /**
     * 省心订订单日历数据
     *
     * 用于数据检查，勿在处理业务时使用
     */
    public function orderDates($params) {
        /* 订单编号参数判断 */
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0;
        if (!$order_id) {
            return array('code' => 300, 'msg' => '订单错误');
        }

        $this->ci->load->bll('subscription');
        $result = $this->ci->bll_subscription->orderDates($order_id);
        return $result;
    }

}