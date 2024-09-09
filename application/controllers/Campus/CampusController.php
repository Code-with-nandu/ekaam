<?php 
defined("BASEPATH") OR exit("No direct script access allowed");
class CampusController extends CI_Controller
{
    public function index()
    {
        $this->load->model("Campus/CampusModel","CM");
        $data['asha'] = $this->CM->campus();
        $this->load->view("campus/campusView.php",$data);
    }
}

