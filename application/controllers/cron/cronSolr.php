<?php
class CronSolr extends CI_Controller
{
    public function __construct()
    {
		parent::__construct ();
        $this->ci = &get_instance();
		$this->load->helper('public');
    }

    public function fullIndexKeywords(){
        $this->load->model('solr_index_model');
        $this->solr_index_model->upFullKeywords();
    }

    public function fullIndexProducts(){
        $this->load->model('solr_index_model');
        $this->solr_index_model->upFullProducts();
    }

    public function indexProducts(){
        $this->load->model('solr_index_model');
        $this->solr_index_model->upIndexProducts();
    }
}
