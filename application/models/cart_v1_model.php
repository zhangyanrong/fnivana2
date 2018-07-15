<?php

// 购物车模型v1
// 蔡昀辰 2016
class Content_v1_model extends CI_model {
    
    // 载入购物车内容
    private function loadContents() {
        $this->ci->load->model('item_v1_model');
        $this->ci->load->model('content_v1_model');
        
        foreach ($this->_cart_item as $key=>$item) {
            $item             = $this->ci->item_v1_model->create($item);
            $this->items[]    = $item;
            $this->contents[] = $this->ci->content_v1_model->create($item);
        }      
            
    }     
    
}