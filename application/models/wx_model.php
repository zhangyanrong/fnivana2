<?php
class Wx_model extends MY_Model {
    function __construct() {
        parent::__construct();
        $this->load->library("session");
    }

    /*
     * 回传官网id等信息
     */
    function set_wx_user_info($openid,$params){
        $this->db->select('id,uid');
        $this->db->from('wx_user_info');
        $this->db->where('openid',$openid);
        $user = $this->db->get()->row_array();
        $data = array(
            'nickname'=>$params['nickname'],
            'sex'=>$params['sex'],
            'city'=>$params['city'],
            'country'=>$params['country'],
            'province'=>$params['province'],
            'headimgurl'=>$params['headimgurl'],
            'subscribe_time'=>$params['subscribe_time'],
            'unionid'=>$params['unionid'],
            'remark'=>$params['remark'],
            'groupid'=>$params['groupid'],
            'bind_time'=>time()
        );
        if(!empty($user)){
//            if($user['uid']!=0){
//                return array('code'=>300,'msg'=>'您已经绑定过啦！');
//            }
            $this->db->update('wx_user_info',$data,array('openid'=>$openid));
            $uinfo =$this->db->select("reg_time,mobile")->from("user")->where(array('id'=>$user['uid']))->get()->row_array();
            $user_info = array(
                'user_id'=>$user['uid'],
                'reg_time'=>$uinfo['reg_time'],
                'mobile'=>$uinfo['mobile'],
//                'bind_time'=>$uinfo['bind_time']
            );
            return array('code'=>200,'user_info'=>$user_info);
        }else{
            return array('code'=>300,'msg'=>'尚未绑定openid！');
//            $data['openid'] = $openid;
//            $this->db->insert('wx_user_info',$data);
        }
    }

    /*
     * 绑定微信号
     */
    function bind($openid,$uid){
        $is_bind = $this->db->select('id')->from('wx_user_info')->where(array(
            'uid'=>$uid
        ))->get()->row_array();
        if(!empty($is_bind)){
            return array('code'=>300,'msg'=>'您已经绑定过啦！');
        }
        $info = $this->db->select('uid')->from('wx_user_info')->where(array(
            'openid'=>$openid
        ))->get()->row_array();
        if(empty($info)){
            $data = array(
                'openid'=>$openid,
                'uid'=>$uid
            );
            $this->db->insert('wx_user_info',$data);
        }else{
            if($info['uid']==0){
                $this->db->update('wx_user_info',array('uid'=>$uid),array('openid'=>$openid));
            }else{
                return array('code'=>300,'msg'=>'您已经绑定过啦！');
            }
        }
    }

    /*
     * 解绑微信号
     */
    function unbind($openid){
        $this->db->where('openid', $openid);
        $result = $this->db->delete('wx_user_info');
        return $result;
    }

    /*
     * 获取官网id
     */
    function getUidByOpenid($oepnid){
        $user_info = $this->db->select('uid')->from('wx_user_info')->where(array(
            'openid'=>$oepnid
        ))->get()->row_array();
        return $user_info;
    }

    /*
     * 查询最后一张有物流信息的订单号
     */
    function getLastSendOrderId($uid){
        $sql = "select o.id,o.order_name,ad.address,ad.name from ttgy_order o left join ttgy_order_address ad on o.id=ad.order_id where o.uid='{$uid}' and o.operation_id not in (0,1,5) and o.order_type = 1 order by id desc limit 1";
        $result = $this->db->query($sql)->row_array();
        return $result;
    }

    /*
     * 获取订单商品信息
     */
    function getOrderProInfo($order_id){
        $sql = "select product_name from ttgy_order_product where order_id='{$order_id}'";
        $result = $this->db->query($sql)->result_array();
        return array('product_name'=>$result[0]['product_name'],'product_num'=>count($result));
    }

    /*
     * 获取可用优惠券信息数量
     */
    function getAvailableCardNum($uid){
        $now_date = date('Y-m-d');
        $sql  ="select count(*) as num from ttgy_card where uid = {$uid} and is_used = 0 and is_sent = 1 and to_date >='{$now_date}'";
        $result = $this->db->query($sql)->row_array();
        return $result['num'];
    }

}