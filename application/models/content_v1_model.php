<?php

// 购物车内容(基于购物车item)v1
// 蔡昀辰 2016
class Content_v1_model extends CI_model {

    public $product_name;       // 商品名称
    public $product_id;         // 商品ID
    public $product_no;         // 商品编码(数据库重新读取)
    public $sku_id;             // 商品规格
    public $item_type;          // 商品类型
    public $gift_send_id;       // 会员赠品相关
    public $gift_active_type;   // 会员赠品相关
    public $pmt_id;             // 换购的优惠ID
    public $qty;                // 商品数量
    public $selected;           // 是否勾选

    private $name;              // 商品名称(数据库读取)，向下兼容一段时间715以后移除
    public $price;              // 商品价格
    private $sale_price;        // 显示价格，向下兼容一段时间715以后移除
    private $amount;            // 小计，向下兼容一段时间715以后移除
    public $unit;               // 单位 g
    public $spec;               // 包装? 2斤
    public $weight;             // 重量 kg
    public $cart_tag;           // 商品促销标签(新品,热卖,第2件半价等)

    public $device_limit;       // 是否限制一个设备只能买一次
    public $group_limit;        // 是否可以单独购买
    public $card_limit;         // 是否限制不能使用优惠券
    public $jf_limit;           // 是否限制不能使用积分
    public $pay_limit;          // 是否限制只能线上支付
    public $first_limit;        // 是否显示只能新用户购买
    public $active_limit;       // 是否限制不参加任何促销活动
    public $delivery_limit;     // 是否限制只能2-3天送达
    public $pay_discount_limit; // 是否限制不参加支付折扣活动

    public $free;               // 是否是企业专享商品
    public $offline;            // 是否是线下活动商品
    public $type;               // 商品类型，1水果;2生鲜
    public $free_post;          // 是否包邮
    public $is_tuan;            // 是否在列表页隐藏
    public $use_store;          // 是否启用库存
    public $ignore_order_money; // 是否无起送限制，单独收取运费
    public $group_pro;          // 组合商品
    public $iscard;             // 组合商品？
    public $expect;             // 单独购买
    public $xsh;                // 是否是抢购商品(抢购时间，每人限购一份)
    public $xsh_limit;          // 抢购商品限购数量
    public $qty_limit;          // 限购数量

    public $status = 'active';  // ?
    public $valid  = true;      // 有效(可选)true/false
    public $photo;

    public function __construct() {
        $this->load->model('item_v1_model');
        $this->load->model('product_model');
    }

    // item 购物车物品
    public function create(Item_v1_model $item) {

        $content = new Content_v1_model();

        // 载入item
        foreach($this->item_v1_model as $attr_name=>$attr_value) {
            if($item->$attr_name === null)
                return "缺少{$attr_name}";
            else
                $content->$attr_name = $item->$attr_name;
        }

        // 载入product和sku
        $product = (object)$this->product_model->getProductSkus($item->product_id);
        $sku     = (object)$product->skus[$item->sku_id];
        if(!$sku)
            return "没有对应规格";

        // 获取产品模板图片
        if ($produc->template_id) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product->template_id);
            if (isset($templateImages['main'])) {
                $product->bphoto = $templateImages['main']['big_image'];
                $product->photo = $templateImages['main']['image'];
                $product->middle_photo = $templateImages['main']['middle_image'];
                $product->thum_photo = $templateImages['main']['thumb'];
                $product->thum_min_photo = $templateImages['main']['small_thumb'];
            }
            if (isset($templateImages['whitebg'])) {
                $product->middle_promotion_photo = $templateImages['whitebg']['middle_image'];
                $product->thum_promotion_photo = $templateImages['whitebg']['thumb'];
                $product->thum_min_promotion_photo = $templateImages['whitebg']['small_thumb'];
            }
        }

        // 商品名称
        $content->product_name              = $item->product_name == $product->product_name ? $item->product_name: $product->product_name;
        $content->name                      = $product->product_name; // todo: remove
        $content->product_no                = $sku->product_no; // 数据库重新读取
        $content->price                     = $sku->price;
        $content->sale_price                = $sku->price;
        $content->amount                    = $sku->price * $item->qty;
        $content->unit                      = $sku->unit;
        $content->spec                      = $sku->volume;
        $content->weight                    = $sku->weight;
        $content->cart_tag                  = $product->cart_tag;

        $content->device_limit              = $product->device_limit;
        $content->group_limit               = $product->group_limit;
        $content->card_limit                = $product->card_limit;
        $content->jf_limit                  = $product->jf_limit;
        $content->pay_limit                 = $product->pay_limit;
        $content->first_limit               = $product->first_limit;
        $content->active_limit              = $product->active_limit;
        $content->delivery_limit            = $product->delivery_limit;
        $content->pay_discount_limit        = $product->pay_discount_limit;

        $content->free                      = $product->free;
        $content->offline                   = $product->offline;
        $content->type                      = $product->type;
        $content->free_post                 = $product->free_post;
        $content->is_tuan                   = $product->is_tuan;
        $content->use_store                 = $product->use_store;
        $content->ignore_order_money        = $product->ignore_order_money;
        $content->group_pro                 = $product->group_pro;
        $content->iscard                    = $product->iscard;
        $content->expect                    = $product->expect;
        $content->xsh                       = $product->xsh;
        $content->xsh_limit                 = $product->xsh_limit;
        $content->qty_limit                 = $product->qty_limit;

        $content->photo                     = new StdClass();
        $content->photo->huge               = PIC_URL.$product->bphoto;
        $content->photo->big                = PIC_URL.$product->photo;
        $content->photo->middle             = PIC_URL.$product->middle_photo;
        $content->photo->small              = PIC_URL.$product->thum_photo;
        $content->photo->thum               = PIC_URL.$product->thum_min_photo;
        $content->photo->thum_promotion     = PIC_URL.$product->thum_promotion_photo;
        $content->photo->thum_min_promotion = PIC_URL.$product->thum_min_promotion_photo;
        $content->photo->middle_promotion   = PIC_URL.$product->middle_promotion_photo;

        // 换购商品修改价格
        if($item->item_type == 'exch') {
            $this->load->model('promotion_v1_model');
            $strategy = $this->promotion_v1_model->getOneStrategy($item->pmt_id);
            if(!$strategy)
                continue;
            $content->price      = $strategy->solution->add_money;
            $content->sale_price = $strategy->solution->add_money;
            $content->amount     = $strategy->solution->add_money * $item->qty;
        }

        // test
        // echo json_encode($content);die;

        return $content;
    }

    // 有效
    // $this->valid = true
    public function enable() {
        $this->valid = true;
    }

    // 无效
    // $this->valid = false
    public function disable() {
        $this->valid = false;
    }

}