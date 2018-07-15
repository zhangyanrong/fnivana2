<?php
namespace bll\pmt\product\solution;

/**
 * 商品优惠扣减单价
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll/pmt/product/solution
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Gift.php 1 2014-08-18 10:23:44Z pax $
 * @link      http://www.fruitday.com
 **/
require_once('solution_abstract.php');
class Gift extends Solution_abstract
{

    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($itemkey,$item,$solution,&$cart_info)
    {
        
        $this->ci->load->model('product_model');
        $productinfo = $this->ci->product_model->dump(array('id'=>$solution['gift']['pid']),'maxgifts');
        $productinfo['maxgifts'] = $productinfo['maxgifts'] ? $productinfo['maxgifts'] : 1;
        
        $key = 'gift_'.$solution['gift']['id'];


        $gleast = $solution['gift']['gleast'] ? $solution['gift']['gleast'] : 1;
        $qty = floor($item['qty']/$gleast) * $solution['gift']['gnum'] * $productinfo['maxgifts']; 

        // $this->ci->load->library('math');
        if ((int) $qty > 0){
            if (isset($cart_info['items'][$key])) {
                $cart_info['items'][$key]['qty'] += $qty;
            } else { 
                $cart_info['items'][$key] = array(
                    'name'          => $solution['gift']['gname'],
                    'item_type'     => 'gift',
                    'product_id'    => $solution['gift']['pid'],
                    'sku_id'        => $solution['gift']['id'],
                    'price'         => $solution['gift']['gprice'],
                    'sale_price'    => '0',
                    'qty'           => $qty,
                    // 'product_photo' => PIC_URL.$solution['gift']['gift_photo'],
                    'photo'         => array(
                        'huge'   => $solution['gift']['gift_photo'] ? PIC_URL.$solution['gift']['gift_photo'] : '',
                        'big'    => $solution['gift']['gift_photo'] ? PIC_URL.$solution['gift']['gift_photo'] : '',
                        'middle' => $solution['gift']['gift_photo'] ? PIC_URL.$solution['gift']['gift_photo'] : '',
                        'small'  => $solution['gift']['gift_photo'] ? PIC_URL.$solution['gift']['gift_photo'] : '',
                        'thum'   => $solution['gift']['gift_photo'] ? PIC_URL.$solution['gift']['gift_photo'] : '',
                        ),
                    'unit'          => '',
                    'spec'          => '',
                    'pmt_price'     => $solution['gift']['gprice'],
                    'product_no'    => $solution['gift']['gno'],
                    'amount'        => '0',
                    'status'        => 'active',
                    // 'tags'          => array('赠品'),
                    'pmt_details'   => array(0=>array('tag'=>'赠品','pmt_id'=>$this->pmt_id,'pmt_type'=>'gifthas','pmt_price'=>0)),
                );
            }
        }

        return 0;
    }
}