<?php
class Snscenter_model extends MY_Model {

	public function getMaxComments($uid, $limits){
		$page_size = $limits['page_size'];
		$page_pos = ($limits['curr_page']-1)*$limits['page_size'];
		$sql = "select id,ctime,aid,1 type,content,remark,pid from ttgy_fruit_comments where state=1 and uid=$uid  
			union 
			select id,ctime,aid,2 type,content,remark,pid from ttgy_bake_comments where state=1 and uid=$uid
			order by ctime desc limit $page_pos,$page_size";
		$res = $this->db->query($sql)->result_array();
		return $res;
	}
}