<?php
class Warehouse_region_model extends MY_Model {

	function Warehouse_region_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'warehouse_region';
    }

}
