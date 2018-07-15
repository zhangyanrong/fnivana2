<?php
namespace bll;

class Open
{
  private $_error = '';

  private $_code = 300;
  private $redis;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('opencard');
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
    }

    public function get_error()
    {
      return $this->_error;
    }

    public function get_code()
    {
      return $this->_code;
    }

    public function getMobile($params){
        $mobile = $params['phone'];
//        $active_tag = 'juice150723';
        $active_tag = 'juice'.date('Ymd');
        if (preg_match('/^1\d{10}$/', $mobile)) {
            $return_result = $this->ci->opencard->getMobileCard($mobile,$active_tag);
            return $return_result;
        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }
    
    //滴滴打车橙先生10元领券活动
    public function getOrangeCard($params)
    {
//        echo 11;exit;
        $mobile = $params['phone'];
        $active_tag = '20151207滴滴10元优惠券';
        if (preg_match('/^1\d{10}$/', $mobile)) {
            $return_result = $this->ci->opencard->getOrangeMobile($mobile,$active_tag);
            return $return_result;
        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }

    function getMsgifts($params){
        $key = 'ms_gift_count';//定义ms银行礼物的key值
        $mobile = trim($params['phone']);//对方传过来的手机号
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

        $this->ci->db->trans_begin();
        $r = $this->ci->opencard->is_send($mobile,'ms_bank161201');
        $gift_num = $this->redis->get($key);
        if($gift_num>=10000){
            return array('code'=>300,'msg'=>'果园大礼包已经领完啦.');
        }

        if(!$r){
            return array('code'=>300,'msg'=>'你已经领取过了');
        }else{
            $uid = $this->ci->opencard->get_uid_by_mobile($mobile);

            $card_info = $this->ci->opencard->get_cardInfo_by_tag(array('9p7n5f7','8b8d3r8','9j2b8f8','5i2p4c6','4b8p2h3','7z9j2a7'));
            $gift_info = $this->ci->opencard->get_gift_send_info("AMYp24");

            if(!empty($card_info)){
                $share_p_card_number = 'msBank';
                $today = date("Y-m-d");
                foreach($card_info as $v){
                    for($i=0;$i<=1;$i++){
                        $share_card_number = $share_p_card_number.$this->ci->opencard->rand_card_number($share_p_card_number);
                        $card_data = array(
                            'uid'=>$uid?$uid:'',
                            'sendtime'=>$today,
                            'card_number'=>$share_card_number,
                            'card_money'=>$v['card_money'],
                            'product_id'=>$v['product_id'], //不能为0 注意看下
                            'maketing'=>'0',
                            'is_sent'=>$uid?1:'',
                            'restr_good'=>$v['product_id']?1:0,
                            'remarks'=>$v['card_desc'],
                            'time'=>$today,
                            'to_date'=>$v['card_to_date'],//券的有效期
                            'can_use_onemore_time'=>'false',
                            'can_sales_more_times'=>'false',
                            'card_discount'=>1,
                            'order_money_limit'=>$v['order_money_limit'],
                            'return_jf'=>'',
                            'black_user_list'=>'',
                            'channel'=>$v['channel']?$v['channel']:'',
                            'direction' => $v['direction']
                        );

                        $card_arr[] = $card_data;
                        $active_data[] = array(
                            'mobile'=>$mobile,
                            'card_number'=>$share_card_number,
                            'active_tag'=>$share_p_card_number,
                            'active_type'=>1
                        );

                        $card_type_arr[] = array(
                            'card_number' => $share_card_number,
                            'type' => $v['card_m_type'],
                            'op_id' => 0,
                            'tag' => $v['card_tag'],
                            'department' => $v['department'],
                        );
                    }
                }
            }else{
                return array('code'=>300,'msg'=>'优惠券信息配置有误.');
            }

            if(!empty($gift_info)){
                $gift_info = $this->ci->opencard->initGiftData($gift_info,$uid);
                $active_data_gift = array(
                    'mobile' => $mobile,
                    'card_number' => '',
                    'active_tag' => $share_p_card_number,
                    'active_type' => 2
                );
//                for($i=0;$i<=1;$i++){
                    $this->ci->opencard->send_gift($gift_info,$active_data_gift);
//                }
            }else{
                return array('code'=>300,'msg'=>'赠品信息配置有误.');
            }

            $this->ci->opencard->addCard_batch($card_arr,$active_data,$card_type_arr);
        }
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            $return_result = array("result" => "300", "msg" => "服务器开小差啦，请稍后再试!");
            return ($return_result);
        } else {
            $this->ci->db->trans_commit();
            //更新
            $this->redis->incr($key);
            $this->redis->expire($key,2592000);
            return (array('result'=>'200','msg'=>'领取成功!'));
        }
    }

}
