<?php
class Bake_tag_model extends MY_Model {

	public function table_name(){
		return 'bake_tag';
	}

	public function getTagList(){
		$where['is_show'] = 1;//显示
		$field = "name";
		$orderby = "sort desc, id desc";
		
		$result = $this->getList($field, $where, 0, -1, $orderby);
		return $result;
	}
}