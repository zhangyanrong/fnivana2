<?php
namespace bll\pmt\product\solution;

abstract class Solution_abstract
{
    
    function __construct()
    {
        $this->ci = & get_instance();
    }

    abstract public function process($itemkey,$item,$solution,&$cart_info);

    public function set_pmt_id($pmt_id)
    {
        $this->pmt_id = $pmt_id;

        return $this;
    }
}
