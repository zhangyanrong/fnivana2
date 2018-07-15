<?php
/**
 * 评论回复
 *
 **/
class Comment_reply_model extends MY_Model {
	/**
	* 评论回复表
	*
	* @var string
	**/
	const _TABLE_NAME = 'comment_reply';

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
	function selectCommentReply($field,$where='',$where_in='',$order='',$limit=''){
		$this->db->select($field);
		$this->db->from('comment_reply');
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'],$where_in['value']);
		}
		if(!empty($order)){
			$this->db->order_by($order);
		}
		if(!empty($limit)){
			$this->db->limit($limit['page_size'],($limit['curr_page']*$limit['page_size']));
		}
		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * [commentReply 获取各个评论的最新一条回复]
	 * @param  [array] $comment_ids [description]
	 * @return [array]              [description]
	 */
	function commentReply($comment_ids){
		if(empty($comment_ids)){
			return array();
		}
		$field = "content,time,comment_id";
		$where_in = array('key'=>'comment_id','value'=>$comment_ids);
		$result_array = $this->selectCommentReply($field,'',$where_in,'','');

		$result = array();
		if(!empty($result_array)){
			foreach ($result_array as $key => $val) {
				if(isset($result[$val['comment_id']])){
					if($result[$val['comment_id']]['time'] < $val['time']){
						$result[$val['comment_id']] = $val;
					}
				}else{
					$result[$val['comment_id']] = $val;
				}
			}
		}
		return $result;
	}
}