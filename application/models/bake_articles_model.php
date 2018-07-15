<?php
class Bake_articles_model extends MY_Model {

	public function table_name(){
		return 'bake_articles';
	}

	/*
	* 获取商品规格信息
	*/
	function selectBakeArticles($field,$where='',$where_in='',$order='',$group_by=''){
		$where['state'] = 1;
		$this->db->select($field);
		$this->db->from($this->table_name());
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'],$where_in['value']);
		}
		if(!empty($order)){
			$this->db->order_by($order);
		}
		if(!empty($group_by)){
			$this->db->group_by($group_by);
		}
		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * [getArticleList 获取文章列表]
	 * @param  [string] $field    [字段:is_pic_tmp必选]
	 * @param  [array] $where    [description]
	 * @param  [array] $where_in [description]
	 * @param  [string] $group    [description]
	 * @return [array]           [description]
	 */
	public function getArticleList($field, $where, $where_in, $group='',$limits=''){
        if($where_in['solr_search']){
            $ids = implode(',', $where_in['solr_search']['value']);
            $sql = "select ".$field." from ttgy_bake_articles join ttgy_bake_article_section on ttgy_bake_articles.id=ttgy_bake_article_section.article_id where id in(".$ids.") and state=1 group by ".$group." order by field(id,".$ids.")";
            $result = $this->db->query($sql)->result_array();
        }else{
            $where['state'] = 1;//显示
            $this->db->where($where);

            if(!empty($field)){
                $this->db->select($field);
            }
            $order_by = true;
            if(!empty($where_in)){
                foreach ($where_in as $key=>$val) {
                    $this->db->where_in($val['key'], $val['value']);
                }
            }
            if(!empty($group)){
                $this->db->group_by($group);
            }
            if(!empty($limits)){
                $this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
            }
            $this->db->order_by('utime desc, id desc');
            $this->db->from('bake_articles');
            $this->db->join('bake_article_section', 'bake_articles.id=bake_article_section.article_id');
            $query = $this->db->get();
            $result = $query->result_array();
        }
		foreach($result as &$val){
			if(isset($val['photo'])){
	                		if(!empty($val['photo'])){
	                			$val['photo'] = PIC_URL.$val['photo'];
	                			$val['photo_arr'] = array($val['photo']);
				}else{
					$val['photo'] = '';
					$val['photo_arr'] = array();
				}
			}
			if(isset($val['thumbs'])){
				if(!empty($val['thumbs'])){
					$val['images_thumbs'] = PIC_URL.$val['thumbs'];
					$val['images_thumbs_arr'] = array($val['images_thumbs_arr']);
				}else{
					$val['images_thumbs'] = '';
					$val['images_thumbs_arr'] = array();
				}
				unset($val['thumbs']);
			}
			if(isset($val['content'])){
				$val['real_content'] = "";
				if(!empty($val['content'])){
					$val['content'] = str_replace('src="/', 'src="'.PIC_URL, $val['content']);
					$val['real_content'] = $val['content'];

					$val['content'] = $this->handleRichText($val['content']);
                }
            }
		}
		return $result;
	}

    public function getArticleSections($aid)
    {
        $result = $this->db->select('*')
                 ->from('bake_article_section')
                 ->join('bake_section', 'bake_section.id=bake_article_section.section_id')
                 ->where('article_id', $aid)
                 ->get()
                 ->result_array();
        return $result;
    }
}