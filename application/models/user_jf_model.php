<?php
class User_jf_model extends MY_Model {
    private $jf_type = array('电话系统操作','OMS操作','订单完成','取消订单返还','推荐新客','消费','新用户注册','充值赠送','摇一摇','签到','评论商品','活动','邀请好友','企业回扣','电子发票','问卷调查','积分兑换');

    public function table_name(){
        return 'user_jf';
    }

    function get_user_last_jf($uid){
        if(empty($uid)) return ;
        $this->db->select('max(time) as maxtime');
        $this->db->from("user_jf");
        $this->db->where(array("uid"=>$uid));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result['maxtime'];
    }

}