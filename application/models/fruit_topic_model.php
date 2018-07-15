<?php
class Fruit_topic_model extends MY_Model {

    public function table_name(){
        return 'fruit_topic';
    }

    public function getTopicList($field, $where, $like, $limits, $where_in=''){
        $where['state'] = 1;//显示
        $this->db->where($where);

        if(!empty($like)){
            $this->db->like($like['key'], $like['value']);
        }
        if(!empty($limits)){
            $this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
        }
        if(!empty($where_in)){
            $this->db->where_in($where_in['key'], $where_in['value']);
        }
        if(!empty($field)){
            $this->db->select($field);
        }
        $this->db->from('fruit_topic');
        $query = $this->db->get();
        $result = $query->result_array();
        //重置图片链接在model层
        foreach($result as &$val){
            if(isset($val['photo'])){
                $val['photo'] = empty($val['photo']) ? "" :PIC_URL.$val['photo'];
            }
            if(isset($val['thumbs'])){
                $val['thumbs'] = empty($val['thumbs']) ? "" :PIC_URL.$val['thumbs'];
            }
        }
        return $result;
    }

    public function getTopicInfo($field, $where, $like, $limits, $where_in=''){
        //$where['state'] = 1;//显示
        $this->db->where($where);

        if(!empty($like)){
            $this->db->like($like['key'], $like['value']);
        }
        if(!empty($limits)){
            $this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
        }
        if(!empty($where_in)){
            $this->db->where_in($where_in['key'], $where_in['value']);
        }
        if(!empty($field)){
            $this->db->select($field);
        }
        $this->db->from('fruit_topic');
        $query = $this->db->get();
        $result = $query->result_array();

        //重置图片链接在model层
        foreach($result as &$val){
            if(isset($val['photo'])){
                $val['photo'] = empty($val['photo']) ? "" :PIC_URL.$val['photo'];
            }
            if(isset($val['thumbs'])){
                $val['thumbs'] = empty($val['thumbs']) ? "" :PIC_URL.$val['thumbs'];
            }

            $description_rich = str_replace('src="/', 'src="'.PIC_URL, $val['description_rich']);
            $val['real_description_rich'] = $description_rich;
            $val['description_rich'] = $this->handleRichText($description_rich);
        }

        return $result;
    }

    /*
     * 新建果食主题
     */
    public function insTopic($data){
        if(empty($data)){
            return false;
        }
        $res = $this->db->insert('fruit_topic',$data);
        return $res;
    }

    /*
     * 获取果食主题标题
     */
    public function getTopicTitle($title){
        $where = array('title'=>$title);
        $this->db->select('id');
        $this->db->from('fruit_topic');
        $this->db->where($where);
        $result = $this->db->get()->row_array();
        return $result;
    }
}