<?php
class Solr_index_model extends MY_Model {

    public function upFullKeywords(){
        $this->db->truncate('ttgy_solr_product_keywords');
        $key_words = array();
        $sql = "SELECT DISTINCT p.product_name FROM ttgy_product p join ttgy_b2o_store_product sp on p.id=sp.product_id";
        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            if($value['product_name']){
                $key_word = array();
                $key_word['keyword'] = $value['product_name'];
                $key_word['type'] = 1;
                $key_words[] = $key_word;
            }
        }
        // $sql = "SELECT DISTINCT promotion_tag FROM ttgy_b2o_store_product";
        // $result = $this->db->query($sql)->result_array();
        // foreach ($result as $key => $value) {
        //     if($value['promotion_tag']){
        //         $key_word = array();
           //      $key_word['keyword'] = $value['promotion_tag'];
           //      $key_word['type'] = 2;
           //      $key_words[] = $key_word;
        //     }
        // }
        $sql = "SELECT tags FROM ttgy_b2o_product_search_tag";
        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            $tags =explode('|', $value['tags']);
            foreach ($tags as $v) {
                if($v){
                    $key_word = array();
                    $key_word['keyword'] = $v;
                    $key_word['type'] = 3;
                    $key_words[] = $key_word;
                }
            }
        }
        $sql = "SELECT DISTINCT name FROM ttgy_b2o_product_class";
        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            if($value['name']){
                $key_word = array();
                $key_word['keyword'] = $value['name'];
                $key_word['type'] = 3;
                $key_words[] = $key_word;
            }
        }
        if(empty($key_words) || empty($key_words[0])) return;
        $key_words = $this->array_unique_2d($key_words);
        $this->db->insert_batch('solr_product_keywords',$key_words);
    }

    private function array_unique_2d($array2D){
        $temp = array();
        foreach ($array2D as $value) {
            $value = json_encode($value);
            $temp[] = $value;
        }
        $temp = array_unique($temp);
        $result = array();
        foreach ($temp as $value) {
            $result[] = json_decode($value,true);
        }
        return $result;
    }

    public function upFullProducts(){
        $this->db->truncate('ttgy_solr_store_product');
        $products = array();
        $sql = "select bsp.id,bsp.product_id,bsp.store_id,bsp.is_hide,bsp.is_store_sell,p.product_name,if(bsp.is_free_post=1,'包邮','') as promotion_tag,replace(concat(bpc.name,'|',bpst.tags),'|',' ') as tag,bsp.is_pc_online,bsp.is_wap_online,bsp.is_app_online,p.channel,bsp.is_delete,bst.sort from ttgy_b2o_store_product bsp join ttgy_product p on p.id=bsp.product_id join ttgy_b2o_store bs on bs.id=bsp.store_id join ttgy_b2o_store_type bst on bst.id=bs.type left join ttgy_b2o_product_template bpt on bpt.id=p.template_id left join ttgy_b2o_product_class bpc on bpc.id=bpt.class_id left join ttgy_b2o_product_search_tag bpst on bpst.class_id=bpc.id";
        $result = $this->db->query($sql)->result_array();
        $result && $this->db->insert_batch('ttgy_solr_store_product',$result);
    }

    public function upIndexProducts(){
    	$products = array();
    	$last_5 = date("Y-m-d H:i:s",strtotime('-10 min'));
        $sql = "select bsp.id,bsp.product_id,bsp.store_id,bsp.is_hide,bsp.is_store_sell,p.product_name,if(bsp.is_free_post=1,'包邮','') as promotion_tag,replace(concat(bpc.name,'|',bpst.tags),'|',' ') as tag,bsp.is_pc_online,bsp.is_wap_online,bsp.is_app_online,p.channel,bsp.is_delete,bst.sort from ttgy_b2o_store_product bsp join ttgy_product p on p.id=bsp.product_id join ttgy_b2o_store bs on bs.id=bsp.store_id join ttgy_b2o_store_type bst on bst.id=bs.type left join ttgy_b2o_product_template bpt on bpt.id=p.template_id left join ttgy_b2o_product_class bpc on bpc.id=bpt.class_id left join ttgy_b2o_product_search_tag bpst on bpst.class_id=bpc.id where bsp.last_modify_time>='".$last_5."' or bpst.last_modify_time>='".$last_5."' or bpc.last_modify_time>='".$last_5."' or p.last_modify_time>='".$last_5."'";
        $result = $this->db->query($sql)->result_array();
        $this->batch_replace('ttgy_solr_store_product',$result);
    }

    private function batch_replace($table,$data){
        if(is_array($data) && is_array($data[0])){
            $keys = array_keys($data[0]);
            $batch = array_chunk($data, 100);
            foreach ($batch as $one) {
                $sql = "REPLACE INTO ".$table." (".implode(', ', $keys).") VALUES ";
                foreach ($one as $key => $value) {
                    $sql .= "('".implode("','", $value)."'),";
                }
                $sql = rtrim($sql,',');
                $this->db->query($sql);
            }
        }
    }
}