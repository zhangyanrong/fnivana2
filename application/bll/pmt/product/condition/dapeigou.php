<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');
class Dapeigou extends Condition_abstract
{
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
        $filter = true;

        $cart_product_id = array();
        foreach ($cart_info['items'] as $cart) {
            if ($cart['item_type'] == 'normal') $cart_product_id[] = $cart['product_id'];
        }

        if (in_array($condition['main_product_id'],$cart_product_id) && in_array($item['product_id'],$cart_product_id)) {
            return false;
        }
        return  true;
    }

    /**
     * 明细的显示标签
     *
     * @return void
     * @author
     **/
    public function get_tag()
    {
        return '搭配购';
    }


    public function meet($pmt_id,$condition,$cart_info)
    {
        $meet = false;  $spid = $condition['product_id'];

        $cart_product_id = array(); $cpid = array();
        foreach ($cart_info['items'] as $cart) {
            if ($cart['item_type'] == 'normal') $cart_product_id[] = $cart['product_id'];
        }

        if (in_array($condition['main_product_id'],$cart_product_id) && array_diff($spid, $cart_product_id)) {
            $meet = true;
        }

        return $meet;
    }

    public function get_solution($pmt_id,$solution,$cart_info)
    {
        $cart_product_id = array();
        foreach ($cart_info['items'] as $cart) {
            if ($cart['item_type'] == 'normal') $cart_product_id[] = $cart['product_id'];
        }

        $solution_product = array();
        foreach ($solution['money'] as $value) {
            if (!in_array($value['product_id'],$cart_product_id)) {
                $solution_product[$value['product_id']] = $value['price'];
            }
        }
        if (!$solution_product) return array();

        $pmt = array('pmt_type' => 'dapeigou','products' => array());

        $this->ci->load->model('product_model');
        foreach ($solution_product as $product_id => $price) {
            $product = $this->ci->product_model->getProductSkus($product_id);
            if (!$product['skus']) continue;

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['bphoto'] = $templateImages['main']['big_image'];
                    $product['photo'] = $templateImages['main']['image'];
                    $product['middle_photo'] = $templateImages['main']['middle_image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                    $product['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
            }

            foreach ($product['skus'] as $sku) {
                $pmt['products'][] = array(
                        'pmt_id'           => $pmt_id,
                        'product_name'     => $product['product_name'],
                        'sale_price'       => $price,
                        // 'product_photo' => PIC_URL. ($f_products[$value['product_id']]['thum_min_photo'] ? $f_products[$value['product_id']]['thum_min_photo'] : $f_products[$value['product_id']]['thum_photo']),
                        'photo' => array(
                            'huge'   => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                            'big'    => $product['photo'] ? PIC_URL.$product['photo'] : '',
                            'middle' => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                            'small'  => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                            'thum'   => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
                            ),
                        'unit'             => $sku['unit'],
                        'spec'             => $sku['volume'],
                        'product_price_id' => $sku['id'],
                        'product_no'       => $sku['product_no'],
                        'price'            => $sku['price'],
                        'product_id'       => $sku['product_id'],
                    );
            }
        }

        return $pmt;
    }

    public function get_alert_title($pmt_id,$pmt,$cart_info)
    {
        $name='';
        if ($pmt['condition']['main_product_id']) {
            $this->ci->load->model('product_model');
            $product = $this->ci->product_model->dump(array('id'=>$pmt['condition']['main_product_id']),'product_name');
            $name="`{$product['product_name']}`";
        }

        return array('url'=>true, 'tag'=>'搭','title'=>'搭配'.$name.'购买立减商品单价，点击查看','pmt_id'=>$pmt_id);
    }
}