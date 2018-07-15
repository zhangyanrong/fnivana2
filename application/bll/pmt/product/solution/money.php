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
 * @version   GIT: $Id: Money.php 1 2014-08-18 10:23:44Z pax $
 * @link      http://www.fruitday.com
 **/
require_once('solution_abstract.php');
class Money extends Solution_abstract
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function process($itemkey,$item,$solution,&$cart_info)
    {
        $ckey = $itemkey;

        $pmt_price = null;$math = null;$main_product_id = null;

        if (!isset($solution[$item['product_id']])) return 0;

        $pmt_price       = $solution[$item['product_id']]['price'];
        $math            = $solution[$item['product_id']]['math'];
        $main_product_id = $solution[$item['product_id']]['main_product_id'];

        $sale_price = $item['sale_price'];
        $price      = $item['price'];
        $qty        = $item['qty'];

        $this->ci->load->library('math');

        if (!is_null($pmt_price)) {
            switch ($math) {
                case 'sub':

                    $sale_price = $this->ci->math->sub($sale_price,$pmt_price);
                    $sale_price = $sale_price > 0 ?  $sale_price : 0;

                    $cart_info['items'][$ckey]['sale_price'] = $sale_price;
                    $cart_info['items'][$ckey]['pmt_price']  = $this->ci->math->add($item['pmt_price'],$pmt_price);
                    $cart_info['items'][$ckey]['amount']     = $this->ci->math->mul($sale_price,$qty);

                    return $pmt_price * $qty;
                    break;
                case 'equal':
                    $sale_price = $pmt_price;

                    $cur_pmt = $this->ci->math->sub($item['sale_price'],$pmt_price);

                    $cart_info['items'][$ckey]['sale_price'] = $sale_price;
                    $cart_info['items'][$ckey]['pmt_price']  = $this->ci->math->sub($item['sale_price'],$pmt_price);
                    $cart_info['items'][$ckey]['amount']     = $this->ci->math->mul($sale_price,$qty);

                    return ($cur_pmt > 0 ? $cur_pmt : 0) * $qty;
                    break;
                case 'dapeigou':
                    $main_qty = 0;
                    foreach ($cart_info['items'] as $key => $value) {
                        if ($value['item_type'] == 'normal' && $value['product_id'] == $main_product_id) {
                            $main_qty = $value['qty'];
                        }
                    }

                    if ($main_qty > 0) {
                        $cart_info['items'][$ckey]['recal'] = false;

                        $excess_qty = $qty > $main_qty ? ($qty - $main_qty) : 0;

                        $cart_info['items'][$ckey]['amount'] = $pmt_price * ($excess_qty > 0 ? $main_qty : $qty) + $excess_qty * $sale_price;
                        $cart_info['items'][$ckey]['sale_price'] = $qty > $main_qty ? $sale_price : $pmt_price;

                        $cur_pmt = $this->ci->math->sub($sale_price,$pmt_price);

                        return ($cur_pmt > 0 ? $cur_pmt : 0) * ($excess_qty > 0 ? $main_qty : $qty);
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }

        return 0;
    }
}