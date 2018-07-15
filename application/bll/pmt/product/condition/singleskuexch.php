<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');
class Singleskuexch extends Condition_abstract
{

    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
        $filter = true;

        $exch_key = null;
        foreach ($cart_info['items'] as $key => $value) {

            if ($value['item_type'] == 'exch' && $value['pmt_id'] == $condition['pmt_id']) {
                $exch_key = $key;
            }
        }

        $product_id = $condition['product_id'];
        $qty = $condition['qty'];
        foreach ($cart_info['items'] as $value) {
            if ($value['item_type']=='normal' && in_array($value['product_id'],$product_id) && $exch_key) {
                $filter = false;

            }
        }

        return $filter;
    }

    /**
     * 满足条件优惠提醒
     *
     * @return void
     * @author
     **/
    public function meet($pmt_id,$condition,$cart_info)
    {
        $meet = false;

        $exch_key = null;
        foreach ($cart_info['items'] as $key => $value) {
            if ($value['item_type'] == 'exch' && $value['pmt_id'] == $pmt_id) {
                $exch_key = $key;
            }
        }

        $product_id = $condition['product_id'];
        $qty = $condition['qty'];
        foreach ($cart_info['items'] as $value) {
            if ($value['item_type']=='normal' && in_array($value['product_id'],$product_id) && is_null($exch_key)) {
                $meet = true;
            }
        }

        return $meet;
    }

    /**
     * 换购提醒
     *
     * @return void
     * @author
     **/

    public function get_solution($pmt_id,$solution,$cart_info)
    {
        $productids = explode(',', $solution['exchage']['product_id']);
        $addmoney = $solution['exchage']['addmoney'];
        // $deppid = $solution['exchage']['dep_product_id'];
        // $pmt_id = $pmt_id;


        $pmt = array('pmt_type' => 'exch','products' => array());

        $this->ci->load->model('product_model');
        // $products = $this->ci->product_model->getList('product_name,id,thum_photo,thum_min_photo,photo,middle_photo,bphoto',array('id'=>$product_id));
        // if (!$products) return ;

        // $f_products = array();
        // foreach ($products as $key => $value) {
        //     $f_products[$value['id']] = $value;
        // }
        // unset($products);

        // $skus = $this->ci->product_model->get_skus($product_id);
        // if (!$skus) return ;

        foreach ($productids as $product_id) {
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
                        'sale_price'       => $addmoney,
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

    /**
     * 获取提示标题
     *
     * @return void
     * @author
     **/
    public function get_alert_title($pmt_id,$pmt,$cart_info)
    {
        return array('url'=>true, 'tag'=>'换','title'=>$pmt['solution']['exchage']['addmoney'].'元换购，点击查看','pmt_id'=>$pmt_id);
    }

    /**
     *
     *
     * @return void
     * @author
     **/
    public function get_tag()
    {
        // return '换购';
    }
}