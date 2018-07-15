<?php
class Active_wind_model extends MY_Model {
	public function table_name(){
		return 'active_wind';
	}

    /**
     * 获取活动配置
     */
    public function get_list()
    {
        $now_data = date("Y-m-d H:i:s");
        $this->db->select('id,active_code,product_id');
        $this->db->from('active_wind');
        $this->db->where(array('start_time <='=>$now_data,'end_time >='=>$now_data));
        $result = $this->db->get()->result_array();
        return $result;
    }
}