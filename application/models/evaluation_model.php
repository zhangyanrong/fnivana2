<?php
class Evaluation_model extends MY_Model {
	public function table_name(){
		return 'evaluation';
	}

    /**
     * 获取物流评价
     */
    public function get_info($uid,$orderid)
    {
        $this->db->select('id,type,uid,order_id,score,remark,ctime');
        $this->db->from('evaluation');
        $this->db->where(array('type'=>1,'uid'=>$uid,'order_id'=>$orderid));
        $result = $this->db->get()->row_array();
        return $result;
    }


    public function get_push_evaluations($order_name=''){
        $this->db->select('id,order_id,remark,score_time,score_service,score_show,FROM_UNIXTIME(ctime) as time');
        $this->db->from('evaluation');
        if($order_name){
            $this->db->where('order_id',$order_name);
        }else{
            $this->db->where('ctime >= ','1475251200');//2016-10-01
        }
        $this->db->where('sync_status',0);
        $this->db->limit(200);
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function set_sync($ids,$sync_status=1){
        $this->update(array('sync_status'=>$sync_status),array('id'=>$ids));
    }
}