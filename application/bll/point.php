<?php
namespace bll;

class Point
{
    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->model('point_model');
        $this->ci->load->helper('public');

    }


    /*
     * 获取积分兑换列表
     */
    public function getlist($params)
    {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
            'sort'=>array('required' => array('code' => '500', 'msg' => 'sort can not be null'))
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //$region_id = $params['region_id'];
        $region_id = $this->get_region_id($params['region_id']);
        $k_sort = $params['sort'];
        $list = $this->ci->point_model->get_list();

        //过滤地区
        $gift = array();
        $card = array();

        foreach($list as $key=>$val)
        {
            $arr_reg = explode(',',$val['region']);

            if(!in_array($region_id,$arr_reg))
            {
                unset($list[$key]);
            }
            else
            {
                if($val['type'] == 1) //赠品
                {
                    array_push($gift,$list[$key]);
                }
                else if($val['type'] == 2)   //优惠券
                {
                    array_push($card,$list[$key]);
                }
            }
        }

        if(count($gift) >0)
        {
            $this->ci->load->model('gsend_model');
            $this->ci->load->model('product_model');

            foreach($gift as $k=>$v)
            {
                $gsend = $this->ci->gsend_model->dump(array('tag'=>$v['tag']));
                $result = $this->ci->product_model->get_point_product($gsend['product_id']);

                if(!empty($result['product']))
                {
                    $product['product'] = $result['product'];
                    $product['items'] = $result['items'];
                    $gift[$k]['info'] = $product;
                }
                else
                {
                    return array('code' =>400, 'msg' =>'赠品活动：'.$v['tag'].'已过期');
                }
            }

        }

        if(count($card) >0)
        {
            foreach($card as $k=>$v)
            {
                $card_info = $this->ci->point_model->get_card_info($v['tag']);
                if(date('Y-m-d',time()) <= $card_info['to_date']){
                    $card[$k]['info'] =$card_info;
                }else{
                    unset($card[$k]);
                }
            }
        }

        $arr = array_merge($gift,$card);
        $rs = array();
        foreach($arr as $k=>$v)
        {
            $rs[$v['order']] = $arr[$k];
        }

        if($k_sort == 0)
        {
            ksort($rs,0);
        }
        else{
            krsort($rs,0);
        }

        $res['code'] = 200;
        $res['msg'] = $rs;

        return $res;
    }


    /*
     * 积分兑换
     */
    public function exchange($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'tag'=>array('required' => array('code' => '500', 'msg' => 'tag can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期');
        }

        $uid = $this->ci->login->get_uid();

        //判断兑换次数

        $point = $this->ci->point_model->get_info($params['tag']);
        $pointLimitKey = 'point:change:limit:'. $point['id'];

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $changedCount = $this->redis->hGet($pointLimitKey, $uid);

        if($changedCount >= $point['limit']){
            return array('code' => 407,'msg'=>'超过兑换次数限制,不可兑换');
        }
        if(empty($point))
        {
            return array('code' => 401,'msg'=>'商品已下架');
        }

        //用户信息
        $this->ci->load->bll('user');
        $user_info = $this->ci->bll_user->userInfo(array('connect_id'=>$params['connect_id']));
        if(empty($user_info))
        {
            return array('code' => 402,'msg'=>'用户信息不完整');
        }

        $bonus_point = $point['bonus_point'];
        $jf = $user_info['jf'];

        if($jf - $bonus_point < 0)
        {
            return array('code' => 403,'msg'=>'积分不足');
        }

        $msg ='';
        $rs['code'] = 200;


        $this->ci->db->trans_begin();

        if($point['type'] == 1)  //赠品
        {
            $this->ci->load->model('gsend_model');
            $this->ci->load->model('user_gifts_model');
            $this->ci->load->model('product_model');

            $gsend = $this->ci->gsend_model->dump(array('tag'=>$point['tag']));
            if(empty($gsend))
            {
                return array('code' => 404,'msg'=>'活动已过期');
            }

            $result = $this->ci->product_model->get_point_product($gsend['product_id']);
            if(empty($result['product']))
            {
                return array('code' => 405,'msg'=>'商品已过期');
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
                'uid'=>$user_info['id'],
                'active_id'=>$gsend['id'],
                'active_type'=>2,
                'has_rec'=>0,
                'start_time'=>$gift_start_time,
                'end_time'=>$gift_end_time,
            );
            $this->ci->user_gifts_model->insert($user_gift);

            $rs['msg'] = $result['product'];
            $rs['msg']['point_type'] = $point['type'];
            $msg = '积分兑换赠品:'.$result['product']['product_name'].'，消费积分：'.$bonus_point.'分';
        }
        else if($point['type'] == 2)  //优惠券
        {
            $mobilecard_info = $this->ci->point_model->get_card_info($point['tag']);

            if(empty($mobilecard_info) || date('Y-m-d',time()) > $mobilecard_info['to_date'])
            {
                return array('code' => 406,'msg'=>'优惠券已过期');
            }

            $card_st = 'jfpt';
            $card_number = $card_st.$this->rand_card_number($card_st);

            $card_data = array(
                'uid' => $user_info['id'],
                'sendtime' => date("Y-m-d", time()),
                'card_number' => $card_number,
                'card_money' => $mobilecard_info['card_money'],
                'product_id' => $mobilecard_info['product_id'],
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => $mobilecard_info['restr_good'],
                'remarks' => $mobilecard_info['card_desc'],
                'time' => date("Y-m-d"),
                'to_date' => $mobilecard_info['to_date'] ,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => $mobilecard_info['can_sales_more_times'],
                'card_discount' => 1,
                'order_money_limit' => $mobilecard_info['order_money_limit'],
                'channel' => $mobilecard_info['channel'],
                'direction' => $mobilecard_info['direction'],
            );

            $this->ci->load->model('card_model');
            $this->ci->card_model->insert($card_data);

            $rs['msg'] = $mobilecard_info;
            $rs['msg']['point_type'] = $point['type'];
            $msg = '积分兑换优惠券:'.$mobilecard_info['remarks'].'，消费积分：'.$bonus_point.'分';
        }

        //扣除积分
        $jf = array(
            'jf' => '-'.$bonus_point,
            'reason' => $msg,
            'time' => date("Y-m-d H:i:s"),
            'uid' => $user_info['id'],
            'type'=>'积分兑换'
        );
        $this->ci->load->model('user_jf_model');
        $this->ci->load->model('user_model');
        $this->ci->user_jf_model->insert($jf);

        //处理并发
        $user_count_jf = $this->ci->user_model->getUserScoreNew($user_info['id']);
        if($user_count_jf['jf'] < 0)
        {
            $this->ci->db->trans_rollback();
            return array("code"=>"300","msg"=>"积分不足");
        }

        $this->ci->user_model->updateJf($user_info['id'],$bonus_point,2);
        if ($this->ci->db->trans_status() === FALSE){
            $this->ci->db->trans_rollback();
            return array("code"=>"300","msg"=>"积分兑换失败，请重新兑换");
        }else{
            $this->redis->hSet($pointLimitKey, $uid, intval($changedCount)+1);
            $this->ci->db->trans_commit();
        }

        return $rs;
    }

    /*
     * 生成优惠券卡号
     */
    private function rand_card_number($p_card_number = '') {
        $tname = '';
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        for ($i = 1; $i <= 10; $i++) {
            $tname.=$a_array[rand(0, 31)];
        }
        if ($this->ci->point_model->checkCardNum($p_card_number . $tname)) {
            $tname = $this->rand_card_number($p_card_number);
        }
        return $tname;
    }

    /*
	*获取地区标识
	*/
    private function get_region_id($region_id=106092){
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if(isset($site_list[$region_id])){
            $region_result = $site_list[$region_id];
        }else{
            $region_result = 106092;
        }
        return $region_result;
    }


}
