<?php
class B2o_parent_order_invoice_model extends MY_Model {

	function B2o_parent_order_invoice_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'b2o_parent_order_invoice';
    }
}
