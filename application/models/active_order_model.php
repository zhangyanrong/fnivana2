<?php
class Active_order_model extends MY_Model {
	public function table_name(){
		return 'active_order';
	}

    /**
     *添加活动标记
     */
    public function add($data)
    {
        $res ='';
        if(!empty($data))
        {
            $res = $this->db->insert("active_order",$data);
        }
        return $res;
    }
}