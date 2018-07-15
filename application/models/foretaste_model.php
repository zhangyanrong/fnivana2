<?php
class Foretaste_model extends MY_Model {
	public function table_name(){
		return 'foretaste_goods';
	}

	public function selectForetaste($field, $where, $limits='', $order='', $where_in=''){
		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'], $where_in['value']);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'], $limits['offset']);
		}
		if(!empty($order)){
			$this->db->order_by($order['key'], $order['value']);
		}
		$this->db->from('foretaste_goods');
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

    public function getForetasteApplyCount($where)
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->from('foretaste_apply');
        $count = $this->db->count_all_results();
        return $count ?: 0;
    }

	public function selectForetasteApply($field, $where, $limits='', $order=''){
		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'], $limits['offset']);
		}
		if(!empty($order)){
			$this->db->order_by($order['key'], $order['value']);
		}
		$this->db->from('foretaste_apply');
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	public function selectForetasteSetting(){
		$row = $this->db->select('*')
                            ->from('setting')
                            ->where('type','foretaste')
                            ->limit(1)
                            ->get()
                            ->row_array();
                            return $row;
	}

	public function getForetasteCommentCount($where)
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->from('foretaste_comments');
        $count = $this->db->count_all_results();
        return $count ?: 0;
    }

    public function selectForetasteComment($field, $where, $limits='', $order='', $where_in=''){
		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'], $where_in['value']);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'], $limits['offset']);
		}
		if(!empty($order)){
			$this->db->order_by($order['key'], $order['value']);
		}
		$this->db->from('foretaste_comments');
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	public function upForetasteApplyCount($id){
		if(empty($id)){
			return false;
		}
		$rs = $this->db->set('applycount','applycount+1',false)->where('id',$id)->limit(1)->update('foretaste_goods');
		return $rs;
	}

	public function upForetasteApply($where,$data){
		if(empty($where) || empty($data)){
			return false;
		}
		$res = $this->db->update('foretaste_apply', $data, $where);
		return $res;
	}

	public function insForetasteApply($data){
		if(empty($data)){
			return false;
		}
		$res = $this->db->insert('foretaste_apply',$data);
		return $res;
	}

	public function insForetasteComments($data){
		if(empty($data)){
			return false;
		}
		$res = $this->db->insert('foretaste_comments',$data);
		return $res;
	}

    public function getStarAvg($fgid)
    {
        $row = $this->db->select_avg('pack_star', 'pack_star_avg')
                    ->select_avg('taste_star', 'taste_star_avg')
                    ->select_avg('logistics_star', 'logistics_star_avg')
                    ->from('foretaste_comments')
                    ->where('foretaste_goods_id',$fgid)
                    ->get()
                    ->row_array();
        return $row;
    }
}