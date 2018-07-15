<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// 开发环境监测
// 蔡昀辰 2016
class Env {
    
    public $machine_name  = '';
    private $staging_name = 'iZ23co673f6Z';
    private $lab_name     = 'iZ23us3c96bZ';
    
    public function __construct() {
        $this->machine_name = php_uname('n');
    }
    
    public function __toString() {
        if($this->isStaging())
            return 'staging';
        if($this->isLab())
            return 'lab';            
    }
    
    public function isStaging() {
        if($this->machine_name == $this->staging_name)
            return true;
    }
    
    public function isLab() {
        if($this->machine_name == $this->lab_name)
            return true;
    }
    
    public function notProd() {
        if( $this->isStaging() || $this->isLab() )
            return true;
    }
    
}