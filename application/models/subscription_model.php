<?php

class Subscription_model extends CI_model
{
    private $_comboCate = array();
    private $date = null;
    private $time = null;
    private $deliveryTime = null;
    private $_freeStock = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('public');
        $this->date = date('Y-m-d');
        $this->time = date('Y-m-d H:i:s');
        $this->deliveryTime = strtotime('+2 days');
    }

    /**
     * 配送订单金额计算
     * @param $order
     * @param bool $one
     * @return array|float|int
     */
    private function getDeliveryOrderMoney($order, $one = true)
    {
        $total = $order['money'] + $order['use_money_deduction'];
        $money = floor($total / $order['count']);
        if ($order['valid_count'] == 1) {
            $money = $total - $money * ($order['count'] - 1);
        }

        if ($one) {
            return $money;
        }
        $use_money_deduction = 0;
        $goods_money = $money;
        if ($order['use_money_deduction'] > 0) {
            if ($order['use_money_deduction'] <= $money) {
                $use_money_deduction = $order['use_money_deduction'];
                $money -= $order['use_money_deduction'];
            } else {
                if ($order['use_money_deduction'] >= $money * ($order['count'] - $order['valid_count'] + 1)) {
                    $use_money_deduction = $money;
                    $money = 0;
                } else {
                    $use_money_deduction = $money * ($order['count'] - $order['valid_count'] + 1) - $order['use_money_deduction'];
                    if ($use_money_deduction < $money) {
                        $money -= $use_money_deduction;
                    } else {
                        $use_money_deduction = 0;
                    }

                }
            }
        }
        return ['money' => $money, 'use_money_deduction' => $use_money_deduction, 'goods_money' => $goods_money];
    }

    /**
     * 生成配送订单
     * @param $order
     * @return array
     */
    private function generateOrder($order, &$error = [])
    {
        $money = $this->getDeliveryOrderMoney($order, false);
        $fields = array(
            "uid" => $order['uid'],
            "order_name" => '',
            "trade_no" => $order['trade_no'],
            "billno" => $order['billno'],
            "time" => $this->time,
            "pay_time" => $this->time,
            "update_pay_time" => $this->time,
            "pay_name" => $order['pay_name'],
            "pay_parent_id" => $money['money'] ? $order['pay_parent_id'] : 5,
            "pay_id" => $money['money'] ? $order['pay_id'] : 0,
            "shtime" => date('Ymd', $this->deliveryTime),
            "stime" => "0914",
            "send_date" => date('Y-m-d', $this->deliveryTime),
            "money" => $money['money'] ? $money['money'] : $money['use_money_deduction'],
            "goods_money" => $money['goods_money'],
            //"bank_discount" => 0,
            "pay_status" => 1,
            "operation_id" => 1,
            "address_id" => $order['address_id'],
            "order_status" => 1,
            "channel" => 6,
            "use_money_deduction" => $money['money'] ? $money['use_money_deduction'] : 0,
            "order_type" => 8,
            "sync_status" => 0,
            "sheet_show_price" => 0,
        );

        //生成配送订单商品
        $products = $this->db->select('product_name, product_id, product_no, gg_name,price, qty, total_money')
            ->from('ttgy_subscription_order_product')
            ->where([
                'date' => date('Y-m-d', $this->deliveryTime),
                'order_id' => $order['id']
            ])->get()->result_array();

        if (empty($products)) {
            $error[] = "订单: ${order['order_name']} 菜单为空";
            return;
        }

        $pCount = array_column($products, 'qty');
        $pCount = array_sum($pCount);


        $cateAmount = $this->selectCateAmount($order, date('Y-m-d', $this->deliveryTime));
        $cateAmount = array_sum($cateAmount);

        //菜单数量校验
        if ($cateAmount != $pCount) {
            $error[] = "订单: ${order['order_name']} 菜单数量校验失败: 应选${cateAmount} 实选${pCount}";
            return;
        }


        $money = $this->getDeliveryOrderMoney($order);
        $price = floor($money / $pCount);
        $fields['pay_discount'] = $money - $price * $pCount;
        if ($fields['money'] > $fields['pay_discount']) {
            $fields['money'] -= $fields['pay_discount'];
        } elseif ($fields['use_money_deduction'] >= $fields['pay_discount']) {
            $fields['use_money_deduction'] -= $fields['pay_discount'];
        } else {
            $fields['money'] -= $fields['pay_discount'] - $fields['use_money_deduction'];
            $fields['use_money_deduction'] = 0;
        }

        $this->db->trans_begin();

        //生成配送订单
        while (true) {
            $rand_code = "";
            for ($i = 0; $i < 4; $i++) {
                $rand_code .= rand(0, 9);
            }
            $order_name = date("ymdi") . $rand_code;
            $fields['order_name'] = $order_name;
            $this->db->query(str_replace('INSERT INTO', 'INSERT IGNORE INTO', $this->db->insert_string('ttgy_order', $fields)));
            $order_id = $this->db->insert_id();
            if ($order_id) {
                break;
            }
        }
        foreach ($products as $k => $v) {
            $products[$k]['price'] = $price;
            $products[$k]['total_money'] = $price * $v['qty'];
            $products[$k]['order_id'] = $order_id;
            if (!isset($this->_freeStock[$v['product_no']])) {
                $this->_freeStock[$v['product_no']]['qty'] = 0;
                $this->_freeStock[$v['product_no']]['product_id'] = $v['product_id'];
            }
            $this->_freeStock[$v['product_no']]['qty'] += $v['qty'];
        }
        $this->db->insert_batch('ttgy_order_product', $products);

        //生成配送订单地址
        $address = $this->db->select('position,address,name,email,telephone,mobile,province,city,area')
            ->from('ttgy_subscription_order_address')
            ->where([
                'order_id' => $order['id']
            ])->get()->row_array();
        $address['order_id'] = $order_id;
        $this->db->insert('ttgy_order_address', $address);

        //生成配送订单和周期购订单关系
        $this->db->insert('ttgy_subscription_order_delivery', [
            'order_id' => $order['id'],
            'delivery_order_id' => $order_id,
            'date' => date('Y-m-d', $this->deliveryTime)
        ]);

        //周期购订单剩余次数-1
        $this->db->set('valid_count', 'valid_count-1', FALSE);
        $this->db->where('id', $order['id']);
        $this->db->update('ttgy_subscription_order');

        if ($order['valid_count'] <= 1) {
            $this->db->update('ttgy_subscription_order', ['status' => 3], ['id' => $order['id']]);
        }

        if ($this->db->trans_status() === FALSE) {
            $error[] = "订单: ${order['order_name']} 生成配送单异常: " . $this->db->_error_message();
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }

    }

    /**
     * 校正锁定库存
     */
    public function fixLockStock()
    {
        $stockFix = $this->db->query('select product_id,sum(qty) sum from ttgy_subscription_order_product where date >= ? group by product_id', [$this->todaySendDate()])->result_array();
        foreach ($stockFix as $v) {
            $this->db->update('ttgy_subscription_product', ['qty' => $v['sum']], ['product_id' => $v['product_id']]);
        }
    }

    public function doCurl($url, $params)
    {
        /*if ($url != 'http://www.500jia.com.cn/api/stock') {
            print_r($params);
            return;
        }*/
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POSTFIELDS => $params, CURLOPT_RETURNTRANSFER => true]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 自动生成配送订单
     */
    public function createDeliveryOrder()
    {
        $day = date('N', $this->deliveryTime);
        $date = date('Y-m-d', $this->deliveryTime);
        $orders = $this->db->select('o.*')
            ->from('ttgy_subscription_order o')
            ->join('ttgy_subscription_order_day d', 'd.order_id=o.id')
            ->where([
                'd.day' => $day,
                'o.status' => 1
            ])->get()->result_array();
        if (empty($orders)) {
            return;
        }

        $existed = $this->db->select('order_id')->from('ttgy_subscription_order_delivery')->where(['date' => $date])->get()->result_array();
        $existed = array_column($existed, 'order_id');
        foreach ($orders as $key => $order) {
            $dates = $this->orderDates($order);
            if ($date < $dates['firstDate'] || in_array($order['id'], $existed)) {
                unset($orders[$key]);
            }
        }

        if (empty($orders)) {
            return;
        }

        $orders = array_values($orders);

        //$this->createDefaultSelection($orders, $date);
        $error = [];
        foreach ($orders as $order) {
            $this->generateOrder($order, $error);
        }
        if (!empty($error)) {
            $this->emailNotify($error, 2);
        }

        //落单库存
        $jia = $this->db->select('product_id')->from('ttgy_subscription_product')->where(['source' => 2])->get()->result_array();
        $jia = array_column($jia, 'product_id');

        $jiaSKU = [];
        foreach ($this->_freeStock as $k => $v) {
            if (in_array($v['product_id'], $jia)) {
                $jiaSKU[] = [
                    'sku' => $k,
                    'count' => $v['qty']
                ];
            } else {
                $this->db->set('stock', 'stock-' . $v['qty'], FALSE);
            }

            $this->db->set('qty', 'qty-' . $v['qty'], FALSE);
            $this->db->where('product_id', $v['product_id']);
            $this->db->update('ttgy_subscription_product');
        }

        $params = [
            'appid' => '500jia',
            'timestamp' => time(),
            'type' => 2,
            'info' => json_encode($jiaSKU),
            'date' => $date
        ];
        $this->doCurl('http://www.500jia.com.cn/api/lockstock', $params);
    }

    /**
     * 过滤商品池中的指定商品
     * @param $pool
     * @param $filter
     * @return mixed
     */
    private function productFilter($pool, $filter)
    {
        if (empty($filter)) {
            return $pool;
        }
        $filter = array_unique($filter);
        foreach ($pool as $tag => $prods) {
            foreach ($prods as $prodID => $amount) {
                if (count($pool) == 1 && count($pool[$tag]) == 1) {
                    continue;
                }
                if (in_array($prodID, $filter)) {
                    unset($pool[$tag][$prodID]);
                    if (empty($pool[$tag])) {
                        unset($pool[$tag]);
                    }
                }
            }
        }
        return $pool;
    }

    private function productFilterFormat($pool)
    {
        return array_reduce($pool, function ($rs, $v) {
            return $rs + $v;
        }, []);
    }

    /**
     * 生成默认菜单
     * @param $orders
     * @param $date
     */
    public function createDefaultSelection($orders, $date = null)
    {
        if (!isset($orders[0])) {
            $orders = array($orders);
        }
        if (empty($date)) {
            $date = $this->todaySendDate();
        }
        $prods = $this->selection();
        $cateProds = [];
        $prodTags = [];
        $prodCates = [];

        foreach ($prods as $v) {
            $cateProds[$v['cate_id']][$v['tag']][$v['product_id']] = $v['stock'];
            $prodTags[$v['product_id']] = $v['tag'];
            $prodCates[$v['product_id']] = $v['cate_id'];
        }

        unset($prods);

        $allSelected = [];
        $error = [];
        foreach ($orders as $order) {
            //订单可选品类数量
            $cateAmount = $this->selectCateAmount($order, $date);

            //已生成菜单
            $selProds = $this->db->query('select product_id, qty, cate_id from ttgy_subscription_order_product where order_id=? and date=?', [$order['id'], $date])->result_array();

            //已生成菜单品类数量
            $selCateCount = array();
            foreach ($selProds as $v) {
                if (!isset($selCateCount[$v['cate_id']])) {
                    $selCateCount[$v['cate_id']] = 0;
                }
                $selCateCount[$v['cate_id']] += $v['qty'];
            }
            $selProds = array_column($selProds, 'product_id');

            //排除已生成菜单品类数量
            if ($selCateCount) {
                foreach ($cateAmount as $k => $v) {
                    if ($cateAmount[$k] <= $selCateCount[$k]) {
                        unset($cateAmount[$k]);
                    } else {
                        $cateAmount[$k] -= $selCateCount[$k];
                    }
                }
            }
            //已经选满菜
            if (empty($cateAmount)) {
                continue;
            }

            //最近一次配送过的菜
            $preSelCateProds = [];
            $preSelDate = $this->db->query('select max(date) date from ttgy_subscription_order_delivery where order_id=?', [$order['id']])->row_array();
            if (!empty($preSelDate['date'])) {
                $preSelProds = $this->db->query('select product_id,cate_id from ttgy_subscription_order_product where order_id=? and date=?', [$order['id'], $preSelDate['date']])->result_array();
                foreach ($preSelProds as $v) {
                    $preSelCateProds[$v['cate_id']][] = $v['product_id'];
                }
            }

            //忌口
            $exclusion = $this->db->query('select product_id from ttgy_subscription_exclusion where uid=?', [$order['uid']])->result_array();
            $exclusion = array_column($exclusion, 'product_id');

            $selected = array();
            foreach ($cateAmount as $cate => $amount) {
                $poolProds = $cateProds[$cate];
                if (empty($poolProds)) {
                    $error[] = "订单: ${order['order_name']} 品类为空：${cate}";
                    continue 2;
                }

                //商品池中过滤已生成, 忌口的菜
                $poolProds = $this->productFilter($poolProds, array_merge($selProds, $exclusion));
                $poolProdsFormat = $this->productFilterFormat($poolProds);

                //商品池中过滤最近一次配送
                $preSelCateProdsCount = 0;
                foreach ((array)$preSelCateProds[$cate] as $v) {
                    if (isset($poolProdsFormat[$v])) {
                        $preSelCateProdsCount++;
                    }
                }

                if ($preSelCateProdsCount && count($poolProdsFormat) >= bcadd($amount, $preSelCateProdsCount, 0)) {
                    $poolProds = $this->productFilter($poolProds, $preSelCateProds[$cate]);
                    $poolProdsFormat = $this->productFilterFormat($poolProds);
                }

                //可选菜种类不足异常处理
                if ($cate == 6 && count($poolProdsFormat) < $amount) {
                    $error[] = "订单: ${order['order_name']} 菜种类不足：应选 ${amount}, 可选 " . count($poolProdsFormat);
                    continue 2;
                }

                $cateCount = count($poolProds);
                $selTag = (array)array_rand($poolProds, $cateCount >= $amount ? $amount : $cateCount);
                foreach ($selTag as $tag) {
                    $selected[$cate][] = array_rand($poolProds[$tag], 1);
                }

                if ($cateCount < $amount) {
                    $poolProds = $this->productFilter($poolProds, $selected[$cate]);
                    $poolProds = $this->productFilterFormat($poolProds);
                    $fill = $amount - $cateCount;
                    $remain = count($poolProds);
                    if ($remain < $fill) {
                        while ($fill--) {
                            foreach ($poolProds as $prodID => $stock) {
                                if ($poolProds[$prodID] > 0) {
                                    $poolProds[$prodID] -= 1;
                                    $selected[$cate][] = $prodID;
                                }
                                if (empty($poolProds[$prodID])) {
                                    unset($poolProds[$prodID]);
                                }
                            }

                            if (empty($poolProds) || $amount == count($selected[$cate])) {
                                break;
                            }
                        }

                    } else {
                        $selected[$cate] = array_merge($selected[$cate], (array)array_rand($poolProds, $fill));
                    }
                }
                //选菜数量异常
                if ($amount != count($selected[$cate])) {
                    $error[] = "订单: ${order['order_name']} 选菜数量异常：应选 ${amount}, 实选 ${$poolProdsFormat}";
                    continue 2;
                }
            }
            $allSelected[$order['id']] = [];
            foreach ($selected as $cate => $sList) {
                $sList = array_count_values($sList);
                $allSelected[$order['id']] += $sList;
                foreach ($sList as $sPid => $sQty) {
                    $cateProds[$cate][$prodTags[$sPid]][$sPid] -= $sQty;
                    if ($cateProds[$cate][$prodTags[$sPid]][$sPid] <= 0) {
                        unset($cateProds[$cate][$prodTags[$sPid]][$sPid]);
                        if (empty($cateProds[$cate][$prodTags[$sPid]])) {
                            unset($cateProds[$cate][$prodTags[$sPid]]);
                        }
                    }
                }
            }
        }

        if (!empty($error)) {
            $this->emailNotify($error, 1);
        }

        if (empty($allSelected)) {
            return;
        }

        $sProdIDs = array_reduce($allSelected, function ($rs, $v) {
            return array_merge($rs, array_keys($v));
        }, []);
        $sProdIDs = array_unique($sProdIDs);

        $sProds = $this->db->select('p.product_name,pp.product_id,pp.product_no,volume,unit')
            ->from('ttgy_product p')
            ->join('ttgy_product_price pp', 'pp.product_id=p.id')
            ->where_in('p.id', $sProdIDs)
            ->get()->result_array();
        $sProds = array_column($sProds, null, 'product_id');

        $insert = [];
        $update = [];
        foreach ($allSelected as $orderID => $sList) {
            foreach ($sList as $pid => $qty) {
                $sop = $this->db->query('select id from ttgy_subscription_order_product where product_id =? and order_id =? and date=?', [$pid, $orderID, $date])->row_array();
                if (!$sop) {
                    $insert[] = array(
                        'date' => $date,
                        'order_id' => $orderID,
                        'product_name' => $sProds[$pid]['product_name'],
                        'product_id' => $sProds[$pid]['product_id'],
                        'product_no' => $sProds[$pid]['product_no'],
                        'gg_name' => $sProds[$pid]['volume'] . '/' . $sProds[$pid]['unit'],
                        'price' => 0,
                        'qty' => $qty,
                        'cate_id' => $prodCates[$pid],
                        'total_money' => 0,
                    );
                } else {
                    $update[$sop['id']] = $qty;
                }
            }
        }

        $this->db->trans_begin();
        if (!empty($insert)) {
            $this->db->insert_batch('ttgy_subscription_order_product', $insert);
        }

        if (!empty($update)) {
            foreach ($update as $id => $qty) {
                $this->db->set('qty', 'qty+' . $qty, FALSE);
                $this->db->where(['id' => $id]);
                $this->db->update('ttgy_subscription_order_product');
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->emailNotify($this->db->_error_message(), 1);
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }

    /**
     * 套餐分类
     * @param $combo_id
     * @return mixed
     */
    public function comboCate($combo_id)
    {
        if (isset($this->_comboCate[$combo_id])) {
            return $this->_comboCate[$combo_id];
        }
        $rs = $this->db->query('select cate_id,amount from ttgy_subscription_combo_cate where combo_id=?', $combo_id)->result_array();
        foreach ($rs as $v) {
            $this->_comboCate[$combo_id][$v['cate_id']] = $v['amount'];
        }
        return $this->_comboCate[$combo_id];

    }

    /**
     * 菜单列表
     * @param  $id
     * @return mixed
     */
    public function selection($id = 0)
    {
        $this->db->select('sp.*,p.product_name,p.product_desc,p.promotion_photo,p.template_id')
            ->from('ttgy_subscription_product sp')
            ->join('ttgy_product p', 'p.id=sp.product_id')
            ->where(['sp.valid' => 1, 'sp.stock >' => 0])
            ->order_by('sp.sort', 'desc');
        if ($id) {
            $this->db->where(['sp.cate_id' => (int)$id]);
        }
        $rs = $this->db->get()->result_array();
        $pc_url = constant('CDN_URL' . rand(1, 9));
        foreach ($rs as $k => $v) {
            // 获取产品模板图片
            if ($v['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($v['template_id'], 'whitebg');
                if (isset($templateImages['whitebg'])) {
                    $v['promotion_photo'] = $templateImages['whitebg']['image'];
                }
            }

            $rs[$k]['promotion_photo'] = $pc_url . $v['promotion_photo'];
        }
        return $rs;
    }

    /**
     * @param $date
     * @param int $oid
     * @param int $cate_id
     * @return mixed
     */
    public function selected($date, $oid = 0, $cate_id = 0)
    {
        $this->db->select('sp.*,p.product_name,p.product_desc,p.promotion_photo,c.name cate_name,p.template_id')
            ->from('ttgy_subscription_order_product sp')
            ->join('ttgy_product p', 'p.id=sp.product_id')
            ->join('ttgy_subscription_cate c', 'c.id=sp.cate_id')
            ->where('sp.date', $date);
        if ($oid) {
            $this->db->where(['sp.order_id' => (int)$oid]);
        }

        if ($cate_id) {
            $this->db->where(['sp.cate_id' => (int)$cate_id]);
        }
        $rs = $this->db->get()->result_array();
        $pc_url = constant('CDN_URL' . rand(1, 9));
        foreach ($rs as $k => $v) {
            // 获取产品模板图片
            if ($v['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($v['template_id'], 'whitebg');
                if (isset($templateImages['whitebg'])) {
                    $v['promotion_photo'] = $templateImages['whitebg']['image'];
                }
            }

            $rs[$k]['promotion_photo'] = $pc_url . $v['promotion_photo'];
        }
        return $rs;
    }

    /**
     * 我的订单列表提示订购订单
     * @param $uid
     * @param $cid
     * @return array|bool
     */
    public function sAlert($uid, $cid = '')
    {
        $rs = $this->db->query('select count(*) cou from ttgy_subscription_order where uid=?', [$uid])->row_array();
        if ($rs['cou'] > 0) {
            return array(
                'msg' => '您的省心订订单在这里哟, 点击查看',
                'url' => 'https://m.fruitday.com/subscription/orderList' . (!empty($cid) ? '?connect_id=' . $cid : '')
            );
        }
        return false;
    }

    /**
     * 会员等级计算
     * @param $uid
     * @return int
     */
    public function userRank($uid)
    {
        $rs = $this->db->query('select time,count,week_count from ttgy_subscription_order where uid=? and pay_status=1 and valid_count > 0 and status != 5', [$uid])->result_array();
        $date = date('Y-m-d');
        $rank = 0;
        foreach ($rs as $v) {
            $week = ceil($v['count'] / $v['week_count']);
            $endTime = date('Y-m-d', strtotime("+" . $week . " months", strtotime($v['time'])));
            if ($endTime >= $date) {
                $year = floor($week / 52);
                $quarter = floor($week / 12);
                if ($year > 0) {
                    $rank = 6;
                    break;
                } elseif ($quarter > 0) {
                    $rank = 4;
                }
            }
        }
        return $rank;
    }

    /**
     * 周期购订单详情列表
     * @param $params
     * @return array
     */
    public function crmOrderList($params)
    {
        $this->load->helper('public');
        $uid = !empty($params['uid']) ? $params['uid'] : 0;
        $mobile = !empty($params['mobile']) ? $params['mobile'] : '';
        $order_name = !empty($params['order_name']) ? $params['order_name'] : '';
        $is_master = !empty($params['is_master']) ? $params['is_master'] : 0;
        $db = !$is_master ? $this->db : ($this->db_master ? $this->db_master : $this->load->database('default_master', TRUE));
        if (empty($uid) && empty($mobile) && empty($order_name)) {
            return array('code' => 300, 'msg' => 'uid,mobile,order_name不能同时为空');
        }
        if ($mobile && !preg_match("/^1[0-9]{10}$/", $mobile)) {
            return array('code' => 300, 'msg' => '手机号格式错误');
        }
        $db->select('o.*,u.username,u.mobile')->from('ttgy_subscription_order o')->join('ttgy_user u', 'u.id=o.uid');
        if ($uid) {
            $db->where('o.uid', (int)$uid);
        } elseif ($mobile) {
            $db->where('u.mobile', $mobile);
        } elseif ($order_name) {
            $db->where_in('o.order_name', explode(',', $order_name));
        }

        $orders = $db->get()->result_array();

        if (empty($orders)) {
            return array('code' => 300, 'msg' => '订单不存在');
        }

        $orderList = array();
        foreach ($orders as $order) {
            $valid_money = ($order['money'] + $order['use_money_deduction']) - floor(($order['money'] + $order['use_money_deduction']) / $order['count']) * ($order['count'] - $order['valid_count']);
            $payment = [];
            $online_pay = $this->config->item("oms_online_pay");
            $payment[] = array(
                'payMethod' => $online_pay[$order['pay_parent_id']]['way_id'],
                'payPlatform' => $online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] ? $online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] : $online_pay[$order['pay_parent_id']]['platform_id'],
                'payAmount' => number_format($order['money'], 2, '.', ''),
                'tradeNo' => ($order['pay_id'] == '00003' && !$order['trade_no']) ? $order['order_name'] : $order['trade_no'],
            );

            if ($order['use_money_deduction'] > 0) {
                $payment[] = array(
                    'payMethod' => 9,
                    'payPlatform' => '',
                    'payAmount' => number_format($order['use_money_deduction'], 2, '.', ''),
                    'tradeNo' => $order['trade_no'],
                );
            }
            $status = [
                0 => '待付款',
                1 => '进行中',
                2 => '暂停',
                3 => '完成',
                5 => '取消'
            ];
            $combo = $this->db->select('name,price,count,week_count,summary')->from('ttgy_subscription_combo')->where(['id' => $order['combo_id']])->get()->row_array();
            $cate = $this->db->select('c.name,cc.amount')->from('ttgy_subscription_cate c')->join('ttgy_subscription_combo_cate cc', 'c.id=cc.cate_id')->where(['cc.combo_id' => $order['combo_id']])->get()->result_array();
            $day = $this->db->select('day')->from('ttgy_subscription_order_day')->where(['order_id' => $order['id']])->get()->result_array();
            $renew = $this->db->select('count')->from('ttgy_subscription_order_renew')->where(['renew_order_id' => $order['id']])->get()->row_array();
            $orderList[] = array(
                'uid' => $order['uid'],
                'order_name' => $order['order_name'],
                'username' => !empty($order['username']) ? $order['username'] : $order['mobile'],
                'mobile' => $order['mobile'],
                'status' => $status[$order['status']],
                'time' => $order['time'],
                'valid_count' => $order['valid_count'],
                'used_count' => $order['count'] - $order['valid_count'],
                'renew_count' => !empty($renew) ? $renew['count'] : 0,
                'money' => $order['money'],
                'valid_money' => $valid_money,
                'pay_time' => $order['update_pay_time'] && $order['update_pay_time'] != '0000-00-00 00:00:00' ? $order['update_pay_time'] : $order['time'],
                'payment' => $payment,
                'no_refund' => $order['pay_parent_id'] == 6 ? 1 : 0,
                'combo' => [
                    'name' => $combo['name'],
                    'price' => $combo['price'],
                    'count' => $combo['count'],
                    'week_count' => $combo['week_count'],
                    'summary' => $combo['summary'],
                    'day' => array_column($day, 'day'),
                    'cate' => $cate
                ]
            );
        }

        return array('code' => 200, 'data' => $orderList);
    }

    /**
     * crm周期购订单取消
     * @param $params
     * @return array
     */
    public function crmOrderCancel($params)
    {
        if (empty($params['order_name'])) {
            return array('code' => 300, 'msg' => '订单号不能为空');
        }
        $order = $this->db->select('id,order_name,pay_parent_id,status,uid')
            ->from('ttgy_subscription_order')
            ->where(['order_name' => $params['order_name']])
            ->get()->row_array();
        if (!$order) {
            return array('code' => 300, 'msg' => '订单不存在');
        }

        if (in_array($order['status'], [3, 5])) {
            return array('code' => 300, 'msg' => '取消失败:订单已取消或已完成');
        }

        $rs = $this->db->update('ttgy_subscription_order', array('status' => 5), array('id' => $order['id']));
        if (!$rs) {
            return array('code' => 300, 'msg' => '取消失败');
        }

        // 提货券
        if ($order['pay_parent_id'] == '6') {
            $deliveryOrder = $this->db->select('count(*) cou')
                ->from('ttgy_subscription_order_delivery')
                ->where(['order_id' => $order['id']])
                ->get()->row_array();
            //未配送过的，重置在线提货劵
            if (empty($deliveryOrder['cou'])) {
                $this->load->bll('card', null, 'bll_card');
                $this->bll_card->return_pro_card($order['uid'], $order['order_name']);
            }
        }
        return array('code' => 200, 'msg' => '取消成功');
    }

    /**
     * crm配送订单取消,退换货
     * @param $params
     * @param $type
     * @return array
     */
    public function crmDeliveryOrderDoCancel($params, $type)
    {
        if (empty($params['order_name'])) {
            return array('code' => 300, 'msg' => '订单号不能为空');
        }

        $order = $this->db->select('so.*,od.delivery_order_id,o.shtime,o.operation_id')
            ->from('ttgy_subscription_order_delivery od')
            ->join('ttgy_subscription_order so', 'so.id=od.order_id')
            ->join('ttgy_order o', 'o.id=od.delivery_order_id')
            ->where('o.order_name', $params['order_name'])
            ->get()->row_array();
        if (empty($order)) {
            return array('code' => 300, 'msg' => '订单不不存在');
        }

        $product_info = '';
        if ($type == 1) {
            if (empty($params['product_info'])) {
                return array('code' => 300, 'msg' => '退换货product_info不能为空');
            }
            $this->load->helper('public');
            if (!is_array($params['product_info'])) {
                $params['product_info'] = json_decode($params['product_info'], true);
            }
            $prods = array_column($params['product_info'], 'count', 'sku');
            $prodLists = $this->db->select('sp.cate_id,pp.product_no')
                ->from('ttgy_subscription_product sp')
                ->join('ttgy_product_price pp', 'pp.product_id=sp.product_id')
                ->where_in('pp.product_no', array_keys($prods))
                ->get()->result_array();
            $product_info = array();
            foreach ($prodLists as $v) {
                if (!isset($product_info[$v['cate_id']])) {
                    $product_info[$v['cate_id']] = $prods[$v['product_no']];
                } else {
                    $product_info[$v['cate_id']] += $prods[$v['product_no']];
                }
            }
            $product_info = serialize($product_info);
            $this->db->trans_begin();
        } else {//整单取消
            if ($order['operation_id'] == 5) {
                return array('code' => 300, 'msg' => '订单已经是取消状态, 不能操作');
            }
            $this->db->trans_begin();
            $rs = $this->db->update('ttgy_order', array('operation_id' => 5), array('id' => $order['delivery_order_id']));
            if (!$rs) {
                $this->db->trans_rollback();
                return array("code" => "300", "msg" => "配送订单取消失败");
            }
            $this->db->set('valid_count', 'valid_count+1', FALSE);
            if ($order['status'] == 3) {
                $this->db->set('status', 1);
            }
            $this->db->where('id', $order['id']);
            $rs = $this->db->update('ttgy_subscription_order');
            if (!$rs) {
                $this->db->trans_rollback();
                return array("code" => "300", "msg" => "周期购订单剩余配送次数更新失败");
            }
            //@todo 释放选择退换数量
        }
        $rs = $this->db->insert('ttgy_subscription_order_cancel', array(
            'order_id' => $order['id'],
            'delivery_order_id' => $order['delivery_order_id'],
            'type' => $type,
            'product_info' => $product_info,
            'date' => is_numeric($order['shtime']) ? date('Y-m-d', strtotime($order['shtime'])) : $order['shtime'],
            'created_at' => $this->time
        ));
        if (!$rs) {
            $this->db->trans_rollback();
            return array("code" => "300", "msg" => "周期购订单添加取消记录失败");
        }
        $this->db->trans_commit();
        return array("code" => "200", "msg" => "订单取消成功");
    }

    /**
     * crm周期购配送订单恢复
     * @param array $params
     * @return array
     */
    public function crmDeliveryOrderRollback($params = array())
    {
        if (empty($params['order_name'])) {
            return array('code' => 300, 'msg' => '订单号不能为空');
        }

        $order = $this->db->select('so.*,od.delivery_order_id,o.shtime,o.operation_id')
            ->from('ttgy_subscription_order_delivery od')
            ->join('ttgy_subscription_order so', 'so.id=od.order_id')
            ->join('ttgy_order o', 'o.id=od.delivery_order_id')
            ->where('o.order_name', $params['order_name'])
            ->get()->row_array();
        if (empty($order)) {
            return array('code' => 300, 'msg' => '订单不不存在');
        }

        if ($order['operation_id'] != 5) {
            return array('code' => 300, 'msg' => '订单不是取消状态, 不能操作');
        }

        $this->db->trans_begin();
        $rs = $this->db->update('ttgy_order', array('operation_id' => 9), array('id' => $order['delivery_order_id']));
        if (!$rs) {
            $this->db->trans_rollback();
            return array("code" => "300", "msg" => "配送订单恢复失败");
        }
        if($order['valid_count'] <= 0){
            return array("code" => "300", "msg" => "周期购订单剩余配送次数恢复失败: 剩余次数 <= 0");
        }

        $this->db->set('valid_count', 'valid_count-1', FALSE);
        $this->db->where('id', $order['id']);
        if ($order['valid_count'] == 1) {
            $this->db->set('status', 3);
        }
        $rs = $this->db->update('ttgy_subscription_order');
        if (!$rs) {
            $this->db->trans_rollback();
            return array("code" => "300", "msg" => "周期购订单剩余配送次数恢复失败");
        }

        $rs = $this->db->delete('ttgy_subscription_order_cancel', ['order_id'=> $order['id'], 'delivery_order_id' => $order['delivery_order_id']]);
        if (!$rs) {
            $this->db->trans_rollback();
            return array("code" => "300", "msg" => "周期购订单剩余配送次数恢复失败");
        }

        $this->db->trans_commit();
        return array("code" => "200", "msg" => "订单恢复成功");
    }

    /**
     * crm周期购商品信息
     * @param $params
     * @return array
     */
    public function crmOrderProduct($params)
    {
        $this->load->helper('public');
        if (empty($params['order_name'])) {
            return array('code' => 300, 'msg' => '订单号不能为空');
        }
        $orderName = explode(',', $params['order_name']);

        $orders = $this->db->select('d.order_id,d.date,o.order_name')
            ->from('ttgy_subscription_order_delivery d')
            ->join('ttgy_order o', 'o.id=d.delivery_order_id')
            ->where_in('o.order_name', $orderName)
            ->get()->result_array();
        $orderIDs = [];
        foreach ($orders as $v) {
            if (!in_array($v['order_id'], $orderIDs)) {
                $orderIDs[] = $v['order_id'];
            }
        }
        $cancel = [];
        if ($orderIDs) {
            $rs = $this->db->select('order_id,used,product_info')
                ->from('ttgy_subscription_order_cancel')
                ->where_in('order_id', $orderIDs)
                ->where(['type' => 1])
                ->get()->result_array();
            foreach ($rs as $v) {
                $info = unserialize($v['product_info']);
                if (!empty($v['used']) && $info) {
                    if (!isset($cancel[$v['order_id']][$v['used']])) {
                        $cancel[$v['order_id']][$v['used']] = $info;
                    } else {
                        foreach ($info as $c => $a) {
                            if (!isset($cancel[$v['order_id']][$v['used']][$c])) {
                                $cancel[$v['order_id']][$v['used']][$c] = 0;
                            }
                            $cancel[$v['order_id']][$v['used']][$c] += $a;
                        }
                    }

                }

            }
        }

        $cate = $this->db->select('id,name')
            ->from('ttgy_subscription_cate')
            ->get()->result_array();

        $cate = array_column($cate, 'name', 'id');

        $exchange = [];
        foreach ($orders as $v) {
            if (isset($cancel[$v['order_id']][$v['date']])) {
                $ec = [];
                foreach ($cancel[$v['order_id']][$v['date']] as $c => $a) {
                    $ec[] = [
                        'name' => $cate[$c],
                        'amount' => $a,
                    ];
                }

                $exchange[] = [
                    'order_name' => $v['order_name'],
                    'cate' => $ec,
                ];
            }
        }

        $data = [];
        $data['exchange'] = $exchange;
        return array('code' => 200, 'data' => $data);
    }

    /**
     * 推送周期购订单详情
     * @param $delivery_order_id
     * @return mixed
     */
    public function pushOrderInfo($delivery_order_id)
    {
        $sql = "select o.order_name,o.money,o.type,o.uid,o.ext_uid,o.use_money_deduction,o.trade_no,o.pay_parent_id,o.bank_discount,o.pay_id from ttgy_subscription_order_delivery d inner join ttgy_subscription_order o on o.id=d.order_id  where d.delivery_order_id=?";
        $subscription = $this->db->query($sql, $delivery_order_id)->row_array();
        $online_pay = $this->config->item("oms_online_pay");
        $payment = [];
        if (!in_array($subscription['pay_parent_id'], [6])) {
            $subscription['bank_discount'] = $subscription['bank_discount'] ? $subscription['bank_discount'] : 0;
            $payment[] = array(
                'paym' => $online_pay[$subscription['pay_parent_id']]['way_id'], // 1:支付宝付款,2:联华OK会员卡在线支付,3:网上银行支付,4:线下支付,5:账户余额支付,6:券卡支付
                'payAmount' => bcsub($subscription['money'], $subscription['bank_discount'], 2),//number_format($order['money'],2,'.',''),
                'payplatform' => $online_pay[$subscription['pay_parent_id']]['children_platform_id'][$subscription['pay_id']] ? $online_pay[$subscription['pay_parent_id']]['children_platform_id'][$subscription['pay_id']] : $online_pay[$subscription['pay_parent_id']]['platform_id'],
                'ticketCode' => '',
                'ticketCount' => 0,
                'chrgno' => ($subscription['pay_id'] == '00003' && !$subscription['trade_no']) ? $subscription['order_name'] : $subscription['trade_no'],
                'disCode' => '',
            );
        }

        if ($subscription['use_money_deduction'] > 0) { // 帐户余额抵消
            $payment[] = array(
                'paym' => 9,
                'payAmount' => number_format($subscription['use_money_deduction'], 2, '.', ''),
                'payplatform' => null,
                'ticketCode' => '',
                'ticketCount' => 0,
                'chrgno' => $subscription['trade_no'],
                'disCode' => '',
            );
        }
        if ($subscription['pay_parent_id'] == '6') { //券卡支付
            $juan = $this->db->select('card_number')->from('pro_card')->where(array('order_name' => $subscription['order_name'], 'is_used' => '1', 'is_sent' => '1'))->get()->row_array();
            $payment[] = array(
                'paym' => 5,
                'payAmount' => number_format($subscription['money'], 2, '.', ''),
                'payplatform' => 5001,
                'ticketCode' => $juan ? $juan['card_number'] : '',
                'ticketCount' => $juan ? 1 : 0,
                'chrgno' => '',
                'disCode' => '',
            );
        }
        return array(
            'groupNo' => $subscription['order_name'],
            'money' => $subscription['money'],
            'type' => $subscription['type'],//1:果园,2:500jia和果园联合,3:500jia
            'uid' => $subscription['ext_uid'],
            'payment' => $payment,
        );
    }

    /**
     * 获取推送sap周期购支付订单列表
     * @param $order_names
     */
    public function orderTrade($order_names)
    {
        $limit = 100;
        $s_time = date('Y-m-d H:i:s', (time() - 3600 * 24 * 3));
        $e_time = date('Y-m-d H:i:s', (time()));
        if ($order_names) {
            $order_names = implode("','", $order_names);
            $where = " and o.order_name in('" . $order_names . "')";
        } else {
            $where = "and (o.update_pay_time between '" . $s_time . "' and '" . $e_time . "' or o.time between '" . $s_time . "' and '" . $e_time . "')";
        }
        $sql = "select o.id,u.username,o.order_name,o.billno,o.trade_no,o.pay_time,o.time,o.update_pay_time,o.money,o.pay_parent_id,o.pay_id,o.uid,o.sync_erp,o.use_money_deduction,o.bank_discount from ttgy_subscription_order o left join ttgy_user u on u.id=o.uid where o.pay_status=1 and o.sync_erp=0 " . $where . " order by o.time limit " . $limit;
        $result = $this->db->query($sql)->result_array();
        return $result;
    }

    /**
     * 同步财务系统成功
     * @param $orderNames
     */
    public function syncErp($orderNames)
    {
        $this->db->where_in('order_name', $orderNames);
        $this->db->update('ttgy_subscription_order', ['sync_erp' => 1]);
    }

    /**
     * 订单日历数据
     * @param $order
     * @return array
     */
    public function getOrderCalendar($order)
    {
        $this->load->helper('public');

        //订单日期列表
        $dates = $this->orderDates($order);

        //日历开始,结束日期
        $minDate = getdate(strtotime(min($dates['firstDate'], $this->date)));
        $maxDate = getdate(strtotime(max($dates['endDate'], $this->date)));
        $data = [
            'minDate' => ['year' => $minDate['year'], 'mon' => $minDate['mon'], 'mday' => $minDate['mday']],
            'maxDate' => ['year' => $maxDate['year'], 'mon' => $maxDate['mon'], 'mday' => $maxDate['mday']],
        ];

        //日历格式
        foreach ($dates['dates'] as $v) {
            $day = [
                'd' => date('n/j', strtotime($v)),
            ];
            if ($v == $this->date) {
                $day['text'] = '配送中';
                $day['color'] = 'rgb(255, 123, 34)';
            } elseif ($v > $this->date) {
                $day['color'] = 'rgb(36,205,209)';
            } else {
                $day['color'] = 'rgb(64,190,95)';

            }

            if ($dates['selSendDate'] && $dates['selSendDate'] == $v && $dates['selStartDate'] <= $this->time && $this->time < $dates['selEndDate']) {
                $day['text'] = '可选菜';
            }

            if (in_array($v, $dates['cancel'])) {
                $day['color'] = 'rgb(64,190,95)';
                $day['text'] = '已取消';
            } elseif (in_array($v, $dates['pause'])) {
                $day['color'] = 'rgb(226,226,226)';
                $day['text'] = '';
                $day['icon'] = 'minus';
            } elseif ($dates['lastDate'] < $v && $v > $this->date) {
                $day['color'] = 'rgb(36,205,209)';
                $day['text'] = '顺延';
            }

            $data['dates'][date('Y-n', strtotime($v))][] = $day;
        }
        return $data;
    }

    /**
     * 订单日期列表计算
     * @param $order array|int
     * @param $toDate
     * @return array
     */
    public function orderDates($order, $toDate = '')
    {
        $this->load->helper('public');

        if (is_numeric($order)) {
            $order = $this->db->get_where('ttgy_subscription_order', ['id' => $order])->row_array();
        }

        if (!empty($order['id'])) {
            //订单每周配送日
            $orderDay = $this->db->get_where('ttgy_subscription_order_day', ['order_id' => $order['id']])->result_array();
            $orderDay = array_column($orderDay, 'day');

            //已经配送日期
            $finish = $this->db->get_where('ttgy_subscription_order_delivery', ['order_id' => $order['id']])->result_array();
            $finish = array_column($finish, 'date');

            //配送订单取消日期
            $cancel = $this->db->get_where('ttgy_subscription_order_cancel', ['order_id' => $order['id'], 'type' => 0])->result_array();
            $cancel = array_column($cancel, 'date');

            //配送订单暂停日期
            $pause = $this->db->get_where('ttgy_subscription_order_pause', ['order_id' => $order['id']])->result_array();
            $pause = array_column($pause, 'date');

            //顺延日期
            $postpone = array_unique(array_merge($cancel, $pause));
        } else {
            $defaultOrder = [
                'orderDay' => [], //[1,4]
                'count' => 0,
                'valid_count' => 0,
                'time' => $this->time,
                'first_date' => null,
            ];
            $order += $defaultOrder;

            $orderDay = $order['orderDay'];
            $finish = $cancel = $pause = $postpone = [];
        }

        if (empty($order['count']) || empty($orderDay)) {
            return [];
        }

        sort($orderDay);

        //配送开始到结束的总次数(包含取消,暂停,顺延)
        $count = $order['count'] + count($postpone);

        //第一单配送日期计算
        if (!empty($order['first_date'])) {
            $orderStartTime = strtotime($order['first_date']);
            $firstDate = $order['first_date'];
        } else {
            $orderStartTime = strtotime($order['time']);
            $firstDate = date('Y-m-d', strtotime('+3 days', $orderStartTime));
        }
        $orderStartDay = date('N', $orderStartTime);
        $firstDateList = [];
        foreach ($orderDay as $v) {
            $firstDateList[] = date('Y-m-d', $orderStartTime + (($v - $orderStartDay) * 86400));
            $firstDateList[] = date('Y-m-d', strtotime('+1 week', $orderStartTime) + (($v - $orderStartDay) * 86400));
        }
        //如：今天是周日，选择周一配送，那第一配送为下下周周一
        if (count($orderDay) == 1) {
            $firstDateList[] = date('Y-m-d', strtotime('+2 weeks', $orderStartTime) + (($orderDay[0] - $orderStartDay) * 86400));
        }
        if (!in_array($firstDate, $firstDateList)) {
            $firstDateList[] = $firstDate;
            sort($firstDateList);
            $firstDate = $firstDateList[array_search($firstDate, $firstDateList) + 1];
        }

        if (empty($firstDate)) {
            return [];
        }
        $dates = [];

        $valid_count = 0;

        $firstTime = strtotime($firstDate);
        $firstDay = date('N', $firstTime);
        $weekDateTime = $firstTime;
        $datesEndItem = $lastDate = $selStartDate = $selEndDate = $selSendDate = $curSendDate = $nextSendDate = '';
        while (true) {
            foreach ($orderDay as $v) {
                //第一个配送日期
                if (empty($dates)) {
                    if ($v != $firstDay) {
                        continue;
                    }
                    $datesEndItem = $firstDate;
                } else {
                    $datesEndItem = date('Y-m-d', $weekDateTime + (($v - date('N', $weekDateTime)) * 86400));
                }
                $dates[] = $datesEndItem;

                if (count($dates) == $order['count']) {
                    $lastDate = $datesEndItem;
                }

                if ($datesEndItem > $this->date && !$selEndDate) {
                    $selStartDate = date('Y-m-d 12:00:00', strtotime('-3 days', strtotime($datesEndItem)));
                    $selEndDate = date('Y-m-d 12:00:00', strtotime('-2 days', strtotime($datesEndItem)));
                    $selSendDate = $datesEndItem;
                    if (!$curSendDate) {
                        $curSendDate = $datesEndItem;
                    }
                    if ($selEndDate < $this->time) {
                        $selStartDate = $selEndDate = $selSendDate = '';
                    } else {
                        $nextSendDate = $datesEndItem;
                    }
                } elseif ($datesEndItem == $this->date) {
                    $curSendDate = $datesEndItem;
                }

                if ($datesEndItem > $this->date) {
                    $valid_count++;
                }

                if (count($dates) >= $count) {
                    if ($valid_count >= $order['valid_count']) {
                        if (!$toDate || date('Y-m-d 12:00:00', strtotime($datesEndItem)) > date('Y-m-d H:i:s', strtotime('+2 days', strtotime($toDate . date(" H:i:s"))))) {
                            break 2;
                        }
                    }

                }

                if ($v == end($orderDay)) {
                    $weekDateTime = strtotime('+1 week', strtotime($datesEndItem));
                }
            }
        }

        if ($selSendDate > $datesEndItem) {
            $selStartDate = $selEndDate = $selSendDate = $nextSendDate = '';
        }
        //firstDate: 第一次配送时间; lastDate:正常最后一次配送时间; endDate:包含顺延最后一次配送时间; dates:所有配送日期; cancel:取消的日期; pause:暂停的日期; finish:已经生成订单
        //selStartDate: 选菜开始时间; selEndDate: 选菜结束时间点; selSendDate: 选菜配送日
        return ['firstDate' => $firstDate, 'lastDate' => $lastDate, 'endDate' => $datesEndItem, 'curSendDate' => $curSendDate, 'nextSendDate' => $nextSendDate, 'selStartDate' => $selStartDate, 'selEndDate' => $selEndDate, 'selSendDate' => $selSendDate, 'dates' => $dates, 'cancel' => $cancel, 'pause' => $pause, 'finish' => $finish];
    }

    /**
     * 今天对应的配送日
     */
    public function todaySendDate()
    {
        $limit = date('Y-m-d 12:00:00', strtotime($this->time));
        $day = 3;
        if ($this->time < $limit) {
            $day = 2;
        }

        return date('Y-m-d', strtotime('+' . $day . ' days', strtotime($this->time)));
    }

    /**
     * 当天订单总数，未来7天有效订单量(提供500jia api)
     * @return array
     */
    public function orderTotal()
    {

        $d0 = $this->db->select('count(*) cou')->from('ttgy_subscription_order')->where_in('status', [1, 2])->get()->row_array();
        $data = [
            'd0' => $d0['cou'],
            'd1' => 0,
            'd2' => 0,
            'd3' => 0,
            'd4' => 0,
            'd5' => 0,
            'd6' => 0,
            'd7' => 0
        ];
        $orders = $this->db->select('id,time,count')->from('ttgy_subscription_order')->where_in('status', 1)->get()->result_array();
        $d = [];
        for ($i = 1; $i <= 7; $i++) {
            $d[$i] = date('Y-m-d', strtotime('+' . $i . ' days'));
        }

        foreach ($orders as $order) {
            $dates = $this->orderDates($order);
            foreach ($dates['dates'] as $date) {
                if ($k = array_search($date, $d)) {
                    $data['d' . $k] += 1;
                }
                if ($date > $d[7]) {
                    break;
                }
            }
        }
        return $data;
    }

    /**
     * 获取周期购配送订单列表
     *
     * @params $orderIdArray array 多个订单ID
     */
    public function getDeliveryOrderLists($orderIdArray)
    {
        $this->load->helper('public');
        if (!$orderIdArray OR !is_array($orderIdArray)) {
            return [];
        }
        $rows = $this->db->select('id,order_name,shtime,time,operation_id')->from('order')->where_in('id', $orderIdArray)->order_by('id', 'DESC')->get()->result_array();
        $sum = $this->db->select('order_id, sum(qty) sum')->from('ttgy_order_product')->where_in('order_id', $orderIdArray)->group_by('order_id')->get()->result_array();
        $sum = array_column($sum, 'sum', 'order_id');
        foreach ($rows as $key => $value) {
            $rows[$key]['sum'] = $sum[$value['id']];
        }
        return $rows;
    }

    /**
     * 暂停
     * @param $order
     * @return string
     */
    public function pause($order)
    {
        if (is_numeric($order)) {
            $order = $this->db->get_where('ttgy_subscription_order', ['id' => $order])->row_array();
        }

        if ($order['valid_count'] <= 0) {
            return false;
        }

        //@todo 释放选择退换数量

        return $this->db->update('ttgy_subscription_order', ['status' => 2], ['id' => $order['id']]);
    }

    /**
     * 暂停配送日
     * @param $order
     * @return mixed
     */
    public function pauseDate($order)
    {
        if (is_numeric($order)) {
            $order = $this->db->get_where('ttgy_subscription_order', ['id' => $order])->row_array();
        }
        $dates = $this->orderDates($order);
        return $dates['selSendDate'];

    }

    /**
     * 恢复暂停
     * @param $order
     * @return bool
     */
    public function restorePause($order)
    {
        if (is_numeric($order)) {
            $order = $this->db->get_where('ttgy_subscription_order', ['id' => $order])->row_array();
        }

        if ($order['status'] != 2) {
            return false;
        }
        $dates = $this->orderDates($order, $this->date);
        $pauseDate = [];

        if ($this->date < $dates['endDate']) {
            $end = array_search($dates['selSendDate'], $dates['dates']);
            $start = $order['count'] - $order['valid_count'] + count($dates['cancel']) + count($dates['pause']);
            if ($end - $start > 0) {
                $pauseDate = array_slice($dates['dates'], $start, $end - $start);
            }
        }

        if (empty($pauseDate)) {
            return $this->db->update('ttgy_subscription_order', ['status' => 1], ['id' => $order['id']]);
        }
        $insert = [];
        $this->db->trans_begin();
        foreach ($pauseDate as $date) {
            if ($this->time < date('Y-m-d 12:00:00', strtotime('-2 days', strtotime($date)))) {
                continue;
            }
            $insert[] = [
                'order_id' => $order['id'],
                'date' => $date,
                'created_at' => $this->time
            ];

            $prods = $this->db->select('product_id,qty')->from('ttgy_subscription_order_product')->where(['order_id' => $order['id'], 'date' => $date])->get()->result_array();
            foreach ($prods as $k => $v) {
                $this->db->set('qty', 'qty-' . $v['qty'], FALSE);
                $this->db->where('product_id', $v['product_id']);
                $this->db->update('ttgy_subscription_product');
            }
            $this->db->delete('ttgy_subscription_order_product', ['order_id' => $order['id'], 'date' => $date]);
        }
        $this->db->update('ttgy_subscription_order', ['status' => 1], ['id' => $order['id']]);
        if (!$insert) {
            $this->db->trans_commit();
            return true;
        }


        $this->db->insert_batch('ttgy_subscription_order_pause', $insert);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
        }

        return true;
    }

    /**
     * 获取单个套餐对应菜品品类的数据
     *
     * @params $id int 套餐ID
     */
    public function combo_cate($id)
    {
        $sql = "SELECT
          d.name,
          c.amount
        FROM
          ttgy_subscription_combo_cate c
          JOIN ttgy_subscription_cate d
            ON d.`id` = c.`cate_id`
        WHERE c.`combo_id` = ?";
        $result = $this->db->query($sql, [$id])->result_array();
        return $result;
    }

    public function selectCateAmount($order, $date)
    {
        //上一次退换的品类数量
        $unused = $this->db->query('select id,used,product_info from ttgy_subscription_order_cancel where order_id=? and type=1 and (used is null or used = ?)', [$order['id'], $date])->row_array();
        if (!empty($unused)) {
            if (!$unused['used']) {
                $this->db->update('ttgy_subscription_order_cancel', ['used' => $date], ['id' => $unused['id']]);
            }
            $unused = unserialize($unused['product_info']);

        }

        //套餐品类数量
        $cateAmount = $this->comboCate($order['combo_id']);
        foreach ($cateAmount as $k => $v) {
            if (isset($unused[$k])) {
                $cateAmount[$k] = $v + $unused[$k];
            }
        }
        return $cateAmount;
    }

    /**
     * 获取官网商品详情
     *
     * @param $product_id int 商品ID
     * @return array
     */
    public function getProductDetail($product_id)
    {
        if (!$product_id) {
            return [];
        }
        $sql = 'select p.product_name,pp.product_id,pp.product_no,volume,unit,p.discription from ttgy_product p join ttgy_product_price pp on pp.product_id=p.id where p.id=?';
        $detail = $this->db->query($sql, $product_id)->row_array();
        if ($detail) {
            $detail['discription'] = str_replace(" src=\"", " src=\"" . trim(PIC_URL, '/'), $detail['discription']);
            $detail['discription'] = str_replace("url(/", "url(" . trim(PIC_URL, '/') . "/", $detail['discription']) . $detail['source'];
            $tr = str_replace('&nbsp;', '', $detail['discription']);
            $tr = trim($tr);
            if (empty($tr)) {
                $detail['discription'] = '';
            }
            if ($detail['discription'] && strpos($detail['discription'], 'id="main_box"') === false) {
                $detail['discription'] = '<div class="fd-detail">' . $detail['discription'] . '</div>';
            }
        }
        return $detail;
    }

    /**
     * 邮件通知
     * @param $msg
     * @param $type
     */
    public function emailNotify($msg, $type)
    {
        $email = ['huangb@fruitday.com', 'yangyx@fruitday.com'];
        $limit = 5;
        switch ($type) {
            case 1:
                $title = '周期购选菜异常';
                break;
            case 2:
                $title = '周期购选菜生成配送单异常';
                break;
            case 3:
                $title = '周期购库存同步异常';
                break;
        }

        if (empty($msg) || empty($title) || empty($email)) {
            return;
        }

        $msg = is_array($msg) ? implode("\r\n", $msg) : $msg;

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->memcached) {
                $this->load->library('memcached');
            }
            $memKey = "subscription_email_notify_" . date('H') . "_" . $type;
            $total = (int)$this->memcached->get($memKey);
            if ($total >= $limit) {
                return;
            }
            $total++;
            $this->memcached->set($memKey, $total, 7200);
        }

        $send_params = [
            "email" => (array)$email,
            "title" => $title,
            "message" => $msg,
        ];

        $this->load->library("notifyv1");
        $this->notifyv1->send('email', 'group', $send_params);
    }

    /**
     * 续订活动
     * @param $uid
     * @param $renew_combo_id
     * @return array|bool
     */
    public function renew($uid, $renew_combo_id)
    {
        if (!$renew_combo_id) {
            return false;
        }
        $sql = 'select * from ttgy_subscription_renew where renew_combo_id = ?';
        $renew = $this->db->query($sql, $renew_combo_id)->result_array();

        $time = date('Y-m-d H:i:s');

        //有效活动
        $renew = array_filter($renew, function ($item) use ($time) {
            return $item['start_date'] <= $time && $item['end_date'] >= $time && $item['valid'] == 1 && $item['count'] > 0;
        });

        if (empty($renew)) {
            return false;
        }

        $sql = 'select id,combo_id,time,count,valid_count,status from ttgy_subscription_order where uid = ? and pay_status=1 and status != 5';
        $orders = $this->db->query($sql, $uid)->result_array();

        if (empty($orders)) {
            return false;
        }

        //匹配到的活动
        $data = [];
        foreach ($renew as $item) {
            foreach ($orders as $order) {
                if ($item['combo_id'] != $order['combo_id']) {
                    continue;
                }
                if ($order['status'] == 3 && $item['end_day'] > 0) {
                    $sql = 'select max(date) date from ttgy_subscription_order_delivery where order_id = ?';
                    $date = $this->db->query($sql, $order['id'])->row_array();
                    if (date('Y-m-d', strtotime($time)) > date('Y-m-d', strtotime('+' . $item['end_day'] . ' days', strtotime($date['date'])))) {
                        continue;
                    }
                } elseif ($item['start_count'] > $order['count'] - $order['valid_count']) {
                    continue;
                }
                $item['order_id'] = $order['id'];
                $data[] = $item;
                break;
            }
        }

        if (empty($data)) {
            return false;
        }

        //只保留一个活动
        array_reduce($data, function ($rs, $item) use ($time) {
            if (isset($rs['count']) && $rs['count'] >= $item['count']) {
                return $rs;
            }
            return $item;
        }, []);

        return $data ? array_shift($data) : false;
    }

    /**
     * 订单续订
     * @param $data
     * @return bool
     */
    public function orderRenewLog($data)
    {
        if (empty($data)) {
            return false;
        }
        return $this->db->insert('ttgy_subscription_order_renew', $data);
    }

    public function fixedYear($datetime)
    {
        if ($datetime == '1483200000') {
            return '2016';
        } else {
            return date('Y', $datetime);
        }
    }
}