<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');

class Limit2buy extends Condition_abstract
{
    private $_region_id = 0;

    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function meet($pmt_id, $condition, $cart_info)
    {
        $this->_region_id = $condition['region_id'];
        return true;
    }

    /**
     * 换购提醒
     *
     * @return void
     * @author
     **/
    public function get_solution($pmt_id,$solution,$cart_info)
    {
        $pmt = ['pmt_type' => 'limit2buy','products' => []];
        $this->ci->load->model('product_model');

        foreach ($solution as $product_id => $info) {
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
                if ($sku['id'] != $solution[$sku['product_id']]['product_sku_id']) {
                    continue;
                }

                $regions = unserialize($product['send_region']);

                if (!in_array($this->_region_id, $regions)) {
                    continue;
                }

                $pmt['products'][] = array(
                        'pmt_id'           => $pmt_id,
                        'product_name'     => $product['product_name'],
                        'sale_price'       => $solution[$sku['product_id']]['product_price'],
                        'photo'            => array(
                            'huge'             => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                            'big'              => $product['photo'] ? PIC_URL.$product['photo'] : '',
                            'middle'           => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                            'small'            => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                            'thum'             => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
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
        return array('url'=>true, 'tag'=>'换','title'=>$pmt['solution']['exchage']['addmoney'].'元换购，点击查看','pmt_id'=>$pmt_id);
    }
}

# end of this file
