<?php
namespace bll;

class Invite
{
    var $denomation = 20;
    var $points = 1000;

    public function __construct()
    {
        $this->ci = &get_instance();
    }


    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function send_ecoupon($uid,$order_name)
    {
        $insert_point = $this->points;

        $allow = $this->check_invite_user($uid,$user);

        if (!$allow) return false;

        $allow = $this->check_invite_region($order_name);
        if ($allow) $insert_point = 2000;

        $reason = '邀请好友'. $user['username'] . "，成功完成订单赠送" . $insert_point . '积分';

        $this->ci->load->bll('order');
        $point_id = $this->ci->bll_order->grant_score($user['invited_by'],$insert_point,$reason,'邀请好友'); 

        if ($point_id) {
            $invite = array(
                'user_id' => $uid,
                'invited_by' => $user['invited_by'],
                'order_name' => $order_name,
                'ecoupon_code' => $point_id,
            );
            $this->ci->db->insert('user_invite',$invite);
        }

        $invite_user = $this->ci->db->select('mobile')->from('user')->where('id',$user['invited_by'])->get()->row_array();
        if ($invite_user['mobile']) {
            $defaultContent = sprintf("推荐有礼成功。您邀请的用户%s已成功注册并完成首笔订单，现赠送您%s积分。详情请登录 www.fruitday.com 查看。", $user['username'], $insert_point);

            $this->ci->load->model('sms_template');
            $sms_template = $this->ci->sms_template;
            $smsContent = $this->ci->sms_template->getSmsTemplate($sms_template::_SMS_RECOMMEND_TO_ORDERCREATE,array('member_name'=>$user['username'],'score'=>$insert_point));

            $sms = $smsContent ? $smsContent : $defaultContent;
            
            $this->ci->load->model("jobs_model");

            $job = array(
                'mobile' => $invite_user['mobile'],
                'text' => $sms,
            );
            $this->ci->jobs_model->add($job,"sms");
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function check_invite_user($uid,&$user)
    {
        $user = $this->ci->db->select('invited_by, username, mobile')
                            ->from('user')
                            ->where(array('id'=>$uid))
                            ->get()
                            ->row_array();
        if (!$user['invited_by']) return false;

        $invite = $this->ci->db->select('*')
                                ->from('user_invite')
                                ->where(array('user_id' => $uid))
                                ->get()
                                ->row_array();

        return $invite ? false : true;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function check_invite_region($order_name)
    {
        $result = $this->ci->db->select('id')->from('order')->where('order_name',$order_name)->get()->row_array();
        if(empty($result)){
            return false;
        }

        $result = $this->ci->db->select('position')->from('order_address')->where('order_id',$result['id'])->get()->row_array();
        if(empty($result)){
            return false;
        }

        if(strstr($result['position'], '四川') || strstr($result['position'], '重庆') || strstr($result['position'], '云南') || strstr($result['position'], '贵州') || strstr($result['position'], '青海') || strstr($result['position'], '湖北')){
            return true;
        }else{
            return false;
        }
    }
}