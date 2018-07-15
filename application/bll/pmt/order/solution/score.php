<?php
namespace bll\pmt\order\solution;

require_once('solution_abstract.php');
class Score extends Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function process($solution,&$cart_info)
    {
        $cart_info['rewards_score'] += $solution['score']; 
    }

    public function desc_txt($filter,$solution)
    {         
        $data = array(
            'score' => $solution['score'],
            'desc_txt' => '送' . $solution['score'] . '积分',
            );      
        return $data;
    }
}