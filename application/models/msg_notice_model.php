<?php
class Msg_notice_model extends MY_Model {
	public function table_name(){
		return 'msg_notice';
	}

    /*
	 * 新增提醒
	 */
    public function addRedTime($uid,$type)
    {
        if($type == 1)
        {
            //交易消息
            $data = [
                'last_order_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 2)
        {
            //账户消息
            $data = [
                'last_user_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 3)
        {
            //评论与赞
            $data = [
                'last_comment_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 4)
        {
            //果园客服
            $data = [
                'last_custom_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 5)
        {
            //物流助手
            $data = [
                'last_trace_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 6)
        {
            //果园公告
            $data = [
                'last_notice_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 7)
        {
            //优惠促销
            $data = [
                'last_cart_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }
        else if($type == 8)
        {
            //订阅消息
            $data = [
                'last_subscribe_time' => date('Y-m-d H:i:s'),
                'uid'=>$uid
            ];
        }

        $this->db->insert("msg_notice", $data);
        $id = $this->db->insert_id();
        return $id;
    }

    /**
     * 红点提醒 － 更新
     * @param int $uid
     * @param int $type
     * @return bool
     */
    public function updateRedTime($uid,$type)
    {
        if($type == 1)
        {
            //交易消息
            $data = [
                'last_order_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 2)
        {
            //账户消息
            $data = [
                'last_user_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 3)
        {
            //评论与赞
            $data = [
                'last_comment_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 4)
        {
            //果园客服
            $data = [
                'last_custom_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 5)
        {
            //物流助手
            $data = [
                'last_trace_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 6)
        {
            //果园公告
            $data = [
                'last_notice_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 7)
        {
            //优惠促销
            $data = [
                'last_cart_time' => date('Y-m-d H:i:s'),
            ];
        }
        else if($type == 8)
        {
            //订阅消息
            $data = [
                'last_subscribe_time' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->where('uid', $uid);
        return $this->db->update('msg_notice', $data);
    }

}