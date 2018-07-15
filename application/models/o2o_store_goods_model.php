<?php
class O2o_store_goods_model extends MY_Model {

    public function table_name(){
        return 'o2o_store_goods';
    }

    /**
     * 扣减库存
     *
     * @param Int $store_id 店铺ID
     * @param Int $product_id 商品ID
     * @param Int $qty 扣减的库存
     * @return void
     * @author
     **/
    public function stockSub($store_id,$product_id,$qty)
    {
        $sql = 'UPDATE `ttgy_o2o_store_goods` SET stock=stock-' . intval($qty) . ' WHERE store_id='.$store_id.' AND product_id='.$product_id.' LIMIT 1';

        $this->db->query($sql);
    }

    //取消返还库存
    public function returnStock($store_id,$product_id,$qty)
    {
        $sql = 'UPDATE `ttgy_o2o_store_goods` SET stock=stock+' . intval($qty) . ' WHERE store_id='.intval($store_id).' AND product_id='.intval($product_id).' LIMIT 1';

        $this->db->query($sql);
    }

    public function getO2oProduct($id,$store_id){
        //获取商品基础信息
        $product = array();
        $sql = "select p.id,p.product_name,p.discription,p.product_desc,p.cart_tag,p.photo,p.bphoto,p.thum_photo,p.app_online as online,p.offline,p.send_region,p.free,p.consumer_tips,p.op_place,p.op_detail_place,p.op_size,p.tag_id,p.jf_limit,p.card_limit,p.op_weight,g.stock,g.store_id,g.qtylimit as buy_limit,s.name as store_name, p.template_id from ttgy_product p join ttgy_o2o_store_goods g on p.id=g.product_id join ttgy_o2o_store s on s.id=g.store_id where p.id=".$id." and g.store_id=".$store_id;

        $result = $this->db->query($sql)->row_array();
        if(empty($result)) {
            return array('code'=>'300','msg'=>'该商品已售罄');
        }
        $result['cart_tag'] = !empty($result['cart_tag']) ? $result['cart_tag'] : '';
        $result['consumer_tips'] = trim(str_replace('&nbsp;','',strip_tags($result['consumer_tips'],'<img>')));
        $result['discription'] = trim(str_replace('&nbsp;','',strip_tags($result['discription'],'<img>')));
        if(!empty($result['consumer_tips'])){
            $result['discription'] = $result['consumer_tips'];
        }
        $result['discription'] = str_replace('src="/', 'src="'.PIC_URL, $result['discription']);
        $discription = $result['discription'];
        $discription = preg_replace(array('/class=".*?"/','/width=".*?"/','/height=".*?"/','/style=".*?"/'), array('','','',''), $discription);
        $result['discription'] = <<<EOT
<html>
<head>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1;" name="viewport" />
    <style>*{margin:0; padding:0}.app-detail{padding:0px; margin:0}.app-detail>img{width:100%;}</style>
</head>
<body>
    <div class="app-detail">$discription</div>
</body>
</html>
EOT;
        // if ($is_filter){
        //     $result['discription'] = strip_tags($result['discription'],'<img>');
        //     preg_match_all('/src=\"(.*?)\"/',$result['discription'],$imglist_result);
        //     $result['discription'] = $imglist_result[1];
        // }
        // $result['region'] = '';
        // $region_arr = array_flip($this->config->item('str_area_refelect'));
        // foreach (unserialize($result['send_region']) as $key => $value) {
        //     $result['region'] .= $region_arr[$value].',';
        // }
        // $result['region'] = trim($result['region'],',');
        unset($result['send_region'],$result['online'],$result['offline']);
        $product_tmp  = $result;

        //获取价格
        $sql = "select mobile_price,price,volume,id,product_no,product_id,old_price from ttgy_product_price where product_id=".$id." order by order_id asc,id desc";
        $price_result = $this->db->query($sql)->result_array();
        $price_result = $price_result[0];

        $product_tmp['price'] = $price_result['price'];
        if($price_result['mobile_price']>0){
            $product_tmp['price'] = $price_result['mobile_price'];
        }
        $product_tmp['price'] = $price_result['price'];
        $product_tmp['price_id'] = $price_result['id'];
        $product_tmp['product_no'] = $price_result['product_no'];
        $product_tmp['product_id'] = $price_result['product_id'];
        $product_tmp['old_price'] = ($price_result['old_price']>0)?$price_result['old_price']:'';
        $product_tmp['volume'] = $price_result['volume'];
        $product_tmp['ptype'] = 1;

        // 获取产品模板图片
        if ($result['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($result['template_id']);
            if (isset($templateImages['main'])) {
                $result['thum_photo'] = $templateImages['main']['thumb'];
                $result['photo'] = $templateImages['main']['image'];
                $result['bphoto'] = $templateImages['main']['big_image'];
            }
        }
        //获取图片
        $photo_arr_tmp = array();
        $photo_arr_tmp[0]['thum_photo'] = $product_tmp['thum_photo'] = PIC_URL.$result['thum_photo'];
        $photo_arr_tmp[0]['photo'] = $product_tmp['photo'] = PIC_URL.$result['photo'];
        $photo_arr_tmp[0]['big_photo'] = $product_tmp['bphoto'] = PIC_URL . $result['bphoto'];

        // 获取产品模板图片
        if ($result['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $key_v = $key+1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL.$value['thumb'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL.$value['image'];
                    $photo_arr_tmp[$key_v]['big_photo'] = PIC_URL . $value['big_image'];
                }
            }
        } else {
            $sql = "select id,product_id,thum_photo,bphoto,photo from ttgy_product_photo where product_id=".$id." order by order_id asc,id desc";
            $photo_arr = $this->db->query($sql)->result_array();
            if(!empty($photo_arr)){
                foreach ($photo_arr as $key => $value) {
                    $key_v = $key+1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL.$value['thum_photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL.$value['photo'];
                    $photo_arr_tmp[$key_v]['big_photo'] = PIC_URL . $value['bphoto'];
                }
            }
        }

        // product promotion
        $sql = "select title, type, target_url, target_product_id from ttgy_product_promotion where product_id=?";
        $product_tmp['promotion'] = $this->db->query($sql, array((int)$id))->result_array();

        $product['product'] = $product_tmp;
        $product['items']  = array($price_result);
        $product['photo'] = $photo_arr_tmp;
        $product['share_url'] = "http://m.fruitday.com/o2o/detail/{$store_id}/{$id}";

        return $product;
    }
}