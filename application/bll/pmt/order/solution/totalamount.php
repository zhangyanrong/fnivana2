<?php
namespace bll\pmt\order\solution;

require_once('solution_abstract.php');
class Totalamount extends Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($solution,&$cart_info)
    {
        $this->ci->load->library('math');

        $totalamount = $this->ci->math->sub($cart_info['total_amount'],$solution['cut_money']);
        $totalamount = $totalamount > 0 ? $totalamount : 0;

        $cart_info['total_amount'] = $totalamount;

        $cart_info['pmt_order'] = $this->ci->math->add($cart_info['pmt_order'],$solution['cut_money']);
        $cart_info['pmt_total'] = $this->ci->math->add($cart_info['pmt_order'],$cart_info['pmt_total']);

    }

    public function desc_txt($filter,$solution)
    {        
        $data = array(
            'cut_money' => $solution['cut_money'],
            'desc_txt' => '减免' . $solution['cut_money'] . '元',
            );       
        return $data;
    }
}