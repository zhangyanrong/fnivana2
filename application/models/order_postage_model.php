<?php

class Order_postage_model extends MY_Model
{
    public function table_name()
    {
        return 'order_postage';
    }

    /**
     * 获取用户特权信息
     */
    public function getUserPostagePrivilegeInfo($uid, $now,$need = 0)
    {
        $this->db->select("op.id,op.uid, op.start_time, op.end_time, op.available_times, op.times, u.mobile, op.exchange_type")
                 ->from("order_postage op")
                 ->join("user u", "op.uid = u.id", "left")
                 ->where([
                     "op.uid" => $uid,
                     "op.available_times >" => 0,
                     "op.start_time <=" => $now,
                     "op.end_time >=" => $now,
                     "op.postage_status" => 1,
                 ]);
        $result = $this->db->get()->row_array();

        if($need == 1)
        {
            $log = $this->getPostageLog($uid);
            if(count($log) >= 3)
            {
                $result = array();
            }
        }

        return $result ?: [];
    }


    /*
    * 获取用户使用邮费特权日志
    */
    function getPostageLog($uid){
        $this->db->select('*');
        $this->db->from('postage_log');
        $this->db->where([
            "uid" => $uid,
            "time >=" =>date('Y-m-d'),
            "time <=" =>date("Y-m-d", strtotime('+1 day')),
        ]);
        $this->db->like('remark','已使用邮费特权');

        $info = $this->db->get()->result_array();
        return $info;
    }

    /*
    *查看订单信息根据ID
    */
    function getInfoById($id){
        $this->db->select('*');
        $this->db->from('order_postage');
        $this->db->where('id',$id);
        $order_info = $this->db->get()->row_array();
        return $order_info;
    }

    /*
     * 变更支付方式
     */
    function set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$order_id){
        $sql = "update ttgy_order_postage set pay_name='".$pay_name."',pay_parent_id=".$pay_parent_id.",pay_id='".$pay_id."',version=version+1 where id=".$order_id;
        $this->db->query($sql);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /*
    * 更新数据
    */
    function update_postage($data,$where){
        $this->_filter($where);
        $this->db->update('order_postage', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    public function returnPostage($id,$times = 1){
        $sql = "update ttgy_order_postage set available_times=available_times+".$times." where id=".$id;
        return $this->db->query($sql);
    }

    public function reducePostage($id,$times = 1){
        $postage = $this->dump(array('id'=>$id));
        if(empty($postage) || $postage['available_times'] == 0 || $postage['available_times'] < $times){
            return false;
        }
        $sql = "update ttgy_order_postage set available_times=available_times-".$times." where id=".$id;
        return $this->db->query($sql);
    }
}
