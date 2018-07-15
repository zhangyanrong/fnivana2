<?php
class Order_fanli_model extends MY_Model
{
	public function table_name()
    {
		return 'order_fanli';
	}

	public function insFanliOrder($order_id,$userdata){
		$data = array();
		if (!empty($order_id)){
			$data['order_id'] = $order_id;
		}
		if (!empty($userdata['fl_channel_id'])){
			$data['channel_id'] = $userdata['fl_channel_id'];
		}
		if (!empty($userdata['fl_u_id'])) {
            $data['u_id'] = $userdata['fl_u_id'];
        }
        if (!empty($userdata['fl_tracking_code'])) {
            $data['tracking_code'] = $userdata['fl_tracking_code'];
        }
        if (4 != count($data)) {
            return false;
        }

        if ($userdata['source'] == 'pc') {
            $data['platform'] = 1;
        } elseif ($userdata['source'] == 'wap') {
            $data['platform'] = 2;
        }
		$data['status'] = 1;
		$this->db->insert('order_fanli',$data);
	}
}
