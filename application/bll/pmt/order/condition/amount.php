<?php
namespace bll\pmt\order\condition;

require_once('condition_abstract.php');
class Amount extends Condition_abstract
{
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$cart_info,$region='')
    {
        $filter = true;

        $total_amount = $goods_amount = $cart_info['total_amount'];

        foreach ($cart_info['items'] as $key => $value) {
            if ((isset($value['active_limit']) && $value['active_limit'] == 0) && (isset($value['iscard']) && $value['iscard']==0)) {
                //$goods_amount += $value['amount'];
            }else{
                $goods_amount -= $value['amount'];
            }
        }

        $total_amount = $goods_amount;

        // 是否使用了卡券
        $this->ci->load->library('login');
        // if ($this->ci->login->is_login()) {
        //     $uid = $this->ci->login->get_uid();
        //     if ($uid) {
        //         $this->ci->load->model('order_model');

        //         $preorder = $this->ci->order_model->dump(array('uid'=>$uid,'order_status'=>'0'));
        //         if ($preorder['use_card']) {
        //             $total_amount = $total_amount > $preorder['card_money'] ? ($total_amount-$preorder['card_money']) : 0;
        //         }
        //     }
        // }

        $real_region = (array) $region ;

        $condition['send_region'] = array_filter($condition['send_region']);

        $amount = $condition['can_card'] == '1' ? $goods_amount : $total_amount;

        $this->cart_total_amount = $amount;

        if ($amount>=$condition['low_amount'] && $amount<=$condition['high_amount']) {

            if ($condition['send_region'] && array_intersect($real_region, $condition['send_region'])) {

                $filter = false;
            } else if(empty($condition['send_region'])) {

                $filter = false;
            }
        }

        $this->outof_money = $condition['low_amount'] - $amount;


        return $filter;
    }

    /**
     * 过滤出下一个优惠活动
     *
     * @return void
     * @author
     **/
    public function filter_next_pmt($condition,$cart_info,$region='')
    {
    }

    public function get_solution($pmt_id,$pmt_type,$cart_info,$region_id)
    {
        $solution = array('pmt_type' => $pmt_type,'products' => array());

        $alert = $cart_info['pmt_alert'];
        if (!$alert) return $solution;

        foreach ($alert as $a) {
            if ($a['solution']['pmt_type'] == $pmt_type && $a['solution']['pmt_id'] == $pmt_id) {
                $pmt = $a['solution']; break;
            }
        }

        if (!$pmt) {
            return $solution;
        }
        $solution['title'] = rtrim($pmt['title'],'，去凑单');

        $outofmoney = $pmt['outofmoney'];

        $sql = 'SELECT p.product_name,p.bphoto,p.photo,p.middle_photo,p.thum_photo,p.thum_min_photo,p.template_id,pp.*
                FROM ttgy_product AS p
                LEFT JOIN ttgy_product_price AS pp ON(p.id=pp.product_id)
                WHERE  p.channel="portal" AND p.lack=0 AND p.iscard=0 AND p.free=0 AND p.offline=0 AND p.expect=0';

        $this->ci->load->library('terminal');
        if ($this->ci->terminal->is_app()) {
            $sql .= ' AND p.app_online=1';
        }
        if ($this->ci->terminal->is_wap()) {
            $sql .= ' AND p.mobile_online=1';
        }
        if ($this->ci->terminal->is_web()) {
            $sql .= ' AND p.online=1';
        }

        $sql .= ' AND p.send_region like "%'.$region_id.'%" AND pp.price >"'.$outofmoney.'" ORDER BY pp.price ASC LIMIT 10';

        $products = $this->ci->db->query($sql)->result_array();


        foreach ($products as $product) {
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

            $solution['products'][] = array(
                'product_name'     => $product['product_name'],
                'sale_price'       => $product['price'],
                'photo'            => array(
                    'huge'             => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                    'big'              => $product['photo'] ? PIC_URL.$product['photo'] : '',
                    'middle'           => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                    'small'            => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                    'thum'             => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
                ),
                'unit'             => $product['unit'],
                'spec'             => $product['volume'],
                'product_price_id' => $product['id'],
                'product_no'       => $product['product_no'],
                'price'            => $product['price'],
                'product_id'       => $product['product_id'],
            );
        }

        return $solution;
    }
}