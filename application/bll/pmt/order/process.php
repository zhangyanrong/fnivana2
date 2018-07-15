<?php
namespace bll\pmt\order;

class Process
{
    private $_cart_info = array();

    private $_province = '106092';

    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function get_cart()
    {
        return $this->_cart_info;
    }

    public function set_cart($cart_info)
    {
        $this->_cart_info = $cart_info;
    }

    public function set_province($province)
    {
        $this->_province = $province;

        return $this;
    }

    public function cal($order_pmt_type=0)
    {
        if (!$this->_cart_info['items']) return ;

        // 参数优惠的商品额
        // $total_amount = 0;
        // foreach ($this->_cart_info['items'] as $key => $value) {
        //     if ((isset($value['active_limit']) && $value['active_limit'] == 0) && (isset($value['iscard']) && $value['iscard']==0)) {
        //         $total_amount += $value['amount'];
        //     }
        // }


        $next_pmt = array();

        $pmtlist = $this->get_pmt($order_pmt_type);

        foreach ((array) $pmtlist as $pmt) {
            $name = 'bll_pmt_order_condition_'.$pmt['pmt_type'];
            $this->ci->load->bll('pmt/order/condition/'.$pmt['pmt_type'],null,$name);

            if (!$this->ci->{$name} instanceof condition\Condition_abstract) {
                show_error("{$name} is not subclass of Condition_abstract");
            }

            $filter = $this->ci->{$name}->filter($pmt['condition'],$this->_cart_info,$this->_province);

            $cart_total_amount = $this->ci->{$name}->cart_total_amount;
            $outof_money       = $this->ci->{$name}->outof_money;

            if ($filter === false) {

                foreach ($pmt['solution'] as $type => $solution) {
                    $name = 'bll_pmt_order_solution_'.$type;
                    $this->ci->load->bll('pmt/order/solution/'.$type,null,$name);   

                    if (!$this->ci->{$name} instanceof solution\Solution_abstract) {
                        show_error("{$name} is not subclass of Solution_abstract");
                    }

                    $this->ci->{$name}->set_pmt_id($pmt['pmt_id'])->process($solution,$this->_cart_info);
                }
                // break;
            }

            if ($pmt['condition']['low_amount'] >= $cart_total_amount && $pmt['condition']['low_amount'] != $next_pmt['condition']['low_amount']) {
                $next_pmt = $pmt;
                $outofmoney = $outof_money;
            }
        }

        // 优惠提醒
        if ($next_pmt) {

            $pmt_alert = array('pmt_type' => $next_pmt['pmt_type'],'solution'=>array());
            
            $title = '满'.$next_pmt['condition']['low_amount'].'元';

            $photo = '';  //by jackchen
            foreach ($next_pmt['solution'] as $type => $solution) {
                $name = 'bll_pmt_order_solution_'.$type;
                $this->ci->load->bll('pmt/order/solution/'.$type,null,$name);

                if (!$this->ci->{$name} instanceof solution\Solution_abstract) {
                    show_error("{$name} is not subclass of Solution_abstract");
                }

                $s = $this->ci->{$name}->desc_txt($next_pmt['condition'],$solution);

                $title .= $s['desc_txt'].',';
                if(isset($s['product_photo']))
                {
                    $photo = PIC_URL.$s['product_photo'];
                }
            }

            $this->ci->load->library('terminal');
            $pmt_alert['solution']['title']      = rtrim($title,',').',还差'.$outofmoney.'元，去凑单';
            $pmt_alert['solution']['tag']        = '促';
            $pmt_alert['solution']['url']        = $this->ci->terminal->is_app() ? true : false;
            $pmt_alert['solution']['pmt_type']   = $next_pmt['pmt_type'];
            $pmt_alert['solution']['pmt_id']     = $next_pmt['pmt_id'];
            $pmt_alert['solution']['outofmoney'] = $outofmoney;
            $pmt_alert['solution']['product_photo'] = $photo;   //by jackchen

            $this->_cart_info['pmt_alert'][] = $pmt_alert;
        }
    }

    private function get_pmt($order_pmt_type=0)
    {
        static $pmtlist;

        if ($pmtlist) return $pmtlist;

        $this->ci->load->model('promotion_model');
        $order_promo = $this->ci->promotion_model->get_order_promotion($order_pmt_type);

        foreach ($order_promo as $key => $value) {
            $content = unserialize($value['content']);
            $send_region = unserialize($value['send_region']);
            $send_region = array_filter($send_region);

            if ( $send_region && $this->_province && !in_array($this->_province,$send_region) ) {
                continue;
            }

            foreach ($content as $c) {
                $pmt = array(
                    'condition' => array('low_amount' => $c['low'],'high_amount'=> $c['high']==-1 ? 100000 : $c['high'],'send_region'=>$send_region,'can_card'=>$c['can_card']),
                    'pmt_type' => 'amount',
                    'pmt_id' => $value['id'],
                );

                if ($c['money_content']) {
                    $pmt['solution']['totalamount'] = array('cut_money'=>$c['money_content']);
                }

                // if ($c['gifts_content']) {
                //     $pmt['solution']['gift'] = array('gift_id' => $c['gifts_content'],'products_num' => $c['products_num'],'is_send_more' => $c['is_send_more'],'low_amount'=>$c['low']);
                // }

                if ($c['score_content']) {
                    $pmt['solution']['score'] = array('score' => $c['score_content']);
                }

                if ($c['card_content']) {
                    $pmt['solution']['card'] = array('card_no' => $c['card_content']);
                }

                if ($c['bonus_content']) {
                    $pmt['solution']['deposit'] = array('bonus' => $c['bonus_content']);
                }

                if ($c['products_content'] && $c['gifts_content']) {
                    $pmt['solution']['gift'] = array('low_amount'=>$c['low'],'product_id'=>$c['products_content'],'products_num' => $c['products_num'],'is_send_more' => $c['is_send_more']);
                }

                $pmtlist[] = $pmt;
            }

        }

        // 按最低额度降序排
        if ($pmtlist) uasort($pmtlist, array($this,'lcmp_pmt'));

        return $pmtlist;
    }

    private function lcmp_pmt($a,$b)
    {
        if ($a['condition']['low_amount'] == $b['condition']['low_amount']) {
            return 0;
        }

        return $a['condition']['low_amount'] > $b['condition']['low_amount'] ? -1 : 1;
    }

    private function ucmp_pmt($a,$b)
    {
        if ($a['condition']['low_amount'] == $b['condition']['low_amount']) {
            return 0;
        }

        return $a['condition']['low_amount'] > $b['condition']['low_amount'] ? 1 : -1;
    }

    public function _format_pmt($pmt){

        $p = array();

        $content     = unserialize($pmt['content']);
        $send_region = unserialize($pmt['send_region']);

        foreach ($content as $c) {
            $data = array(
                'condition' => array(
                    'low_amount'  => $c['low'],
                    'high_amount' => $c['high']==-1 ? 100000 : $c['high'],
                    'send_region' => $send_region,
                    'can_card'    => $c['can_card']
                ),
                'pmt_type' => 'amount',
                'pmt_id'   => $pmt['id'],
            );

            if ($c['money_content']) {
                $data['solution']['totalamount'] = array('cut_money'=>$c['money_content']);
            }

            if ($c['score_content']) {
                $data['solution']['score'] = array('score' => $c['score_content']);
            }

            if ($c['card_content']) {
                $data['solution']['card'] = array('card_no' => $c['card_content']);
            }

            if ($c['bonus_content']) {
                $data['solution']['deposit'] = array('bonus' => $c['bonus_content']);
            }

            if ($c['products_content'] && $c['gifts_content']) {
                $data['solution']['gift'] = array(
                    'low_amount'   => $c['low'],
                    'product_id'   => $c['products_content'],
                    'products_num' => $c['products_num'],
                    'is_send_more' => $c['is_send_more']
                );
            }

            $p[] = $data;
        }

        return $p;
    }
}