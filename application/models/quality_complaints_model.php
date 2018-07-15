<?php
/**
 * 申诉
 *
 **/
class Quality_complaints_model extends MY_Model {
	/**
	* 申诉表
	*
	* @var string
	**/
	const _TABLE_NAME = 'quality_complaints';

	public function table_name()
	{
		return self::_TABLE_NAME;
	}

	public function __construct(){
		parent::__construct();
		$this->load->helper("public");
	}

	/**
	*获取商品评论
	*/
	function selectQualitys($field,$where='',$where_in=''){
		$this->db->select($field);
		$this->db->from('quality_complaints');
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			foreach($where_in as $val){
				$this->db->where_in($val['key'],$val['value']);
			}
		}
		$result = $this->db->get()->result_array();
		return $result;
	}

	function insQualitys($data){
		if(empty($data)){
			return false;
		}
		$this->db->insert('quality_complaints', $data);
        		$insert_id = $this->db->insert_id();
        		return $insert_id;
	}
}