<?php
namespace bll;

/**
 * 周期购业务
 */
class Subscription
{
    public function __construct($params = array())
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
        $this->_source = $params['source'];
        $this->_version = $params['version'];
    }

    /**
     * 获取用户默认收货地址和选择的套餐
     *
     * @params $uid int 用户ID
     * @params $pid int 套餐ID
     * @params $aid int 用户新配送地址ID
     */
    public function orderConfirm($uid, $pid, $aid)
    {
        $this->ci->load->model('user_address_model');
        $this->ci->load->model('subscription_combo_model');
        $userAddress = $this->ci->user_address_model->get_default_address($uid, $aid);
        $package = $this->ci->subscription_combo_model->detail($pid);

        //续订活动
        $this->ci->load->model('subscription_model');
        $renew = $this->ci->subscription_model->renew($uid, $pid);
        if($renew){
            $package['count'] += $renew['count'];
        }

        return ['address' => $userAddress, 'package' => $package, 'renew' => $renew];
    }

    /**
     * 生成订单
     *
     * @params $order_data array 订单插入数组
     * @params $order_address_data array 订单配送地址插入数组
     * @params $week_data array 订单配送周期数
     */
    public function createOrder($order_data, $order_address_data, $week_data, $card, $renew)
    {
        $this->ci->load->model('subscription_order_model');
        $this->ci->load->model('subscription_order_address_model');
        $this->ci->load->model('subscription_order_day_model');

        /* 事务开始 */
        $this->ci->db->trans_begin();

        //添加订单
        $order_id = $this->ci->subscription_order_model->add($order_data);
        if (!$order_id) {
            $this->ci->db->trans_rollback();
            return 0;
        }

        //添加订单配送地址
        $order_address_data['order_id'] = $order_id;
        $address_id = $this->ci->subscription_order_address_model->add($order_address_data);
        if (!$address_id) {
            $this->ci->db->trans_rollback();
            return 0;
        }

        //添加订单配送日
        $day_batch_data = [];
        foreach ($week_data as $item) {
            $day_batch_data[] = [
                'order_id' => $order_id,
                'day' => $item
            ];
        }
        $week_result = $this->ci->subscription_order_day_model->add_batch($day_batch_data);
        if (!$week_result) {
            $this->ci->db->trans_rollback();
            return 0;
        }

        //券卡支付
        if(!empty($card)){
            $this->ci->load->model('cardchange_model');
            $exchange_result = $this->ci->cardchange_model->exchange($card['card_number'],$order_data['order_name']);
            if($exchange_result['result'] == 'fail'){
                $this->ci->db->trans_rollback();
                return 0;
            }
        }

        //续订活动
        if(!empty($renew)){
            $this->ci->load->model('subscription_model');
            if(!$this->ci->subscription_model->orderRenewLog([
                'renew_order_id' => $order_id,
                'renew_id' => $renew['id'],
                'order_id' => $renew['order_id'],
                'count' => $renew['count'],
                'created_at' => date('Y-m-d H:i:s')
            ])){
                $this->ci->db->trans_rollback();
                return 0;
            }
        }

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return 0;
        } else {
            $this->ci->db->trans_commit();
            return $order_id;
        }
    }

    /**
     * 获取订单列表
     *
     * @params $uid int 用户ID
     */
    public function orderList($uid)
    {
        $this->ci->load->model('subscription_order_model');
        $this->ci->load->model('subscription_combo_model');
        $this->ci->load->model('subscription_order_delivery_model');

        $lists = $this->ci->subscription_order_model->get_list($uid);
        if (!$lists) {
            return [];
        }
        //获取订单套餐数据和子订单数据
        foreach ($lists as $key => $val) {
            $package = $this->ci->subscription_combo_model->detail($val['combo_id']);
            $lists[$key]['package'] = $package;
            //进行中或暂停的订单获取一条最新的子订单，页面展示
            if (in_array($val['status'], [1, 2])) {
                $rows = $this->getChildOrder($val['id']);
                if ($rows) {
                    $lists[$key]['children'] = $rows[0];
                }
            }

            //券卡支付，金额显示: 0.00
            if($lists[$key]['pay_parent_id'] == 6){
                $lists[$key]['money'] = $lists[$key]['package']['price'] = '0.00';
            }

            //获取子订单总数
            $lists[$key]['child_total'] = $this->ci->subscription_order_delivery_model->get_total($val['id']);
        }
        return $lists;
    }

    /**
     * 获取子订单列表
     *
     * @params $order_id int 定购父订单ID
     */
    public function getChildOrder($order_id)
    {
        if (!$order_id) {
            return [];
        }
        $this->ci->load->model('subscription_model');
        $this->ci->load->model('subscription_order_delivery_model');
        $lists = $this->ci->subscription_order_delivery_model->get_list($order_id);
        $result = [];
        if ($lists) {
            $orderIds = [];
            foreach ($lists as $val) {
                $orderIds[] = $val['delivery_order_id'];
            }
            $result = $this->ci->subscription_model->getDeliveryOrderLists($orderIds);
        }
        return $result;
    }

    /**
     * 获取定期购子订单列表
     *
     * @params $order_id int 定购父订单ID
     */
    public function orderSub($order_id)
    {
        $rows = $this->getChildOrder($order_id);
        return $rows;
    }

    /**
     * 取消订单
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function orderCancel($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $result = $this->ci->subscription_order_model->cancel($uid, $order_id);
        return $result;
    }

    /**
     * 统计指定用户的订单总数
     *
     * @params $uid int 用户ID
     */
    public function orderTotal($uid)
    {
        $this->ci->load->model('subscription_order_model');
        $total = $this->ci->subscription_order_model->get_total($uid); //用户订单总数
        $result = $this->ci->subscription_order_model->get_calendar_orders($uid, 1);
        $max_order_id = 0; //用户进行中或暂停订单的最大ID
        if ($result) {
            $max_order_id = $result[0]['id'];
        }
        return ['total' => $total, 'order_id' => $max_order_id];
    }

    /**
     * 订购订单详情
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     * @params $order_name string 订单编码
     */
    public function orderDetail($uid, $order_id, $order_name)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id, $order_name);
        if ($orderDetail) {
            $this->ci->load->model('subscription_order_address_model');
            $this->ci->load->model('subscription_combo_model');
            $orderAddress = $this->ci->subscription_order_address_model->detail(0, $orderDetail['id']);
            $package = $this->ci->subscription_combo_model->detail($orderDetail['combo_id']);

            //支付状态文字描述
            $pay_status = $this->ci->config->item("pay");
            $orderDetail['pay_status_text'] = $pay_status[$orderDetail['pay_status']];

            //券卡支付，金额显示: 0.00
            if($orderDetail['pay_parent_id'] == 6){
                $orderDetail['money'] = $package['price'] = '0.00';
            }
            return ['detail' => $orderDetail, 'address' => $orderAddress, 'package' => $package];
        }
        return [];
    }

    /**
     * 订购配送订单详情
     *
     * @params $params array 参数集合
     */
    public function orderSubDetail($params)
    {
        $order_id = $params['order_id'];
        $this->ci->load->model('order_model');
        $orderDetail = $this->ci->order_model->getInfoById($order_id);
        if ($orderDetail) {
            $this->ci->load->bll('order');
            $orderDetail['order_status_text'] = $this->ci->bll_order->getOrderStatusText(
                $orderDetail['operation_id'],
                $orderDetail['pay_parent_id'],
                $orderDetail['pay_status'],
                $orderDetail['time'],
                $orderDetail['had_comment']
            );
            $this->ci->load->model('order_address_model');
            $orderAddress = $this->ci->order_address_model->detail(0, $orderDetail['id']);

            //物流数据调取
            $params['order_name'] = $orderDetail['order_name'];
            $logisticsResult = $this->ci->bll_order->logisticTrace($params);
            //暂时提供物流测试数据
            //$logisticsResult = json_decode('{"type":0,"driver_name":"\u6682\u65e0","driver_phone":"","logistic_company":"\u5929\u5929\u679c\u56ed","logistic_logo":"http:\/\/cdn.fruitday.com\/assets\/logistic\/ic_fruitday@2x.png","logistic_order":"","logistic_trace":[{"trace_desc":"\u60a8\u7684\u8ba2\u5355\u5df2\u7ecf\u62e3\u8d27\u5b8c\u6210,\u51c6\u5907\u53d1\u8d27","trace_time":"2016-10-28 15:12:20"},{"trace_desc":"\u8ba2\u5355\u5df2\u786e\u8ba4\uff0c\u9884\u8ba110\u670829\u65e59\u70b9-18\u70b9\u9001\u8fbe\u60a8\u624b\u4e2d","trace_time":"2016-10-28 10:13:33"},{"trace_desc":"\u60a8\u63d0\u4ea4\u4e86\u8ba2\u5355\u3001\u8bf7\u7b49\u5f85\u7cfb\u7edf\u786e\u8ba4","trace_time":"2016-10-28 10:13:17"}]}', true);
            $logistics = [];
            if (isset($logisticsResult['logistic_trace']) && $logisticsResult['logistic_trace']) {
                $logistics = current($logisticsResult['logistic_trace']);
            }

            /* 订单商品列表获取 */
            $this->ci->load->model('order_product_model');
            $select_field = 'order_product.product_name,
            order_product.gg_name,
            order_product.qty,
            product.promotion_photo,
            product.template_id
            ';
            $productLists = $this->ci->order_product_model->getOrderProductList($orderDetail['id'], $select_field);
            if ($productLists) {
                $photo_url = constant(CDN_URL . rand(1, 9));
                foreach ($productLists as $key => $val) {
                    // 获取产品模板图片
                    if ($val['template_id']) {
                        $this->ci->load->model('b2o_product_template_image_model');
                        $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($val['template_id'], 'whitebg');
                        if (isset($templateImages['whitebg'])) {
                            $val['promotion_photo'] = $templateImages['whitebg']['image'];
                        }
                    }

                    $productLists[$key]['photo_url'] = $photo_url . $val['promotion_photo'];
                }
            }

            return ['detail' => $orderDetail, 'address' => $orderAddress, 'logistics' => $logistics, 'productLists' => $productLists];
        }
        return [];
    }

    /**
     * 订购订单日历数据
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function orderCycle($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if ($orderDetail) {
            $this->ci->load->model('subscription_order_address_model');
            $this->ci->load->model('subscription_combo_model');
            $this->ci->load->model('subscription_model');
            $this->ci->load->model('region_model');
            $orderAddress = $this->ci->subscription_order_address_model->detail(0, $orderDetail['id']);
            $orderAddress['area_name'] = $this->ci->region_model->get_region($orderAddress['area']);

            //获取订单套餐名称
            $orderDetail['combo_name'] = $this->ci->subscription_combo_model->get_name($orderDetail['combo_id']);

            //整理菜品品类信息
            $comboCates = $this->ci->subscription_model->combo_cate($orderDetail['combo_id']);
            $combo_cate_text = '';
            if ($comboCates) {
                foreach ($comboCates as $val) {
                    $combo_cate_text .= $val['name'] . ' X ' . $val['amount'] . '份　';
                }
            }
            $orderDetail['combo_cate_text'] = rtrim($combo_cate_text, '　');

            //获取订单套餐列表
            $ids = $this->ci->subscription_order_model->get_ids($uid);
            if ($ids) {
                foreach ($ids as $key => $val) {
                    $ids[$key]['combo_name'] = $this->ci->subscription_combo_model->get_name($val['combo_id']);
                }
            }

            //获取日历
            $calendar = $this->ci->subscription_model->getOrderCalendar($orderDetail);
            $dates = $this->ci->subscription_model->orderDates($orderDetail);

            //获取已完成的菜单
            $finish_format_date = '';
            $finish = [];
            if ($dates['curSendDate']) {
                $curSendDate = $dates['curSendDate'];
                $finish_format_date = $curSendDate;
                $finished = $this->ci->subscription_model->selected($curSendDate, $order_id);
                foreach ($finished as $v) {
                    $i = $v['qty'];
                    while ($i--) {
                        $finish[$v['cate_id']]['name'] = $v['cate_name'];
                        $finish[$v['cate_id']]['list'][] = $v;
                    }
                }
            }

            //获取可选菜的列表
            $option = [];
            $option_format_date = $dates['selSendDate'];
            $now_time = date('Y-m-d H:i:s');
            $selStartDate = $dates['selStartDate'];
            $selEndDate = $dates['selEndDate'];
            if ($selStartDate && ($now_time > $selStartDate) && ($now_time < $selEndDate)) {
                $this->ci->subscription_model->createDefaultSelection($orderDetail, $option_format_date);
                $optioned = $this->ci->subscription_model->selected($option_format_date, $order_id);
                foreach ($optioned as $v) {
                    $i = $v['qty'];
                    while ($i--) {
                        $option[$v['cate_id']]['name'] = $v['cate_name'];
                        $option[$v['cate_id']]['list'][] = $v;
                    }
                }
            }

            $result = [
                'detail' => $orderDetail,
                'address' => $orderAddress,
                'orders' => $ids,
                'calendar' => json_encode($calendar, JSON_UNESCAPED_SLASHES),
                'curSendDate' => $finish_format_date,
                'finish' => $finish,
                'nextSendDate' => $option_format_date,
                'option' => $option,
                'selStartDate' => $selStartDate,
                'selEndDate' => $selEndDate
            ];
            return $result;
        }
        return [];
    }

    /**
     * 暂停订单
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function orderSuspend($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return ['code' => '300', 'msg' => '订单错误'];
        }
        $this->ci->load->model('subscription_model');
        $result = $this->ci->subscription_model->pause($orderDetail);
        return $result;
    }

    /**
     * 恢复暂停订单
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function orderRegain($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return ['code' => '300', 'msg' => '订单错误'];
        }
        $this->ci->load->model('subscription_model');
        $result = $this->ci->subscription_model->restorePause($orderDetail);
        return $result;
    }

    /**
     * 获取暂停订单生效日
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function orderEffectiveDate($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return '';
        }
        $this->ci->load->model('subscription_model');
        $pause_data = $this->ci->subscription_model->pauseDate($orderDetail);
        return $pause_data;
    }

    /**
     * 选菜确认页
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function choose($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return [];
        }
        $this->ci->load->model('subscription_model');
        $dates = $this->ci->subscription_model->orderDates($orderDetail);

        //获取已完成的菜单
        /*
        $finish_format_date = '';
        $finish = [];
        if ($dates['curSendDate']) {
            $curSendDate = $dates['curSendDate'];
            $finish_format_date = $curSendDate;
            $finished = $this->ci->subscription_model->selected($curSendDate, $order_id);
            foreach ($finished as $v){
                $i = $v['qty'];
                while ($i--){
                    $finish[$v['cate_id']]['name'] = $v['cate_name'];
                    $finish[$v['cate_id']]['list'][] = $v;
                }
            }
        }
        */

        //获取可选菜的列表
        $option = [];
        $option_format_date = $dates['selSendDate'];
        $now_time = date('Y-m-d H:i:s');
        $selStartDate = $dates['selStartDate'];
        $selEndDate = $dates['selEndDate'];
        if ($selStartDate && ($now_time > $selStartDate) && ($now_time < $selEndDate)) {
            $this->ci->subscription_model->createDefaultSelection($orderDetail, $option_format_date);
            $optioned = $this->ci->subscription_model->selected($option_format_date, $order_id);
            foreach ($optioned as $v) {
                $i = $v['qty'];
                while ($i--) {
                    $option[$v['cate_id']]['name'] = $v['cate_name'];
                    $option[$v['cate_id']]['list'][] = $v;
                }
            }
        }
        $result = [
            //'curSendDate' => $finish_format_date,
            //'finish' => $finish,
            'nextSendDate' => $option_format_date,
            'option' => $option
        ];
        return $result;
    }

    /**
     * 用户换菜操作页
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     * @params $cate_id int 菜品品类ID
     */
    public function chooseDetail($uid, $order_id, $cate_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return false;
        }
        $this->ci->load->model('subscription_model');
        $dates = $this->ci->subscription_model->orderDates($orderDetail);
        $option_format_date = $dates['selSendDate'];
        $now_time = date('Y-m-d H:i:s');
        $selStartDate = $dates['selStartDate'];
        $selEndDate = $dates['selEndDate'];
        $sum = 0;
        $total = 0;
        $returnArr = [];
        if ($selStartDate && ($now_time > $selStartDate) && ($now_time < $selEndDate)) {
            $selected = $this->ci->subscription_model->selected($option_format_date, $order_id, $cate_id);
            $selection = $this->ci->subscription_model->selection($cate_id);
            $selected = array_column($selected, NULL, 'product_id');
            $selection = array_column($selection, NULL, 'product_id');
            $returnArr = $selected + $selection;
            foreach ($returnArr as $key => $item) {
                $count = 0;
                if (isset($selected[$key])) {
                    $count = intval($item['qty']);
                }
                $returnArr[$key]['total'] = $count;
                $sum += $count;
            }
            $total = $this->ci->subscription_model->selectCateAmount($orderDetail, $option_format_date)[$cate_id];
        }
        return ['selection' => $returnArr, 'sum' => $sum, 'total' => $total];
    }

    /**
     * 周期购订单配送地址修改
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     * @params $address_id int 配送地址ID
     */
    public function orderUpdateAddress($uid, $order_id, $address_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return ['code' => '3001', 'msg' => '订单错误'];
        }
        $this->ci->load->model('subscription_order_address_model');

        /* 事务开始 */
        $this->ci->db->trans_begin();

        //删除老的订单配送地址
        $delResult = $this->ci->subscription_order_address_model->delete(0, $order_id);
        if (!$delResult) {
            $this->ci->db->trans_rollback();
            return ['code' => '3002', 'msg' => '删除老地址失败'];
        }

        //插入新的订单配送地址
        $address = $this->getAddressDetail($address_id);
        $order_address_data = [
            'order_id' => $order_id,
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
        $addResult = $this->ci->subscription_order_address_model->add($order_address_data);
        if (!$addResult) {
            $this->ci->db->trans_rollback();
            return ['code' => '3003', 'msg' => '插入新地址失败'];
        }

        //修改订单配送地址:address_id
        $upResult = $this->ci->subscription_order_model->update($order_id, ['address_id' => $address_id]);
        if (!$upResult) {
            $this->ci->db->trans_rollback();
            return ['code' => '3004', 'msg' => '更新订单地址编号失败'];
        }

        if ($this->ci->db->trans_status() === false) {
            $this->ci->db->trans_rollback();
            return ['code' => '300', 'msg' => '操作失败'];
        } else {
            $this->ci->db->trans_commit();
            return ['code' => '200', 'msg' => '操作成功'];
        }
    }

    /**
     * 获取指定的配送地址明细
     *
     * @params $address_id int 配送地址ID
     */
    public function getAddressDetail($address_id)
    {
        $this->ci->load->model('user_address_model');
        $address = $this->ci->user_address_model->get_address_detail($address_id);
        return $address;
    }

    /**
     * 获取指定的订单明细
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function getOrderDetail($uid, $order_id)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        return $orderDetail;
    }

    /**
     * 获取指定的套餐明细
     *
     * @params $combo_id int 套餐ID
     */
    public function getComboDetail($combo_id)
    {
        $this->ci->load->model('subscription_combo_model');
        $combo = $this->ci->subscription_combo_model->detail($combo_id);
        return $combo;
    }

    /**
     * 保存用户的选菜数据
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     * @params $cate_id int 菜品品类ID
     * @params $foods array 已选择的菜品集
     */
    public function saveChooseDish($uid, $order_id, $cate_id, $foods)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if (!$orderDetail) {
            return ['code' => 300, 'msg' => '订单不存在'];
        }
        $this->ci->load->model('subscription_model');
        $dates = $this->ci->subscription_model->orderDates($orderDetail);
        $selSendDate = $dates['selSendDate']; //配送日期
        $nextSendDate = $dates['nextSendDate']; //下次配送日期
        $now_time = date('Y-m-d H:i:s');
        $selStartDate = $dates['selStartDate']; //选菜开始日期
        $selEndDate = $dates['selEndDate']; //选菜结束日期
        if ($selStartDate && ($now_time > $selStartDate) && ($now_time < $selEndDate)) {
            $selected = $this->ci->subscription_model->selected($selSendDate, $order_id, $cate_id);
            $selected = array_column($selected, 'qty', 'product_id'); //用户之前选择的默认集
            $delGoods = []; //用户不再需要的菜品
            $productIds = array_keys($foods); //获取用户新选择的所有菜品ID
            foreach ($selected as $key => $val) {
                if (!in_array($key, $productIds)) {
                    $delGoods[$key] = $val;
                }
            }

            $this->ci->load->model('subscription_order_product_model');
            $this->ci->load->model('subscription_product_model');

            /* 事务开始 */
            $this->ci->db->trans_begin();

            //删除用户不要的菜品和恢复对应菜品的库存
            if ($delGoods) {
                foreach ($delGoods as $k => $v) {
                    //删除老的菜品
                    $delWhere = [
                        'date' => $selSendDate,
                        'order_id' => $order_id,
                        'product_id' => $k,
                        'cate_id' => $cate_id
                    ];
                    $this->ci->subscription_order_product_model->delete($delWhere);

                    //恢复删除菜品的库存
                    $upWhere = [
                        'product_id' => $k,
                        'cate_id' => $cate_id
                    ];
                    $this->ci->subscription_product_model->update_qty($upWhere, $v);
                }
            }

            //添加用户新选的菜品和对继续保留选择菜品的库存的修改
            foreach ($foods as $k => $v) {
                //获取菜品库存，用于判断防止超卖
                $stockWhere = [
                    'product_id' => $k,
                    'cate_id' => $cate_id
                ];
                $stockDetail = $this->ci->subscription_product_model->detail($stockWhere);
                if (!$stockDetail) { //无库存数据，退出
                    $this->ci->db->trans_rollback();
                    return ['code' => 300, 'msg' => '无库存'];
                }

                /*
                 * 查找菜品是否已存在：
                 * 存在：修改qty及对应的库存
                 * 不存在：新增数据及减少对应菜品的库存
                 */
                $selWhere = [
                    'date' => $selSendDate,
                    'order_id' => $order_id,
                    'product_id' => $k,
                    'cate_id' => $cate_id
                ];
                $selDetail = $this->ci->subscription_order_product_model->detail($selWhere);
                if ($selDetail) {
                    $old_qty = $selDetail['qty'];
                    if ($old_qty > $v) { //说明此菜品数量减少，故减少差量库存
                        $qty_add_value = ($old_qty - $v);
                        $replace_type = '-';
                    } elseif ($old_qty < $v) { //说明此菜品数量增加，故增加差量库存
                        $add_count = ($v - $old_qty);

                        //库存不足，提示并退出
                        if ($add_count >= $stockDetail['stock']) {
                            $this->ci->db->trans_rollback();

                            //查找商品详情
                            $goodsDetail = $this->ci->subscription_model->getProductDetail($k);
                            if (!$goodsDetail) { //无产品数据，退出
                                return ['code' => 300, 'msg' => '商品不存在'];
                            }
                            $product_name = $goodsDetail['product_name'];
                            return ['code' => 300, 'msg' => $product_name . '库存不足'];
                        }

                        $qty_add_value = $add_count;
                        $replace_type = '+';
                    } else { //相等不做任何操作
                        continue;
                    }

                    //修改此菜品的qty
                    $this->ci->subscription_order_product_model->update(['id' => $selDetail['id']], ['qty' => $v, 'user_selection' => 1]);
                } else {
                    $qty_add_value = $v;
                    $replace_type = '+';

                    /* 新增已选择菜品数据 */
                    //获取当前菜品详情
                    $productDetail = $this->ci->subscription_model->getProductDetail($k);
                    if (!$productDetail) { //无产品数据，退出
                        $this->ci->db->trans_rollback();
                        return ['code' => 300, 'msg' => '商品不存在'];
                    }

                    //库存不足，提示并退出
                    if ($v >= $stockDetail['stock']) {
                        $this->ci->db->trans_rollback();
                        $product_name = $productDetail['product_name'];
                        return ['code' => 300, 'msg' => $product_name . '库存不足'];
                    }

                    //根据菜品详情插入菜品池子
                    $addData = [
                        'date' => $selSendDate,
                        'order_id' => $order_id,
                        'product_name' => $productDetail['product_name'],
                        'product_id' => $k,
                        'product_no' => $productDetail['product_no'],
                        'cate_id' => $cate_id,
                        'gg_name' => $productDetail['volume'] . '/' . $productDetail['unit'],
                        'qty' => $v,
                        'user_selection' => 1
                    ];
                    $this->ci->subscription_order_product_model->add($addData);
                }

                //修改菜品的库存
                $upWhere = [
                    'product_id' => $k,
                    'cate_id' => $cate_id
                ];
                $this->ci->subscription_product_model->update_qty($upWhere, $qty_add_value, $replace_type);
            }


            if ($this->ci->db->trans_status() === false) {
                $this->ci->db->trans_rollback();
                return ['code' => 300, 'msg' => '选菜失败'];
            } else {
                $this->ci->db->trans_commit();
                return ['code' => 200, 'msg' => '选菜已完成'];
            }
        } else {
            return ['code' => 300, 'msg' => '选菜未开始'];
        }
    }

    /**
     * 周期购订单支付完成
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     * @params $unpaid int 是否未完成支付标识：1未完成，0完成
     */
    public function orderPaySucc($uid, $order_id, $unpaid = 0)
    {
        $this->ci->load->model('subscription_order_model');
        $orderDetail = $this->ci->subscription_order_model->detail($uid, $order_id);
        if ($orderDetail) {
            //支付中心未及时修改状态时，把支付状态修改成：2支付确认中
            // if (($orderDetail['status'] == 0) && ($orderDetail['pay_status'] == 0) && ($unpaid == 0)) {
            //     $upResult = $this->ci->subscription_order_model->update($order_id, ['pay_status' => 2]);
            //     if ($upResult) {
            //         $orderDetail['pay_status'] = 2;
            //     }
            // }

            //支付状态文字描述
            $pay_status = $this->ci->config->item("pay");
            $orderDetail['pay_status_text'] = $pay_status[$orderDetail['pay_status']];

            $this->ci->load->model('subscription_order_address_model');
            $orderAddress = $this->ci->subscription_order_address_model->detail(0, $orderDetail['id']);
            return ['detail' => $orderDetail, 'address' => $orderAddress];
        }
        return [];
    }

    /**
     * 省心订订单日历数据
     *
     * 用于数据检查，勿在处理业务时使用
     * @params $order_id int 订单ID
     */
    public function orderDates($order_id)
    {
        $this->ci->load->model('subscription_model');
        return $dates = $this->ci->subscription_model->orderDates($order_id);
    }

    /**
     * 配送日期
     * @param $params
     * @return array
     */
    public function sendDates($params){
        $this->ci->load->model('subscription_model');
        $orderDay = !empty($params['orderDay']) ? $params['orderDay'] : '';
        if(!$orderDay || !preg_match('#^[1-7](,[1-7])*$#', $orderDay)){
            return ['code' => 300, 'msg' => '参数错误'];
        }
        $orderDay = explode(',', $orderDay);
        $orderDay = array_unique($orderDay);
        $order =  [
            'orderDay' => $orderDay,
            'count' => $params['count'],
        ];

        $dates = $this->ci->subscription_model->orderDates($order);
        if(empty($dates)){
            return ['code' => 300, 'msg' => '获取配送日期错误'];
        }
        return ['code' => 200, 'data' => $dates['dates']];
    }

    /**
     * 商品详情
     * @param $params
     * @return array
     */
    public function productDetail($params){
        $product_id = isset($params['product_id']) ? $params['product_id'] : 0;
        if (!$product_id) {
            return array('code' => 300, 'msg' => '商品不存在');
        }
        $this->ci->load->model('subscription_model');
        $product = $this->ci->subscription_model->getProductDetail($product_id);
        if (!$product) {
            return array('code' => 300, 'msg' => '商品不存在');
        }
        return ['code' => 200, 'data' => $product];
    }
}