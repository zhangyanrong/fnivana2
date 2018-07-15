<?php
class O2o_rpc_log_model extends MY_Model{
    // protected $_table_name = 'rpc_log';

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function table_name()
    {
        return 'o2o_rpc_log';
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    // public function __destruct()
    // {
    //     // 删除15天之前的日志
    //     $filter = array('createtime <'=>strtotime('-15 day'));

    //     $row = $this->dump($filter,'id');
    //     if ($row) {
    //         $this->delete($filter);
    //     }
    // }
}