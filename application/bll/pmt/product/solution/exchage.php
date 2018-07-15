<?php
namespace bll\pmt\product\solution;

require_once('solution_abstract.php');
class Exchage extends Solution_abstract
{

    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($itemkey,$item,$solution,&$cart_info)
    {
        $exch_key = null; $sku_id = 0; $product_id = 0;
        foreach ($cart_info['items'] as $key => $value) {
            if ($value['item_type'] == 'exch' && $value['pmt_id'] == $solution['pmt_id']) {
                $exch_key = $key;
                $sku_id = $value['sku_id'];
                $product_id = $value['product_id'];

            }
        }

        if (!$sku_id || !$product_id) return 0;

        $this->ci->load->model('product_model');
        $productinfo = $this->ci->product_model->dump(array('id'=>$product_id));
        if (!$productinfo) return ;

        // 获取产品模板图片
        if ($productinfo['template_id']) {
            $this->ci->load->model('b2o_product_template_image_model');
            $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($productinfo['template_id'], 'main');
            if (isset($templateImages['main'])) {
                $productinfo['bphoto'] = $templateImages['main']['big_image'];
                $productinfo['photo'] = $templateImages['main']['image'];
                $productinfo['middle_photo'] = $templateImages['main']['middle_image'];
                $productinfo['thum_photo'] = $templateImages['main']['thumb'];
                $productinfo['thum_min_photo'] = $templateImages['main']['small_thumb'];
            }
        }

        $skus = $this->ci->product_model->get_skus($product_id);
        if (!$skus) return 0;

        $skuinfo = array();
        foreach ($skus as $key => $value) {
            if ($value['id'] == $sku_id) {
                $skuinfo = $value;
            }
        }

        if (!$skuinfo) return 0;

        $this->ci->load->library('math');
        $pmt_price = $this->ci->math->sub($skuinfo['price'],$solution['addmoney']);
        $pmt_price = $pmt_price > 0 ? $pmt_price : 0;

        $cart_info['items'][$exch_key]['name']          = $productinfo['product_name'];
        $cart_info['items'][$exch_key]['price']         = $skuinfo['price'];
        $cart_info['items'][$exch_key]['unit']          = $skuinfo['unit'];
        $cart_info['items'][$exch_key]['spec']          = $skuinfo['volume'];
        $cart_info['items'][$exch_key]['sale_price']    = $solution['addmoney'];
        // $cart_info['items'][$exch_key]['product_photo'] = PIC_URL.($productinfo['thum_min_photo'] ? $productinfo['thum_min_photo'] : $productinfo['thum_photo']);
        $cart_info['items'][$exch_key]['photo'] = array(
                'huge'   => $productinfo['bphoto'] ? PIC_URL.$productinfo['bphoto'] : '',
                'big'    => $productinfo['photo'] ? PIC_URL.$productinfo['photo'] : '',
                'middle' => $productinfo['middle_photo'] ? PIC_URL.$productinfo['middle_photo'] : '',
                'small'  => $productinfo['thum_photo'] ? PIC_URL.$productinfo['thum_photo'] : '',
                'thum'   => $productinfo['thum_min_photo'] ? PIC_URL.$productinfo['thum_min_photo'] : '',
            );

        $cart_info['items'][$exch_key]['product_id']    = $productinfo['id'];
        $cart_info['items'][$exch_key]['sku_id']        = $skuinfo['id'];
        $cart_info['items'][$exch_key]['product_no']    = $skuinfo['product_no'];
        $cart_info['items'][$exch_key]['weight']        = $skuinfo['weight'];
        $cart_info['items'][$exch_key]['pmt_price']     = $pmt_price;
        $cart_info['items'][$exch_key]['status']        = 'active';
        $cart_info['items'][$exch_key]['tags']          = array('换购');
        $cart_info['items'][$exch_key]['amount']        = $solution['addmoney'] * 1;//$item['qty']
        $cart_info['items'][$exch_key]['pmt_details']   = array(0=>array('tag'=>'换购','pmt_id'=>$this->pmt_id,'pmt_type'=>'singleskuexch','pmt_price'=>$this->ci->math->mul($pmt_price,$qty)));
        $cart_info['items'][$exch_key]['qty_limit']     = $item['qty'];
        // $cart_info['items'][$exch_key]['deppid']        = $item['product_id'];


        return 0;
    }
}
