<?php
namespace bll;
/**
 * 礼品券
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: gcoupon.php 1 2015-01-27 16:47:26Z pax $
 * @link      http://www.fruitday.com
 **/
class Gcoupon
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

 
    public function check_gift_coupon($giftcard,&$msg,$exchange=false)
    {

        if (!$giftcard) {
            $msg = '礼品码不存在';
            return false;
        }

        if ($giftcard['is_used'] != '0') {
            $msg = '礼品码已被兑换';
            return false;
        }

        $channel = @unserialize($giftcard['channel']);

        if ($channel) {
            $this->ci->load->library('terminal');
            if (($this->ci->terminal->is_web() && !in_array(1,$channel)) ||
                ($this->ci->terminal->is_app() && !in_array(2,$channel)) || 
                ($this->ci->terminal->is_wap() && !in_array(3,$channel))
                ) {
                $name = array();
                foreach ($channel as $key => $value) {
                    switch ($value) {
                        case 1:
                            $name[] = '官网';
                            break;
                        case 2:
                            $name[] = 'APP';
                            break;
                        case 3:
                            $name[] = 'WAP';
                            break;
                    }
                }
                $msg = '该卡仅限' . implode(',',$name) . '使用';
                return false;
            }
        }

        $join = $this->ci->gcoupon_model->dump(array('gift_send_id'=>$giftcard['gift_send_id'],'uid'=>$giftcard['uid'],'is_used'=>1));

        if ($join) {
            $msg = '您已参与过了，该活动每个帐号限参与一次';
            return false;
        }

        // 赠品活动
        // $this->ci->load->model('user_gifts_model');
        // $giftsend = $this->ci->user_gifts_model->get_gift_send($giftcard['gift_send_id']);
        $this->ci->load->model('gsend_model');
        $giftsend = $this->ci->gsend_model->dump(array('id'=>$giftcard['gift_send_id']));
        if (!$giftsend) {
            $msg = '兑换活动不存在';
            return false;
        }

        if($exchange === true){
            if (strtotime($giftsend['start']) > time() ) {
                $msg = '兑换活动还未开始';
                return false;
            }

            if (strtotime($giftsend['end']) < time()) {
                $msg = '兑换活动已结束';
                return false;
            }
        }
        
        // 判断是否已经被处理
        $this->ci->load->model('user_gifts_model');
        $user_gifts = $this->ci->user_gifts_model->dump(array('uid'=>$giftcard['uid'],'active_id'=>$giftsend['id']));
        if ($user_gifts['has_rec'] !=0 ) {
            $msg = '礼品已被兑换';
            return false;
        }

        $giftsend['user_gifts'] = $user_gifts;

        return $giftsend;
    }

    /**
     * 获取礼品券礼品
     *
     * @return void
     * @author 
     **/
    public function giftsGet($card_number,$uid,&$msg)
    {
        $this->ci->load->model('gcoupon_model');
        $giftcoupon = $this->ci->gcoupon_model->dump(array('card_number'=>$card_number));
        if (!$giftcoupon) {
            $msg = '礼品码不存在';
            return false;
        }
        $giftcoupon['uid'] = $uid;
        $giftsend = $this->check_gift_coupon($giftcoupon,$msg,true);
        if ($giftsend === false) {
            return false;
        }

        $this->ci->load->bll('gsend');
        $gifts = $this->ci->bll_gsend->format_gifts($giftsend);
        if (!$gifts['products']) {
            $msg = '赠品已兑换完';
            return false;
        }
        $gifts['giftsend'] = $giftsend;
        $gifts['giftcoupon'] = $giftcoupon;

        // 礼品券置状态
        $affected_row = $this->ci->gcoupon_model->update(array('is_used'=>1,'uid'=>$uid),array('id'=>$giftcoupon['id']));
        if (!$affected_row) {
            $msg = "兑换失败";
            return false;
        }

        // 赠品标记主人
        $this->ci->load->model('user_gifts_model');
        $usergift = $giftsend['user_gifts'];
        $usergift['uid'] = $uid;
        unset($usergift['id']);

        $insert_data['uid'] = $uid;
        $insert_data['active_id'] = $giftsend['id'];
        $insert_data['active_type'] = 2;

        $gift_send = $giftsend;
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
        $insert_data['start_time'] = $gift_start_time;
        $insert_data['end_time'] = $gift_end_time;
        $insert_id = $this->ci->user_gifts_model->insert($insert_data);
        if (!$insert_id) {
            $msg = "兑换失败";
            return false;
        }

        return $gifts;
    }


}