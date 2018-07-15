<?php
class Msg_model extends MY_Model {
	public function table_name(){
		return 'msg';
	}


    /*
	 * 新增消息
	 */
    public function addMsg($data)
    {
        $this->db->insert("msg", $data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
	 * 新增－账户升级
	 */
    public function addMsgAccount($uid,$rank,$type)
    {
        $data = array();
        if($type == 1)
        {
            $msg_text = '恭喜!成功升级为Vip'.$rank.',去看看我的新特权吧!';
            $data = array(
                'uid'=>$uid,
                'content'=>$msg_text,
                'class'=>'2',
                'type'=>'5',
                'time'=>date('Y-m-d H:i:s')
            );
        }
        else if($type == 2)
        {
            $msg_text = '很遗憾,由于近一年消费较少,您的会员等级降低到Vip'.$rank;
            $data = array(
                'uid'=>$uid,
                'content'=>$msg_text,
                'class'=>'2',
                'type'=>'6',
                'time'=>date('Y-m-d H:i:s')
            );
        }
        $this->db->insert("msg", $data);
        $id = $this->db->insert_id();
        return $id;
    }

    /**
     * 获取消息详细
     */
    public function get_msgInfo($uid,$class,$type=0,$order_name='')
    {
        $this->db->select('id,uid,content,class,type,order_name,time,last_time');
        $this->db->from('msg');

        $where = array(
            'uid'=>$uid,
            'class'=>$class,
            'type'=>$type,
        );

        if(!empty($order_name))
        {
            $where['order_name'] = $order_name;
        }
        $this->db->where($where);
        $result = $this->db->get()->row_array();
        return $result;
    }


    /**
     * 红点提醒 － 更新
     * @param int $msg_id
     * @return bool
     */
    public function update_redTime($msg_id)
    {
        $data = [
            'last_time' => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', $msg_id);
        return $this->db->update('msg', $data);
    }

    /**
     * 获取消息详细 - 最新时间
     * @param int $uid
     * @param int $class
     *
     * @return array
     */
    public function get_msgTime($uid,$class)
    {
        $this->db->select('id,content,time');
        $this->db->from('msg');

        $where = array(
            'uid'=>$uid,
            'class'=>$class,
        );
        $this->db->where($where);
        $this->db->order_by("time","desc");
        $result = $this->db->get()->row_array();
        return $result;
    }

    /**
     * 更新
     * @param int $msg_id
     * @return bool
     */
    public function update_data($msg_id,$data)
    {
        if(empty($data))
        {
            return false;
        }
        $this->db->where('id', $msg_id);
        return $this->db->update('msg', $data);
    }

}