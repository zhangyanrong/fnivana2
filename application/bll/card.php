<?php
namespace bll;

class Card
{
    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->model('card_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model("b2o_delivery_tpl_model");
        $this->ci->load->helper('public');
    }

    /**
     * 退卡
     *
     * @return void
     * @author
     **/
    public function return_card($uid,$card_number,$order_name='',$msg='')
    {
        if (!$card_number) return false;

        $this->ci->load->model('card_model');

        $card = $this->ci->card_model->dump(array('card_number'=>$card_number));
        if (!$card) return false;

        $set = array(
            'used_times'=> $card['used_times']-1,
            'content' => $msg,
            'is_used' => '0',
        );
        $affected_row = $this->ci->card_model->update($set,array('card_number' => $card_number));

        if ($affected_row) {
            $history = array(
                'uid'          => $uid,
                'card_number'  => $card_number,
                'order_code'   => $order_name,
                'is_cancelled' => '1',
                'created_at'   => date('Y-m-d H:i:s'),
            );

            $this->ci->load->model('card_history_model');
            $this->ci->card_history_model->insert($history);

            return true;
        }

        return false;
    }

    /**
     * 重置提货劵
     *
     * @return void
     * @author
     **/
    public function return_pro_card($uid,$order_name)
    {
        $this->ci->load->model('pro_card_model');
        $set = array(
            'is_used' => '0',
            'card_opt' => '0',
            'used_time' => '0000-00-00 00:00:00',
            // 'order_name' => '0',
        );

        $affected_row = $this->ci->pro_card_model->update($set,array('order_name'=>$order_name));

        return $affected_row ? true : false;
    }

    public function return_pro_card_new($card_number,$order_name)
    {
        $this->ci->load->model('pro_card_model');
        $set = array(
            'is_used' => '0',
            'card_opt' => '0',
            'used_time' => '0000-00-00 00:00:00',
            // 'order_name' => '0',
        );

        $affected_row = $this->ci->pro_card_model->update($set,array('card_number'=>$card_number,'order_name'=>$order_name,'is_used'=>1));

        return $affected_row ? true : false;
    }

    /**
     * 退红包
     *
     * @return void
     * @author
     **/
    public function return_hongbao($order_id,$uid)
    {
        //如有发红包则取消后收回,并更改红包状态
        $packet_number = $this->ci->db->select("card_number")
                                    ->from('red_packets')
                                    ->join('red_packets_log',"red_packets.id=red_packets_log.packet_id")
                                    ->where(array("order_id"=>$order_id,"red_packets_log.uid"=>$uid))
                                    ->get()->row_array();

        if($packet_number !=''){
            $has_card = $this->ci->db->from('card')
                                    ->where(array('card_number' => $packet_number['card_number'],'is_used !='=>1))
                                    ->get()->row_array();
            if(!empty($has_card)){
                $this->ci->db->where(array('card_number' => $packet_number['card_number']));

                $this->ci->db->update('card', array(
                    'is_sent' => 0
                ));
            }

            $this->ci->db->where(array("order_id"=>$order_id));
            $this->ci->db->update('red_packets',array("status"=>0));
        }
    }

    /**
     * @api {post} / 获取优惠券产品
     * @apiDescription 获取优惠券产品
     * @apiGroup card
     * @apiName getCardProducts
     *
     * @apiParam {Number} card_id 优惠券ID
     * @apiParam {String} [store_id_list] 门店ID，多个用英文逗号分隔
     *
     * @apiSampleRequest /api/test?service=card.getCardProducts&source=app
     */
    public function getCardProducts($params)
    {
        $required = [
            'card_id' => ['required' => ['code' => '500', 'msg' => 'card_id can not be null']],
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        // 获取优惠券信息
        $cardInfo = $this->ci->card_model->dump([
            'id' => $params['card_id'],
        ], 'product_id');
        if (!$cardInfo['product_id']) {
            return ['code' => '300', 'msg' => '优惠券不包含商品ID'];
        }

        // 获取优惠券商品列表
        $products = $this->ci->product_model->getProductByStore(explode(',', $params['store_id_list']), $params['tms_region_type'], $params['source'], ['product.id' => explode(',', $cardInfo['product_id'])]);
        $data = [];
        foreach ($products as $product) {
            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'whitebg');
                if (isset($templateImages['whitebg'])) {
                    $product['promotion_photo'] = $templateImages['whitebg']['image'];
                    $product['has_webp'] = $templateImages['whitebg']['has_webp'];
                }
            }

            // webp 图片
            if ($params['source'] == 'app' && $product['has_webp'] && $params['platform'] != 'IOS') {
                $product['promotion_photo'] = str_replace(['.jpg', '.jpeg'], '.webp', $product['promotion_photo']);
            }

            // 标签时间过滤
            if (!empty($product['promotion_tag']) && $product['promotion_tag_start'] && $product['promotion_tag_end']) {
                $iNowUnixTime = $_SERVER['REQUEST_TIME'];

                if ($iNowUnixTime < strtotime($product['promotion_tag_start']) || $iNowUnixTime > strtotime($product['promotion_tag_end'])) {
                    $product['promotion_tag'] = '';
                }
            }

            // 是否有货
            if ($product['stock'] <= 0) {
                $product['stock'] = 0;
            }

            // 配送类型
            $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(['tpl_id' => $product['delivery_template_id']], 'type');
            $deliverType2word = ['1' => '当日达', '2' => '次日达', '3' => '预售'];

            $data[] = [
                'id' => $product['product_id'],
                'promotion_tag' => $product['promotion_tag'],
                'photo' => empty($product['promotion_photo']) ? '' : cdnImageUrl($product['product_id']) . $product['promotion_photo'],
                'product_name' => $product['product_name'],
                'product_desc' => $product['product_desc'],
                'price' => $product['price'],
                'volume' => $product['volume'],
                'stock' => $product['stock'],
                'store_id' => $product['store_id'],
                'deliver_type' => $deliverType2word[$deliveryInfo['type']],
                'deliverType' => $deliverType2word[$deliveryInfo['type']],
            ];
        }

        array_multisort(array_column($data, 'stock'), SORT_DESC, SORT_NUMERIC, $data);
        return ['code' => '200', 'msg' => 'succ', 'data' => $data];
    }
}