<?php

class Subs extends CI_Controller
{
    /**
     * 订单迁移
     */
    public function OrderMigration()
    {
        //$this->db = $this->load->database('default_master', TRUE);
        $path = __DIR__ . '/order.json';
        if (!file_exists($path)) {
            echo $path . '文件找不到';
        }

        $skuMap = [
            '10369' => '2160823109',
            '10373' => '2161013105',
            '10374' => '2160824113',
            '10375' => '2160824114',
            '10376' => '2160824117',
            '10377' => '2160708103',
            '10383' => '2161013105',
            '10372' => '2160517104',
            '10371' => '2160823108',
            '10370' => '2160823110',
        ];

        $data = file_get_contents($path);
        $data = json_decode($data, true);
        if (!$data) {
            echo 'json 格式异常';
        }

        $this->db->trans_begin();
        $this->load->model('subscription_model');
        foreach ($data as $ind => $order) {
            $existed = $this->db->select('id')->from('ttgy_subscription_order')->where('ext_uid', $order['id'])->get()->row_array();
            if ($existed) {
                echo "===存在{$order['id']}===";
                continue;
            }

            echo "-----{$ind}:{$order['id']}-----\n";
            if(empty($order['openid']) || empty($order['valid_count'])){
                echo 'id:' . $order['id'] . '数据异常' . "\n";
                continue;
            }
            $user = $this->db->select('*')->from('ttgy_auth')->where(['openid' => $order['openid']])->get()->row_array();
            $uid = $user['uid'];

            $address = $this->addAddr($order, $uid);
            $this->dbLog();
            $current = $order['combo']['current_subscription'];
            $current = $this->addCombo($current);
            $o = $this->addOrder($order, $current, $address, $uid);
            $dates = $this->subscription_model->orderDates($o);

            //print_r($dates);

            /*if(empty($dates['selSendDate'])){
                print_r($dates);
                continue;
            }*/
            if (!empty($order['lastestselectioninfo']['selection'])) {
                foreach ($order['lastestselectioninfo']['selection'] as $k =>$v){
                    if(isset($skuMap[$v['sku']])){
                        $order['lastestselectioninfo']['selection'][$k]['sku'] = $skuMap[$v['sku']];
                    }
                }
                if($order['lastestselectioninfo']['status'] == 0){
                    $this->addSelected($order['lastestselectioninfo']['selection'], $o['id'], $dates['selSendDate']);
                }
                $this->dbLog();
            }

            if (!empty($order['combo']['backup_subscription'])) {
                $order['time'] = $dates['endDate'];
                $backup = $this->addCombo($order['combo']['backup_subscription']);
                $order['valid_count'] = 0;
                $order['status'] = 1;
                $this->addOrder($order, $backup, $address, $uid);
            }
        }

        $this->fixLockStock();

        $this->db->trans_rollback();die;
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }

    private function dbLog(){
        echo $this->db->last_query() . "\n\n";
    }

    public function fixLockStock(){
        $this->load->model('subscription_model');
        $this->subscription_model->fixLockStock();
    }

    /**
     * 获取500家库存
     */
    public function syncStock()
    {

        $this->lockStock();

        $this->load->helper('public');
        $this->load->model('subscription_model');
        $params = [
            'appid' => '500jia',
            'timestamp' => time(),
            'date' => $this->subscription_model->todaySendDate()
        ];

        $ors = $rs = $this->subscription_model->doCurl('http://www.500jia.com.cn/api/stock', $params);
        if (!$rs) {
            $this->subscription_model->emailNotify("500家库存数据为空", 3);
            return;
        }

        $rs = json_decode($rs, true);
        if(JSON_ERROR_NONE !== json_last_error()){
            $this->subscription_model->emailNotify("500家库存数据格式错误: " . $ors, 3);
            return;
        }

        $rs = array_column($rs, 'quantity', 'sku');
        if(empty($rs)){
            $this->subscription_model->emailNotify("500家库存数据为空或数据异常:" . $ors, 3);
            return;
        }

        $prods = $this->db->select('sp.product_id,pp.product_no,sp.valid')->from('ttgy_subscription_product sp')
            ->join('ttgy_product_price pp', 'pp.product_id=sp.product_id')
            ->where('source', 2)->get()->result_array();
        $prods = array_column($prods, null, 'product_no');
        $update = [];
        $delete = [];
        $insert = [];
        foreach ($prods as $v) {
            if (isset($rs[$v['product_no']])) {
                $update[$v['product_id']] = (int)$rs[$v['product_no']];
            } else {
                $delete[$v['product_id']] = 1;
            }
        }
        foreach ($rs as $k => $v) {
            if (!isset($prods[$k])) {
                $insert[$k] = (int)$v;
            }
        }

        $valid = array_filter($update);

        if(count($valid) < 10){
            $this->subscription_model->emailNotify("500家库存商品小于10", 3);
        }

        $this->db->trans_begin();

        foreach ($update as $k => $v) {
            $this->db->update('ttgy_subscription_product', ['stock' => $v], ['product_id' => $k]);
        }

        if (!empty($delete)) {
            $this->db->where_in('product_id', array_keys($delete));
            $this->db->update('ttgy_subscription_product', ['stock' => 0]);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }

        //log
        /*if(!empty($insert)){
            $insert = array_keys($insert);
            // 失败发邮件
            $this->load->model('jobs_model');
            $emailList = array('huangb@fruitday.com', 'luys@fruitday.com', 'lusc@fruitday.com');
            foreach ($emailList as $email) {
                $emailContent = '周期购商品SKU[' . implode(',', $insert) . ']找不到, 请运营联系500家添加';
                $this->jobs_model->add(array('email' => $email, 'text' => $emailContent, 'title' => "周期购商品SKU缺失"), "email");
            }
        }*/

    }

    /**
     * 锁定库存
     */
    public function lockStock()
    {
        $this->load->helper('public');
        $this->load->model('subscription_model');
        $vps = $this->db->query('select pp.product_no from ttgy_subscription_product p join ttgy_product_price pp on p.product_id=pp.product_id  where p.source=2 and p.valid=1')->result_array();

        $date = $this->subscription_model->todaySendDate();

        $prods = $this->db->query('select product_no,sum(qty) sum from ttgy_subscription_order_product where date=? group by product_no', [$date])->result_array();
        $prods = array_column($prods, 'sum', 'product_no');
        $post = [];
        foreach ($vps as $v){
            $post[] = [
                'sku' => $v['product_no'],
                'count' => isset($prods[$v['product_no']]) ? $prods[$v['product_no']] : 0
            ];
        }

        $params = [
            'appid' => '500jia',
            'timestamp' => time(),
            'type' => 1,
            'info'=>json_encode($post),
            'date' => $date,
        ];
        $this->subscription_model->doCurl('http://www.500jia.com.cn/api/lockstock', $params);
    }

    private function addCombo($combo)
    {
        $rs = $this->db->select('*')
            ->from('ttgy_subscription_combo')
            ->where('ext_id', $combo['id'])
            ->get()->row_array();
        if ($rs) {
            return $rs;
        }
        $insCombo = array(
            'name' => trim($combo['name']),
            'price' => $combo['price'] ?: 0,
            'count' => (int)$combo['count'] ?: 0,
            'limit' => 99999,
            'summary' => trim($combo['summay']),
            'photo' => '',
            'start_date' => null,
            'end_date' => null,
            'valid' => 0,
            'ext_id' => $combo['id'],
            'region' => '106092',
            'created_at' =>date('Y-m-d')
        );
        $this->db->insert('ttgy_subscription_combo', $insCombo);
        $combo_id = $this->db->insert_id();
        $this->dbLog();
        $insComboCate = [];
        foreach ($combo['cate'] as $k => $cate) {
            $c = $this->db->select('id')->where(['name' => $cate['name']])->from('ttgy_subscription_cate')->get()->row_array();
            if (empty($c)) {
                $this->db->insert('ttgy_subscription_cate', array(
                    'name' => $cate['name']
                ));
                $this->dbLog();
            }
            $insComboCate[] = [
                'cate_id' => empty($c) ? $this->db->insert_id() : $c['id'],
                'amount' => $cate['count'],
                'combo_id' => $combo_id,
            ];
        }
        $this->db->insert_batch('ttgy_subscription_combo_cate', $insComboCate);
        $this->dbLog();
        $insCombo['id'] = $combo_id;
        return $insCombo;
    }

    private function addAddr($order, $uid)
    {
        $address = $order['address']['addr1'];
        $this->config->load("region");
        if($address['area'] == '崇明县'){
            $province['area_id'] = 145855;
            $city['area_id'] = 145856;
            $area['area_id'] = 145857;
        }else{
            $region = $this->config->item('o2o_region_to_ttgy');
            $province = $region[$address['Province']];
            $city = $province['son'][$address['city']];
            $area = $city['son'][$address['area']];
        }


        $ins = [
            'uid' => $uid,
            'name' => $address['name'],
            'province' => $province['area_id'],
            'city' => $city['area_id'],
            'area' => $area['area_id'],
            'address' => $address['address'],
            'mobile' => $address['tel']
        ];
        $this->db->insert('ttgy_user_address', $ins);
        $ins['id'] = $this->db->insert_id();
        return $ins;
    }

    public function addOrder($order, $combo, $address, $uid)
    {
        $insOrder = array(
            'uid' => $uid,
            'address_id' => $address['id'],
            'status' => $order['status'] == 1 ? 2 : 1,
            'combo_id' => $combo['id'],
            'count' => $combo['count'],
            'valid_count' => $order['valid_count'],
            'week_count' => 2,
            'money' => $combo['price'],
            'time' => $order['time'],
            'type' => 2,
            'ext_uid' => $order['id'],
            'pay_status' => 1,
            'pay_name'=>'账户余额支付',
            'pay_parent_id'=> 5,
            'update_pay_time'=> $order['time'],
            'sync_erp' => 1
        );
        while (true) {
            $rand_code = "";
            for ($i = 0; $i < 4; $i++) {
                $rand_code .= rand(0, 9);
            }
            $order_name = 'G' . date("ymdi") . $rand_code;
            $insOrder['order_name'] = $order_name;
            $this->db->query(str_replace('INSERT INTO', 'INSERT IGNORE INTO', $this->db->insert_string('ttgy_subscription_order', $insOrder)));
            $insOrder['id'] = $this->db->insert_id();
            if ($insOrder['id']) {
                break;
            }
        }
        $this->dbLog();
        $this->db->insert_batch('ttgy_subscription_order_day', [
            ['order_id' => $insOrder['id'],
            'day' => $order['d1']],
            ['order_id' => $insOrder['id'],
                'day' => $order['d2']],
        ]);
        $this->dbLog();
        $this->db->insert('ttgy_subscription_order_address', [
            'order_id' => $insOrder['id'],
            'address' => $address['address'],
            'name' => $address['name'],
            'mobile' => $address['mobile'],
            'province' => $address['province'],
            'city' => $address['city'],
            'area' => $address['area']
        ]);
        $this->dbLog();
        return $insOrder;
    }

    private function addSelected($sku, $order_id, $date)
    {
        $this->load->helper('public');
        $skuID = array_column($sku, 'sku');
        $prods = $this->db->select('p.product_name,pp.product_no,pp.product_id,sp.cate_id,pp.volume,pp.unit')
            ->from('ttgy_product p')
            ->join('ttgy_product_price pp', 'pp.product_id=p.id')
            ->join('ttgy_subscription_product sp', 'sp.product_id=p.id')
            ->where_in('pp.product_no', $skuID)
            ->get()->result_array();
        $prods = array_column($prods, null, 'product_no');
        $ins = [];
        foreach ($sku as $v) {
            if(empty($prods[$v['sku']]['product_no'])){
                echo "\n===sku: {$v['sku']}===\n";
            }
            $ins[] = [
                'date' => $date,
                'order_id' => $order_id,
                'product_name' => $prods[$v['sku']]['product_name'],
                'product_id' => $prods[$v['sku']]['product_id'],
                'product_no' => $prods[$v['sku']]['product_no'],
                'cate_id' => $prods[$v['sku']]['cate_id'],
                'gg_name' => $prods[$v['sku']]['volume'] . '/' . $prods[$v['sku']]['unit'],
                'qty' => $v['qty']
            ];
        }
        $this->db->insert_batch('ttgy_subscription_order_product', $ins);
    }

    /**
     * 自动生成默认菜单
     */
    public function createDefaultSelection(){
        $this->load->model('subscription_model');
        $day = date('N', strtotime('+2 days'));
        $date = date('Y-m-d', strtotime('+2 days'));
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


        foreach ($orders as $key => $order) {
            $dates = $this->subscription_model->orderDates($order);
            if ($date < $dates['firstDate']) {
                unset($orders[$key]);
            }
        }

        if (empty($orders)) {
            return;
        }

        $orders = array_values($orders);
        $this->subscription_model->createDefaultSelection($orders, $date);
    }

    /**
     * 选菜通知
     */
    public function noticeSelection()
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }
        $this->load->helper('public');
        $this->load->model('subscription_model');
        $date = $this->subscription_model->todaySendDate();
        $datetime = strtotime($date);
        $day = date('N', $datetime);

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

        $selected = $this->db->select('order_id')
            ->from('ttgy_subscription_order_product')
            ->where([
                'date' => $date
            ])->group_by('order_id')
            ->get()->result_array();
        $selected = array_column($selected, 'order_id');

        $uids = [];
        foreach ($orders as $key => $order) {
            $dates = $this->subscription_model->orderDates($order);
            if ($date >= $dates['firstDate'] && (!$selected || !in_array($order['id'], $selected)) && !in_array($order['combo_id'], [10, 11, 33, 34])) {
                $uids[] = $order['uid'];
            }
        }

        if (empty($uids)) {
            return;
        }

        $users = $this->db->select('mobile')
            ->from('ttgy_user')
            ->where_in('id', $uids)
            ->get()->result_array();

        $mobiles = array_column($users, 'mobile');
        $msg = "高贵的订菜用户，您" . date('m月d日', $datetime) . "配送的订购订单开始选菜了，新鲜的蔬菜在等着您选哦，赶快去App看看吧！选菜时间：" . date('m-d', strtotime("-3 days", $datetime)) . " 12:00至" . date('m-d', strtotime("-2 days", $datetime)) . " 12:00";
        if (date('Y-m-d') == date('Y-m-d', strtotime("-2 days", $datetime))) {
            $msg = "高贵的订菜用户，您" . date('m月d日', $datetime) . "配送的订购订单还剩3小时就要结束选菜了，想换菜的话要抓紧啦！赶快去App看看吧！";
        }
        $send_params = [
            "mobile" => $mobiles,
            "message" => $msg,
        ];

        $this->load->library("notifyv1");
        $this->notifyv1->send('sms', 'group', $send_params);
    }
}
