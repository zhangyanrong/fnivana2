<?php
namespace bll\pmt\order\solution;

abstract class Solution_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    abstract public function process($solution,&$cart_info);

    public function desc_txt($filter,$solution)
    {
        return array();
    }

    public function set_pmt_id($pmt_id)
    {
        $this->pmt_id = $pmt_id;

        return $this;
    }
}