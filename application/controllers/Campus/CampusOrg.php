<?php
defined("BASEPATH") OR exit("No direct script access allowed");

class CampusOrg extends CI_Controller
{
    public function index($ashram_id = "", $program_id = "", $sharing_id = "", $proceed_to_pay = "")
    {
        $data = array(
            "ashram_id" => $ashram_id,
            "program_id" => $program_id,
            "sharing_id" => $sharing_id,
            "proceed_to_pay" => $proceed_to_pay
        );

        if (trim($ashram_id) != "" && strlen($ashram_id) == 4) {
            $this->load->view('public/campus_detailed', $data);
        } else {
            $this->load->view('public/campusindex', $data);
        }
    }
}
