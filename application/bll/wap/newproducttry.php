<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/1
 * Time: 下午5:01
 */

namespace bll\wap;
include_once("wap.php");

class Newproducttry extends wap {
    function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('new_product_try_model');
        $this->ci->load->helper('public');
    }

    /*
 * 获取积分兑换列表
 */
    public function getlist($params) {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
            'user_rank' => array('required' => array('code' => '500', 'msg' => 'user rank can not be null')),
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null'))
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //$region_id = $params['region_id'];
        $region_id = $this->get_region_id($params['region_id']);
        $list = $this->ci->new_product_try_model->get_list($params['user_rank']);
        //过滤地区
        $gift = array();
        foreach ($list as $key => $val) {
            $arr_reg = explode(',', $val['region']);

            if (!in_array($region_id, $arr_reg)) {
                unset($list[$key]);
            } else {
                array_push($gift, $list[$key]);
            }
        }

        if (count($gift) > 0) {
            $picPrefix = constant(CDN_URL.rand(1, 9));
            $this->ci->load->model('gsend_model');
            $this->ci->load->model('product_model');
            foreach ($gift as $k => $v) {
                $gsend = $this->ci->gsend_model->dump(array('tag' => $v['tag']));
                $result = $this->ci->product_model->get_point_product($gsend['product_id']);
                $gift[$k]['picture'] = $picPrefix . $gift[$k]['picture'];
                if (!empty($result['product'])) {
                    $product['product'] = $result['product'];
                    $product['items'] = $result['items'];
                    $gift[$k]['info'] = $product;

                    $this->ci->load->library('phpredis');
                    $this->redis = $this->ci->phpredis->getConn();
                    $key = 'newProductTry:' . $v['id'];
                    $this->ci->load->library('login');
                    $this->ci->login->init($params['connect_id']);
                    $uid = $this->ci->login->get_uid();
                    $checkDraw = $this->redis->sIsMember($key, $uid);
                    if ($checkDraw != false) {
                        $gift[$k]['hasDraw'] = 1;
                    } else {
                        $gift[$k]['hasDraw'] = 0;
                    }
                } else {
                    return array('code' => 400, 'msg' => '赠品活动：' . $v['tag'] . '已过期');
                }
            }
        }

        $rs = array();
        foreach ($gift as $k => $v) {
            $rs[$v['order']] = $gift[$k];
        }

        $res['code'] = 200;
        $res['msg'] = $rs;
        return $res;
    }


    /*
     * 生成优惠券卡号
     */
    private function rand_card_number($p_card_number = '') {
        $tname = '';
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        for ($i = 1; $i <= 10; $i++) {
            $tname .= $a_array[rand(0, 31)];
        }
        if ($this->ci->point_canteen_model->checkCardNum($p_card_number . $tname)) {
            $tname = $this->rand_card_number($p_card_number);
        }
        return $tname;
    }

    /*
	*获取地区标识
	*/
    private function get_region_id($region_id = 106092) {
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = 106092;
        }
        return $region_result;
    }


    public function get($params) {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'id' => array('required' => array('code' => '500', 'msg' => 'id can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期');
        }


        $point = $this->ci->new_product_try_model->get_info($params['id']);

        if (empty($point)) {
            return array('code' => 401, 'msg' => '商品已下架');
        }

        //用户信息
        $this->ci->load->bll('user');
        $user_info = $this->ci->bll_user->userInfo(array('connect_id' => $params['connect_id']));
        if (empty($user_info)) {
            return array('code' => 402, 'msg' => '用户信息不完整');
        }

        $user_info['user_rank'] = ($user_info['user_rank'] <= 0) ? 0 : ($user_info['user_rank'] - 1);


        if ($user_info['user_rank'] != $point['user_rank']) {
            return array('code' => 403, 'msg' => '等级不匹配');
        }

        $now = time();
        $begin = strtotime($point['begin_time']);
        $end = strtotime($point['end_time']);
        if ($now < $begin || $now > $end) {
            return array('code' => 407, 'msg' => '活动已过期');
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $key = 'newProductTry:' . $point['id'];
        $checkDraw = $this->redis->sIsMember($key, $uid);
        if ($checkDraw != false) {
            return array('code' => 406, 'msg' => '不可重复领取');
        }

        $rs['code'] = 200;

        $this->ci->db->trans_begin();

        $this->ci->load->model('gsend_model');
        $this->ci->load->model('user_gifts_model');
        $this->ci->load->model('product_model');

        $gsend = $this->ci->gsend_model->dump(array('tag' => $point['tag']));
        if (empty($gsend)) {
            return array('code' => 404, 'msg' => '活动已过期');
        }

        $result = $this->ci->product_model->get_point_product($gsend['product_id']);
        if (empty($result['product'])) {
            return array('code' => 405, 'msg' => '商品已过期');
        }
        $gift_send = $gsend;
        if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
            $gift_start_time = date('Y-m-d');
            $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
        }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
            $gift_start_time = $gift_send['gift_start_time'];
            $gift_end_time = $gift_send['gift_end_time'];
        }else{
            $gift_start_time = $gift_send['start'];
            $gift_end_time = $gift_send['end'];
        }
        $user_gift = array(
            'uid' => $user_info['id'],
            'active_id' => $gsend['id'],
            'active_type' => 2,
            'has_rec' => 0,
            'start_time'=>$gift_start_time,
            'end_time'=>$gift_end_time,
        );
        $this->ci->user_gifts_model->insert($user_gift);

        $rs['msg'] = $result['product'];
        $rs['msg']['point_type'] = $point['type'];

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "积分兑换失败，请重新兑换");
        } else {
            $this->redis->sAdd($key, $uid);
            if ($this->redis->ttl($key) == -1) {
                $expire = strtotime($point['end_time']);
                $this->redis->expireAt($key, $expire);
            }
            $this->ci->db->trans_commit();
        }

        return $rs;
    }
}