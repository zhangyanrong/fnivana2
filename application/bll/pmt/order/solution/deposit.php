<?php
namespace bll\pmt\order\solution;

require_once('solution_abstract.php');
class Deposit extends Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($solution,&$cart_info)
    {
        $cart_info['rewards_deposit'] += $solution['bonus']; 
    }

    public function desc_txt($filter,$solution)
    {
        $data = array(
            'bonus' => $solution['bonus'], 
            'desc_txt' => '送' . $solution['bonus'] . '元预存款',
            );
        return $data;
    }

}