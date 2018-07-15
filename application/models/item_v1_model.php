<?php

// 购物车物品v1
// 蔡昀辰 2016
class Item_v1_model extends CI_model {
    
    public $product_name = "";      // 商品名称(可选)
    public $product_id;             // 商品ID(必须)
    public $product_no = "";        // 商品编号(必须)
    public $sku_id;                 // 规格ID(必须)
    public $item_type;              // 商品类型(必须):
    public $gift_send_id = "";      // 会员赠品相关(可选)
    public $gift_active_type = "";  // 会员赠品相关(可选)
    public $pmt_id = "";            // 换购的优惠ID(可选)
    public $qty = 1;                // 商品数量(可选)
    public $selected = true;        // 勾选(可选) true/false
    
    private $item_types = [
        "normal",       // 正常商品
        "exch",         // 换购
        "gift",         // 满额赠品/满件赠品
        "user_gift",    // 会员赠品
        "mb_gift",      // 满百赠品 优惠券?
        "coupon_gift",  // 礼品券赠品
        "presell",      // 预售
        "group",        // 团购(停用)
        "kjt",          // 跨境通(弃用)
        "limit2buy",    // 换购(弃用)
        "o2o",          // 闪电送(停用)
    ];    
    
    // 创建item对象
    public function create(Array $item) {
        
        $obj = new Item_v1_model();

        foreach($item as $key=>$value) {
            $obj->$key = $value;
        }
        
        foreach($obj as $atrr_name=>$atrr_value) {
            if($atrr_value === null)
                return "缺少{$atrr_name}";
        }        
        
        // check exch:pmt_id, user_gift:gift_send_id, gift_active_type
        
        return $obj;
    }
    
    // 勾选
    public function select() {
        
    }
    
    // 不勾选
    public function unselect() {
        
    }    
    
    
}