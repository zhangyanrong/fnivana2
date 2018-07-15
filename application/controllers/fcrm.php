<?php
/**
 * 与CRM申诉推送
 *
 *
 **/
class Fcrm extends CI_Controller
{

	//推送申诉订单
    public function fcomplaints()
    {

        $this->load->bll('crm');

        $fres = $this->bll_crm->orderComplaints();

    }

    //推送客户申诉反馈
    public function ffeedback()
    {
    	$this->load->bll('crm');

        $fres = $this->bll_crm->receiveFeedback();
    }


}
