<?php
namespace bll;

/**
 * 赠品
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: gsend.php 1 2015-01-28 09:18:54Z pax $
 * @link      http://www.fruitday.com
 **/
class Gsend {
    public function __construct() {
        $this->ci = &get_instance();
    }

    /**
     * 赠品格式化
     *
     * @return void
     * @author
     **/
    public function format_gifts($gsend) {
        $productids = explode(',', $gsend['product_id']);

        if (!$productids) return array();

        $this->ci->load->model('product_model');
        // $products = $this->ci->product_model->getList('product_name,id,thum_photo,thum_min_photo',array('id'=>$productids));
        // if (!$products) return array();

        // $f_products = array();
        // foreach ($products as $key => $value) {
        //     $f_products[$value['id']] = $value;
        // }
        // unset($products);

        // $skus = $this->ci->product_model->get_skus($productids);
        // if (!$skus) return array();
        $gifts = array();
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
                $gifts['products'][] = array(
                    'product_name' => $product['product_name'],
                    'sale_price' => 0,
                    // 'product_photo'    => PIC_URL . ($f_products[$value['product_id']]['thum_min_photo'] ? $f_products[$value['product_id']]['thum_min_photo'] : $f_products[$value['product_id']]['thum_photo']),
                    'photo' => array(
                        'huge' => $product['bphoto'] ? PIC_URL . $product['bphoto'] : '',
                        'big' => $product['photo'] ? PIC_URL . $product['photo'] : '',
                        'middle' => $product['middle_photo'] ? PIC_URL . $product['middle_photo'] : '',
                        'small' => $product['thum_photo'] ? PIC_URL . $product['thum_photo'] : '',
                        'thum' => $product['thum_min_photo'] ? PIC_URL . $product['thum_min_photo'] : '',
                    ),
                    'unit' => $sku['unit'],
                    'spec' => $sku['volume'],
                    'product_price_id' => $sku['id'],
                    'product_no' => $sku['product_no'],
                    'price' => $sku['price'],
                    'product_id' => $sku['product_id'],
                    'qty' => $gsend['qty'],
                    'end' => $gsend['end_time'] ? $gsend['end_time'] : $gsend['end'],
                    'gift_send_id' => $gsend['gift_send_id'],
                    'gg_name' => $sku['volume'] . '/' . $sku['unit'],
                    'active_id' => $gsend['gift_send_id'],
                    'user_gift_id' => $gsend['user_gift_id'],
                );
            }
        }
        // $gifts['gsend'] = $gsend;

        // foreach ($skus as $key => $value) {
        //     $product = array(
        //         'product_name'     => $f_products[$value['product_id']]['product_name'],
        //         'product_id'       => $value['product_id'],
        //         'sale_price'       => 0,
        //         'product_photo'    => PIC_URL . ($f_products[$value['product_id']]['thum_min_photo'] ? $f_products[$value['product_id']]['thum_min_photo'] : $f_products[$value['product_id']]['thum_photo']),
        //         'unit'             => $value['unit'],
        //         'spec'             => $value['volume'],
        //         'product_price_id' => $value['id'],
        //         'product_no'       => $value['product_no'],
        //         'price'            => $value['price'],
        //         );

        //     // $product = array_merge($product,$usergift[$value['product_id']]);

        //     $gifts['products'][] = $product;
        //     $gifts['gsend'] = $gsend;
        // }

        return $gifts;
    }


    /**
     * 赠品格式化
     *
     * @return void
     * @author
     **/
    public function format_gifts_new($gsend) {
        $productids = explode(',', $gsend['product_id']);

        if (!$productids) return array();

        $this->ci->load->model('product_model');
        $gifts = array();
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
                $gifts['products'][] = array(
                    'product_name' => $product['product_name'],
                    'sale_price' => 0,
                    // 'product_photo'    => PIC_URL . ($f_products[$value['product_id']]['thum_min_photo'] ? $f_products[$value['product_id']]['thum_min_photo'] : $f_products[$value['product_id']]['thum_photo']),
                    'photo' => array(
                        'huge' => $product['bphoto'] ? PIC_URL . $product['bphoto'] : '',
                        'big' => $product['photo'] ? PIC_URL . $product['photo'] : '',
                        'middle' => $product['middle_photo'] ? PIC_URL . $product['middle_photo'] : '',
                        'small' => $product['thum_photo'] ? PIC_URL . $product['thum_photo'] : '',
                        'thum' => $product['thum_min_photo'] ? PIC_URL . $product['thum_min_photo'] : '',
                    ),
                    'unit' => $sku['unit'],
                    'spec' => $sku['volume'],
                    'product_price_id' => $sku['id'],
                    'product_no' => $sku['product_no'],
                    'price' => $sku['price'],
                    'product_id' => $sku['product_id'],
                    'qty' => $gsend['qty'],
                    'end' => $gsend['end_time'],
                    'start' => $gsend['start_time'],
                    'gift_send_id' => $gsend['gift_send_id'],
                    'gg_name' => $sku['volume'] . '/' . $sku['unit'],
                    'active_id' => $gsend['gift_send_id'],
                    'critical_status' => $gsend['critical_status'],
                    'user_gift_id' => $gsend['user_gift_id'],
                    'storeEnabled' => (int)$gsend['storeEnabled'],
                    'storeId' => (int)$gsend['storeId'],
                );
            }
        }

        return $gifts;
    }

    /**
     * 获取会员赠品
     *
     * @return void
     * @author
     **/
    public function get_user_gifts($uid, $region_id = 106092, $valid = 1, $gift_type = 2, $page = 1, $limit = -1) {
        $this->ci->load->model('user_gifts_model');

        if ($valid == 0) {
            $rows = $this->ci->user_gifts_model->get_expired_gifts($uid, $region_id, $gift_type, $page, $limit);
        } else {
            $rows = $this->ci->user_gifts_model->get_valid_gifts($uid, $region_id, $gift_type);
        }
        $trade_gifts = array();
        if ($gift_type != 1) {
            $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($uid, $region_id, $valid);
        }
        if (!empty($trade_gifts)) {
            $rows = array_merge($rows, $trade_gifts);
        }

        if (!$rows) return array();

        $ugifts = array();

        $this->ci->load->bll('cart');
        $cart_items = $this->ci->bll_cart->get_cart_items();

        $this->ci->load->bll('o2ocart');
        $o2o_cart_items = $this->ci->bll_o2ocart->get_cart_items();
        $cart_items = array_merge($cart_items, $o2o_cart_items);

        foreach ($rows as $key => $value) {
            $p = $this->format_gifts($value);

            if (!$p['products']) continue;

            foreach ($p['products'] as $k => $v) {
                // 判断是否在购物车
                $item = array(
                    'sku_id' => $v['product_price_id'],
                    'product_id' => $v['product_id'],
                    'item_type' => 'user_gift',
                    'gift_send_id' => $v['gift_send_id'],
                    // 'user_gift_id'       => $p['gsend']['user_gift_id'],
                );

                $itemkey = $this->ci->bll_cart->get_citem_key($item);
                $o2o_itemkey = $this->ci->bll_o2ocart->get_citem_key($item);
                $itemkey = array_merge($itemkey, $o2o_itemkey);

                $v['status'] = isset($cart_items[$itemkey]) ? 1 : 0;
                $v['active_type'] = $value['active_type'];
                $v['gift_source'] = $value['active_type'] == 1 ? '帐户余额充值赠品' : $value['remarks'];
                $v['gift_type'] = $value['gift_type'] ? $value['gift_type'] : 0;
                $ugifts[] = $v;
            }
        }
        return $ugifts;
    }

    /**
     * 获取会员赠品
     *
     * @return void
     * @author
     **/
    public function get_user_gifts_new($storeIdList, $uid, $valid = 1, $gift_type = 2) {
        $this->ci->load->model('user_gifts_model');

        if ($valid == 0) {
            $rows = $this->ci->user_gifts_model->get_expired_gifts_new($uid, $gift_type);
        } else if ($valid == 1) {
            $rows = $this->ci->user_gifts_model->get_valid_gifts_new($storeIdList, $uid, $gift_type);
        } else if ($valid == 2) {
            $rows = $this->ci->user_gifts_model->get_has_rec_gifts($uid, $gift_type);
        }

//        $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($uid,$region_id);
        $trade_gifts = array();
        if ($gift_type != 1) {
            $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts_new($storeIdList, $uid, $valid);
        }

        if (!empty($trade_gifts)) {
            $rows = array_merge($rows, $trade_gifts);
        }

        if (!$rows) return array();

        $ugifts = array();

        $this->ci->load->bll('cart');
        $cart_items = $this->ci->bll_cart->get_cart_items();

        $this->ci->load->bll('o2ocart');
        $o2o_cart_items = $this->ci->bll_o2ocart->get_cart_items();
        $cart_items = array_merge($cart_items, $o2o_cart_items);

        foreach ($rows as $key => $value) {
            $p = $this->format_gifts_new($value);

            if (!$p['products']) continue;

            foreach ($p['products'] as $k => $v) {
                // 判断是否在购物车
                $item = array(
                    'sku_id' => $v['product_price_id'],
                    'product_id' => $v['product_id'],
                    'item_type' => 'user_gift',
                    'gift_send_id' => $v['gift_send_id'],
                    // 'user_gift_id'       => $p['gsend']['user_gift_id'],
                );

                $itemkey = $this->ci->bll_cart->get_citem_key($item);
                $o2o_itemkey = $this->ci->bll_o2ocart->get_citem_key($item);
                $itemkey = array_merge($itemkey, $o2o_itemkey);

                $v['status'] = isset($cart_items[$itemkey]) ? 1 : 0;
                $v['active_type'] = $value['active_type'];
                $v['gift_source'] = $value['active_type'] == 1 ? '帐户余额充值赠品' : $value['remarks'];
                $v['gift_type'] = $value['gift_type'] ? $value['gift_type'] : 0;
                $v['start_time'] = $v['start_time'] . ' +86';
                $v['end_time'] = $v['end_time'] . ' +86';
                $ugifts[] = $v;
            }
        }
        return $ugifts;
    }


    /**
     * 会员赠品提醒
     *
     * @return void
     * @author
     **/
    public function get_usergift_alert($uid, $draw_usergifts = array(), $region_id = 106092, $gift_type = 2) {
        if (!$uid) return array();

        $this->ci->load->model('user_gifts_model');
        $rows = $this->ci->user_gifts_model->get_valid_gifts($uid, $region_id, $gift_type);
        $trade_gifts = array();
        if ($gift_type != 1) {
            $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($uid, $region_id, $valid = 1);
        }
        if (!empty($trade_gifts)) {
            $rows = array_merge($rows, $trade_gifts);
        }

        if (!$rows) return array();
        foreach ($rows as $key => $value) {
            if (isset($draw_usergifts[$value['gift_send_id']]) && $value['product_id'] == implode(',', $draw_usergifts[$value['gift_send_id']])) {
                unset($rows[$key]);
            }
        }

        if (!$rows) return array();

        $this->ci->load->library('terminal');
        $data = array(
            'id' => 0,
            'name' => '会员赠品提醒',
            'type' => 'usergift',           // pmt_alert统一用type
            'pmt_type' => 'usergift'            // 蔡昀辰：todo:reomove
        );
        $data['solution'] = [
            'url' => true,
            'tag' => $this->ci->terminal->is_app() ? '赠' : '', 'title' => '您还有赠品没有领取哦，快去看看吧',
            'pmt_id' => 0,
            'type' => 'usergift',           // pmt_alert统一用type
            'pmt_type' => 'usergift'            // 蔡昀辰：todo:reomove
        ];

        return $data;
    }
}
