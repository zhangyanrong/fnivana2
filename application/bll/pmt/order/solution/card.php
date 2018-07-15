<?php
namespace bll\pmt\order\solution;

require_once('solution_abstract.php');
class Card extends Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($solution,&$cart_info)
    {
        $cart_info['rewards_card'][] = $solution['card_no'];
    }

    public function desc_txt($filter,$solution)
    {
        $this->ci->load->model('card_model');
        $card = $this->ci->card_model->get_card_info($solution['card_no']);

        $data = array(
            'card_no' => $solution['card_no'],
            'desc_txt' => '送' . $card['card_money'] . '元优惠券',
        );

        return $data;
    }
}