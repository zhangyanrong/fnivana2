<?php
namespace bll\pmt\order\solution;

require_once('solution_abstract.php');
class Gift extends Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($solution,&$cart_info)
    {

        $gift_id = $solution['product_id'];

        $num = 1;
        if ($solution['low_amount'] > 0)
            $num = $solution['is_send_more'] ? floor($cart_info['total_amount']/$solution['low_amount']): 1;

        if($num<=0){
            $num = 1;
        }
        $this->ci->load->model('product_model');
        $this->ci->load->model('skus_model');


        $gift_product = $this->ci->product_model->dump(array('id'=>$gift_id),'thum_photo,thum_min_photo,photo,middle_photo,bphoto,product_name,template_id');
        if (!$gift_product) return;
        $sku = $this->ci->skus_model->dump(array('product_id'=>$gift_id));
        if (!$sku) return;

        // 获取产品模板图片
        if ($gift_product['template_id']) {
            $this->ci->load->model('b2o_product_template_image_model');
            $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($gift_product['template_id'], 'main');
            if (isset($templateImages['main'])) {
                $gift_product['bphoto'] = $templateImages['main']['big_image'];
                $gift_product['photo'] = $templateImages['main']['image'];
                $gift_product['middle_photo'] = $templateImages['main']['middle_image'];
                $gift_product['thum_photo'] = $templateImages['main']['thumb'];
                $gift_product['thum_min_photo'] = $templateImages['main']['small_thumb'];
            }
        }

        // if ($sku)

        $key = 'mb_gift_'.$sku['id'];
        if (isset($cart_info['items'][$key])) {
            $cart_info['items'][$key]['qty'] += $solution['products_num'] * $num;
        } else {
            $cart_info['items'][$key] = array(
                'name'          => $gift_product['product_name'],
                'item_type'     => 'mb_gift',
                'product_id'    => $sku['product_id'],
                'sku_id'        => $sku['id'],
                'price'         => $sku['price'],
                'sale_price'    => '0',
                'qty'           => $solution['products_num'] * $num,
                // 'product_photo' => PIC_URL.($gift_product['thum_min_photo'] ? $gift_product['thum_min_photo'] : $gift_product['thum_photo']),
                'photo' => array(
                    'huge'   => $gift_product['bphoto'] ? PIC_URL.$gift_product['bphoto'] : '',
                    'big'    => $gift_product['photo'] ? PIC_URL.$gift_product['photo'] : '',
                    'middle' => $gift_product['middle_photo'] ? PIC_URL.$gift_product['middle_photo'] : '',
                    'small'  => $gift_product['thum_photo'] ? PIC_URL.$gift_product['thum_photo'] : '',
                    'thum'   => $gift_product['thum_min_photo'] ? PIC_URL.$gift_product['thum_min_photo'] : '',
                    ),
                'unit'          => $sku['unit'],
                'spec'          => $sku['volume'],
                'pmt_price'     => $sku['price'],
                'product_no'    => $sku['product_no'],
                'status'        => 'active',
                // 'tags'          => array('满百赠品'),
                'amount'        => '0',
                'pmt_details'   => array(0=>array('tag'=>'满百赠品','pmt_id'=>$this->pmt_id,'pmt_type'=>'amount','pmt_price'=>0)),
            );
        }
    }

    public function desc_txt($filter,$solution)
    {
        $gift_id = $solution['product_id'];

        $this->ci->load->model('product_model');

        $gift_product = $this->ci->product_model->dump(array('id'=>$solution['product_id']));

        $data = array(
            'product_name' => $gift_product['product_name'],
            'product_photo' => $gift_product['thum_photo'],
            'desc_txt' => '送' . $gift_product['product_name'],
            );
        return $data;
    }
}