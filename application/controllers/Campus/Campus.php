<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


require_once(APPPATH . 'razorpay-php/Razorpay.php');

use Razorpay\Api\Api as RazorpayApi;

class Campus extends CI_Controller
{

    var $baseUrl; //// //"http://localhost/paypal/";
    var $clientId;

    var $secret;
    var $return_url;
    var $cancel_url;
    var $golive;


    function __construct()
    {
        //die("welcome");
        session_start();
        parent::__construct();
        //ini_set('display_errors', 1);
        //ini_set('display_startup_errors', 1); error_reporting(E_ERROR | E_WARNING | E_PARSE);

        //die("<html><center style='color:orange;'><h1><br><br><br> We are performing website maintainance.<br><br> Please check back in couple hours.<br><br> We will be back soon! <br>Thanks for your kind patience :-) </h1></center></html>");

        if (isset($_SESSION['publicuser'])) {
            // redirect('public/user');
            if (trim($_SESSION['profileincomplete']) != "" || !isset($_SESSION['natint'])) {
                $url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

                $escaped_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

                header("Location: " . site_url("public/user/profile") . "?next=" . urlencode($escaped_url));
                //die("<center><h1>Please <a href='".site_url("public/user/profile")."'>complete the profile</a> before proceeding..</h1></center>");
            }
            //echo "<pre>"; print_r($_SESSION); die();


        }
        //die("Server Overloaded... Please try after 1 hour.");


        $this->payusalt = "kI3R7IFH"; //LIVE              // "pjVQAWpA"; //  test
        $this->payukey = "LmSmUD"; //LIVE              //"7rnFly";
        $this->payuurl = "https://secure.payu.in"; // LIVE        // "https://test.payu.in";

        $this->golive = TRUE;

        $this->baseUrl =  site_url("public/campus/");

        $this->clientId =  "AZbsj7aehQIcbx1ZO8bZwYpftS94Q6bGSp-IPUEiDWs5E7JJriasuXjaa0EJZzSqnikTUygikbUiLRpN"; //
        $this->secret  =   "EI_fWC_yYitzhZp_zmfbwGYYiOAlahZxsaGqXKt7P_AD4Xb2mMWHm_BXv52dzqUH8OrUS5gSFS3GNiT6"; //

        $this->return_url = site_url("public/campus/return_url");
        $this->cancel_url = site_url("public/campus/cancel_url");

        $this->db = $this->load->database("default", true);
        $this->adb = $this->load->database("ashrams", true);

        if (!isset($_SESSION['paycurrency'])) {
            if ($_SESSION['natint'] == "national")
                $_SESSION['paycurrency'] = "INR";
            else
                $_SESSION['paycurrency'] = "USD";
        }
        $this->RedirectToProfile();
    }

    public function fetchCurrentUser()
    {
        echo "<pre>";
        print_r($_SESSION);
    }

    public function RedirectToProfile()
    {
        $this->load->model("Signzy_model");
        $_SESSION['error_msg'] = NULL;
        if (trim($_SESSION['publicuser']['country']) == 'India') {
            if (strlen($_SESSION['publicuser']['pan_number']) != 12) {
                $checkValidPan = $this->Signzy_model->validatePANIndi($_SESSION['publicuser']['pan_number']);
                //echo "valid PAn".$checkValidPan;
                if ($checkValidPan == "") {
                    $_SESSION['error_msg'] = 'The entered PAN is invalid';
                    redirect('public/user/profile');
                }
            }
        }
    }


    public function razorpaysuccess()
    {
        $data = [
            'razorpay_payment_id' => $this->input->post('razorpay_payment_id'),
            'amount_paid' => $this->input->post('totalAmount'),
            "status" => "CAPTURED",
            "updated_at" => time()

        ];

        // same as public/campus controller / payrazor method
        if ($_SESSION['publicuser']['id'] == '372003') {
            $secret = "XdWG8p7XhyVOVPkbo1alHaZT";  // TEST
        } else {
            $secret = "3NrcWcXUlwTc9zALkMIdQ6go"; // live
        }

        $string =  $this->input->post('razorpay_order_id') . "|" . $this->input->post('razorpay_payment_id');

        $sig = hash_hmac('sha256', $string, $secret);

        $rsig = $this->input->post('razorpay_signature');

        if ($sig == $rsig) {
            $insert = $this->adb
                ->where("razorpay_order_id", $this->input->post('razorpay_order_id'))
                ->where("id", $this->input->post('rp_id'))
                ->where('visitor_id', $this->input->post('uid'))
                ->limit(1)
                ->update('payments_razorpay', $data);

            //echo $this->adb->last_query();

            if ($this->adb->affected_rows() == 1) {
                $rega = $this->adb
                    ->where("id", intval($this->input->post('registration_id')))
                    ->where("visitor_id", $this->input->post('uid'))
                    ->limit(1)
                    ->get("registrations")->result_array();
                if (empty($rega)) die("registration not found");

                $reg = array_pop($rega);

                $this->adb
                    ->where("id", intval($this->input->post('registration_id')))
                    ->where("visitor_id", $this->input->post('uid'))
                    ->limit(1)
                    ->update(
                        "registrations",
                        array(
                            "payment_mode" => "Razorpay",
                            "comment" => " Razorpay Donation SUCCESSFUL at " . date(" d/m/Y H:i  ") . " " . $reg['comment'],
                            "status" => "CAPTURED"
                        )
                    );
                if ($this->adb->affected_rows() == 1)
                    $arr = "ok"; // array('msg' => 'Payment successfully credited', 'status' => true);
                else
                    $arr = "REGISTRATION UPDATION FAILED";
            } else
                $arr = "Unable to update";
        } else {
            $arr = "signature failed"; // array('msg' => 'Payment Saving Failed', 'status' => false);
        }
        echo ($arr);
    }  // fucntion() success



    public function sms($i = '')
    {
        $this->load->model("sms_model");
        $rega = $this->adb
            ->where("status", "CAPTURED")
            ->where("sms", "0")
            ->get("registrations")
            ->result_array();

        if (!empty($rega)) {
            foreach ($rega as $k => $reg) {
                $vid = $reg['visitor_id'];
                $va = $this->db
                    ->where("id", $vid)
                    ->get("m_visitor")
                    ->result_array();


                $orgid = substr($reg['ashram_id'], 0, 2);
                $ashid = substr($reg['ashram_id'], 2, 2);


                $aa = $this->db
                    ->select("displayname")
                    ->where("organisation_id", intval($orgid))
                    ->where("id", intval($ashid))
                    ->get("m_ashram")
                    ->result_array();


                //echo "<pre>"; print_r($aa); die($this->db->last_query());

                $vn = "One";
                $ph  = "";

                if (!empty($va)) {
                    $v = array_pop($va);
                    $vn = ucwords(strtolower($v['first_name']));
                    $ph = substr($v['mobile'], -10);
                }

                $pd = " ";


                $pa = $this->adb
                    ->where("id", $reg['program_id'])
                    ->get("programs")
                    ->result_array();

                //echo "<pre>"; print_r($pa); die($this->db->last_query());

                if (!empty($pa)) {
                    $p = array_pop($pa);
                    $pd = "for '{$p['program_name']}'.";
                }

                $rn = "";
                $an = "";

                $rid = $reg['room_id'];
                if ($rid > 0) {
                    $rna = $this->adb
                        ->where("id", $reg['room_id'])
                        ->get("m_room")
                        ->result_array();

                    if (!empty($rna)) {
                        $r = array_pop($rna);
                        $ba = $this->adb
                            ->where("id", $r['block_id'])
                            ->get("block")
                            ->result_array();
                        if (!empty($ba)) {

                            $b = array_pop($ba);
                            $rn = "Your room is {$b['name']}-{$r['room_number']}";
                        }
                    }
                }
                //else $rn = "for";

                if (!empty($aa)) {
                    $a = array_pop($aa);
                    $an2 = explode(",", $a['displayname']);
                    $an = "{$an2[0]},{$an2[1]} Campus";
                }

                //$msg = "Dear {$vn}, you have donated {$reg['currency']} {$reg['amount']}/- to {$an} {$pd} {$rn} Reg #{$reg['id']} ";
                $msg = "Dear {$vn},Thank you for your kind donation of {$reg['currency']} {$reg['amount']}/- to VVMVP towards our {$an} activities. We look forward for your continued support. Reg#{$reg['id']}";

                $cg = $this->sms_model->send($ph, $msg);

                $this->adb->where("id", $reg['id'])->limit(1)->update("registrations", array("sms" => "1"));


                echo "<br>$msg  &nbsp;<-- Chars: " . strlen($msg);

                //die();
            }
        }
    }

    public function donatenowlaunch($value = '')
    {
        /*if($_SESSION['publicuser']['id'] == '372003'){
echo"<pre>";print_r($_REQUEST);die();
}*/
        if (!isset($_POST['cid'])) {
            header("Location: " . site_url("public/campus/"));
            exit();
            // die("Invalid Data : Campus ID MISSING");
        }

        $uid = 0;
        //if(!isset($_POST['uid'])) die("Invalid Data : UID MISSING");



        if (!isset($_POST['amount'])) die("Invalid Data : Amount MISSING");

        $amount = $_POST['amount'];
        //if(!isset($amount) || intval($amount)<=49)
        //   {die("<center><h1><br><br><br> Minimum Donation Amount is INR 50/- </h1></center>");}


        $campusid = $_POST['cid'];

        //if(!isset($_POST['uid'])) die("Invalid Data : UID MISSING");
        if (!isset($_POST['purposeid'])) die("Invalid Data : purposeid MISSING");

        $_GET['donation_head'] = $_POST["purposeid"];

        $currency = "USD";

        if ($_POST["currency"] == "INR") $currency = "INR";




        if (!isset($_POST['uid']) || intval($_POST['uid']) == 0)  //
        {

            $va = $this->db->select(array("id"))->where("email", $_POST['email'])->get("m_visitor")->result_array();

            if (!empty($va)) {
                $uid = array_pop($va)['id'];
            } else /// UID to be created
            {
                if (
                    isset($_POST['email'])  &&  filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)
                    && isset($_POST['mobile'])  &&  strlen($_POST['mobile']) > 6
                    && isset($_POST['first_name'])  &&  strlen($_POST['mobile']) > 1
                    && isset($_POST['last_name'])  &&  strlen($_POST['last_name']) > 1
                ) {
                    $_POST['remail_address'] = $_POST['email'];
                    $_POST['rpassword'] = $_POST['rpassword2'] = "pass" . rand(100000, 999999);
                    $_POST['phone'] = $_POST['mobile'];
                    $_POST['fname'] = $_POST['first_name'];
                    $_POST['lname'] = $_POST['last_name'];
                    $_POST['gender'] = "M";

                    $this->load->model('publiclogin_model', 'plm');
                    $ret = $this->plm->doregister();

                    //echo "<pre>"; print_r($ret); die();

                    if ($ret['stat'] == "ok" && isset($ret['uid']) && intval($ret['uid']) > 0) {
                        $uid = $ret['uid'];
                    } else die($ret['msg']);
                } else {
                    echo "<h1><br><br><br><br>Insufficient Data to create donation record. Sorry. Please go back (press the back button of your browser) and fill the form again. Thanks";
                    die();
                }
            } // uid to be created

        } //  uid not submitted
        else {
            $uid = $_POST['uid'];
        }



        $va = $this->db->where("id", $uid)->get("m_visitor")->result_array();

        if (empty($va))  die("UID FETCHING ERROR");
        $v = array_pop($va);

        $up = array();

        if (isset($_POST['address'])  &&  strlen($_POST['address']) > 6  &&  strlen($v['address']) <= 6)
            $up['address'] = $_POST['address'];

        if (isset($_POST['country'])  &&  strlen($_POST['country']) > 2  &&  strlen($v['country']) <= 2)
            $up['country'] = $_POST['country'];

        if (isset($_POST['state'])  &&  strlen($_POST['state']) > 2  &&  strlen($v['state']) <= 2)
            $up['state'] = $_POST['state'];

        if (isset($_POST['zip'])  &&  strlen($_POST['zip']) > 2  &&  strlen($v['zip']) <= 2)
            $up['zip'] = $_POST['zip'];

        if (isset($_POST['town'])  &&  strlen($_POST['town']) > 2  &&  strlen($v['town']) <= 2)
            $up['town'] = $_POST['town'];

        if (!empty($up))
            $this->db->limit(1)->where("id", $uid)->update("m_visitor", $up);






        if ($uid <= 0) die("Could not find / create the UID");


        $comment = $campusid . " Campus Razorpay Donation of INR {$_POST['amount']}/- Initiated at " . date(" d/m/Y H:i  ") . " by {$_POST['first_name']} {$_POST['last_name']} UID {$uid} created for {$_POST['purposeid']}  | ";



        $ia = array(
            "ashram_id" => $campusid,
            "visitor_id" => $uid,
            "gender" => "",
            "amount" => $amount,
            "currency" => $currency,
            "payment_mode" => "Razorpay",
            "program_subtype_id" => "28",
            "comment" => $comment,
            "status" => "PROCESSING",
            "receipt_no" => "",
            "timestamp" => time(),
            "donation_purpose" => "Campus {$_POST['campusid']} : {$_POST['purposeid']}  ",
            "created_by" => $uid
        );

        $this->adb->insert("registrations", $ia);


        if ($this->adb->affected_rows() == 1) {
            $regid = $this->adb->insert_id();
            //header("Location: ".site_url("public/campus/payusd/".$regid));
        } else die("Could not create payment record. Campus Error #342.5");



        //CHECK IF ITS A YES TO RECURRING E-MANDATE--Starts
        if ($_REQUEST['recurring'] == 'on') {
            redirect('public/campus/recurring/?userId=' . base64_encode(trim($regid)));
        }
        //CHECK IF ITS A YES TO RECURRING E-MANDATE--Ends



        $rega = $this->adb->where("id", $regid)->get("registrations")->result_array();

        if (empty($rega)) die("could not find the registration #" . $regid);

        $va = $this->db->select(array("mobile", "email"))->where("id", $uid)->get("m_visitor")->result_array();

        if (empty($va)) die("could not find the visitor #" . $uid);
        $va = array_pop($va);

        //$vam =  $va['mobile'];
        $vae =  $va['email'];

        $reg = array_pop($rega);





        $orgid = substr($reg['ashram_id'], 0, 2);
        $ashid = substr($reg['ashram_id'], 2, 2);

        $aa = $this->db
            ->where("organisation_id", intval($orgid))
            ->where("id", intval($ashid))
            ->get("m_ashram")
            ->result_array();

        if (empty($aa))
            die("<h1>ERROR! <hr> Could not find Campus info ! </h1>");

        $aa =  array_pop($aa);

        unset($aa['description']);
        unset($aa['moreinfo']);

        //$_SESSION['razorpay']['ash'] =$aa;

        if ($reg['status'] == "CAPTURED") {
            echo "Payment Successful already! Press back button of your browser to continue.";
        } else // not captured
        {
            if (!isset($_GET['donation_head']) || trim($_GET['donation_head']) == "")
                $_GET['donation_head'] = "General donation.";


            if ($currency == "INR") {
                //echo "<pre> va : "; print_r($vam); die();

                if ($_SESSION['publicuser']['id'] == '372003') {
                    #  TEST
                    $_SESSION['razorpay']['api_key'] = 'rzp_test_92TbiZdYhCyjZk';

                    // remember to change in payments/razorpay controller as well
                    $_SESSION['razorpay']['api_secret'] = 'XdWG8p7XhyVOVPkbo1alHaZT';
                } else {
                    # LIVE  rzp_live_NqEHdfvyltFX7y,ptlXoIl1QYkkx6OO3iyg7Qc0
                    $_SESSION['razorpay']['api_key'] = 'rzp_live_36oDova5OdHkbP';

                    // remember to change in payments/razorpay controller as well
                    $_SESSION['razorpay']['api_secret'] = '3NrcWcXUlwTc9zALkMIdQ6go';
                }

                $paymentmode = "Razorpay";




                $this->adb
                    ->where("id", intval($regid))
                    ->where("visitor_id", $uid)
                    ->limit(1)
                    ->update(
                        "registrations",
                        array(
                            "payment_mode" => $paymentmode,
                            "comment" => $paymentmode . " {$currency} Donation Initiated at " . date(" d/m/Y H:i  ") . " " . $reg['comment'],
                            "donation_head" => $_GET['donation_head']
                        )
                    );

                $url = 'https://api.razorpay.com/v1/orders';
                $fields = array(
                    'currency' => urlencode("INR"),
                    'amount' => urlencode("{$reg['amount']}00"),
                    'payment_capture' => urlencode("0"),
                    'receipt' => urlencode($regid)
                );

                //url-ify the data for the POST
                foreach ($fields as $key => $value) {
                    $fields_string .= $key . '=' . $value . '&';
                }
                rtrim($fields_string, '&');

                //open connection
                $ch = curl_init();

                //set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_USERPWD, "{$_SESSION['razorpay']['api_key']}:{$_SESSION['razorpay']['api_secret']}");
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

                // Will return the response, if false it print the response
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                //execute post
                $result = curl_exec($ch);

                //close connection
                curl_close($ch);

                $r = json_decode($result, true);

                $r['razorpay_order_id'] = $r['id'];
                $r['ashram_id'] = $reg['ashram_id'];
                $r['registration_id'] = $regid;

                $r['visitor_id'] = $uid;
                $r['first_name'] = $_POST['first_name'];
                $r['last_name'] = $_POST['last_name'];
                $r['email'] = $vae;
                $r['mobile'] = $vam;

                unset($r['id']);
                $r['notes'] = json_encode($r['notes']);
                if (isset($r['offers']) && is_array($r['offers']) && !empty($r['offers']))
                    $r['offers'] = json_encode($r['offers']);
                //unset($r['offers']);
                $this->adb->insert("payments_razorpay", $r);

                if ($this->adb->affected_rows() == 1) {
                    //header("Location: https://register.vvmvp.org/ekam/razorpay");
?>
                    <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                    <script>
                        var SITEURL = "<? echo site_url(); ?>";

                        function go() {
                            var totalAmount = "<?= $reg['amount'] ?>00";
                            var options = {
                                "key": "<?= $_SESSION['razorpay']['api_key'] ?>",
                                "amount": (totalAmount), // 2000 paise = INR 20
                                "name": "Art of Living",
                                "order_id": "<?= $r['razorpay_order_id'] ?>",
                                "description": "<?= $aa['displayname'] ?>",
                                "image": "<? echo base_url(); ?>/logo.png",
                                "prefill.contact": "<?= $vam ?>",
                                "prefill.email": "<?= $vae ?>",
                                "handler": function(response) {
                                    console.log("response", response);

                                    $.ajax({
                                        url: SITEURL + '/public/campus/razorpaysuccess',
                                        type: 'post',
                                        dataType: 'json',
                                        data: {
                                            razorpay_order_id: response.razorpay_order_id,
                                            razorpay_payment_id: response.razorpay_payment_id,
                                            razorpay_signature: response.razorpay_signature,
                                            totalAmount: totalAmount,
                                            rp_id: <?= $this->adb->insert_id() ?>,
                                            uid: <?= $uid ?>,
                                            registration_id: <?= $regid ?>
                                        },
                                        complete: function(rdata, status, xhr) {
                                            console.log("rdata", rdata.responseText);

                                            if (rdata.responseText == "ok") {
                                                alert("Successful Donation!  ");
                                                window.location.href = "<?= site_url("public/campus/index/" . $reg['ashram_id']) ?>";
                                            } else
                                                alert("Could not save this payment to our server. " + rdata.responseText);
                                        }
                                    });

                                },

                                "theme": {
                                    "color": "orange"
                                }
                            };
                            var rzp1 = new Razorpay(options);
                            rzp1.open();
                        }


                        (function() {
                            go();
                        })();
                    </script>
                <?

                } else die(" Could not create the Razorpay Order!");

                //echo "<pre> asddsadd"; print_r($r);  die();

            } // INR RAZORPAY
            else  // USD PAYU
            {

                $paymentmode = "PayU";


                //$aa  = array_pop($aa);

                //echo "<pre>"; print_r($aa); die();

                $ash = array(
                    "id" => $ashid,
                    "organisation_id" => $orgid,
                    "displayname" => $aa['displayname']
                );

                $this->decohead($ash);

                echo //'<script src="'. base_url().'public/js/paypalcheckout.js"></script>
                '<div id="box">
';

                if ($reg['program_subtype_id'] == "28") {
                    $f = "";
                    if (isset($_SESSION['research_uid']))
                        $f =  "Towards Sri Sri Institute for Advanced Research ";
                    if (isset($_SESSION['donateprojectudaan_uid']))
                        $f =  "Towards Project Udaan ";

                    echo "<center><h1 style='color:#f00; background-color:lightyellow;border:13px double darkgrey; padding:10px;'>PLEASE NOTE! <br> This is a voluntary contribution only {$f} ! </h1></center>";
                }

                $total_price = $_POST['amount'] = $reg['amount'] . ".00";
                $currency = $reg['currency'];

                $_POST['firstname'] = $va['first_name'];
                $_POST['lastname'] = $va['last_name'];
                $_POST['email']  = $vae; //= $_POST['udf5']

                $_POST['phone'] = ""; // preg_replace('/\D/', '',  $_SESSION['publicuser']['mobile']  );
                $_POST['productinfo'] = "Reg Id " . intval($regid) . " " . $reg['donation_purpose'];


                //$_POST['lastname'] = "Reg Id ".intval($regid);
                $_POST['address1'] = "UID " . $reg['visitor_id'];
                $_POST['address2'] = "CAMPUS " . $reg['ashram_id'];
                //$_POST['phone'] = $_POST['phone'] ;


                // "CUR ".$reg['currency'];

                // echo "<pre>"; print_r($_SESSION['publicuser']); die(" try later");
                //echo "<pre>"; print_r($reg);

                $paya = $this->adb
                    ->where("registration_id", intval($regid))
                    ->where("visitor_id", $reg['visitor_id'])
                    ->get("payments_payu")
                    ->result_array();

                $showppb = FALSE;
                $successfulpayment = FALSE;


                //if($reg['status']=="success") unset($paya);


                // past payment for this registration found
                if (!empty($paya)) {
                ?>
                    <style type="text/css">
                        td,
                        th {
                            padding: 15px;
                            color: white;
                        }
                    </style>
                    <center>
                        <h2>This is your payment history for the selected program/donation</h2><br>
                        <table border=1>
                            <tr>
                                <th>#</th>
                                <th>Transaction Id</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                            <?

                            $count = 0;
                            $showppb = TRUE;

                            foreach ($paya as $key => $pay) {
                                $count++;
                                //  <th>Transaction Id</th <td>{$pay['txn_id']}</td
                                echo "<tr> <td> {$count} </td><td> {$pay['txnid']} </td><td> {$pay['amount']} </td><td> {$pay['currency_code']} </td><td> {$pay['status']} </td><td> " . date("H:i:s d M Y", $pay['timestamp_initiated']) . " </td> </tr>";

                                if (trim(strtolower($pay['status'])) == "success") {
                                    $showppb = FALSE;
                                    $successfulpayment = TRUE;
                                } else {
                                }
                            }

                            echo "</table></center>";

                            if (intval($go) != 0) //  override go
                            {
                                $showppb = TRUE;
                                echo "<h2>Overriding</h2>";
                            }
                        } else //first time payment initiation
                        {
                            $showppb = TRUE;
                        }

                        if ($successfulpayment == FALSE && $count > 0 && !$showppb) {
                            echo "<center> <a class='btn btn-warning' href='" . site_url("public/campus/payusd/{$regid}/1") . "'> Make Another Payment Attempt ? </a></center>";
                        }

                        if ($reg['status'] == "CAPTURED") {
                            $showppb = FALSE;
                            echo "<center><h2 style='color:green;'> Registration #{$regid} is SUCCESSFUL. </h2></center>";
                            if (trim($reg['receipt_no']) == "") // Loading your receipt
                            { ?>
                                <center>
                                    ... Please wait.
                                </center>
                                <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                                <script type="text/javascript">
                                    console.log("init");
                                    setTimeout(function() {
                                        $.get("<?= site_url("public/generatereceipts") ?>", function(data, status) {

                                            window.location = window.location;
                                        });
                                    }, 5000);
                                </script>
                            <? } else {
                                echo "<br><Center><a class='btn btn-default' href='" . site_url("public/campus/receipt/" . $reg['id']) . "' target='reg{$reg['id']}'>Login and Download PDF Receipt for this Registration.</a></center>";
                            }

                            //$this->alloc($regid);
                            //if($reg['program_subtype_id']!="28"  && $reg['room_id']=="0")
                            //$this->alloc($reg['id']);

                        } // captured

                        if ($showppb) {
                            ?>
                            <div class="row">
                                <div class='col-xs-12'>
                                    <br>

                                    <p class="" style='text-align: center; color:white;'><i>I understand that the donations are <u>non-refundable</u> and registrations are <u>non-transferable</u>.<br>
                                            <? echo " Continue Donating {$currency} {$total_price}/-  by clicking the orange checkout button below :"; ?>
                                        </i></p>
                                    <?php

                                    // Merchant key here as provided by Payu
                                    $MERCHANT_KEY = $_POST['key'] =  $this->payukey; //  "gtKFFx"; //Please change this value with live key for production
                                    $hash_string = '';
                                    // Merchant Salt as provided by Payu
                                    $SALT = $salt = $this->payusalt; //Please change this value with live salt for production

                                    // End point - change to https://secure.payu.in for LIVE mode
                                    $PAYU_BASE_URL = $this->payuurl; // "https://test.payu.in";

                                    $action = '';

                                    $posted = array();
                                    if (!empty($_POST)) {
                                        //print_r($_POST);
                                        foreach ($_POST as $key => $value) {
                                            $posted[$key] = $value;
                                        }
                                    }


                                    $posted['firstname'] = $va['first_name'];

                                    $posted['lastname'] = $va['last_name'];

                                    $formError = 0;


                                    $_POST['txnid'] = $posted['txnid'] = $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);



                                    // Hash Sequence
                                    $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";

                                    $hashVarsSeq = explode('|', $hashSequence);

                                    $testHashSequence = "";

                                    foreach ($hashVarsSeq as $hash_var) {
                                        $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
                                        $hash_string .= '|';
                                    }

                                    $hash_string .= $SALT;

                                    $_POST['hash'] = $hash = strtolower(hash('sha512', $hash_string));

                                    $action = $PAYU_BASE_URL . '/_payment';

                                    //echo "<pre>"; print_r($posted); die("<hr>testHashSequence <br>".$hash_string );
                                    try {


                                        $uid = $reg['visitor_id'];

                                        $email = $vae;


                                        $ia = array(
                                            "registration_id" => intval($reg['id']),
                                            "visitor_id" => $uid,
                                            "txnid" => $txnid,
                                            "amount" => $posted['amount'],
                                            "currency_code" => "USD",
                                            "payer_email" => $email,
                                            "status" => "Payment Initiated",
                                            "timestamp_initiated" => time(),
                                            "productinfo" => $posted['productinfo'],
                                            "firstname" => $posted['firstname'],
                                            "lastname" => $posted['lastname'],
                                            "state" => $posted['address1'],
                                            "country" => $posted['address2']
                                        );

                                        $this->adb->insert("payments_payu", $ia);


                                        $DR = "Registration";
                                        if ($reg['program_subtype_id'] == "28")
                                            $DR = "Donation";

                                        $this->adb
                                            ->where("id", intval($reg['id']))
                                            ->limit(1)
                                            ->update(
                                                "registrations",
                                                array(
                                                    "comment" => "PAYU {$DR} Initiated at " . date('Y-m-d H:i:s', time()),
                                                    "payment_mode" => "PAYU"
                                                )
                                            );

                                        $_SESSION['donatenowuser'] = array("id" => $uid);
                                    } catch (Exception $e) {
                                        echo " Could not save the transaction details. Please retry later.";
                                    }
                                    ?>

                                    <script>
                                        var hash = '<?php echo $hash ?>';
                                    </script>
                                    <CENTER>

                                        <?php if ($formError) { ?>
                                            <span style="color:red"> Sorry. Unable to get the payment ready for processing at this time.. please report this error to the website manager. </span>
                                            <br />
                                            <br />
                                        <?php } else {  ?>
                                            <form action="<?php echo $action; ?>" method="post" name="payuForm">
                                                <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY ?>" />
                                                <input type="hidden" name="hash" value="<?php echo $hash ?>" />
                                                <input type="hidden" name="txnid" value="<?php echo $txnid ?>" />

                                                <input type="hidden" name="surl" value="<? echo site_url(); ?>/public/campus/responseusd" />
                                                <input type="hidden" name="furl" value="<? echo site_url(); ?>/public/campus/responseusd" />
                                                <input type="hidden" name="curl" value="<? echo site_url(); ?>/public/campus/responseusd" />





                                                <input type="hidden" name="amount" value="<?php echo (empty($posted['amount'])) ? '999999' : $posted['amount'] ?>" />
                                                <input type="hidden" name="firstname" id="firstname" value="<?php echo (empty($posted['firstname'])) ? '' : $posted['firstname']; ?>" />
                                                <input type="hidden" name="email" id="email" value="<?php echo (empty($posted['email'])) ? '' : $posted['email']; ?>" />
                                                <input type="hidden" name="phone" value="<?php echo (empty($posted['phone'])) ? '' : $posted['phone']; ?>" />

                                                <input type="hidden" name="productinfo" value="<?php echo (empty($posted['productinfo'])) ? '' : $posted['productinfo'] ?>" />
                                                <input type="hidden" name="address1" value="<?php echo (empty($posted['address1'])) ? '' : $posted['address1']; ?>" />
                                                <input type="hidden" name="address2" value="<?php echo (empty($posted['address2'])) ? '' : $posted['address2']; ?>" />
                                                <input type="hidden" name="lastname" value="<?php echo (empty($posted['lastname'])) ? '' : $posted['lastname']; ?>" />
                                                <input type="hidden" name="city" value="USD" />
                                                <? /*
<input type="hidden"  name="udf5" value="<?php echo (empty($posted['udf5'])) ? '' : $posted['udf5']; ?>" />
*/ ?>
                                                <input type="hidden" name="pg" value="<?php echo (empty($posted['pg'])) ? '' : $posted['pg']; ?>" />
                                                <input type="submit" value="Pay using Debit or Credit Card" class='btn btn-default btn-lg ' />

                                            </form>
                                        <? } ?>
                                    </CENTER>
                                </div>
                            </div>


                            <style>
                                /* Remove the navbar's default margin-bottom and rounded borders */
                                .navbar {
                                    margin-bottom: 0;
                                    border-radius: 0;
                                }

                                /* Set height of the grid so .sidenav can be 100% (adjust as needed) */
                                .row.content {
                                    height: 450px
                                }

                                /* Set gray background color and 100% height */
                                .sidenav {
                                    padding-top: 20px;
                                    background-color: #f1f1f1;
                                    height: 100%;
                                }



                                /* Set black background color, white text and some padding */
                                footer {
                                    background-color: #555;
                                    color: white;
                                    padding: 15px;
                                }

                                td {
                                    background-color: lightgrey;
                                }

                                th {
                                    background-color: darkgrey;
                                    color: white;
                                }

                                td,
                                th {
                                    text-align: center;
                                    padding: 10px;

                                }

                                /* On small screens, set height to 'auto' for sidenav and grid */
                                @media screen and (max-width: 767px) {
                                    .sidenav {
                                        height: auto;
                                        padding: 15px;
                                    }

                                    .row.content {
                                        height: auto;
                                    }
                                }
                            </style>
                            <!-- PAYPAL END -->

                <?
                        }

                        if ($reg['status'] != "CAPTURED")
                            echo "<center style='color:yellow; font-size:25px;'>  Note: Click on the 'Pay using Debit or Credit Card' button above to initiate the payment. </center>
<script>



setTimeout(function(){

//window.location = window.location;
},75000);

</script>
</div>
</body></html>";

                        die();
                    } // USD PAYU
                } // else // not captured
            } // donatenow launch()


            public function recurring()
            {
                $this->db = $this->load->database("default", true);
                $this->adb = $this->load->database("ashrams", true);

                $did = $_GET['userId'];
                $did = base64_decode($did);
                $data['did'] = $did;
                $data['page_name'] = "recurring";
                $data['page_title'] = 'Recurring_payment';
                //print_r($this->session->userdata());die;
                if ($did != '') {
                    $this->adb->where('id', $did);
                }
                //$this->db->where('dtstatus',1'');
                $donations = $this->adb->get('registrations')->row_array();
                if ($donations != "") {
                    $data['donations'] = $donations;
                } else {
                    $data['donations'] = '';
                }
                $this->load->view('public/header');

                $this->load->view("public/recurring", $data);
            }


            public function DonationSubscription($did)
            {
                $this->db = $this->load->database("default", true);
                $this->adb = $this->load->database("ashrams", true);
                if ($did != '') {
                    $this->adb->where('id', $did);
                }
                //$this->db->where('dtstatus',1'');
                $getU = $this->adb->get('registrations')->row_array();
                //echo"<pre>";print_r($getU);die;
                if ($_POST) {
                    $pdata['period'] = $_POST['period'];
                    $pdata['interval'] = $_POST['interval'];
                    $pdata['plan_start_date'] = date('Y-m-d H:i:s', strtotime($_POST['plan_start_date']));
                    $pdata['plan_end_date'] = date('Y-m-d H:i:s', strtotime($_POST['plan_end_date']));
                }
                $upq = $this->adb->where('id', $did)->update('registrations', $pdata);
                if ($upq) {
                    $response = $this->callPlanApi($did);
                } else {
                    $response = 'Blank';
                }

                //echo "Response ".$response;
                echo '<h3 style"color:orange;">subscription_link_has_sent_to_you_please_check_your_inbox_for_more_details</h3>';
                redirect($_SERVER['HTTP_REFERER']);
            }


            public function callPlanApi($did)
            {
                $this->db = $this->load->database("default", true);
                $this->adb = $this->load->database("ashrams", true);
                if ($did != '') {
                    $this->adb->where('id', $did);
                }
                //$this->db->where('dtstatus',1'');
                $getU = $this->adb->get('registrations')->row_array();

                $getUserd = $this->db->where('id', trim($getU['visitor_id']))->get('m_visitor')->row_array();
                $getU['country'] = trim($getUserd['country']);
                $getU['first_name'] = trim($getUserd['first_name']);
                $getU['last_name'] = trim($getUserd['last_name']);
                $getU['email'] = trim($getUserd['email']);
                $getU['mobile'] = trim($getUserd['mobile']);

                $postfields = array();
                $period = $getU['period'];
                $interval = $getU['interval'];
                $postfields['period'] = $period;
                $postfields['interval'] = $interval;
                $item['name'] = 'Plan ' . $getU['id'] . ' - ' . $period;
                $item['amount'] = $getU['amount'];
                $curr = trim($getU['currency']);
                $country = $getU['country'];
                $item['currency'] = $curr;
                $item['description'] = 'Plan Request Generated by ' . $getU['first_name'] . ' ' . $getU['last_name'];
                $postfields['item'] = $item;
                //echo"<pre>";print_r($postfields);
                $jsonDataEncoded = json_encode($postfields);

                if ($getU['plan_id'] == NULL || $getU['plan_id'] == '') {
                    try {
                        if ($curr == 'INR') {
                            $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                        } else {
                            echo "Internation Subscriptions Not Allowed Yet!!";
                            die();
                        }
                        $plan = $api->plan->create(
                            array('period' => $postfields['period'], 'interval' => $postfields['interval'], 'item' => array('name' => 'Donation Plan', 'description' => 'Description for donation Id' . $did, 'amount' => $item['amount'] * 100, 'currency' => $curr), 'notes' => array('donation_id' => $getU['id'], 'txn_details' => $did))
                        );
                        //echo"result <pre>";print_r($res);
                    }
                    //catch exception
                    catch (Exception $e) {
                        echo 'Plan Error Message: ' . $e->getMessage();
                        die;
                    }


                    //echo"<pre>";print_r($plan->id);die;
                    $udata['plan_id'] = $plan->id;
                    $this->adb->where('id', $did)->update('registrations', $udata);
                    $plan_id = $plan->id;
                } else {
                    $plan_id = $getU['plan_id'];
                }

                $st_date = strtotime($getU['plan_start_date']);
                $en_date = strtotime($getU['plan_end_date']);

                $nom = $this->get_no_of_months($st_date, $en_date);
                $nom = $nom - 1;

                $quantity = $nom / $interval;


                /*$subArray =  array('plan_id' => $plan_id,'total_count' => 1,'quantity' => 1,'expire_by' => $en_date,'customer_notify' => 1, 'addons' => array(array('item'=>array('name' => 'VVMVP Donation Subscription','amount' => $getU['amount']*100,'currency' => $curr))),'notes'=>array('donation_id'=> $getU['id'],'txn_details'=> $did),'notify_info'=>array('notify_phone' => $getU['mobile'],'notify_email'=> $getU['email']));*/
                $subArray =  array('plan_id' => $plan_id, 'total_count' => 1, 'quantity' => 1, 'expire_by' => $en_date, 'customer_notify' => 1, 'notes' => array('donation_id' => $getU['id'], 'txn_details' => $did), 'notify_info' => array('notify_phone' => $getU['mobile'], 'notify_email' => $getU['email']));
                //echo"subArray<pre>";print_r($subArray);
                if ($getU['plan_subs_id'] == NULL || $getU['plan_subs_id'] == '') {
                    //echo "plan_subs_id null hai";
                    $subs = $this->callSubsApi($did, $subArray);
                    //echo"<pre>";print_r($api);
                    //echo"sub api call <pre>";print_r($subs);die;
                    $udata['plan_subs_id'] = $subs->id;
                    $this->adb->where('id', $did)->update('registrations', $udata);
                    $plan_subs_id = $subs->id;
                } else {
                    $plan_subs_id = $getU['plan_subs_id'];
                }
                return $plan_subs_id;
            }


            public function get_no_of_months($date1, $date2)
            {
                $date1 = date('Y-m-d', $date1);
                $date2 = date('Y-m-d', $date2);
                $begin = new DateTime($date1);
                $end = new DateTime($date2);
                $end = $end->modify('+1 month');

                $interval = DateInterval::createFromDateString('1 month');

                $period = new DatePeriod($begin, $interval, $end);
                $counter = 0;
                foreach ($period as $dt) {
                    $counter++;
                }

                return $counter;
            }

            public function callSubsApi($did, $subArray)
            {
                $this->db = $this->load->database("default", true);
                $this->adb = $this->load->database("ashrams", true);
                if ($did != '') {
                    $this->adb->where('id', $did);
                }
                //$this->db->where('dtstatus',1'');
                $getU = $this->adb->get('registrations')->row_array();

                $getUserd = $this->db->where('id', trim($getU['visitor_id']))->get('m_visitor')->row_array();
                $getU['country'] = trim($getUserd['country']);
                $getU['first_name'] = trim($getUserd['first_name']);
                $getU['last_name'] = trim($getUserd['last_name']);
                $getU['email'] = trim($getUserd['email']);
                $getU['mobile'] = trim($getUserd['mobile']);
                $curr = trim($getU['currency']);
                $key_id = 'rzp_live_u62LRst1sQn2Nm';
                $secret = 'P34sCha6SFang3IZDeqUl2Kp';
                //echo"$subArray ";print_r($subArray);die;
                try {
                    //$razorpayOrder = $api->order->create($orderData);
                    if ($curr == 'INR') {
                        $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                    } else {
                        echo "Internation Subscriptions Not Allowed Yet!!";
                        die();
                    }
                    $res = $api->subscription->create($subArray);
                    //echo"result <pre>";print_r($res);
                    return $res;
                }
                //catch exception
                catch (Exception $e) {
                    echo 'Message: ' . $e->getMessage();
                    die;
                }
                //echo"<pre> api";print_r($api);//die;
                //
                //echo"<pre> subs";print_r($api);die;
            }


            public function donatenow($ashram_id = '', $currency = '', $donationhead = '')
            {

                //if($currency == 'USD' && $ashram_id == '0115' && $donationhead == 'SadhuBandar-Annadaan'){
                if ($currency == 'USD') {
                    echo "<center><br><br><h1 style='color:orange; '>Error #87 : Sorry, we can't let you access this page. </h1></center>";
                    redirect(base_url());
                }

                $data = array();
                $asha = $this->db
                    ->get("m_ashram")
                    ->result_array();

                //test



                if (empty($asha)) die("CAMPUS SETTINGS ERROR");

                if (strlen($ashram_id) != 4) {
                    header("Location: " . site_url("public/donatenow"));
                }


                $this->load->view('public/header');

                // get ashram  & cache it
                $orgid = substr($ashram_id, 0, 2);
                $ashid = substr($ashram_id, 2, 2);

                $data = array(
                    "orgid" => $orgid,
                    "ashid" => $ashid,
                    "ashram_id" => $ashram_id
                );

                //echo " <pre> [{$orgid}][{$ashid}] ";


                $asha = $this->db
                    ->where("organisation_id", intval($orgid))
                    ->where("id", intval($ashid))
                    ->get("m_ashram")
                    ->result_array();
                if (empty($asha)) {
                    echo "<center><br><br><h1 style='color:orange; '>Error #210 : Sorry, Invalid Campus details provided.</h1></center>";
                    die(" ");
                }

                $ash  = array_pop($asha);
                unset($asha);

                $ashram_name = $data['ashram_name'] = $ash['name'];
                $ashram_dname = $data['ashram_dname'] = $ash['displayname'];
                $ashram_csv_donationheads =  explode(",",  urldecode($ash['csv_donationheads']));
                $donationhead = urldecode($donationhead);


                //print_r($ashram_csv_donationheads);
                $data['currency'] = $currency;
                $data['donationhead'] = $donationhead;
                $data['ashram_csv_donationheads'] = $ashram_csv_donationheads;

                $data['countries'] = $this->db->order_by('order', 'asc')->get("m_country")
                    ->result_array();
                $data['states'] = $this->db->where('active', '1')->get("m_india_states")
                    ->result_array();

                $this->load->view("public/donatenowcampus", $data);
            }




            public function donate($special = "")
            {

                if (!isset($_SESSION['publicuser'])) {
                    if (isset($_POST['ashram']) && intval($_POST['ashram_id']) > 1)
                        redirect('public/welcome/login/?next=' . urlencode(site_url("public/campus/index/{$_POST['ashram_id']}/?amount={$_POST['amount']}#donate")));
                    else
                        redirect('public/welcome/login/');
                    exit();
                }




                if ($_SESSION['natint'] == "national")
                    $_SESSION['paycurrency'] = "INR";
                else
                    $_SESSION['paycurrency'] = "USD";



                //die($_SESSION['paycurrency']);



                if ($_SESSION['paycurrency'] == "INR") {
                    if (!isset($_POST['amount']) || intval($_POST['amount']) < 99) {
                        die("<center><h1><br><br><br> Minimum Donation Amount is INR 100/- </h1></center>");
                    }
                } else // USD
                {
                    if (!isset($_POST['amount']) || intval($_POST['amount']) < 1) {
                        die("<center><h1><br><br><br> Minimum Donation Amount is USD 10/- </h1></center>");
                    }
                }

                if (!isset($_POST['ashram_id']) || intval($_POST['ashram_id']) < 100) {
                    die("<center><h1><br><br><br>Campus Id missing. </h1></center>");
                }

                $comment = "PayPal Donation Initiated at " . date(" d/m/Y H:i  ") . $comment;



                $ashram_id_my = $_POST['ashram_id'];
                $ia = array(
                    "ashram_id" => $_POST['ashram_id'],
                    "visitor_id" => $_SESSION['publicuser']['id'],
                    "gender" => strtoupper($_SESSION['publicuser']['gender'][0]),
                    "amount" => $_POST['amount'],
                    "currency" => $_SESSION['paycurrency'], //"INR",
                    "payment_mode" => "PayPal",
                    "program_subtype_id" => "28",
                    "comment" => $comment,
                    "status" => "PROCESSING",
                    "timestamp" => time(),
                    "created_by" => $_SESSION['publicuser']['id']
                );


                $this->adb->insert("registrations", $ia);
                if ($this->adb->affected_rows() == 1) {
                    $regid = $this->adb->insert_id();
                    header("Location: " . site_url("public/campus/pay/" . $regid . "/" . $ashram_id_my));
                }
            }




            public function receipts()
            {

                if (!isset($_SESSION['publicuser']))   redirect('login/logout');

                $ash = array(
                    "id" => "02",
                    "organisation_id" => "01",
                    "displayname" => "Other Campus",
                    ""
                );
                $this->decohead($ash);
                if (!isset($_SESSION['publicuser'])) {
                    redirect('login/logout');
                }

                $uid = $_SESSION['publicuser']['id'];

                $rega =  $this->adb
                    ->where("visitor_id", $uid)
                    ->where("status", "CAPTURED")
                    ->order_by("id", "desc")
                    ->get("registrations")
                    ->result_array();

                if (!empty($rega)) {

                    echo "<center><a href='" . site_url("public/welcome") . "?receipts' class='btn btn-default'>Bangalore Ashram receipts</a><br>" . count($rega) . " Receipt(s) Found!<hr></center>";

                    $c = 0;
                    foreach ($rega as $key => $r) {
                        $c++;
                        echo "<br> {$c}.  Receipt #{$r['id']} of <a  href='" . site_url("public/campus/receipt/" . $r['id']) . "' target='r{$c}'>{$r['currency']} {$r['amount']}/-  </a>";
                    }
                } else {
                    echo "<h1><center><br><br><br><br>No Receipts Found!";
                }
                echo " <hr><br>";
            }




            public function receipt($regid = "0")
            {
                if (!isset($_SESSION['publicuser']))   redirect('login/logout');


                $uid = $_SESSION['publicuser']['id'];

                $rega =  $this->adb
                    ->where("id", $regid)
                    ->limit(1)
                    ->get("registrations")
                    ->result_array();

                if (!empty($rega)) {
                    $reg = array_pop($rega);

                    if (isset($_GET['html'])) {
                        $this->load->model("ashram_model");
                        $this->ashram_model->receipt($regid);
                    } else

if ($reg['visitor_id'] == $uid || $reg['created_by'] == $uid) {
                        $this->load->model("ashram_model");
                        $this->ashram_model->receipt($regid);
                    } else {
                        header("Location: https://twitter.com/srisri");
                    }
                } else {
                    header("Location: https://artofliving.org/");
                }
            }




            public function decohead($ash)
            {
                $this->load->view('public/header');

                //$ash  =  $_SESSION['ash'];


                /*
<!DOCTYPE html>
<html lang="en">
<head>

<!-- Bootstrap Core CSS -->
<link href="https://register.vvmvp.org/ekam/public/css/bootstrap.min.css" rel="stylesheet">
<!-- Custom Fonts -->
<link href="https://register.vvmvp.org/ekam/public/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="The Art of Living Foundation">
<meta name="description" content="Ashram Donations Page">
*/ ?>

                <!-- Bootstrap core CSS -->
                <link href="<? echo base_url(); ?>css/bootstrap.min.css" rel="stylesheet">
                <!-- Bootstrap theme -->
                <link href="<? echo base_url(); ?>css/bootstrap-theme.min.css" rel="stylesheet">
                <title><?= $ash['displayname'] ?> Registration Page</title>
                <!-- jQuery -->
                <script src="<? echo base_url(); ?>/public/js/jquery-1.10.2.min.js"></script>
                <!-- Bootstrap Core JavaScript -->
                <script src="<? echo base_url(); ?>/public/js/bootstrap.min.js"></script>

                <style type="text/css">
                    #box {
                        clear: both;
                        color: white;
                        background-color: #1aa8c6;
                        width: 100%;
                        padding: 20px;
                        text-align: left;
                        margin-top: 20px;
                        border: 15px double white;
                        border-radius: 20px;
                    }

                    body,
                    .login {
                        background-color: #9ce6f0;
                    }

                    .sn {
                        background-color: yellow;
                        color: red;
                        font-size: 15px;
                    }
                </style>

                </head>

                <body>
                    <nav class="navbar navbar-default navbar-fixed-top topnav" role="navigation">
                        <div class="container topnav">
                            <!-- Brand and toggle get grouped for better mobile display -->
                            <div class="navbar-header">
                                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                                <a class="navbar-brand topnav" href="#"><img src='<? echo  base_url(); ?>logo.png' alt='Art of Living Logo' style='height:30px;' /></a>
                            </div>
                            <? echo "
<link rel='stylesheet' href='" . base_url() . "css/jquery-ui.css'>
<script src='" . base_url() . "js/jquery-ui.js'></script>

"; ?>
                            <!-- Collect the nav links, forms, and other content for toggling -->
                            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                                <ul class="nav navbar-nav navbar-right">
                                    <li> <a href="<? echo site_url("public/welcome/"); ?>">Home</a> </li>
                                    <li> <a href="<? echo site_url("public/campus/"); ?>">Campuses</a> </li>
                                    <li> <a href="<? echo site_url("public/welcome/faqs#{$ashram_name}"); ?>">FAQs</a> </li>
                                    <li>
                                        <?
                                        if (isset($_SESSION['publicuser'])) {
                                        ?>
                                            <a href='<? echo site_url("public/login/logout"); ?>'>Logout</a>
                                        <?
                                        } else {
                                        ?>
                                            <a href='<? echo site_url("public/welcome/login/"); ?>'>Login</a>
                                        <?
                                        }
                                        ?>
                                    </li>
                                </ul>
                            </div>
                            <!-- /.navbar-collapse -->
                        </div>
                        <!-- /.container -->
                    </nav>
                    <br style="clear:both;"><br>
                    <div class="login">
                        <div class="container">
                            <br><br>
                            <center>
                                <a href='<?= site_url("public/campus/") ?>' class='btn btn-default'>Go to Dashboard</a>
                                &nbsp;
                                <a href='<?= site_url("public/campus/index/" . $ash['organisation_id'] . $ash['id']) ?>' class='btn btn-default'>Go to <?= $ash['displayname'] ?> Campus Page</a>

                            </center>


                            <style type="text/css">
                                .github-ribbon {
                                    background-color: #8c8888;
                                    top: 8.5em;
                                    left: -3.7em;
                                    -webkit-transform: rotate(-45deg);
                                    -moz-transform: rotate(-45deg);
                                    -ms-transform: rotate(-45deg);
                                    -o-transform: rotate(-45deg);
                                    transform: rotate(-45deg);
                                    -webkit-box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                                    -moz-box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                                    -ms-box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                                    -o-box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                                    box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                                    color: rgba(255, 255, 255, 0.9);
                                    display: block;
                                    padding: .6em 3.5em;
                                    position: fixed;
                                    font: bold .82em sans-serif;
                                    text-align: center;
                                    text-decoration: none;
                                    text-shadow: 1px -1px 8px rgba(0, 0, 0, 0.6);
                                    -webkit-user-select: none;
                                    -moz-user-select: none;
                                    -ms-user-select: none;
                                    -o-user-select: none;
                                    user-select: none;
                                    z-index: 111;
                                }
                            </style>



                            <a class="github-ribbon" href="#" title="<?= $ash['displayname'] ?>"><?= $ash['displayname'] ?></a>
                            <?

                        }

                        public function register($ashram_id = 0, $program_id = 0, $uid = 0, $sharing_id = 0, $confirmed = 0)
                        {

                            $sevakloggedin = false;
                            if (isset($_SESSION['department']) && $_SESSION['department'] == "ashram" && $ashram_id == $_SESSION['m_ashram_id']) {
                                $sevakloggedin = true;
                                //print_r($_SESSION);
                                //echo "</pre>";
                            }


                            if (!isset($_SESSION['publicuser'])) {
                                redirect('public/welcome/login/?next=' . urlencode(site_url("public/campus/register/{$ashram_id}/{$program_id}/{$uid}")));
                                exit();
                            }

                            if (!isset($_SESSION['countrygroupid'])) {
                                $_SESSION['countrygroupid'] = 4;
                                $_SESSION['paycurrency'] = "USD";
                                $_SESSION['FORCEDcountrygroupidANDpaycurrency'] = "YES";

                                $country = $_SESSION['publicuser']['country_last_year'];

                                if (trim($country) == "") {
                                    $country = $_SESSION['publicuser']['country'];
                                    if ($sevakloggedin) echo "<p class='sn'>Sevak Note: 'country_last_year' is blank so using 'country' field value: {$country}</p>";
                                }

                                if (trim($country) == "") {
                                    $country = "India";
                                    if ($sevakloggedin) echo "<p class='sn'>Sevak Note: 'country' is blank so using 'India'</p>";
                                }

                                $coua = $this->db
                                    ->where("name", trim($country))
                                    ->get("m_country")
                                    ->result_array();


                                if (!empty($coua)) {
                                    $_SESSION['countrygroupid'] = array_pop($coua)["group_id"];
                                    if ($_SESSION['countrygroupid'] == "0" && $_SESSION['natint'] == "national") {
                                        $_SESSION['paycurrency'] = "INR";

                                        $_SESSION['FORCEDcountrygroupidANDpaycurrency'] = "NO";

                                        if ($sevakloggedin) echo "<p class='sn'>Sevak Note: Pay currency is assumed to be INR </p>";
                                    }
                                }
                            }



                            if ($_SESSION['natint'] == "national")
                                $_SESSION['paycurrency'] = "INR";
                            else
                                $_SESSION['paycurrency'] = "USD";


                            if (isset($_GET['test'])) {
                                //  echo "<pre>"; print_r($coua); die();
                            }



                            $d = FALSE;
                            //if(isset($_GET['debug'])) $d = TRUE;

                            // Need to be logged in



                            // get ashram  & cache it
                            $orgid = substr($ashram_id, 0, 2);
                            $ashid = substr($ashram_id, 2, 2);

                            //echo " <pre> [{$orgid}][{$ashid}] ";


                            $asha = $this->db
                                ->where("organisation_id", intval($orgid))
                                ->where("id", intval($ashid))
                                ->get("m_ashram")
                                ->result_array();
                            if (empty($asha)) {
                                echo "<center><br><br><h1 style='color:orange; '>Error #67 : Sorry, Invalid Campus details provided.</h1></center>";
                                die(" ");
                            }

                            $ash  = array_pop($asha);
                            unset($asha);
                            //$_SESSION['ash'] = array($ashram_id=>$ash);



                            $ashram_name = $ash['name'];
                            $ashram_dname = $ash['displayname'];



                            $this->decohead($ash);


                            if ($sevakloggedin) echo "<br><br><br><br> <p class='sn' style='font-size:32px;'>Sevak Note: <br>  All the things in Yellow color and some details in between them are only visible to logged in Sevaks (adminitstrative login) of THIS campus only!  This page is showing information for the LOGGED IN PUBLIC USER : {$_SESSION['publicuser']['first_name']}  {$_SESSION['publicuser']['last_name']}</p><br><br><br><br> ";

                            if ($uid == 0) // considering logged in user
                                $uid = $_SESSION['publicuser']['id'];

                            if ($d) echo "<br> UID: {$uid} ";


                            // get user  & cache it
                            if (!isset($_SESSION['rv'][$uid])) {

                                $va =  $this->db
                                    ->where("id", intval($uid))
                                    ->limit(1)
                                    ->get("m_visitor")
                                    ->result_array();

                                // no user found
                                if (empty($va)) {
                                    echo "<center><br><br><h1 style='color:orange; '>Error #65 : Unable to find user #{$uid} .</h1></center>";
                                    die(" ");
                                }

                                $v = array_pop($va);
                                unset($va);

                                $_SESSION['rv'] = array($uid => $v);
                            } else {
                                $v = $_SESSION['rv'][$uid];
                            }






                            if ($d) echo "<br> {$v['first_name']}  {$v['last_name']}  --  country: <b>{$v['country']} </b> country_last_year : <b>{$v['country_last_year']}</b>   ";


                            // if country is India but last year field is empty.. then allow
                            if (strtolower($v['country']) == "india" && trim(strtolower($v['country_last_year'])) == "") {
                                $v['country_last_year'] = "india";

                                if ($d) echo "<br> Overriding and allowing this profile to be registered .   ";
                            }

                            /*
if (strpos($_SERVER['REQUEST_URI'], 'public/campus/register/0111') === false)  // NOT JAIPur
{
// is user allowed?
if(strtolower($v['country'])!="india" || strtolower($v['country_last_year'])!="india" )
{
echo "<center><br><br><h1 style='color:orange; '>Error #74 :Currently, only Indian nationals are allowed to register online for campus other than Bangalore Campus. Profile with UID #{$v['id']} of {$v['first_name']} {$v['last_name']} does not qualify.   </h1></center>";
die(" ");
}
}
else echo " <h1> JAIPUR INTERNATIONL TESTING RULE EXCEPTION IS:  ON!</h1>";
*/


                            // is program public?  get program & cache it

                            if (!isset($_SESSION['rprogram_id'][$program_id])) {
                                $pa = $this->adb
                                    ->where("id", $program_id)
                                    ->where("ashram_id", $ashram_id)
                                    ->where("public", "1")
                                    ->get("programs")
                                    ->result_array();

                                if (empty($pa)) {
                                    echo "<center><br><br><h1 style='color:orange; '>Error #87 : Sorry, could not find program #{$program_id}  for public viewing</h1></center>";
                                    die(" ");
                                }

                                $p = array_pop($pa);
                                unset($pa);
                                $_SESSION['rprogram_id'] = array($program_id => $p);
                            } else {
                                $p = $_SESSION['rprogram_id'][$program_id];
                            }



                            if ($d) echo "<br> program_name : {$p['program_name']} <pre><small><small>  "; //
                            if ($d) print_r($p);
                            if ($d) echo "</small></small>";


                            echo "<div id='box'><center><h1><br> <b>{$p['program_name']}</b></h1><h3>at {$ash['displayname']} </h3>";

                            echo "<p>Program Starts on  " . date("d M y", strtotime($p['program_start'])) . " </p>";
                            echo "<p>Program Ends on " . date("d M y", strtotime($p['program_end'])) . " </p>";

                            /*
echo "<p>Registration Start {$p['regstart']}</p>";
echo "<p>Registration End {$p['regend']}</p>";


echo "<p>Arrival {$p['arrival']}</p>";

echo "<p>Departure {$p['departure']}</p>";

echo "<p><i>".strip_tags($p['program_description'])."</i></p>";

echo "<h4>Requesting Registration in <br>"; */

                            echo " <h4>Program #{$p['id']}</h4> ";
                            echo "<h2>for {$v['first_name']}  {$v['last_name']}</h2> </center>";





                            //are the registration dates stil open?
                            if (intval($p['program_start']) < 20180505 || intval($p['program_end']) < 20180505 || intval($p['regend']) < 20180505 || intval($p['regend']) < 20180505 || intval($p['arrival']) < 20180505 || intval($p['departure']) < 20180505) {
                                echo "<center><br><br><h1 style='color:orange; '>Error #104 : Sorry, the dates are not configured properly yet for program #{$program_id} .</h1></center>";
                                //if($d) echo "Adarsh bhaiya, here is link you can open in private window. <br>".site_url("trustoffice/manage/programs/edit/{$p['id']}");
                                die(" ");
                            } else {
                                if ($p['regend'] >= date("Ymd") && $p['regstart'] <= date("Ymd")  && $p['program_start'] >= date("Ymd")) {
                                    // reg dates are ok
                                    if ($sevakloggedin) echo "<p class='sn'>Sevak Note: Registration dates are ok </p>";
                                } else {
                                    if ($p['program_start'] < date("Ymd"))
                                        echo "<center><br><br><h1 style='color:orange; '>Error #119 : Sorry, this program #{$program_id} has already started.</h1></center>";
                                    else

                                        echo "<center><br><br><h1 style='color:orange; '>Error #122 : Sorry, this program #{$program_id} registration dates are from {$p['regstart']} till {$p['regend']} only today is " . date("Ymd") . ".</h1></center>";
                                    die(" ");
                                }
                            }



                            if ($d) echo "<br> #130 reg from {$p['regstart']} till {$p['regend']}   ";




                            #######    NAT INT ALLOWED

                            // die( "<pre> NATINT:".$_SESSION['natint']." na : ".$p['nationals_allowed']." ia : ".$p['internationals_allowed']);

                            if ($_SESSION['natint'] == "national" && $p['nationals_allowed'] == "0") // not allowed
                            {
                                echo "<center><br><br><h1 style='color:orange; '>Error #55 : Sorry, Indian Nationals are not allowed for program #{$program_id} at this time.</h1></center>";
                                die(" ");
                            }


                            if ($_SESSION['natint'] != "national" && $p['internationals_allowed'] == "0") // not allowed
                            {
                                echo "<center><br><br><h1 style='color:orange; '>Error #55 : Sorry, Internationals are not allowed for program #{$program_id} at this time.</h1></center>";
                                die(" ");
                            }

                            #######    NAT INT ALLOWED

                            $this->load->model("ashram_model");

                            // get sharing & cache it
                            if (!isset($_SESSION['sha'])) {


                                $shaa = $this->adb->get("sharing")->result_array();

                                $sha = array();

                               foreach ($shaa as $key => $sh) {

                                 $sha[$sh['id']] = $sh['name'];



                                }
                                unset($shaa);

                                $_SESSION['sha'] = $sha;


                            } else {
                                $sha = $_SESSION['sha'];
                            }


                            // is this person already registered? - NOT cached
                            $rega = $this->adb
                                ->where("program_id", $program_id)
                                ->where("ashram_id", $ashram_id)
                                ->where("visitor_id", $v['id'])
                                ->get("registrations")
                                ->result_array();


                            $getdonationsandcapacity = TRUE;

                            $alreadyregistered = "";

                            # ALREADY REGISTERED!

                            if (!empty($rega)) {
                                $reg = array_pop($rega);
                                $alreadyregistered = "<br> {$v['first_name']}'s request with id #{$reg['id']} for this program ";


                                foreach ($sha as $shid => $shname) {
                                    if ($shid == $reg['sharing_id']) {
                                        $alreadyregistered .= " with {$shname} accomodation preference ";
                                    }
                                }

                                $status = $reg['status'];
                                if ($reg['status'] == "CAPTURED") {
                                    $status = "SUCCESSFUL";
                                }

                                $alreadyregistered .= ", is currently with status : <b>" . $status . "</b>";

                                if ($reg['status'] != "CAPTURED") {
                                    $alreadyregistered .= "<br><center> <a class='btn btn-warning  btn-lg' href='" . site_url("public/campus/pay/{$reg['id']}/") . "'>Make Payment</a></center>  ";
                                    $alreadyregistered =  "<br><center>" . $alreadyregistered . "</center>";
                                } else {
                                    $getdonationsandcapacity = FALSE;

                                    echo "<br><center>" . $alreadyregistered . "</center>";

                                    die();
                                }
                            } else // NOT REGISTERED
                            {
                                if ($d) echo "<br>{$v['first_name']} request not found for program: {$p['program_name']}  ";
                            }


                            if ($getdonationsandcapacity) {

                                // Get campus capacities & cache it
                                if (!isset($_SESSION['capa'][$ashram_id])) {
                                    $capa = $this->adb
                                        ->select(" sum(`room_capacity_current`) as `cc`,`room_type` ")
                                        ->group_by("`room_type`")
                                        ->where("ashram_id", $ashram_id)
                                        ->get("m_room")
                                        ->result_array();



                                    //

                                    $_SESSION['capa'] = array($ashram_id => $capa);
                                } else {
                                    $capa = $_SESSION['capa'][$ashram_id];
                                }




                                //echo "<pre>";    print_r($capa); die();

                                if (empty($capa)) {
                                    echo "<center><br><br><h1 style='color:orange; '>Error #139 : Incorrect Room Configuration for this Campus #{$ashram_id}</h1></center>";
                                    die(" ");
                                }


                                //  room types available

                                $rt = "";
                                $rta = array();
                                $rtc = array();
                                if (!empty($capa)) {
                                    foreach ($capa as $ucak => $capaa) {
                                        if ($capaa['cc'] > 0) {
                                            //$rt .= "<div id='rt{$capaa['room_type']}' class='rt' rt='{$capaa['room_type']}'><br>Loading Room Type : {$capaa['room_type']}</div>";
                                            $rtc[$capaa['room_type']] = $capaa['cc'];

                                            $rta[$capaa['room_type']] = $capaa['room_type'];
                                        }
                                    }
                                }

                                //  if($_SESSION['publicuser']['id']=="1")  { echo "<pre>";    print_r($rtc); die(); }

                                //  get donation settings for available room types


                                if (!isset($_SESSION['dona'][$ashram_id][$p['program_subtype_id']])) {

                                    $dona = $this->adb

                                        ->where("ashram_id", $ashram_id)
                                        ->where("program_subtype_id", $p['program_subtype_id'])
                                        ->where("deleted", 1)
                                        ->get("donations")
                                        ->result_array();
                                        
                                    $_SESSION['dona'] = array(
                                        $ashram_id => array(
                                            $p['program_subtype_id'] => $dona
                                        )
                                    );
                                } else {
                                    $dona = $_SESSION['dona'][$ashram_id][$p['program_subtype_id']];
                                }




                                $capdon = array();
                              
                                if (!empty($dona)) {
                                    foreach ($dona as $dkey => $don) {
                                  //      echo "room_id "."{$don['sharing_id']}";
                                        if ($d) echo "<br> Room Type " . $don['sharing_id'] . " Capacity :" . $rtc[$don['sharing_id']] . " seats  &nbsp; &nbsp;   donation " . $don['INR'] . " Teacher donation " . $don['teacher_INR'];

                                        if ($rtc[$don['sharing_id']] > 0) // capacity found!
                                        {
                                          // echo "{$don['sharing_id']}";
                                            if ($d)  $rt .= "<br><div id='rt{$don['sharing_id']}' class='rt' rt='{$don['sharing_id']}'>Loading Room Type : {$don['sharing_id']} it has Capacity of {$rtc[$don['sharing_id']]}</div>";
                                            $capdon[$don['sharing_id']] = $don;
                                            $capdon[$don['sharing_id']]['capacity'] = $rtc[$don['sharing_id']];

                                        }
                                    }
                                }
                                if ($d) echo "<hr>" . $rt;

                                if ($d) echo "<br> Campus Max Capacity + Donations Settings found :  <pre><small><small>  "; //
                                //if($d) print_r($capdon);
                                if ($d) echo "</small></small>";

                                //   if($_SESSION['publicuser']['id']=="1")  {   echo "<pre>";   print_r($rtc);   print_r($dona);    print_r($capdon); die(); }


                                if ($sevakloggedin) {
                                    echo "<pre class='sn'>Donations settings :<hr>";
                                    foreach ($capa as $key => $c) {
                                        echo "<br><br><br><b>{$_SESSION['sha'][$c['room_type']]}</b><br> &nbsp;  &nbsp; Total Capacity :  {$c['cc']}  beds  <br> &nbsp;  &nbsp; Donations Settings:  ";

                                        $ds = 0;
                                        foreach ($dona as $dkey => $d1) {
                                            //print_r($c);

                                            if ($c['room_type'] == $d1['sharing_id']) {
                                                //print_r($d);
                                                foreach ($d1 as $dk => $dv) {
                                                    $sk = array("id", "ashram_id", "program_subtype_id", "country_group_id", "sharing_id");
                                                    if ($dv > 0 && !in_array($dk, $sk)) {
                                                        echo " <br> &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; &nbsp; {$dk}  : " . $dv;
                                                        $ds++;
                                                    }
                                                }
                                                if ($ds == 1)
                                                    echo " <br><p><h1 style='color:white;background-color:red;'><u>DONATION SETTINGS NEEDED TO <br>ALLOW REGISTRATION OF <br><br>{$_SESSION['sha'][$c['room_type']]}<br><br> BEDS!</u> </h1></p>";
                                            }
                                        }
                                    }


                                    //print_r($_SESSION);              echo "<p class='sn'>Sevak Note: 'country_last_year' is blank so using 'country' field value: {$country}</p>";
                                    echo " </pre>";
                                }


                                // checking availibility for each sharing

                                
                                if (!empty($capdon)) {

                                    /*
$oldregsharingid = 0;
$oldSelectedSharingIdAvailable = FALSE;


if(isset($reg))
if($reg['status']!="CAPTURED")
{
$oldregsharingid  = $reg['sharing_id'];
}
*/
                                    echo "<center>";

                                    if ($sharing_id == 0)
                                        echo "Please choose the seat type :<br>";


                                    foreach ($capdon as $key => $cd) {
                                        //echo "{$cd['sharing_id']}";
                                        $roomallocated = $this->ashram_model->getsharingavailibility($ashram_id, $cd['sharing_id'], $program_id, $v['gender'][0], $p['arrival'], $p['departure'], $mode = "silent");

                                        if ($roomallocated > 0) {
                                            if ($d) echo "<br>Can be allocated in room {$roomallocated} ";
                                            if (isset($sha[$cd['sharing_id']])) {
                                                if ($sharing_id == 0) {
                                                    /*
if(isset($reg))
if($reg['status']!="CAPTURED")
{
if($oldregsharingid  == $cd['sharing_id'])
{
$oldSelectedSharingIdAvailable = TRUE;
}
}

//{$cd['INR']} / {$cd['teacher_INR']}
*/

                                                    echo "  <a class='btn btn-default btn-md' href='" . site_url("public/campus/register/{$ashram_id}/{$program_id}/{$uid}/{$cd['sharing_id']}") . "'>" . $sha[$cd['sharing_id']] . " </a> "; //
                                                } else if ($sharing_id == $cd['sharing_id']) {

                                                    $amount = 999999;

                                                    //echo "<pre>"; print_r($cd); echo "</pre>";

                                                    if ($_SESSION['paycurrency'] == "INR") {
                                                        # IS A TEACHER
                                                        if ($v['vvki_confirmed_teacher'] == "1") {
                                                            echo "<h5> Teacher Registration </h5>";
                                                            # DONATION FOR TEACHER IS DEFINED
                                                            if (intval($cd['teacher_INR']) > 0) {
                                                                $amount = intval($cd['teacher_INR']);
                                                                $comment = " Teacher Scholarship Payment INR {$amount}/- ";
                                                            } else  # USE NORMAL DONATION
                                                            {
                                                                $amount = intval($cd['INR']);
                                                                $comment = " Teacher But Normal Payment INR {$amount}/-  ";
                                                            }
                                                        } else  # NOT A TEACHER
                                                        {
                                                            $amount = intval($cd['INR']);
                                                            $comment = " Normal Payment INR {$amount}/- ";
                                                        }
                                                    } else  // USD
                                                    {

                                                        # IS A TEACHER
                                                        if ($v['vvki_confirmed_teacher'] == "1") {
                                                            echo "<h5> Teacher Registration </h5>";

                                                            # DONATION FOR TEACHER IS DEFINED
                                                            if (intval($cd['teacher_USD' . $_SESSION['countrygroupid']]) > 0) {
                                                                $amount = intval($cd['teacher_USD' . $_SESSION['countrygroupid']]);
                                                                $comment = " Teacher Scholarship Payment USD {$amount}/- ";
                                                            } else  # USE NORMAL DONATION
                                                            {
                                                                $amount = intval($cd['USD' . $_SESSION['countrygroupid']]);
                                                                $comment = " Teacher But Normal Payment USD {$amount}/-  ";
                                                            }
                                                        } else  # NOT A TEACHER
                                                        {
                                                            $amount = intval($cd['USD' . $_SESSION['countrygroupid']]);
                                                            $comment = " Normal Payment USD {$amount}/- ";
                                                        }
                                                    }

                                                    if ($amount <  999990 && $amount > 0) {


                                                        $arrival = $p['arrival'];
                                                        $departure = $p['departure'];

                                                        $comment = "PayPal Registration Initiated at " . date(" d/m/Y H:i  ") . $comment;

                                                        $ia = array(

                                                            "sharing_id" => $sharing_id,
                                                            "ashram_id" => $ashram_id,
                                                            "program_subtype_id" => $p['program_subtype_id'],
                                                            "program_id" => $program_id,
                                                            "visitor_id" => $uid,
                                                            "gender" => strtoupper($v['gender'][0]),
                                                            "amount" => $amount,
                                                            "currency" => $_SESSION['paycurrency'], //"INR",
                                                            "arrival" => $arrival,
                                                            "departure" => $departure,
                                                            "payment_mode" => "PayPal",
                                                            "comment" => $comment,
                                                            "status" => "PROCESSING",
                                                            "timestamp" => time(),
                                                            "created_by" => $_SESSION['publicuser']['id']



                                                        );
                                                        if (isset($_POST['ans']) && isset($_POST['ques'])) {
                                                            $aa = array();
                                                            if (!empty($_POST['ans']) && !empty($_POST['ans'])) {
                                                                foreach ($_POST['ans'] as $key => $a) {
                                                                    $aa[$_POST['ques'][$key]] = $a;
                                                                }
                                                                $ia['answers'] = json_encode($aa);
                                                            }
                                                        }

                                                        if (trim($p['questions']) != "" && !isset($_POST['ans'])) $confirmed = 0;

                                                        if ($confirmed == 1) {

                                                            $this->adb->insert("registrations", $ia);
                                                            if ($this->adb->affected_rows() == 1) {
                                                                $regid = $this->adb->insert_id();
                                                                echo "<br><br><br><br><center><h1 style='color:green;'>SUCCESS! Saved the data <br> <a class='btn btn-md btn-warning' href='" . site_url("public/campus/pay/{$regid}/") . "'>Click Here To Proceed</a></h1></center>";
                                                                echo "<script>window.location='" . site_url("public/campus/pay/{$regid}/") . "'</script>";
                                                            } // success
                                                            else {
                                                                echo "<br><br><br><br><center><h1 style='color:red;'>ERROR #355! Could not save the request for registration</h1></center>";
                                                            } //error
                                                        } else  // not yet confirmed
                                                        {
                                                            echo " <br><center> ";
                                                            if ($amount > 0) {
                            ?>
                                                                <script type="text/javascript">
                                                                    function gog() {

                                                                        var a = 0;
                                                                        $(".ans").each(function() {
                                                                            console.log("qa", $(this).attr("placeholder"), $(this).val());
                                                                            if ($.trim($(this).val()) != "") {
                                                                                a++;
                                                                            }
                                                                        });
                                                                        console.log("a ans", a, $(".ans").length);
                                                                        if (a == $(".ans").length) {
                                                                            $("#frm").submit();
                                                                        } else {
                                                                            alert("Please fill answers to all the questions.");
                                                                        }


                                                                    }
                                                                </script>
                                                                <style type="text/css">
                                                                    .ans {
                                                                        color: black;
                                                                        background-color: gold;
                                                                    }
                                                                </style>
                                                                <form id='frm' method="post" action='<?= site_url("public/campus/register/{$ashram_id}/{$program_id}/{$uid}/{$cd['sharing_id']}") ?>/1/'>
                                    <?
                                                                if (trim($p['questions']) != "") {

                                                                    $qa = array_filter(explode("|", $p['questions']));
                                                                    if (!empty($qa)) {
                                                                        foreach ($qa as $key => $q) {
                                                                            echo "<br> {$q} <br> <input type='text' name='ans[]' class='ans' placeholder='Your Answer' /><input type='hidden' name='ques[]' class='ques' value='" . trim($q) . "' /> ";
                                                                        }
                                                                    }
                                                                }

                                                                echo "</form> <a  onclick='gog();' class='btn btn-md btn-warning' href='#'>Click here to Donate {$_SESSION['paycurrency']} {$amount} </a>

<br>or <br>";
                                                            } else
                                                                echo " Donation Settings not set yet!";

                                                            echo "  <a class='btn btn-md btn-info' href='" . site_url("public/campus/register/{$ashram_id}/{$program_id}/{$uid}/") . "'>Go back to previous page </a>

</center>";
                                                        }
                                                    } else # INVALID AMOUNT
                                                    {
                                                        echo "<br style='clear:both;'><br>Though seats are available, the donation settings NOT VALID ({$_SESSION['natint']} {$_SESSION['paycurrency']} {$amount}/-) for {$sha[$cd['sharing_id']]} sharing accomodation, thus online payment is not possible at the moment.<br>"
                                                            . "<br>Please Contact <a href='mailto:{$ash['email']}?subject=Donation Settings not valid for {$sha[$cd['sharing_id']]} sharing accomodation in Program #{$program_id} &body=Jai Gurudev, The Donation Settings not valid for {$sha[$cd['sharing_id']]} sharing accomodation in Program #{$program_id}'>{$ash['email']}</a> for assistance.";


                                                        $chatid = "555747775"; // omkar
                                                        $msg = " Donation settings for {$sha[$cd['sharing_id']]} sharing accomodation in Program #{$program_id} , Country Group #{$_SESSION['countrygroupid']} for UID {$_SESSION['publicuser']['id']}  {$_SESSION['publicuser']['first_name']}  {$_SESSION['publicuser']['last_name']} from  {$_SESSION['publicuser']['town']}, {$_SESSION['publicuser']['state']}  {$_SESSION['publicuser']['country']}  {$_SESSION['publicuser']['email']}   ";
                                                        $this->load->model('telegram_model', 'telegram');
                                                        $this->telegram->send($chatido, $msg);
                                                    }
                                                } // matching sharing selected


                                            }
                                        } else {
                                            if ($d) echo "<br>Can NOT be allocated in room  type {$cd['sharing_id']} ";
                                            if ($sevakloggedin) {
                                                echo "<p class='sn' style='font-size:18px;'>Can not be allocated in room type {$_SESSION['sha'][$cd['sharing_id']]} for program id {$program_id} for gender {$v['gender'][0]}  between {$p['arrival']} and  {$p['departure']} ! <br><br><br>Before jumping up and down that registration are not happening, please check the room settings, status of the room, capacity, if there is already booking for those days in that room (even if there is one person of other gender in that room, it obviously cant be used!) .<br><br> :-)  ";
                                                $roomallocated = $this->ashram_model->getsharingavailibility($ashram_id, $cd['sharing_id'], $program_id, $v['gender'][0], $p['arrival'], $p['departure'], $mode = "notsilent");
                                                echo "<p class='sn' style='font-size:18px;'> END OF TRIAL FOR ATTEMPT TO CHECK IF BED IS AVAILABLE IN  room type {$_SESSION['sha'][$cd['sharing_id']]} for program id {$program_id} for gender {$v['gender'][0]}  between {$p['arrival']} and  {$p['departure']} </p> <hr>";
                                            }
                                        }
                                    }
                                    echo "<br><br><small>(Country Group #{$_SESSION['countrygroupid']})</small></center>";
                                } //capdon
                            } // getdonationsandcapacity
                            //echo "<br><p><center><i>Kindly note that requested sharing type is completely subject to availibility at the time of arrival.</i><br>".date('l jS \of F Y h:i:s A')." IST </center></p></div>";
                            echo "<br><p><center><i>Kindly note that, in case of residential programs, the requested seat type is completely subject to availibility at the time of arrival at the venue.</i></p></div>";
                        } // END OF FUNCTION register()



                        public function test_alloc($regid = "", $mode = "silent")
                        {
                            if ($regid > 0) {


                                $rega =  $this->adb
                                    ->where("id", $regid)
                                    ->limit(1)
                                    ->get("registrations")
                                    ->result_array();

                                echo "<pre> Before";

                                print_r($rega);


                                $this->adb
                                    ->where("id", $regid)
                                    ->limit(1)
                                    ->update(
                                        "registrations",
                                        array(
                                            "status" => "CAPTURED",
                                            "lastupdated" => time()
                                        )
                                    );

                                $this->load->model("ashram_model");

                                $aa = $this->ashram_model->allocateroom(intval($regid), $mode);
                                if ($aa  > 0) {
                                    echo "allocate reg #{$regid} in room " . $aa;
                                }

                                $rega =  $this->adb
                                    ->where("id", $regid)
                                    ->limit(1)
                                    ->get("registrations")
                                    ->result_array();

                                echo "<pre> AFTER ";

                                print_r($rega);

                                //else echo " not allocated reg  #{$regid} "; //debug

                                //echo "<pre>".$this->adb->last_query(); print_r($rega);

                            } else echo "zero";
                        }



                        public function alloc($regid = 0)
                        {
                            if ($regid > 0) {
                                $this->load->model("ashram_model");
                                $aa = $this->ashram_model->allocateroom(intval($regid), $mode = "silent");
                            }
                        }



                        public function allowed($value = '')
                        {
                            if (0) {
                                if (strtolower($_SESSION['publicuser']['country']) != "india" || strtolower($_SESSION['publicuser']['country_last_year']) != "india") {
                                    echo "<center><br><br><h1 style='color:orange; '>Currently, only Indian nationals are allowed to register online for Campus other than Bangalore Campus.</h1></center>";
                                    die(" ");
                                }
                            }
                            // else echo "<br><br><br><br> <h1> JAIPUR INTERNATIONL TESTING RULE EXCEPTION IS:  ON!</h1>";
                        }




                        public function index($ashram_id = "", $program_id = "", $sharing_id = "", $proceed_to_pay = "")
                        {

                            // die("You are indexed ");
                            $data =  array(
                                "ashram_id" => $ashram_id,
                                "program_id" => $program_id,
                                "sharing_id" => $sharing_id,
                                "proceed_to_pay" => $proceed_to_pay
                            );

                            //if(isset($_SESSION['publicuser']))
                            //  $allowed = $this->allowed();

                            if (trim($ashram_id) != "" && strlen($ashram_id) == 4) {
                                $this->load->view('public/campus_detailed', $data);
                            } else {
                                $this->load->view('public/campusindex', $data);
                            }
                        }

                        public function createpayment($regid = '')
                        {
                            if (!isset($_SESSION['publicuser'])) {
                                redirect('public/login');
                            }
                            $this->allowed();

                            $baseUrl = $this->baseUrl;
                            $clientId = $this->clientId;
                            $secret = $this->secret;
                            $return_url = $this->return_url . '/' . $regid;
                            $cancel_url = $this->cancel_url . '/' . $regid;


                            $rega = $this->adb
                                ->where("id", intval($regid))
                                // ->where("visitor_id",$_SESSION['publicuser']['id'])
                                ->get("registrations")
                                ->result_array();


                            if (empty($rega))
                                die("Registration NOT found!");

                            $reg = array_pop($rega);

                            $product_price = $reg['amount'] . ".00";
                            $total_price = $reg['amount'] . ".00";
                            $currency = $reg['currency'];
                            $product_name = $reg['program_id'];

                            $orgid = substr($reg['ashram_id'], 0, 2);
                            $ashid = substr($reg['ashram_id'], 2, 2);

                            //echo " <pre> [{$orgid}][{$ashid}] ";

                            $asha = $this->db
                                ->where("organisation_id", intval($orgid))
                                ->where("id", intval($ashid))
                                ->get("m_ashram")
                                ->result_array();

                            //print_r($asha);
                            if (empty($asha)) die("Error #384 ... Campus Configuration.");

                            $ash = array_pop($asha);

                            $ashrammerchantid = $ash['paypal_merchantid'];





                            $v = $_SESSION['publicuser'];

                            $addy = $v['address'];

                            if (strtolower($v['country']) != "india") {
                                $addy = "";
                            }

                            $country_code = "IN";


                            $addya = explode("||||", wordwrap($addy, 99, "||||"));
                            if (!isset($addya[0]))
                                $addya[0] = "";
                            if (!isset($addya[1]))
                                $addya[1] = "";

                            $mobile = substr($v['mobile'], -10);
                            if (!isset($_SESSION['publicuser']['mobile'])) {
                                $mobile = "9876543210";
                            }

                            $town = $v['town'];
                            $state = $v['state'];
                            $zip = substr($v['zip'], 0, 6);


                            if (!isset($_SESSION['publicuser']['address'])) {
                                $addya[0] = $town =  $addya[1] =   " fill ";
                                $zip = "560082";
                                $state = "Karnataka";
                            }

                            $zip = "560082";


                            $addya[0] = substr($addya[0], 0, 48);
                            $addya[1] = substr($addya[1], 0, 48);



                            $ch = curl_init();
                            $json_data = json_encode(
                                array(
                                    "intent" => "sale",
                                    "redirect_urls" => array(
                                        "return_url" => $return_url,
                                        "cancel_url" => $cancel_url
                                    ),
                                    "payer" => array(
                                        "payment_method" => "paypal",
                                        "payer_info" => array(
                                            "email" => $v['email']
                                        )
                                    ),
                                    "experience_profile_id" => "XP-W9XQ-HMH7-BJ3N-G7NP", //Your experience profile id
                                    "transactions" =>  array(
                                        array(
                                            "payee" => array(
                                                "merchant_id" => $ashrammerchantid
                                            ),
                                            "amount" => array(
                                                "total" => $total_price,
                                                "currency" => $currency
                                            ),
                                            "description" => "UID# {$reg['visitor_id']} at Campus #{$reg['ashram_id']} for Prog Id {$reg['program_id']}  Reg Id# {$reg['id']} .",

                                            "item_list" => array(
                                                "items" => array(
                                                    array(
                                                        "quantity" => "1",
                                                        "name"      => $product_name,
                                                        "price"     => $product_price,
                                                        "currency"  => $currency,
                                                        "description" => $product_name,
                                                        "tax"   => 1
                                                    )
                                                ),
                                                "shipping_address" => array(

                                                    "recipient_name" => $v['first_name'] . " " . $v['last_name'],

                                                    // "line1"=>  "Please enter address here",




                                                    //"line1"=>  preg_replace('/[^\p{L}\p{N}]/u', ' ', trim($addy ) ),

                                                    // stripcslashes( $addy),

                                                    // $addy,
                                                    "line1" =>   $addya[0],
                                                    "line2" =>  $addya[1],

                                                    "city" => $town,

                                                    "state" => $state,

                                                    "postal_code" => $zip, // $v['zip'],

                                                    "country_code" => $country_code

                                                ),

                                                "shipping_phone_number" => $mobile
                                            )
                                        )
                                    )
                                )
                            );







                            //if($this->golive)
                            curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/payments/payment/");
                            //else
                            //   curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/payment/");

                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                            $result = curl_exec($ch);
                            curl_close($ch);

                            //print_r($result); die($result);


                            echo $result;

                            $jr = json_decode($result, true);

                            if (trim($jr["id"]) == "") die("NO Payment ID returned by PayPal Server!");

                            $data = array();
                            $data['visitor_id'] = $v['id'];
                            $data['pay_id']    = $jr["id"];
                            $data['registration_id']    = $reg['id'];
                            $data['payment_gross'] = $reg['amount'] . ".00";
                            $data['currency_code'] = $reg['currency'];
                            $data['payer_email'] = $v['email'];
                            $data['payment_status']    = "Payment Initiated";
                            $data['timestamp']    = time();



                            //check whether the payment is verified
                            if (preg_match("/VERIFIED/i", $result)) {
                                //insert the transaction data into the database
                                $data['payment_status']    = "Payment Init Verified";
                            }

                            $this->adb->insert("payments", $data);
                        }



                        public function cancel_url($regid = '')
                        {
                            $this->executepayment($regid);
                        }

                        public function return_url($regid = '')
                        {
                            $this->executepayment($regid);
                        }


                        public function executepayment($regid = '')
                        {

                            $baseUrl = $this->baseUrl; //"http://localhost/paypal/";
                            $clientId = $this->clientId; //"AV2UJ4rXMH6vaJcJTUTJR4doweN1og37fTV6xTKIhEPqqmEU7ZuI_Kl86PeTm1EXf6CjdNEixjXmYM7v";
                            $secret = $this->secret; //"EM1PKF6OWi3lonGwnuCeK8LAfqFr6Rpqbbo-98Ed9hMzNNWOJAvtEMb46m9jVvHjNHKc7kcribk31NrM";
                            $return_url = $this->return_url . '/' . $regid; //$baseUrl."return_url.php?invoice_no=INV00001";
                            $cancel_url = $this->cancel_url . '/' . $regid; //$baseUrl."cancel_url.php";


                            $rega = $this->adb
                                ->where("id", intval($regid))
                                //->where("visitor_id",$_SESSION['publicuser']['id'])
                                ->get("registrations")
                                ->result_array();


                            if (empty($rega))
                                die("Registration NOT found!");

                            $reg = array_pop($rega);

                            $product_price = $reg['amount'] . ".00";
                            $total_price = $reg['amount'] . ".00";
                            $currency = $reg['currency'];
                            $product_name = $reg['program_id'];




                            $ch = curl_init();

                            $paymentID = $_REQUEST["paymentID"];

                            $payerID = $_REQUEST["payerID"];
                            $json_data = json_encode(
                                array(
                                    "payer_id" => $payerID
                                )
                            );



                            //if($this->golive)
                            curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/payments/payment/" . $paymentID . "/execute/");
                            //else
                            //  curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/payment/".$paymentID."/execute/");


                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                            $result = curl_exec($ch);
                            echo $result;
                            curl_close($ch);
                            $data = array("payment_status" => "Executing Payment");

                            $this->adb
                                ->where("registration_id", $reg['id'])
                                ->where('pay_id', $paymentID)
                                ->limit(1)
                                ->update("payments", $data);
                        }




                        public function paymentconfirm($regid = '')
                        {
                            $baseUrl = $this->baseUrl; //"http://localhost/paypal/";
                            $clientId = $this->clientId; //"AV2UJ4rXMH6vaJcJTUTJR4doweN1og37fTV6xTKIhEPqqmEU7ZuI_Kl86PeTm1EXf6CjdNEixjXmYM7v";
                            $secret = $this->secret; //"EM1PKF6OWi3lonGwnuCeK8LAfqFr6Rpqbbo-98Ed9hMzNNWOJAvtEMb46m9jVvHjNHKc7kcribk31NrM";
                            $return_url = $this->return_url . '/' . $regid; //$baseUrl."return_url.php?invoice_no=INV00001";
                            $cancel_url = $this->cancel_url . '/' . $regid; //$baseUrl."cancel_url.php";




                            $rega = $this->adb
                                ->where("id", intval($regid))
                                // ->where("visitor_id",$_SESSION['publicuser']['id'])
                                ->get("registrations")
                                ->result_array();


                            if (empty($rega))
                                die("Registration NOT found!");

                            $reg = array_pop($rega);

                            $product_price = $reg['amount'] . ".00";
                            $total_price = $reg['amount'] . ".00";
                            $currency = $reg['currency'];
                            $product_name = $reg['program_id'];


                            $ch = curl_init();

                            $paymentID = $_REQUEST["payment_id"];

                            if (trim($paymentID) == "") {
                                $pa = $this->adb->where("registration_id", $reg['id'])->where('payment_status', 'approved')->get("payments")->result_array();
                                if (empty($pa)) {
                                    $pa = $this->adb->where("registration_id", $reg['id'])->get("payments")->result_array();
                                }
                                if (empty($pa)) die("Payment not found");
                                $paymentID = array_pop($pa)["pay_id"];
                            }

                            curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/payments/payment/" . $paymentID);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
                            $result = curl_exec($ch);
                            $data = json_decode($result);
                            curl_close($ch);

                            /*
if(isset($_GET['tttters']))
{
echo "<pre>paymentID:{$paymentID}  "; print_r($data); die();
}
*/

                            $data2 = array("payment_status" => $data->state);

                            $this->adb->where("registration_id", $reg['id'])->where('pay_id', $paymentID)->limit(1)->update("payments", $data2);



                            if (!empty($data) && isset($data->state)) // && $cart['status']!="CAPTURED" )
                            {
                                // $this->ekamdb = $this->load->database("default",TRUE);
                                if ($reg["status"] != "CAPTURED") {
                                    if (trim($data->state) == "approved")
                                        $data->state = "CAPTURED";

                                    $this->adb
                                        ->where("visitor_id", $reg['visitor_id'])
                                        ->where("id", $regid)
                                        ->limit(1)
                                        ->update(
                                            "registrations",
                                            array(
                                                "status" => trim($data->state),
                                                "lastupdated" => time()
                                            )
                                        );

                                    if ($reg['program_subtype_id'] != "28"  && $reg['room_id'] == "0")
                                        $this->alloc($regid);
                                }
                            }
                            header("Location: " . site_url("public/campus/pay/" . $regid));
                        }


                        public function responseusd($value = '')
                        {
                            $status = "UNKNOWN";
                            if (isset($_POST["status"]))
                                $status = $_POST["status"];
                            $firstname = $_POST["firstname"];

                            $txnid = $_POST["txnid"];
                            //  GET  amount from the transaction id row in db

                            $uid = 0;
                            if (isset($_SESSION['publicuser']['id'])) $uid = $_SESSION['publicuser']['id'];
                            if (isset($_SESSION['donatenowuser']['id'])) $uid = $_SESSION['donatenowuser']['id'];

                            //echo "<pre>"; print_r($_SESSION['donatenowuser']); die();

                            $ra = $this->adb
                                ->where("visitor_id", $uid)
                                ->where("txnid", $txnid)
                                ->limit(1)
                                ->get("payments_payu")
                                ->result_array();

                            //$amount= $_POST["amount"];  //Please use the amount value from database

                            if (empty($ra)) die("Sorry! No transaction found in your login with requested Id : " . $txnid);

                            $ra = array_pop($ra);
                            $amount = $ra['amount'];

                            //echo "<pre> amount {$amount} = {$_POST["amount"]} || ".intval($amount)." = ".intval($_POST["net_amount_debit"])." | ";   die();

                            if ($amount != $_POST["amount"] || intval($amount) != intval($_POST["net_amount_debit"])) {
                                if (intval($_POST["net_amount_debit"]) == 0) echo "<center><h1>TRANSACTION FAILED! </h1><hr> <h4>";
                                die(" Please retry after some time.");
                            }

                            $posted_hash = $_POST["hash"];
                            $key = $_POST["key"];
                            $productinfo = $_POST["productinfo"];
                            $email = $_POST["email"];
                            $SALT = $salt = $this->payusalt; // "eCwWELxi"; //"GD0Sjd9s"; //Please change the value with the live salt for production environment


                            $posted = $_POST;


                            //Validating the reverse hash
                            if (isset($_POST["additionalCharges"])) {
                                $additionalCharges = $_POST["additionalCharges"];
                                $retHashSeq = $additionalCharges . '|' . $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
                            } else {

                                $retHashSeq = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
                            }




                            $hash = hash("sha512", $retHashSeq);



                            if ($hash != $posted_hash) {
                                die("<h1><center><br><br>Transaction has been tampered. Please try again later.</h1>");
                            } else {
                                //echo "<h3>Thank You, " . $firstname .".Your order status is ". $status .".</h3>";
                                //echo "<h4>Your Transaction ID for this transaction is ".$txnid.".</h4>";


                                try {
                                    $fields = array("status", "mihpayid", "mode", "unmappedstatus", "key", "amount", "net_amount_debit", "cardCategory", "discount", "addedon", "productinfo", "firstname", "lastname", "address1", "address2", "city", "zipcode", "email", "phone", "payment_source", "PG_TYPE", "bank_ref_num", "bankcode", "error", "error_Message", "name_on_card", "cardnum", "issuing_bank", "card_type");

                                    $ua = array();

                                    foreach ($_POST as $k => $p) {
                                        if (in_array($k, $fields)) {
                                            $ua[$k] = $p;
                                        }
                                    }
                                    $ua['timestamp_bankresponse'] = time();

                                    $this->adb
                                        ->where("visitor_id", $uid)
                                        ->where("txnid", $txnid)
                                        ->limit(1)
                                        ->update("payments_payu", $ua);

                                    $st = $status;
                                    if ($st == "success")
                                        $st = "CAPTURED";

                                    $this->adb
                                        ->where("id", $ra["registration_id"])
                                        ->limit(1)
                                        ->update(
                                            "registrations",
                                            array(
                                                "status" => $st,
                                                "lastupdated" => time()
                                            )
                                        );

                                    unset($_SESSION['research_uid']);
                                    if (isset($_SESSION['donatenowuser']['id'])) {
                                        unset($_SESSION['donatenowuser']);
                                        echo "<center><h1><br><br><br>Donation Successful! <hr> ";
                                        die();
                                    }

                                    redirect("public/campus/payusd/" . $ra["registration_id"]);
                                } catch (Exception $e) {
                                    echo "<h1><center><br><br> Could not save the transaction details. It is important to copy-paste below details and save it with you and also email it to programs@vvmvp.org <hr><pre>";
                                    print_r($posted);
                                    echo "<hr>";
                                }
                            }

                            //echo "<pre> calculated hash: {$hash} <br> POSTED HASH: {$posted_hash}<br>  HASH string: {$retHashSeq} <hr>";
                            //echo '$salt."|".$status."|||||||||||".$email."|".$firstname."|".$productinfo."|".$amount."|".$txnid."|".$key;';
                            //print_r($posted);


                            /*
(
[mihpayid] => 403993715518819539
[mode] => CC
[status] => success
[unmappedstatus] => captured
[key] => gtKFFx
[txnid] => 10b4894fb388a0b82c09
[amount] => 123.00
[cardCategory] => domestic
[discount] => 0.00
[net_amount_debit] => 123
[addedon] => 2019-01-07 12:13:09
[productinfo] => Reg Id 1643
[firstname] => International
[lastname] =>
[address1] =>
[address2] =>
[city] =>
[state] =>
[country] =>
[zipcode] =>
[email] => internationalfemale@yopmail.com
[phone] => 0049123456789
[udf1] => Reg Id 1643
[udf2] => UID 127691
[udf3] => CAMPUS 0111
[udf4] => 0049123456789
[udf5] =>
[udf6] =>
[udf7] =>
[udf8] =>
[udf9] =>
[udf10] =>
[hash] => 4475ffe7f69127e1ee201130e3992840b2c00742e0971d23d112256e27fc210624823e385c1e6f1df3aefcf9988aeaeed1643afcdaebe1e1d641c7762c237ce4
[field1] => 5468436658476992403007
[field2] => 831000
[field3] => 123.00
[field4] => 403993715518819539
[field5] => 100
[field6] => 05
[field7] => 403993715518819539
[field8] =>
[field9] => Transaction is Successful
[payment_source] => payu
[PG_TYPE] => AxisCYBER
[bank_ref_num] => 5468436658476992403007
[bankcode] => CC
[error] => E000
[error_Message] => No Error
[name_on_card] => test
[cardnum] => 411111XXXXXX1111
[cardhash] => This field is no longer supported in postback params.
[issuing_bank] => UNKNOWN
[card_type] => VISA
*/
                        }





                        public function donateresearch($amount = "")
                        {


                            if (isset($_GET['ddd'])) {
                                echo "<pre>"; //print_r($_POST);
                                //print_r($_SESSION);
                                die();
                            }


                            $_SESSION['paycurrency'] = "USD";

                            if (isset($_SESSION['publicuser']['id'])) $_SESSION['disposable_uid'] = $_SESSION['publicuser']['id'];


                            //die($_SESSION['paycurrency']);


                            if (!isset($amount) || intval($amount) < 1) {
                                die("<center><h1><br><br><br> Minimum Donation Amount is USD 100/- </h1></center>");
                            }

                            $ashram_id = "0101";

                            $comment = "Research PayU Donation Initiated at " . date(" d/m/Y H:i  ") . $comment;




                            $ia = array(
                                "ashram_id" => $ashram_id,
                                "visitor_id" => $_SESSION['research_uid'],
                                "gender" => "",
                                "amount" => $amount,
                                "currency" => $_SESSION['paycurrency'], //"INR",
                                "payment_mode" => "Research PayU ",
                                "program_subtype_id" => "28",
                                "comment" => $comment,
                                "status" => "PROCESSING",
                                "receipt_no" => "Research",
                                "timestamp" => time(),
                                "donation_purpose" => "Research PayU",
                                "created_by" => $_SESSION['research_uid']
                            );

                            $this->adb->insert("registrations", $ia);


                            // for payusd function to work properly
                            $_SESSION['publicuser'] = 0;

                            if ($this->adb->affected_rows() == 1) {
                                $regid = $this->adb->insert_id();
                                header("Location: " . site_url("public/campus/payusd/" . $regid));
                            }
                        }



                        public function donateprojectudaan($amount = "")
                        {


                            if (isset($_GET['ddd'])) {
                                echo "<pre>"; //print_r($_POST);
                                print_r($_SESSION);
                                die();
                            }


                            $_SESSION['paycurrency'] = "USD";

                            if (isset($_SESSION['publicuser']['id'])) $_SESSION['disposable_uid'] = $_SESSION['publicuser']['id'];


                            //die($_SESSION['paycurrency']);


                            if (!isset($amount) || intval($amount) < 1) {
                                die("<center><h1><br><br><br> Minimum Donation Amount is USD 100/- </h1></center>");
                            }

                            $ashram_id = "0101";

                            $comment = "Project Udaan PayU Donation Initiated at " . date(" d/m/Y H:i  ") . $comment;




                            $ia = array(
                                "ashram_id" => $ashram_id,
                                "visitor_id" => $_SESSION['donateprojectudaan_uid'],
                                "gender" => "",
                                "amount" => $amount,
                                "currency" => $_SESSION['paycurrency'], //"INR",
                                "payment_mode" => "ProjectUdaan PayU ",
                                "program_subtype_id" => "28",
                                "comment" => $comment,
                                "status" => "PROCESSING",
                                "receipt_no" => "ProjectUdaan",
                                "timestamp" => time(),
                                "donation_purpose" => "ProjectUdaan PayU",
                                "created_by" => $_SESSION['donateprojectudaan_uid']
                            );

                            $this->adb->insert("registrations", $ia);


                            // for payusd function to work properly
                            $_SESSION['publicuser'] = 0;

                            if ($this->adb->affected_rows() == 1) {
                                $regid = $this->adb->insert_id();
                                header("Location: " . site_url("public/campus/payusd/" . $regid));
                            }
                        }




                        public function payusd($regid = '', $go = 0)
                        {
                            $ashram_id_my = $this->uri->segment(5);
                            /*
$hash_string =  "gtKFFx|f2598eccd86bb7909ac5|11|Reg Id 1626|International|internationalmale@yopmail.com|Reg Id 1626|UID 127690|CAMPUS 0111|001123456789|||||||eCwWELxi";

$hash  = strtolower(hash('sha512', $hash_string));

die($hash."<hr>");*/

                            if (!isset($_SESSION['publicuser'])) {
                                redirect('public/login');
                                exit();
                            }

                            if (isset($_GET['test'])) {
                                //echo "<hr><pre>";
                                //print_r($_SESSION); die();
                            }

                            if (!isset($_SESSION['paycurrency'])) die("Currency not set error!");

                            if ($_SESSION['paycurrency'] == "INR") redirect("public/campus/pay/" . $regid);

                            $rega = $this->adb
                                ->where("id", intval($regid))
                                //->where("visitor_id",$_SESSION['publicuser']['id'])
                                ->get("registrations")
                                ->result_array();

                            if (empty($rega))
                                die("<h1>ERROR! <hr> Could not find Registration Id #{$regid} ! </h1>");

                            $reg = array_pop($rega);

                            $orgid = substr($reg['ashram_id'], 0, 2);
                            $ashid = substr($reg['ashram_id'], 2, 2);


                            $aa = $this->db
                                ->where("organisation_id", intval($orgid))
                                ->where("id", intval($ashid))
                                ->get("m_ashram")
                                ->result_array();

                            if (empty($aa))
                                die("<h1>ERROR! <hr> Could not find Campus info ! </h1>");

                            $_SESSION['ash'] =   array_pop($aa);

                            $ash = array(
                                "id" => $ashid,
                                "organisation_id" => $orgid,
                                "displayname" => $ash['displayname']
                            );

                            $this->decohead($ash);

                            echo //'<script src="'. base_url().'public/js/paypalcheckout.js"></script>
                            '<div id="box">
';

                            if ($reg['program_subtype_id'] == "28") {
                                $f = "";
                                if (isset($_SESSION['research_uid']))
                                    $f =  "Towards Sri Sri Institute for Advanced Research ";
                                if (isset($_SESSION['donateprojectudaan_uid']))
                                    $f =  "Towards Project Udaan ";

                                echo "<center><h1 style='color:#f00; background-color:lightyellow;border:13px double darkgrey; padding:10px;'>PLEASE NOTE! <br> This is a voluntary contribution only {$f} ! </h1></center>";
                            }

                            $total_price = $_POST['amount'] = $reg['amount'] . ".00";
                            $currency = $reg['currency'];

                            $_POST['firstname'] = $_SESSION['publicuser']['first_name'];
                            $_POST['lastname'] = $_SESSION['publicuser']['last_name'];
                            $_POST['email']  = $_SESSION['publicuser']['email']; //= $_POST['udf5']

                            $_POST['phone'] =  preg_replace('/\D/', '',  $_SESSION['publicuser']['mobile']);
                            $_POST['productinfo'] = "Reg Id " . intval($regid) . " " . $reg['donation_purpose'];


                            //$_POST['lastname'] = "Reg Id ".intval($regid);
                            $_POST['address1'] = "UID " . $reg['visitor_id'];
                            $_POST['address2'] = "CAMPUS " . $reg['ashram_id'];
                            $_POST['phone'] = $_POST['phone'];


                            // "CUR ".$reg['currency'];

                            // echo "<pre>"; print_r($_SESSION['publicuser']); die(" try later");
                            //echo "<pre>"; print_r($reg);

                            $paya = $this->adb
                                ->where("registration_id", intval($regid))
                                ->where("visitor_id", $reg['visitor_id'])
                                ->get("payments_payu")
                                ->result_array();

                            $showppb = FALSE;
                            $successfulpayment = FALSE;


                            //if($reg['status']=="success") unset($paya);


                            // past payment for this registration found
                            if (!empty($paya)) {
                                    ?>
                                    <style type="text/css">
                                        td,
                                        th {
                                            padding: 15px;
                                            color: white;
                                        }
                                    </style>
                                    <center>
                                        <h2>This is your payment history for the selected program/donation</h2><br>
                                        <table border=1>
                                            <tr>
                                                <th>#</th>
                                                <th>Transaction Id</th>
                                                <th>Amount</th>
                                                <th>Currency</th>
                                                <th>Status</th>
                                                <th>Time</th>
                                            </tr>
                                            <?

                                            $count = 0;
                                            $showppb = TRUE;

                                            foreach ($paya as $key => $pay) {
                                                $count++;
                                                //  <th>Transaction Id</th <td>{$pay['txn_id']}</td
                                                echo "<tr> <td> {$count} </td><td> {$pay['txnid']} </td><td> {$pay['amount']} </td><td> {$pay['currency_code']} </td><td> {$pay['status']} </td><td> " . date("H:i:s d M Y", $pay['timestamp_initiated']) . " </td> </tr>";

                                                if (trim(strtolower($pay['status'])) == "success") {
                                                    $showppb = FALSE;
                                                    $successfulpayment = TRUE;
                                                } else {
                                                }
                                            }

                                            echo "</table></center>";

                                            if (intval($go) != 0) //  override go
                                            {
                                                $showppb = TRUE;
                                                echo "<h2>Overriding</h2>";
                                            }
                                        } else //first time payment initiation
                                        {
                                            $showppb = TRUE;
                                        }

                                        if ($successfulpayment == FALSE && $count > 0 && !$showppb) {
                                            echo "<center> <a class='btn btn-warning' href='" . site_url("public/campus/payusd/{$regid}/1") . "'> Make Another Payment Attempt ? </a></center>";
                                        }

                                        if ($reg['status'] == "CAPTURED") {
                                            $showppb = FALSE;
                                            echo "<center><h2 style='color:green;'> Registration #{$regid} is SUCCESSFUL. </h2></center>";
                                            if (trim($reg['receipt_no']) == "") { ?>
                                                <center>
                                                    Loading your receipt ... Please wait.
                                                </center>
                                                <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                                                <script type="text/javascript">
                                                    console.log("init");
                                                    setTimeout(function() {
                                                        $.get("<?= site_url("public/generatereceipts") ?>", function(data, status) {

                                                            window.location = window.location;
                                                        });
                                                    }, 5000);
                                                </script>
                                            <? } else {
                                                echo "<br><Center><a class='btn btn-default' href='" . site_url("public/campus/receipt/" . $reg['id']) . "' target='reg{$reg['id']}'>Download PDF Receipt for this Registration.</a></center>";
                                            }

                                            //$this->alloc($regid);
                                            if ($reg['program_subtype_id'] != "28"  && $reg['room_id'] == "0")
                                                $this->alloc($reg['id']);
                                        } // captured

                                        if ($showppb) {
                                            ?>
                                            <div class="row">
                                                <div class='col-xs-12'>
                                                    <br>

                                                    <p class="" style='text-align: center; color:white;'><i>I understand that the donations are <u>non-refundable</u> and registrations are <u>non-transferable</u>.<br>
                                                            <? echo " Continue Donating {$currency} {$total_price}/-  by clicking the orange checkout button below :"; ?>
                                                        </i></p>
                                                    <?php

                                                    // Merchant key here as provided by Payu
                                                    $MERCHANT_KEY = $_POST['key'] =  $this->payukey; //  "gtKFFx"; //Please change this value with live key for production
                                                    $hash_string = '';
                                                    // Merchant Salt as provided by Payu
                                                    $SALT = $salt = $this->payusalt; //Please change this value with live salt for production

                                                    // End point - change to https://secure.payu.in for LIVE mode
                                                    $PAYU_BASE_URL = $this->payuurl; // "https://test.payu.in";

                                                    $action = '';

                                                    $posted = array();
                                                    if (!empty($_POST)) {
                                                        //print_r($_POST);
                                                        foreach ($_POST as $key => $value) {
                                                            $posted[$key] = $value;
                                                        }
                                                    }


                                                    if (isset($_SESSION['research_first_name']))  $posted['firstname'] = $_SESSION['research_first_name'];
                                                    if (isset($_SESSION['research_last_name']))  $posted['lastname'] = $_SESSION['research_last_name'];

                                                    if (isset($_SESSION['donateprojectudaan_first_name']))  $posted['firstname'] = $_SESSION['donateprojectudaan_first_name'];
                                                    if (isset($_SESSION['donateprojectudaan_last_name']))  $posted['lastname'] = $_SESSION['donateprojectudaan_last_name'];

                                                    $formError = 0;


                                                    $_POST['txnid'] = $posted['txnid'] = $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);



                                                    // Hash Sequence
                                                    $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";

                                                    $hashVarsSeq = explode('|', $hashSequence);

                                                    $testHashSequence = "";

                                                    foreach ($hashVarsSeq as $hash_var) {
                                                        $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
                                                        $hash_string .= '|';
                                                    }

                                                    $hash_string .= $SALT;

                                                    $_POST['hash'] = $hash = strtolower(hash('sha512', $hash_string));

                                                    $action = $PAYU_BASE_URL . '/_payment';

                                                    //echo "<pre>"; print_r($posted); die("<hr>testHashSequence <br>".$hash_string );
                                                    try {

                                                        if (isset($_SESSION['publicuser']['id']))
                                                            $uid = $_SESSION['publicuser']['id'];
                                                        else if (isset($_SESSION['research_uid']))
                                                            $uid = $_SESSION['research_uid'];
                                                        else if (isset($_SESSION['donateprojectudaan_uid']))
                                                            $uid = $_SESSION['donateprojectudaan_uid'];
                                                        else die("UID missing error");

                                                        if (isset($_SESSION['publicuser']['email']))
                                                            $email = $_SESSION['publicuser']['email'];
                                                        else if (isset($_SESSION['research_email']))
                                                            $email = $_SESSION['research_email'];
                                                        else if (isset($_SESSION['donateprojectudaan_email']))
                                                            $email = $_SESSION['donateprojectudaan_email'];
                                                        else die("Email id missing error");



                                                        $ia = array(
                                                            "registration_id" => intval($regid),
                                                            "visitor_id" => $uid,
                                                            "txnid" => $txnid,
                                                            "amount" => $posted['amount'],
                                                            "currency_code" => "USD",
                                                            "payer_email" => $email,
                                                            "status" => "Payment Initiated",
                                                            "timestamp_initiated" => time(),
                                                            "productinfo" => $posted['productinfo'],
                                                            "firstname" => $posted['firstname'],
                                                            "lastname" => $posted['lastname'],
                                                            "state" => $posted['address1'],
                                                            "country" => $posted['address2']
                                                        );

                                                        $this->adb->insert("payments_payu", $ia);


                                                        $DR = "Registration";
                                                        if ($reg['program_subtype_id'] == "28")
                                                            $DR = "Donation";

                                                        $this->adb
                                                            ->where("id", intval($regid))
                                                            ->limit(1)
                                                            ->update(
                                                                "registrations",
                                                                array(
                                                                    "comment" => "PAYU {$DR} Initiated at " . date('Y-m-d H:i:s', time()),
                                                                    "payment_mode" => "PAYU"
                                                                )
                                                            );
                                                    } catch (Exception $e) {
                                                        echo " Could not save the transaction details. Please retry later.";
                                                    }
                                                    ?>

                                                    <script>
                                                        var hash = '<?php echo $hash ?>';
                                                    </script>
                                                    <CENTER>

                                                        <?php if ($formError) { ?>
                                                            <span style="color:red"> Sorry. Unable to get the payment ready for processing at this time.. please report this error to the website manager. </span>
                                                            <br />
                                                            <br />
                                                        <?php } else {  ?>
                                                            <form action="<?php echo $action; ?>" method="post" name="payuForm">
                                                                <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY ?>" />
                                                                <input type="hidden" name="hash" value="<?php echo $hash ?>" />
                                                                <input type="hidden" name="txnid" value="<?php echo $txnid ?>" />

                                                                <input type="hidden" name="surl" value="<? echo site_url(); ?>/public/campus/responseusd" />
                                                                <input type="hidden" name="furl" value="<? echo site_url(); ?>/public/campus/responseusd" />
                                                                <input type="hidden" name="curl" value="<? echo site_url(); ?>/public/campus/responseusd" />





                                                                <input type="hidden" name="amount" value="<?php echo (empty($posted['amount'])) ? '999999' : $posted['amount'] ?>" />
                                                                <input type="hidden" name="firstname" id="firstname" value="<?php echo (empty($posted['firstname'])) ? '' : $posted['firstname']; ?>" />
                                                                <input type="hidden" name="email" id="email" value="<?php echo (empty($posted['email'])) ? '' : $posted['email']; ?>" />
                                                                <input type="hidden" name="phone" value="<?php echo (empty($posted['phone'])) ? '' : $posted['phone']; ?>" />

                                                                <input type="hidden" name="productinfo" value="<?php echo (empty($posted['productinfo'])) ? '' : $posted['productinfo'] ?>" />
                                                                <input type="hidden" name="address1" value="<?php echo (empty($posted['address1'])) ? '' : $posted['address1']; ?>" />
                                                                <input type="hidden" name="address2" value="<?php echo (empty($posted['address2'])) ? '' : $posted['address2']; ?>" />
                                                                <input type="hidden" name="lastname" value="<?php echo (empty($posted['lastname'])) ? '' : $posted['lastname']; ?>" />
                                                                <input type="hidden" name="city" value="USD" />
                                                                <? /*
<input type="hidden"  name="udf5" value="<?php echo (empty($posted['udf5'])) ? '' : $posted['udf5']; ?>" />
*/ ?>
                                                                <input type="hidden" name="pg" value="<?php echo (empty($posted['pg'])) ? '' : $posted['pg']; ?>" />
                                                                <!--                          <input type="submit" value="Pay using Debit or Credit Card" class='btn btn-default btn-lg '  />  -->

                                                                <a class='btn btn-lg btn-primary cura' href='https://online.vvmvp.org/home/donate?Dtype=4&payingas=fr&campus_id=<?php echo $ashram_id_my; ?>'><input type="button" value="USD" class='btn btn-default btn-lg ' /> </a>
                                                            </form>
                                                        <? } ?>
                                                    </CENTER>
                                                </div>
                                            </div>


                                            <style>
                                                /* Remove the navbar's default margin-bottom and rounded borders */
                                                .navbar {
                                                    margin-bottom: 0;
                                                    border-radius: 0;
                                                }

                                                /* Set height of the grid so .sidenav can be 100% (adjust as needed) */
                                                .row.content {
                                                    height: 450px
                                                }

                                                /* Set gray background color and 100% height */
                                                .sidenav {
                                                    padding-top: 20px;
                                                    background-color: #f1f1f1;
                                                    height: 100%;
                                                }



                                                /* Set black background color, white text and some padding */
                                                footer {
                                                    background-color: #555;
                                                    color: white;
                                                    padding: 15px;
                                                }

                                                td {
                                                    background-color: lightgrey;
                                                }

                                                th {
                                                    background-color: darkgrey;
                                                    color: white;
                                                }

                                                td,
                                                th {
                                                    text-align: center;
                                                    padding: 10px;

                                                }

                                                /* On small screens, set height to 'auto' for sidenav and grid */
                                                @media screen and (max-width: 767px) {
                                                    .sidenav {
                                                        height: auto;
                                                        padding: 15px;
                                                    }

                                                    .row.content {
                                                        height: auto;
                                                    }
                                                }
                                            </style>
                                            <!-- PAYPAL END -->

                                            <?
                                        }

                                        if ($reg['status'] != "CAPTURED")
                                            //        echo "<center style='color:yellow; font-size:25px;'>  Note: Click on the 'Pay using Debit or Credit Card' button above to initiate the payment. </center>
                                            echo "<center style='color:yellow; font-size:25px;'>  Note: You will be redirected to online.vvmvp.org. </center>
<script>



setTimeout(function(){

//window.location = window.location;
},75000);

</script>
</div>
</body></html>";

                                        die();
                                    }

                                    public function payrazor($regid = 0)
                                    {
                                        if (intval($regid) == 0)
                                            die("Invalid / Missing Registration Id");

                                        if (!isset($_SESSION['publicuser'])) {
                                            redirect('public/login');
                                            exit();
                                        }

                                        if (!isset($_SESSION['paycurrency'])) die("Currency not set error!");

                                        if ($_SESSION['paycurrency'] == "USD") redirect("public/campus/payusd/" . $regid);

                                        $rega = $this->adb
                                            ->where("id", intval($regid))
                                            ->where("visitor_id", $_SESSION['publicuser']['id']) // why was it commented?
                                            ->get("registrations")
                                            ->result_array();

                                        if (empty($rega))
                                            die("<h1>ERROR! <hr> Could not find Registration Id #{$regid} ! </h1>");

                                        $reg =    array_pop($rega);

                                        if ($_SESSION['publicuser']['id'] == '372003') {
                                            #  TEST
                                            $_SESSION['razorpay']['api_key'] = 'rzp_test_92TbiZdYhCyjZk';

                                            // remember to change in payments/razorpay controller as well
                                            $_SESSION['razorpay']['api_secret'] = 'XdWG8p7XhyVOVPkbo1alHaZT';
                                        } else {
                                            # LIVE  rzp_live_NqEHdfvyltFX7y,ptlXoIl1QYkkx6OO3iyg7Qc0
                                            $_SESSION['razorpay']['api_key'] = 'rzp_live_36oDova5OdHkbP';

                                            // remember to change in payments/razorpay controller as well
                                            $_SESSION['razorpay']['api_secret'] = '3NrcWcXUlwTc9zALkMIdQ6go';
                                        }


                                        $orgid = substr($reg['ashram_id'], 0, 2);
                                        $ashid = substr($reg['ashram_id'], 2, 2);

                                        $aa = $this->db
                                            ->where("organisation_id", intval($orgid))
                                            ->where("id", intval($ashid))
                                            ->get("m_ashram")
                                            ->result_array();

                                        if (empty($aa))
                                            die("<h1>ERROR! <hr> Could not find Campus info ! </h1>");

                                        $aa =  array_pop($aa);

                                        unset($aa['description']);
                                        unset($aa['moreinfo']);

                                        //$_SESSION['razorpay']['ash'] =$aa;





                                        if ($reg['status'] == "CAPTURED") {
                                            echo "Payment Successful already! Press back button of your browser to continue.";
                                        } else {
                                            if (!isset($_GET['donation_head']) || trim($_GET['donation_head']) == "")
                                                $_GET['donation_head'] = "General donation.";

                                            $this->adb
                                                ->where("id", intval($regid))
                                                ->where("visitor_id", $_SESSION['publicuser']['id'])
                                                ->limit(1)
                                                ->update(
                                                    "registrations",
                                                    array(
                                                        "payment_mode" => "Razorpay",
                                                        "comment" => " Razorpay Donation Initiated at " . date(" d/m/Y H:i  ") . " " . $reg['comment'],
                                                        "donation_head" => $_GET['donation_head']
                                                    )
                                                );

                                            $url = 'https://api.razorpay.com/v1/orders';
                                            $fields = array(
                                                'currency' => urlencode("INR"),
                                                'amount' => urlencode("{$reg['amount']}00"),
                                                'payment_capture' => urlencode("0"),
                                                'receipt' => urlencode($reg['id'])
                                            );

                                            //url-ify the data for the POST
                                            foreach ($fields as $key => $value) {
                                                $fields_string .= $key . '=' . $value . '&';
                                            }
                                            rtrim($fields_string, '&');

                                            //open connection
                                            $ch = curl_init();

                                            //set the url, number of POST vars, POST data
                                            curl_setopt($ch, CURLOPT_USERPWD, "{$_SESSION['razorpay']['api_key']}:{$_SESSION['razorpay']['api_secret']}");
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, count($fields));
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

                                            // Will return the response, if false it print the response
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                                            //execute post
                                            $result = curl_exec($ch);

                                            //close connection
                                            curl_close($ch);

                                            $r = json_decode($result, true);

                                            $r['razorpay_order_id'] = $r['id'];
                                            $r['ashram_id'] = $reg['ashram_id'];
                                            $r['registration_id'] = $regid;

                                            $r['visitor_id'] = $_SESSION['publicuser']['id'];
                                            $r['first_name'] = $_SESSION['publicuser']['first_name'];
                                            $r['last_name'] = $_SESSION['publicuser']['last_name'];
                                            $r['email'] = $_SESSION['publicuser']['email'];
                                            $r['mobile'] = $_SESSION['publicuser']['mobile'];

                                            unset($r['id']);
                                            $r['notes'] = json_encode($r['notes']);
                                            if (isset($r['offers']) && is_array($r['offers']) && !empty($r['offers']))
                                                $r['offers'] = json_encode($r['offers']);
                                            //unset($r['offers']);
                                            $this->adb->insert("payments_razorpay", $r);

                                            if ($this->adb->affected_rows() == 1) {




                                                //header("Location: https://register.vvmvp.org/ekam/razorpay");
                                            ?>
                                                <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                                                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                                                <script>
                                                    var SITEURL = "<? echo site_url(); ?>";

                                                    function go() {
                                                        var totalAmount = "<?= $reg['amount'] ?>00";
                                                        var options = {
                                                            "key": "<?= $_SESSION['razorpay']['api_key'] ?>",
                                                            "amount": (totalAmount), // 2000 paise = INR 20
                                                            "name": "Art of Living",
                                                            "order_id": "<?= $r['razorpay_order_id'] ?>",
                                                            "description": "<?= $aa['displayname'] ?>",
                                                            "image": "<? echo base_url(); ?>/logo.png",
                                                            "prefill.contact": "<?= $_SESSION['publicuser']['mobile'] ?>",
                                                            "prefill.email": "<?= $_SESSION['publicuser']['email'] ?>",
                                                            "handler": function(response) {
                                                                console.log("response", response);

                                                                $.ajax({
                                                                    url: SITEURL + '/payment/razorpay/success',
                                                                    type: 'post',
                                                                    dataType: 'json',
                                                                    data: {
                                                                        razorpay_order_id: response.razorpay_order_id,
                                                                        razorpay_payment_id: response.razorpay_payment_id,
                                                                        razorpay_signature: response.razorpay_signature,
                                                                        totalAmount: totalAmount,
                                                                        rp_id: <?= $this->adb->insert_id() ?>,
                                                                        registration_id: <?= $regid ?>,
                                                                    },
                                                                    complete: function(rdata, status, xhr) {
                                                                        console.log("rdata", rdata.responseText);

                                                                        if (rdata.responseText == "ok")
                                                                            window.location.href = SITEURL + '/public/campus/pay/<?= $regid ?>';
                                                                        else
                                                                            alert("Could not save this payment to our server. " + rdata.responseText);
                                                                    }
                                                                });

                                                            },

                                                            "theme": {
                                                                "color": "orange"
                                                            }
                                                        };
                                                        var rzp1 = new Razorpay(options);
                                                        rzp1.open();
                                                    }


                                                    (function() {
                                                        go();
                                                    })();
                                                </script>
                                            <?

                                            } else die(" Could not create the Razorpay Order!");

                                            //echo "<pre> asddsadd"; print_r($r);  die();



                                        }
                                    }


                                    public function payBKP($regid = '', $go = 0)
                                    {
                                        $ashram_id_my = $this->uri->segment(5);
                                        if (!isset($_SESSION['publicuser'])) {
                                            redirect('public/login');
                                            exit();
                                        }

                                        if (isset($_GET['test'])) {
                                            //echo "<hr><pre>";
                                            //print_r($_SESSION); die();
                                        }

                                        if (!isset($_SESSION['paycurrency'])) die("Currency not set error!");

                                        if ($_SESSION['paycurrency'] == "USD") redirect("public/campus/payusd/" . $regid . "/" . $ashram_id_my);



                                        if (isset($_GET['omkar'])) {
                                            //echo "<hr><pre>"; print_r(explode(",",$_SESSION['ash']['csv_donationheads'])); die();
                                        }


                                        // $this->allowed();



                                        //echo " Reg id :".$regid;

                                        $rega = $this->adb
                                            ->where("id", intval($regid))
                                            ->where("visitor_id", $_SESSION['publicuser']['id']) // why was it commented?
                                            ->get("registrations")
                                            ->result_array();

                                        if (empty($rega))
                                            die("<h1>ERROR! <hr> Could not find Registration Id #{$regid} ! </h1>");

                                        $reg = array_pop($rega);

                                        $orgid = substr($reg['ashram_id'], 0, 2);
                                        $ashid = substr($reg['ashram_id'], 2, 2);


                                        $aa = $this->db
                                            ->where("organisation_id", intval($orgid))
                                            ->where("id", intval($ashid))
                                            ->get("m_ashram")
                                            ->result_array();

                                        if (empty($aa))
                                            die("<h1>ERROR! <hr> Could not find Campus info ! </h1>");

                                        $_SESSION['ash'] = $ash = array_pop($aa);

                                        $ash = array(
                                            "id" => $ashid,
                                            "organisation_id" => $orgid,
                                            "displayname" => $ash['displayname']
                                        );

                                        $this->decohead($ash);

                                        echo '<script src="' . base_url() . 'public/js/paypalcheckout.js"></script>
<div id="box">
';

                                        if ($reg['program_subtype_id'] == "28") {
                                            echo "<center><h1 style='color:#f00; background-color:lightyellow;border:13px double darkgrey; padding:10px;'>PLEASE NOTE! <br> This is a voluntary contribution only ! </h1></center>";
                                        }

                                        $total_price = $reg['amount'] . ".00";
                                        $currency = $reg['currency'];


                                        //echo "<pre>"; print_r($_SESSION['publicuser']); die(" try later");
                                        //echo "<pre>"; print_r($reg);

                                        $paya = $this->adb
                                            ->where("registration_id", intval($regid))
                                            ->where("visitor_id", $reg['visitor_id'])
                                            ->get("payments")
                                            ->result_array();

                                        $showppb = FALSE;
                                        $successfulpayment = FALSE;


                                        if ($reg['status'] == "CAPTURED") unset($paya);


                                        // past payment for this registration found
                                        if (!empty($paya)) {
                                            ?>

                                            <center>
                                                <h2>This is your payment history for the selected program</h2><br>
                                                <table border=1>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Payment Id</th>
                                                        <th>Amount</th>
                                                        <th>Currency</th>
                                                        <th>Status</th>
                                                        <th>Time</th>
                                                    </tr>
                                                    <?

                                                    $count = 0;
                                                    $showppb = TRUE;

                                                    foreach ($paya as $key => $pay) {
                                                        $count++;
                                                        //  <th>Transaction Id</th <td>{$pay['txn_id']}</td
                                                        echo "<tr> <td> {$count} </td><td> {$pay['pay_id']} </td><td> {$pay['payment_gross']} </td><td> {$pay['currency_code']} </td><td> {$pay['payment_status']} </td><td> " . date("H:i:s d M Y", $pay['timestamp']) . " </td> </tr>";

                                                        if (trim(strtolower($pay['payment_status'])) == "approved") {
                                                            $showppb = FALSE;
                                                            $successfulpayment = TRUE;
                                                        }
                                                    }

                                                    echo "</table></center>";

                                                    if (intval($go) != 0) //  override go
                                                    {
                                                        $showppb = TRUE;
                                                        echo "<h2>Overriding</h2>";
                                                    }
                                                } else //first time payment initiation
                                                {
                                                    $showppb = TRUE;
                                                }

                                                if ($successfulpayment == FALSE && $count > 0 && !$showppb) {
                                                    echo "<center> <a class='btn btn-warning' href='" . site_url("public/campus/pay/{$regid}/1") . "'> Make Another Payment Attempt ? </a></center>";
                                                }

                                                if ($reg['status'] == "CAPTURED") {
                                                    $showppb = FALSE;
                                                    echo "<center><h2 style='color:green;'> Registration #{$regid} is SUCCESSFUL. </h2></center>";
                                                    if (trim($reg['receipt_no']) == "") { ?>
                                                        <center>
                                                            Loading your receipt ... Please wait.
                                                        </center>
                                                        <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                                                        <script type="text/javascript">
                                                            console.log("init");
                                                            setTimeout(function() {
                                                                $.get("<?= site_url("public/generatereceipts") ?>", function(data, status) {

                                                                    window.location = window.location;
                                                                });
                                                            }, 5000);
                                                        </script>
                                                    <? } else {
                                                        echo "<br><Center><a class='btn btn-default' href='" . site_url("public/campus/receipt/" . $reg['id']) . "' target='reg{$reg['id']}'>Download PDF Receipt for this Registration.</a></center>";
                                                    }


                                                    if ($reg['program_subtype_id'] != "28"  && $reg['room_id'] == "0")
                                                        $this->alloc($regid);
                                                }

                                                if ($showppb) {
                                                    ?>


                                                    <div class="row">
                                                        <div class='col-xs-12'>
                                                            <br>

                                                            <p class="" style='text-align: center; color:white;'><i>I understand that the donations are <u>non-refundable</u> and registrations are <u>non-transferable</u>.<br>
                                                                    <? echo " Continue Donating {$currency} {$total_price}/-  by clicking the yellow button below :"; ?>
                                                                </i></p>



                                                            <script type="text/javascript">
                                                                function godo() {
                                                                    //alert("Work in progress, please come back in few minutes");
                                                                    dhc();
                                                                    var href = '<?= site_url("public/campus/payrazor/" . $regid) ?>/?donation_head=' + $("#dh").val();
                                                                    //console.log(href);
                                                                    window.location = href;

                                                                }

                                                                function dhc() {
                                                                    var dhv = $("#dh").val();
                                                                    if ($.trim(dhv) == "") {
                                                                        $("#dh").val("General");

                                                                    }

                                                                }
                                                            </script>

                                                            <center>
                                                                <p>
                                                                    <select id="dh" onchange="dhc();" style="color: black;">
                                                                        <option value='General' selected="selected">General</option>
                                                                        <?
                                                                        $donation_heads = explode(",", $_SESSION['ash']['csv_donationheads']);

                                                                        if (isset($donation_heads) && !empty($donation_heads)) {
                                                                            foreach ($donation_heads as $key => $dh) {
                                                                                if (trim($dh) != "")
                                                                                    echo "<option value='{$dh}'>{$dh}</option>";
                                                                            }
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </p>
                                                                <p>
                                                                <div id='donation_head_description_div'></div>
                                                                </p>
                                                                <h3 style="background-color:yellow;color:black;" class="btn btn-lg" onclick="javascript:godo();">Donate Now</h3>
                                                            </center>

                                                            <? /*
<div id="paypalb" style='text-align: center;' >
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAAUVBMVEX/////AAAAAADOzs5BAADwAACTk5OUAAC8AADNzc3AAADc3NxPT09JAAB2dnafAACsAADIAAD39/dCQkI8AAD3AAAKAADx8fGhoaF+AACKAADvJE5LAAADfElEQVR4nO3d2ZLaMBBGYWKGZBgzzL7l/R806VBDLGPA1tLqVp3/PhV/dRTpLqxWcXved7rbP0d+aexelIFd96IL7NWBXderCrcVhFtV4bqCcI2wgPD1baOxt9dqwpsfOrtBiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESL0Kvz4/Ll8+wrCfcR3fn6k/Q/5usK49atd48LdKuEPd/dKwvuUj0xq+KUk/Er4xl3Kv8P3jZJw8x7/kf3gZw7uH34t2qOST/a47NMejuf63xNz/DWOJ8VPLrunb9J2+Hz/3W3tL8u026BgQLyr/W1ZdncCbKziacHGiNPAhg7q1BFtquK5gsGj4bniseDkj0M1UPFSwSYqXi7YQMVrBd3fqOdv0UYqzinomjgX6PagzjuijivOLyhz+GhcfyacV1xW0GHFpQXdVVxeMCDar7jkFnVZMa6gI2I80MlBjT2ibiqmFJSZfzRinglXFVMLmq+YXtB4xRwFA6K1imm3qIOKuQqaJeYEmjyo+Y7oiGilYt6CMmOPRp5nIpypivkLygxVLFFQZqZimYIBsW7F3LfoJLFmxXIFjRDLAg0c1JJHdESsU7F0QVnVR6PUMxGuYkWNgrJqFXUKyipV1CoYEDUrlr9FJ4l6FTULViFqA9UPqu4RHRE1KuoXlCk+GnrPRDi1inUKypQq1iooU6lYr2BALFexxi06SSxVsW5BBWJ9YOGDWvuIjoj5K1ooKCv2aNR8JsIVqmiloKxIRTsFZQUqWiooy36j2rhFh8tc0VpBWVaiRWDWg2rviB6WraLNgrJMj4atZyJclop2C8oyVLRcUJZc0XZBWeKNavUWHS6pov2CsgSiD2DCQfVwRA+LrOiloCzq0bD+TISLqOipoGxxRV8FZQsreisoW3Sj+rlFh1tQ0WNB2WyiV+Dsg+rziB42q6LfgrIZj4a/ZyLc1Yq+C8quVPReUHaxov+Csgs3qudbdLizFdsoKDtDbAd45qC2ckQPm6jYUkHZyaPRwjMRblSxtYKyoGJ7BWWDii0WlB2JvxsFDoitAsfEBoEhsUngkNgo8P+j0dQzEW7ddkFZv+t2ve5f+Qfzdo1eAecpQgAAAABJRU5ErkJggg=="  style=' height:30px;'/><br><br>
<div id="paypal-button" ></div>

<!-- PayPal Logo --><img style='height:60px;' src="https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png" border="0" alt="PayPal Acceptance Mark"><!-- PayPal Logo -->
<p>Please note, this is ONLY for Domestic PayPal Users (Indian Nationals Residing in India) </p>
</div>
<br>

<center>
<input type="button" class="btn btn-lg btn-warning" onclick="gop();"  value="Click here to pay using Credit Card or Debit Card" />
</center>
*/

                                                            if (isset($_GET['test'])) {
                                                                //echo "<h1><a href='".site_url("public/campus/payrazor/".$regid)."'>Razorpay</a></h1>";
                                                            } // test

                                                            ?>
                                                        </div>
                                                    </div>
                                                <? /*
<!-- PAYPAL START -->

<script>

var registration_id = <?=intval($regid)?> ;

function getParameterByName(name, url) {
name = name.replace(/[\[\]]/g, "\\$&");
var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
results = regex.exec(url);
if (!results) return null;
if (!results[2]) return '';
return decodeURIComponent(results[2].replace(/\+/g, " "));
}


// Set up a url on your server to create the payment  create-payment.php
var CREATE_URL = '<?=site_url("public/campus/createpayment")?>/';
// Set up a url on your server to execute the payment execute-payment.php
var EXECUTE_URL = '<?=site_url("public/campus/executepayment")?>/';

paypal.Button.render({

env: 'production', // sandbox | production

// Show the buyer a 'Pay Now' button in the checkout flow
commit: true,

locale : 'en_IN',

// payment() is called when the button is clicked
payment: function() {

// Make a call to your server to set up the payment
return paypal.request.post(CREATE_URL+registration_id)
.then(function(res) {
return res.id;
});
},

// onAuthorize() is called when the buyer approves the payment
onAuthorize: function(data, actions) {
console.log(data);
//var query = data.returnUrl;
//alert(getParameterByName('invoice_no',query));
// Set up the data you need to pass to your server
//alert(JSON.stringify(actions.payment.get()));
var data = {
paymentID: data.paymentID,
payerID: data.payerID
};

// Make a call to your server to execute the payment
return paypal.request.post(EXECUTE_URL+registration_id, data)
.then(function (res) {

//window.location  = "payment-confirm.php?payment_id="+res.id;
window.location = "<?=site_url('public/campus/paymentconfirm')?>/"+registration_id+"?payment_id="+res.id;
});
}

}, '#paypal-button');
</script>


<style>
/* Remove the navbar's default margin-bottom and rounded borders * /
.navbar {
margin-bottom: 0;
border-radius: 0;
}

/* Set height of the grid so .sidenav can be 100% (adjust as needed) * /
.row.content {height: 450px}

/* Set gray background color and 100% height * /
.sidenav {
padding-top: 20px;
background-color: #f1f1f1;
height: 100%;
}



/* Set black background color, white text and some padding * /
footer {
background-color: #555;
color: white;
padding: 15px;
}

td {
background-color: lightgrey;
}
th
{
background-color: darkgrey;
color:white;
}
td,th
{
text-align: center;
padding: 10px;

}

/* On small screens, set height to 'auto' for sidenav and grid * /
@media screen and (max-width: 767px) {
.sidenav {
height: auto;
padding: 15px;
}
.row.content {height:auto;}
}
</style>
<!-- PAYPAL END -->

<?

*/
                                                }

                                                if ($reg['status'] != "CAPTURED")
                                                    echo "<center style='color:yellow; font-size:25px;'>  Note: Click on the yellow color button above to pay using credit / debit card / UPI BHIM / WhatsApp / PhonePe / Google Pay / Paytm / UPI QR Code / Netbanking / Mobikwik wallet / PayZapp / Ola Money / Airtel Money / Freecharge / Jio Money  </center>
<script>



setTimeout(function(){

//window.location = window.location;
},75000);

</script>
</div>
</body></html>";

                                                die();
                                            }

                                            public function pay($regid = '', $go = 0)
                                            {
                                                //echo " Reg id :".$regid;
                                                $rega = $this->adb
                                                    ->where("id", intval($regid))
                                                    ->where("visitor_id", $_SESSION['publicuser']['id']) // why was it commented?
                                                    ->get("registrations")
                                                    ->result_array();


                                                if (empty($rega))
                                                    die("<h1>ERROR! <hr> Could not find Registration Id #{$regid} ! </h1>");

                                                $reg = array_pop($rega);

                                                //Start Sending Confirmation Mail If TXN is SUCCESS/CAPTURED
                                                if ($reg['status'] == "CAPTURED" && $_SESSION['publicuser']['id'] == '372003') {
                                                    $this->course_purchase_notification_student($reg['program_id'], $reg['visitor_id'], $reg['  payment_mode'], $reg['amount']);
                                                }
                                                //End Sending Confirmation Mail If TXN is SUCCESS/CAPTURED


                                                $ashram_id_my = $this->uri->segment(5);
                                                if (!isset($_SESSION['publicuser'])) {
                                                    redirect('public/login');
                                                    exit();
                                                }

                                                if (isset($_GET['test'])) {
                                                    //echo "<hr><pre>";
                                                    //print_r($_SESSION); die();
                                                }

                                                if (!isset($_SESSION['paycurrency'])) die("Currency not set error!");

                                                if ($_SESSION['paycurrency'] == "USD") redirect("public/campus/payusd/" . $regid . "/" . $ashram_id_my);



                                                if (isset($_GET['omkar'])) {
                                                    //echo "<hr><pre>"; print_r(explode(",",$_SESSION['ash']['csv_donationheads'])); die();
                                                }


                                                // $this->allowed();

                                                $orgid = substr($reg['ashram_id'], 0, 2);
                                                $ashid = substr($reg['ashram_id'], 2, 2);


                                                $aa = $this->db
                                                    ->where("organisation_id", intval($orgid))
                                                    ->where("id", intval($ashid))
                                                    ->get("m_ashram")
                                                    ->result_array();

                                                if (empty($aa))
                                                    die("<h1>ERROR! <hr> Could not find Campus info ! </h1>");

                                                $_SESSION['ash'] = $ash = array_pop($aa);

                                                $ash = array(
                                                    "id" => $ashid,
                                                    "organisation_id" => $orgid,
                                                    "displayname" => $ash['displayname']
                                                );

                                                $this->decohead($ash);

                                                echo '<script src="' . base_url() . 'public/js/paypalcheckout.js"></script>
<div id="box">
';

                                                if ($reg['program_subtype_id'] == "28") {
                                                    echo "<center><h1 style='color:#f00; background-color:lightyellow;border:13px double darkgrey; padding:10px;'>PLEASE NOTE! <br> This is a voluntary contribution only ! </h1></center>";
                                                }

                                                $total_price = $reg['amount'] . ".00";
                                                $currency = $reg['currency'];


                                                //echo "<pre>"; print_r($_SESSION['publicuser']); die(" try later");
                                                //echo "<pre>"; print_r($reg);

                                                $paya = $this->adb
                                                    ->where("registration_id", intval($regid))
                                                    ->where("visitor_id", $reg['visitor_id'])
                                                    ->get("payments")
                                                    ->result_array();

                                                $showppb = FALSE;
                                                $successfulpayment = FALSE;


                                                if ($reg['status'] == "CAPTURED") unset($paya);


                                                // past payment for this registration found
                                                if (!empty($paya)) {
                                                ?>

                                                    <center>
                                                        <h2>This is your payment history for the selected program</h2><br>
                                                        <table border=1>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Payment Id</th>
                                                                <th>Amount</th>
                                                                <th>Currency</th>
                                                                <th>Status</th>
                                                                <th>Time</th>
                                                            </tr>
                                                            <?

                                                            $count = 0;
                                                            $showppb = TRUE;

                                                            foreach ($paya as $key => $pay) {
                                                                $count++;
                                                                //  <th>Transaction Id</th <td>{$pay['txn_id']}</td
                                                                echo "<tr> <td> {$count} </td><td> {$pay['pay_id']} </td><td> {$pay['payment_gross']} </td><td> {$pay['currency_code']} </td><td> {$pay['payment_status']} </td><td> " . date("H:i:s d M Y", $pay['timestamp']) . " </td> </tr>";

                                                                if (trim(strtolower($pay['payment_status'])) == "approved") {
                                                                    $showppb = FALSE;
                                                                    $successfulpayment = TRUE;
                                                                }
                                                            }

                                                            echo "</table></center>";

                                                            if (intval($go) != 0) //  override go
                                                            {
                                                                $showppb = TRUE;
                                                                echo "<h2>Overriding</h2>";
                                                            }
                                                        } else //first time payment initiation
                                                        {
                                                            $showppb = TRUE;
                                                        }

                                                        if ($successfulpayment == FALSE && $count > 0 && !$showppb) {
                                                            echo "<center> <a class='btn btn-warning' href='" . site_url("public/campus/pay/{$regid}/1") . "'> Make Another Payment Attempt ? </a></center>";
                                                        }

                                                        if ($reg['status'] == "CAPTURED") {
                                                            $showppb = FALSE;
                                                            echo "<center><h2 style='color:green;'> Registration #{$regid} is SUCCESSFUL. </h2></center>";
                                                            if (trim($reg['receipt_no']) == "") { ?>
                                                                <center>
                                                                    Loading your receipt ... Please wait.
                                                                </center>
                                                                <script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>

                                                                <script type="text/javascript">
                                                                    console.log("init");
                                                                    setTimeout(function() {
                                                                        $.get("<?= site_url("public/generatereceipts") ?>", function(data, status) {

                                                                            window.location = window.location;
                                                                        });
                                                                    }, 5000);
                                                                </script>
                                                            <? } else {
                                                                echo "<br><Center><a class='btn btn-default' href='" . site_url("public/campus/receipt/" . $reg['id']) . "' target='reg{$reg['id']}'>Download PDF Receipt for this Registration.</a></center>";
                                                            }


                                                            if ($reg['program_subtype_id'] != "28"  && $reg['room_id'] == "0")
                                                                $this->alloc($regid);
                                                        }

                                                        if ($showppb) {
                                                            ?>


                                                            <div class="row">
                                                                <div class='col-xs-12'>
                                                                    <br>

                                                                    <p class="" style='text-align: center; color:white;'><i>I understand that the donations are <u>non-refundable</u> and registrations are <u>non-transferable</u>.<br>
                                                                            <? echo " Continue Donating {$currency} {$total_price}/-  by clicking the yellow button below :"; ?>
                                                                        </i></p>



                                                                    <script type="text/javascript">
                                                                        function godo() {
                                                                            //alert("Work in progress, please come back in few minutes");
                                                                            dhc();
                                                                            var href = '<?= site_url("public/campus/payrazor/" . $regid) ?>/?donation_head=' + $("#dh").val();
                                                                            //console.log(href);
                                                                            window.location = href;

                                                                        }

                                                                        function dhc() {
                                                                            var dhv = $("#dh").val();
                                                                            if ($.trim(dhv) == "") {
                                                                                $("#dh").val("General");

                                                                            }

                                                                        }
                                                                    </script>

                                                                    <center>
                                                                        <p>
                                                                            <select id="dh" onchange="dhc();" style="color: black;">
                                                                                <option value='General' selected="selected">General</option>
                                                                                <?
                                                                                $donation_heads = explode(",", $_SESSION['ash']['csv_donationheads']);

                                                                                if (isset($donation_heads) && !empty($donation_heads)) {
                                                                                    foreach ($donation_heads as $key => $dh) {
                                                                                        if (trim($dh) != "")
                                                                                            echo "<option value='{$dh}'>{$dh}</option>";
                                                                                    }
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        </p>
                                                                        <p>
                                                                        <div id='donation_head_description_div'></div>
                                                                        </p>
                                                                        <h3 style="background-color:yellow;color:black;" class="btn btn-lg" onclick="javascript:godo();">Donate Now</h3>
                                                                    </center>

                                                                    <? /*
<div id="paypalb" style='text-align: center;' >
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAAUVBMVEX/////AAAAAADOzs5BAADwAACTk5OUAAC8AADNzc3AAADc3NxPT09JAAB2dnafAACsAADIAAD39/dCQkI8AAD3AAAKAADx8fGhoaF+AACKAADvJE5LAAADfElEQVR4nO3d2ZLaMBBGYWKGZBgzzL7l/R806VBDLGPA1tLqVp3/PhV/dRTpLqxWcXved7rbP0d+aexelIFd96IL7NWBXderCrcVhFtV4bqCcI2wgPD1baOxt9dqwpsfOrtBiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESJEiBAhQoQIESL0Kvz4/Ll8+wrCfcR3fn6k/Q/5usK49atd48LdKuEPd/dKwvuUj0xq+KUk/Er4xl3Kv8P3jZJw8x7/kf3gZw7uH34t2qOST/a47NMejuf63xNz/DWOJ8VPLrunb9J2+Hz/3W3tL8u026BgQLyr/W1ZdncCbKziacHGiNPAhg7q1BFtquK5gsGj4bniseDkj0M1UPFSwSYqXi7YQMVrBd3fqOdv0UYqzinomjgX6PagzjuijivOLyhz+GhcfyacV1xW0GHFpQXdVVxeMCDar7jkFnVZMa6gI2I80MlBjT2ibiqmFJSZfzRinglXFVMLmq+YXtB4xRwFA6K1imm3qIOKuQqaJeYEmjyo+Y7oiGilYt6CMmOPRp5nIpypivkLygxVLFFQZqZimYIBsW7F3LfoJLFmxXIFjRDLAg0c1JJHdESsU7F0QVnVR6PUMxGuYkWNgrJqFXUKyipV1CoYEDUrlr9FJ4l6FTULViFqA9UPqu4RHRE1KuoXlCk+GnrPRDi1inUKypQq1iooU6lYr2BALFexxi06SSxVsW5BBWJ9YOGDWvuIjoj5K1ooKCv2aNR8JsIVqmiloKxIRTsFZQUqWiooy36j2rhFh8tc0VpBWVaiRWDWg2rviB6WraLNgrJMj4atZyJclop2C8oyVLRcUJZc0XZBWeKNavUWHS6pov2CsgSiD2DCQfVwRA+LrOiloCzq0bD+TISLqOipoGxxRV8FZQsreisoW3Sj+rlFh1tQ0WNB2WyiV+Dsg+rziB42q6LfgrIZj4a/ZyLc1Yq+C8quVPReUHaxov+Csgs3qudbdLizFdsoKDtDbAd45qC2ckQPm6jYUkHZyaPRwjMRblSxtYKyoGJ7BWWDii0WlB2JvxsFDoitAsfEBoEhsUngkNgo8P+j0dQzEW7ddkFZv+t2ve5f+Qfzdo1eAecpQgAAAABJRU5ErkJggg=="  style=' height:30px;'/><br><br>
<div id="paypal-button" ></div>

<!-- PayPal Logo --><img style='height:60px;' src="https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png" border="0" alt="PayPal Acceptance Mark"><!-- PayPal Logo -->
<p>Please note, this is ONLY for Domestic PayPal Users (Indian Nationals Residing in India) </p>
</div>
<br>

<center>
<input type="button" class="btn btn-lg btn-warning" onclick="gop();"  value="Click here to pay using Credit Card or Debit Card" />
</center>
*/

                                                                    if (isset($_GET['test'])) {
                                                                        //echo "<h1><a href='".site_url("public/campus/payrazor/".$regid)."'>Razorpay</a></h1>";
                                                                    } // test

                                                                    ?>
                                                                </div>
                                                            </div>
                                                <? /*
<!-- PAYPAL START -->

<script>

var registration_id = <?=intval($regid)?> ;

function getParameterByName(name, url) {
name = name.replace(/[\[\]]/g, "\\$&");
var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
results = regex.exec(url);
if (!results) return null;
if (!results[2]) return '';
return decodeURIComponent(results[2].replace(/\+/g, " "));
}


// Set up a url on your server to create the payment  create-payment.php
var CREATE_URL = '<?=site_url("public/campus/createpayment")?>/';
// Set up a url on your server to execute the payment execute-payment.php
var EXECUTE_URL = '<?=site_url("public/campus/executepayment")?>/';

paypal.Button.render({

env: 'production', // sandbox | production

// Show the buyer a 'Pay Now' button in the checkout flow
commit: true,

locale : 'en_IN',

// payment() is called when the button is clicked
payment: function() {

// Make a call to your server to set up the payment
return paypal.request.post(CREATE_URL+registration_id)
.then(function(res) {
return res.id;
});
},

// onAuthorize() is called when the buyer approves the payment
onAuthorize: function(data, actions) {
console.log(data);
//var query = data.returnUrl;
//alert(getParameterByName('invoice_no',query));
// Set up the data you need to pass to your server
//alert(JSON.stringify(actions.payment.get()));
var data = {
paymentID: data.paymentID,
payerID: data.payerID
};

// Make a call to your server to execute the payment
return paypal.request.post(EXECUTE_URL+registration_id, data)
.then(function (res) {

//window.location  = "payment-confirm.php?payment_id="+res.id;
window.location = "<?=site_url('public/campus/paymentconfirm')?>/"+registration_id+"?payment_id="+res.id;
});
}

}, '#paypal-button');
</script>


<style>
/* Remove the navbar's default margin-bottom and rounded borders * /
.navbar {
margin-bottom: 0;
border-radius: 0;
}

/* Set height of the grid so .sidenav can be 100% (adjust as needed) * /
.row.content {height: 450px}

/* Set gray background color and 100% height * /
.sidenav {
padding-top: 20px;
background-color: #f1f1f1;
height: 100%;
}



/* Set black background color, white text and some padding * /
footer {
background-color: #555;
color: white;
padding: 15px;
}

td {
background-color: lightgrey;
}
th
{
background-color: darkgrey;
color:white;
}
td,th
{
text-align: center;
padding: 10px;

}

/* On small screens, set height to 'auto' for sidenav and grid * /
@media screen and (max-width: 767px) {
.sidenav {
height: auto;
padding: 15px;
}
.row.content {height:auto;}
}
</style>
<!-- PAYPAL END -->

<?

*/
                                                        }

                                                        if ($reg['status'] != "CAPTURED")
                                                            echo "<center style='color:yellow; font-size:25px;'>  Note: Click on the yellow color button above to pay using credit / debit card / UPI BHIM / WhatsApp / PhonePe / Google Pay / Paytm / UPI QR Code / Netbanking / Mobikwik wallet / PayZapp / Ola Money / Airtel Money / Freecharge / Jio Money  </center>
<script>



setTimeout(function(){

//window.location = window.location;
},75000);

</script>
</div>
</body></html>";

                                                        die();
                                                    }


                                                    public function course_purchase_notification_student($course_id = "", $student_id = "", $payment_method = "", $amount_paid = "")
                                                    {
                                                        $course_id = trim($course_id);
                                                        $payment_method = trim($payment_method);
                                                        $student_id = trim($student_id);
                                                        $amount_paid = trim($amount_paid);

                                                        $course_details = $this->adb->where('id', trim($course_id))->get('programs')->row_array();
                                                        //echo"<pre>";print_r($course_details);die;
                                                        $template_details = $this->adb->where('etid', $course_details['email_template_id'])->get('email_templates')->row_array();
                                                        //echo"<pre>";print_r($template_details);die;
                                                        $student_data       = $this->db->where('id', trim($student_id))->get('m_visitor')->row_array();
                                                        $student_email_to = $student_data['email'];
                                                        $student_name1 = $student_data['first_name'] . ' ' . $student_data['middle_name'];
                                                        $student_name2 = $student_data['last_name'];
                                                        $mobile = $student_data['mobile'];
                                                        $course =  substr($course_details['program_name'], 0, 49);
                                                        //echo"<pre>";print_r($student_email_to);die;
                                                        //echo $course;die;
                                                        if ($payment_method == 'PayPal') {
                                                            $curr = 'USD $';
                                                        } else {
                                                            $curr = 'Rs ';
                                                        }
                                                        //echo $message;die;

                                                        $today = date('d-M-Y');
                                                        $inc = 3; //($course_details['number_of_days_for_course']);
                                                        $exp_date = date('d-M-Y', strtotime($today . ' + ' . $inc . ' days'));
                                                        $subject = $template_details['subject'];
                                                        //echo"<pre>";print_r($subject);die;

                                                        $course_details['program_start'] = str_split($course_details['program_start']);
                                                        //print_r($course_details['program_start']);die;
                                                        $sy = $course_details['program_start'][0] . $course_details['program_start'][1] . $course_details['program_start'][2] . $course_details['program_start'][3];
                                                        $sm = $course_details['program_start'][4] . $course_details['program_start'][5];
                                                        $sd = $course_details['program_start'][6] . $course_details['program_start'][7];
                                                        $course_details['program_start'] = $sy . '-' . $sm . '-' . $sd;


                                                        $course_details['program_end'] = str_split($course_details['program_end']);
                                                        $ey = $course_details['program_end'][0] . $course_details['program_end'][1] . $course_details['program_end'][2] . $course_details['program_end'][3];
                                                        $em = $course_details['program_end'][4] . $course_details['program_end'][5];
                                                        $ed = $course_details['program_end'][6] . $course_details['program_end'][7];

                                                        $course_details['program_end'] = $ey . '-' . $em . '-' . $ed;

                                                        if (strpos($subject, '{{course_title}}') == true) {
                                                            $subject = str_replace('{{course_title}}', $course_details['program_name'], $subject);
                                                        }
                                                        //echo"<pre>";print_r($subject);die;

                                                        $student_msg = $template_details['body'];
                                                        //echo"<pre>";print_r($student_msg);die;

                                                        if (strpos($student_msg, '{{course_title}}') == true) {
                                                            $student_msg = str_replace('{{course_title}}', $course_details['program_name'], $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{student_name}}') == true) {
                                                            $student_msg = str_replace('{{student_name}}', $student_name1, $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{currency}}') == true) {
                                                            $student_msg = str_replace('{{currency}}', $curr, $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{amount_paid}}') == true) {
                                                            $student_msg = str_replace('{{amount_paid}}', $amount_paid, $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{starting_date}}') == true) {
                                                            $student_msg = str_replace('{{starting_date}}', $course_details['program_start'], $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{ending_date}}') == true) {
                                                            $student_msg = str_replace('{{ending_date}}', $course_details['program_end'], $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{starting_time}}') == true) {
                                                            $student_msg = str_replace('{{starting_time}}', $course_details['starting_time'], $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{ending_time}}') == true) {
                                                            $student_msg = str_replace('{{ending_time}}', $course_details['ending_time'], $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{today}}') == true) {
                                                            $student_msg = str_replace('{{today}}', $today, $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{expiry_date}}') == true) {
                                                            $student_msg = str_replace('{{expiry_date}}', $exp_date, $student_msg);
                                                        }
                                                        if (strpos($student_msg, '{{number_of_days}}') == true) {
                                                            $student_msg = str_replace('{{number_of_days}}', $inc, $student_msg);
                                                        }


                                                        if ($course_details['clickable_links'] != NULL) {
                                                            if (strpos($student_msg, '{{clickable_links}}') == true) {
                                                                $student_msg = str_replace('{{clickable_links}}', $course_details['clickable_links'], $student_msg);
                                                            }
                                                        } else {
                                                            if (strpos($student_msg, '{{clickable_links}}') == true) {
                                                                $student_msg = str_replace('{{clickable_links}}', '', $student_msg);
                                                            }
                                                        }

                                                        $student_msg .= $template_details['footer'];
                                                        //echo $student_msg;die;
                                                        $this->send_smtp_mail($student_msg, $subject, $student_email_to);
                                                        /*if($use_username == true && $use_password == true){
$uCreds['status'] = 'sent';
$uCreds['user_id'] = trim($student_id);
$uCreds['created_at'] = date('y-m-d h:i:s');
$this->db->where('id',$storedCreds['id']);
$this->db->update('storedCredentials',$uCreds);
}

if($use_token == true){
$uCreds['status'] = 'sent';
$uCreds['user_id'] = trim($student_id);
$uCreds['created_at'] = date('y-m-d h:i:s');
$this->db->where('id',$storedTokens['id']);
$this->db->update('storedCredentialsforICTF',$uCreds);
}*/
                                                        return true;
                                                    }

                                                    public function send_smtp_mail($msg = NULL, $sub = NULL, $to = NULL, $from = NULL)
                                                    {
                                                        //Load email library
                                                        $this->load->library('email');        // Always set content-type when sending HTML email
                                                        $headers = "MIME-Version: 1.0" . "\r\n";
                                                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                                                        // More headers
                                                        $headers .= 'From:EKAM AOL Dashboard<donationreceipts@vvmvp.org>' . "\r\n";
                                                        mail($to, $sub, $msg, $headers);
                                                        return true;
                                                    }



                                                    public function Bangalore_Karnataka_India($program_id = "")
                                                    {
                                                        redirect("public");
                                                    }

                                                    public function agreement()
                                                    {
                                                        $this->load->view('public/agreement');
                                                    }

                                                    public function logout()
                                                    {
                                                        session_destroy();
                                                        redirect('login');
                                                    }



                                                    public function webhook()
                                                    {
                                                        /* echo "hi";
exit(); */
                                                        $response = file_get_contents('php://input');
                                                        $data = json_decode($response, true);
                                                        //echo"<pre>";print_r(var_dump($data));die();

                                                        $webhook_body = $response;
                                                        $storedata['json_decode'] = $response;
                                                        $storedata['account_id'] = $data['account_id'];
                                                        $storedata['event'] = $data['event'];
                                                        $storedata['entity'] = $data['payload'];
                                                        $storedata['payment_id'] = $data['payload']['payment']['entity']['id'];
                                                        $storedata['amount'] = $data['payload']['payment']['entity']['amount'];
                                                        $storedata['currency'] = $data['payload']['payment']['entity']['currency'];
                                                        $storedata['status'] = $data['payload']['payment']['entity']['status'];
                                                        $storedata['order_id'] = $data['payload']['payment']['entity']['order_id'];
                                                        $storedata['invoice_id'] = $data['payload']['payment']['entity']['invoice_id'];
                                                        $storedata['international'] = $data['payload']['payment']['entity']['international'];
                                                        $storedata['method'] = $data['payload']['payment']['entity']['method'];
                                                        $storedata['amount_refunded'] = $data['payload']['payment']['entity']['amount_refunded'];
                                                        $storedata['refund_status'] = $data['payload']['payment']['entity']['refund_status'];
                                                        $storedata['captured'] = $data['payload']['payment']['entity']['captured'];
                                                        $storedata['description'] = $data['payload']['payment']['entity']['description'];
                                                        $storedata['card_id'] = $data['payload']['payment']['entity']['card_id'];
                                                        $storedata['bank'] = $data['payload']['payment']['entity']['bank'];
                                                        $storedata['vpa'] = $data['payload']['payment']['entity']['wallet'];
                                                        $storedata['email'] = $data['payload']['payment']['entity']['email'];
                                                        $storedata['contact'] = $data['payload']['payment']['entity']['contact'];
                                                        $storedata['notes'] = "null";
                                                        $storedata['fee'] = $data['payload']['payment']['entity']['fee'];
                                                        $storedata['tax'] = $data['payload']['payment']['entity']['tax'];
                                                        $storedata['error_code'] = $data['payload']['payment']['entity']['error_code'];
                                                        $storedata['error_description'] = $data['payload']['payment']['entity']['error_description'];
                                                        $storedata['error_source'] = $data['payload']['payment']['entity']['error_source'];
                                                        $storedata['error_step'] = $data['payload']['payment']['entity']['error_step'];
                                                        $storedata['error_reason'] = $data['payload']['payment']['entity']['error_reason'];
                                                        $storedata['created_at'] = $data['payload']['payment']['entity']['created_at'];
                                                        $storedata['entity'] = "payment";
                                                        $res = $this->db->insert('webhook_data', $storedata);


                                                        $this->pdb = $this->load->database("paypal", TRUE);
                                                        $anotherIns['data'] = $storedata['json_decode'];
                                                        $anotherIns['order_id'] = $storedata['order_id'];
                                                        $anotherIns['status'] = $storedata['status'];
                                                        $anotherIns['pay_id'] = $storedata['payment_id'];
                                                        $anotherIns['description'] = $storedata['description'];

                                                        if (isset($anotherIns) && !empty($anotherIns) && !empty($storedata['json_decode']) && $anotherIns['status'] == 'captured') {
                                                            $trans = $this->pdb->insert("razorpay_webhook", $anotherIns);
                                                            echo $this->pdb->affected_rows();
                                                        } else {
                                                            $orderId = $pay_id = $status = "-";
                                                            $data = json_encode(array("expected_signature" => $expected_signature, "webhook_signature" => $webhook_signature));
                                                            //$this->pdb->insert("razorpay_webhook", array("data"=>$data,"pay_id"=>$pay_id,"status"=>$status,"order_id"=>$orderId));

                                                            echo " Unauthorized attempt. Hang in there buddy!  Your details have been noted. Cops will be meeting you soon.  ";
                                                        }
                                                        //print_r($_REQUEST); print_r($_SERVER);
                                                        # code...https://register.vvmvp.org/ekam/index.php/cron/payments/razorpay_response_hook
                                                        $this->addInPR($storedata);

                                                        //Route API Work Starts--
                                                        //For Xfering the razorpay amount to the respective ashrams linked accounts
                                                        $this->MannHitXfer($storedata['payment_id']);
                                                        //Route API Work Ends--
                                                    }


                                                    public function addInPR($rarray)
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $udata['status'] = 'CAPTURED';
                                                        $udata['razorpay_payment_id'] = $rarray['payment_id'];
                                                        $udata['amount_paid'] = $rarray['amount'];
                                                        $check1 = $this->adb->where('razorpay_order_id', $rarray['order_id'])->get('payments_razorpay')->row_array();
                                                        if (!empty($check1) && trim($check1['razorpay_payment_id']) == '' && $rarray['status'] == 'captured') {
                                                            $upquery = $this->adb->where('id', $check1['id'])->update('payments_razorpay', $udata);
                                                            $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                            if (!empty($check2) && trim($check2['status']) != 'CAPTURED') {
                                                                $rdata['status'] = 'CAPTURED';
                                                                //for registratin Table starts
                                                                $upquery2 = $this->adb->where('id', $check1['registration_id'])->update('registrations', $rdata);
                                                            }

                                                            //Route API Work Starts--
                                                            //For Xfering the razorpay amount to the respective ashrams linked accounts
                                                            // $this->hitXferAPI($rarray);
                                                            //Route API Work Ends--
                                                        }
                                                    }

                                                    public function hitXferAPI($rarray)
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        //echo $aid;
                                                        if ($rarray !== NULL) {
                                                            $checkXferred = $this->db->where('pay_id', trim($rarray['payment_id']))->get('transferred_records')->row_array();
                                                            if ($checkXferred == NULL) {
                                                                $check1 = $this->adb->where('razorpay_order_id', $rarray['order_id'])->get('payments_razorpay')->row_array();
                                                                $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                                $program_id = $check2['program_id'];
                                                                $user_id = $check1['visitor_id'];
                                                                $fullName = $check1['first_name'] . ' ' . $check1['last_name'];

                                                                $aid = trim($check2['ashram_id']);
                                                                $getAshramDetails = $this->db->get('m_ashram')->result();
                                                                foreach ($getAshramDetails as $k => $v) {
                                                                    $orgid = trim($v->organisation_id);
                                                                    $iid = trim($v->id);
                                                                    $ashId = $orgid . $iid;
                                                                    $program_id = trim($program_id);
                                                                    if ($program_id == NULL) {
                                                                        $role = 'did';
                                                                    } else {
                                                                        $role = 'pid';
                                                                    }

                                                                    if ($ashId == $aid) {
                                                                        $acc_id = trim($v->acc_id);
                                                                        $PayId = $rarray['payment_id'];
                                                                        $amount = $rarray['amount'];

                                                                        try {
                                                                            $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                                                                            $transfer['transfers'][0]['account'] = $acc_id;
                                                                            $transfer['transfers'][0]['amount'] = $amount;
                                                                            $transfer['transfers'][0]['currency'] = "INR";
                                                                            $transfer['transfers'][0]['notes']['name'] = $fullName;
                                                                            $transfer['transfers'][0]['notes'][$role] = $user_id;
                                                                            //$transfer['transfers'][0]['linked_account_notes'] = array($v->name);
                                                                            /*$res = $api->payment->fetch($PayId)->transfer(array('transfers' => array('account'=> $acc_id, 'amount'=> $amount, 'currency'=>'INR', 'notes'=> array('name'=>$fullName, $role => $user_id), 'linked_account_notes'=>array($v->name))));*/
                                                                            $res = $api->payment->fetch($PayId)->transfer($transfer);
                                                                            //if($res !== NULL){
                                                                            $tdata['pay_id'] = trim($rarray['payment_id']);
                                                                            $tdata['pay_status'] = $rarray['status'];
                                                                            $tdata['order_id'] = trim($rarray['order_id']);
                                                                            $tdata['xfer_to'] = trim($acc_id);
                                                                            //$tdata['xfer_id'] = trim($acc_id);
                                                                            $tdata['created_at'] = date('Y-m-d H:i:s');
                                                                            $tdata['aid'] = $aid;
                                                                            $this->db->insert('transferred_records', $tdata);
                                                                            //}
                                                                        }
                                                                        //catch exception
                                                                        catch (Exception $e) {
                                                                            //echo 'Message: ' .$e->getMessage();//die;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    public function AutoCapturePayAPI()
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $storedata = array();
                                                        $from = strtotime(date('Y-m-d') . ' 00:00:00');
                                                        $to = strtotime(date('Y-m-d') . ' 23:59:00');
                                                        $this->db->where('created_at >=', $from);
                                                        $this->db->where('created_at <=', $to);
                                                        $this->db->where('status', 'authorized');
                                                        $todayCaps = $this->db->get('webhook_data')->result();
                                                        //echo $this->db->last_query();
                                                        //print_r($todayCaps);die;
                                                        foreach ($todayCaps as $k => $v) {
                                                            $checkIfNotCaptured = $this->db->where('payment_id', trim($v->payment_id))->where('status', 'captured')->get('webhook_data')->row();
                                                            if ($checkIfNotCaptured !== NULL) {
                                                                unset($todayCaps[$k]);
                                                            }
                                                        }
                                                        $todayCaps = array_values($todayCaps);
                                                        foreach ($todayCaps as $tdc) {
                                                            $response = $tdc->json_decode;
                                                            $data = json_decode($response, true);
                                                            //echo"<pre>";print_r(var_dump($data));die();

                                                            $webhook_body = $response;
                                                            $storedata['json_decode'] = $response;
                                                            $storedata['account_id'] = $data['account_id'];
                                                            $storedata['event'] = $data['event'];
                                                            $storedata['entity'] = $data['payload'];
                                                            $storedata['payment_id'] = $data['payload']['payment']['entity']['id'];
                                                            $storedata['amount'] = $data['payload']['payment']['entity']['amount'];
                                                            $storedata['currency'] = $data['payload']['payment']['entity']['currency'];
                                                            $storedata['status'] = $data['payload']['payment']['entity']['status'];
                                                            $storedata['order_id'] = $data['payload']['payment']['entity']['order_id'];
                                                            $storedata['invoice_id'] = $data['payload']['payment']['entity']['invoice_id'];
                                                            $storedata['international'] = $data['payload']['payment']['entity']['international'];
                                                            $storedata['method'] = $data['payload']['payment']['entity']['method'];
                                                            $storedata['amount_refunded'] = $data['payload']['payment']['entity']['amount_refunded'];
                                                            $storedata['refund_status'] = $data['payload']['payment']['entity']['refund_status'];
                                                            $storedata['captured'] = $data['payload']['payment']['entity']['captured'];
                                                            $storedata['description'] = $data['payload']['payment']['entity']['description'];
                                                            $storedata['card_id'] = $data['payload']['payment']['entity']['card_id'];
                                                            $storedata['bank'] = $data['payload']['payment']['entity']['bank'];
                                                            $storedata['vpa'] = $data['payload']['payment']['entity']['wallet'];
                                                            $storedata['email'] = $data['payload']['payment']['entity']['email'];
                                                            $storedata['contact'] = $data['payload']['payment']['entity']['contact'];
                                                            $storedata['notes'] = "null";
                                                            $storedata['fee'] = $data['payload']['payment']['entity']['fee'];
                                                            $storedata['tax'] = $data['payload']['payment']['entity']['tax'];
                                                            $storedata['error_code'] = $data['payload']['payment']['entity']['error_code'];
                                                            $storedata['error_description'] = $data['payload']['payment']['entity']['error_description'];
                                                            $storedata['error_source'] = $data['payload']['payment']['entity']['error_source'];
                                                            $storedata['error_step'] = $data['payload']['payment']['entity']['error_step'];
                                                            $storedata['error_reason'] = $data['payload']['payment']['entity']['error_reason'];
                                                            $storedata['created_at'] = $data['payload']['payment']['entity']['created_at'];
                                                            $storedata['entity'] = "payment";
                                                            //}
                                                            $rarray = $storedata;
                                                            //print_r($rarray);die();
                                                            $this->db = $this->load->database("default", true);
                                                            $this->adb = $this->load->database("ashrams", true);
                                                            //echo $aid;
                                                            if ($rarray !== NULL) {
                                                                $checkXferred = $this->db->where('pay_id', trim($rarray['payment_id']))->get('transferred_records')->row_array();
                                                                $check1 = $this->adb->where('razorpay_order_id', $rarray['order_id'])->get('payments_razorpay')->row_array();
                                                                $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                                $program_id = $check2['program_id'];
                                                                $user_id = $check1['visitor_id'];
                                                                $fullName = $check1['first_name'] . ' ' . $check1['last_name'];

                                                                $aid = trim($check2['ashram_id']);
                                                                $getAshramDetails = $this->db->get('m_ashram')->result();
                                                                $orgid = trim($v->organisation_id);
                                                                $iid = trim($v->id);
                                                                $ashId = $orgid . $iid;
                                                                $program_id = trim($program_id);
                                                                if ($program_id == NULL) {
                                                                    $role = 'did';
                                                                } else {
                                                                    $role = 'pid';
                                                                }

                                                                $acc_id = trim($v->acc_id);
                                                                $PayId = $rarray['payment_id'];
                                                                $amount = $rarray['amount'];

                                                                try {
                                                                    $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                                                                    $transfer['transfers'][0]['account'] = $acc_id;
                                                                    $transfer['transfers'][0]['amount'] = $amount;
                                                                    $transfer['transfers'][0]['currency'] = "INR";
                                                                    $transfer['transfers'][0]['notes']['name'] = $fullName;
                                                                    $transfer['transfers'][0]['notes'][$role] = $user_id;

                                                                    $res = $api->payment->fetch($PayId)->capture(array('amount' => $amount, 'currency' => 'INR'));

                                                                    echo '<br> Captured ' . $PayId;
                                                                }
                                                                //catch exception
                                                                catch (Exception $e) {
                                                                    echo '<br> Message: ' . $e->getMessage(); //die;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    public function hit2XferAPI()
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $storedata = array();
                                                        $from = strtotime(date('Y-m-d') . ' 00:00:00');
                                                        $to = strtotime(date('Y-m-d') . ' 23:59:00');
                                                        $this->db->where('created_at >=', $from);
                                                        $this->db->where('created_at <=', $to);
                                                        $this->db->where('status', 'captured');
                                                        $todayCaps = $this->db->get('webhook_data')->result();
                                                        //echo $this->db->last_query();
                                                        //print_r($todayCaps);die;
                                                        foreach ($todayCaps as $tdc) {

                                                            $response = $tdc->json_decode;
                                                            $data = json_decode($response, true);
                                                            //echo"<pre>";print_r(var_dump($data));die();

                                                            $webhook_body = $response;
                                                            $storedata['json_decode'] = $response;
                                                            $storedata['account_id'] = $data['account_id'];
                                                            $storedata['event'] = $data['event'];
                                                            $storedata['entity'] = $data['payload'];
                                                            $storedata['payment_id'] = $data['payload']['payment']['entity']['id'];
                                                            $storedata['amount'] = $data['payload']['payment']['entity']['amount'];
                                                            $storedata['currency'] = $data['payload']['payment']['entity']['currency'];
                                                            $storedata['status'] = $data['payload']['payment']['entity']['status'];
                                                            $storedata['order_id'] = $data['payload']['payment']['entity']['order_id'];
                                                            $storedata['invoice_id'] = $data['payload']['payment']['entity']['invoice_id'];
                                                            $storedata['international'] = $data['payload']['payment']['entity']['international'];
                                                            $storedata['method'] = $data['payload']['payment']['entity']['method'];
                                                            $storedata['amount_refunded'] = $data['payload']['payment']['entity']['amount_refunded'];
                                                            $storedata['refund_status'] = $data['payload']['payment']['entity']['refund_status'];
                                                            $storedata['captured'] = $data['payload']['payment']['entity']['captured'];
                                                            $storedata['description'] = $data['payload']['payment']['entity']['description'];
                                                            $storedata['card_id'] = $data['payload']['payment']['entity']['card_id'];
                                                            $storedata['bank'] = $data['payload']['payment']['entity']['bank'];
                                                            $storedata['vpa'] = $data['payload']['payment']['entity']['wallet'];
                                                            $storedata['email'] = $data['payload']['payment']['entity']['email'];
                                                            $storedata['contact'] = $data['payload']['payment']['entity']['contact'];
                                                            $storedata['notes'] = "null";
                                                            $storedata['fee'] = $data['payload']['payment']['entity']['fee'];
                                                            $storedata['tax'] = $data['payload']['payment']['entity']['tax'];
                                                            $storedata['error_code'] = $data['payload']['payment']['entity']['error_code'];
                                                            $storedata['error_description'] = $data['payload']['payment']['entity']['error_description'];
                                                            $storedata['error_source'] = $data['payload']['payment']['entity']['error_source'];
                                                            $storedata['error_step'] = $data['payload']['payment']['entity']['error_step'];
                                                            $storedata['error_reason'] = $data['payload']['payment']['entity']['error_reason'];
                                                            $storedata['created_at'] = $data['payload']['payment']['entity']['created_at'];
                                                            $storedata['entity'] = "payment";
                                                            //}
                                                            $rarray = $storedata;
                                                            //print_r($rarray);die();
                                                            $this->db = $this->load->database("default", true);
                                                            $this->adb = $this->load->database("ashrams", true);
                                                            //echo $aid;
                                                            if ($rarray !== NULL) {
                                                                $checkXferred = $this->db->where('pay_id', trim($rarray['payment_id']))->get('transferred_records')->row_array();
                                                                if ($checkXferred == NULL) {
                                                                    $check1 = $this->adb->where('razorpay_order_id', $rarray['order_id'])->get('payments_razorpay')->row_array();
                                                                    $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                                    $program_id = $check2['program_id'];
                                                                    $user_id = $check1['visitor_id'];
                                                                    $fullName = $check1['first_name'] . ' ' . $check1['last_name'];

                                                                    $aid = trim($check2['ashram_id']);
                                                                    $getAshramDetails = $this->db->get('m_ashram')->result();
                                                                    foreach ($getAshramDetails as $k => $v) {
                                                                        $orgid = trim($v->organisation_id);
                                                                        $iid = trim($v->id);
                                                                        $ashId = $orgid . $iid;
                                                                        $program_id = trim($program_id);
                                                                        if ($program_id == NULL) {
                                                                            $role = 'did';
                                                                        } else {
                                                                            $role = 'pid';
                                                                        }

                                                                        if ($ashId == $aid) {
                                                                            $acc_id = trim($v->acc_id);
                                                                            $PayId = $rarray['payment_id'];
                                                                            $amount = $rarray['amount'];

                                                                            try {
                                                                                $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                                                                                $transfer['transfers'][0]['account'] = $acc_id;
                                                                                $transfer['transfers'][0]['amount'] = $amount;
                                                                                $transfer['transfers'][0]['currency'] = "INR";
                                                                                $transfer['transfers'][0]['notes']['name'] = $fullName;
                                                                                $transfer['transfers'][0]['notes'][$role] = $user_id;
                                                                                //print_r($transfer);die;
                                                                                //$transfer['transfers'][0]['linked_account_notes'] = array($v->name);
                                                                                /*$res = $api->payment->fetch($PayId)->transfer(array('transfers' => array('account'=> $acc_id, 'amount'=> $amount, 'currency'=>'INR', 'notes'=> array('name'=>$fullName, $role => $user_id), 'linked_account_notes'=>array($v->name))));*/
                                                                                $res = $api->payment->fetch($PayId)->transfer($transfer);
                                                                                //if($res !== NULL){
                                                                                $tdata['pay_id'] = trim($rarray['payment_id']);
                                                                                $tdata['pay_status'] = $rarray['status'];
                                                                                $tdata['order_id'] = trim($rarray['order_id']);
                                                                                $tdata['xfer_to'] = trim($acc_id);
                                                                                //$tdata['xfer_id'] = trim($acc_id);
                                                                                $tdata['created_at'] = date('Y-m-d H:i:s');
                                                                                $tdata['aid'] = $aid;
                                                                                $this->db->insert('transferred_records', $tdata);
                                                                                //}
                                                                                echo '<br>' . $this->db->last_query();
                                                                            }
                                                                            //catch exception
                                                                            catch (Exception $e) {
                                                                                echo '<br> Message: ' . $e->getMessage(); //die;
                                                                            }
                                                                        }
                                                                    }
                                                                } else {
                                                                    echo " Already Xferred!!";
                                                                }
                                                            }
                                                        }
                                                    }



                                                    public function MannHitXfer($payId)
                                                    {
                                                        //$acc_id = 'acc_Cz318rdxCRiURz';
                                                        $getPayD = $this->db->where('payment_id', $payId)->where('status', 'captured')->get('webhook_data')->row_array();
                                                        //echo trim($getPayD['status']);die;
                                                        if (trim($getPayD['status']) !== NULL) {
                                                            $check1 = $this->adb->where('razorpay_order_id', trim($getPayD['order_id']))->get('payments_razorpay')->row_array();
                                                            $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                            $program_id = $check2['program_id'];
                                                            $user_id = $check1['visitor_id'];
                                                            $fullName = $check1['first_name'] . ' ' . $check1['last_name'];

                                                            $aid = trim($check2['ashram_id']);
                                                            //echo"<br>". $aid;
                                                            $getAshramDetails = $this->db->get('m_ashram')->result();
                                                            foreach ($getAshramDetails as $k => $v) {
                                                                $orgid = trim($v->organisation_id);
                                                                $iid = trim($v->id);
                                                                $ashId = $orgid . $iid;
                                                                $program_id = trim($program_id);
                                                                if ($program_id == NULL) {
                                                                    $role = 'did';
                                                                } else {
                                                                    $role = 'pid';
                                                                }

                                                                if ($ashId == $aid) {
                                                                    //echo "<br>".$ashId."<br>";

                                                                    $acc_id = trim($v->acc_id);
                                                                    $PayId = $payId;
                                                                    $amount = $getPayD['amount'];
                                                                    try {
                                                                        $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');

                                                                        $v->country_state_location = str_replace(',', ', ', $v->country_state_location);

                                                                        $transfer['transfers'][0]['account'] = $acc_id;
                                                                        $transfer['transfers'][0]['amount'] = $amount;
                                                                        $transfer['transfers'][0]['currency'] = "INR";
                                                                        $transfer['transfers'][0]['notes']['name'] = $fullName;
                                                                        $transfer['transfers'][0]['notes'][$role] = $user_id;
                                                                        //$transfer['transfers'][0]['linked_account_notes'] = array($v->country_state_location);
                                                                        /*$res = $api->payment->fetch($PayId)->transfer(array('transfers' => array('account'=> $acc_id, 'amount'=> $amount, 'currency'=>'INR', 'notes'=> array('name'=>$fullName, $role => $user_id), 'linked_account_notes'=>array($v->name))));*/
                                                                        //echo $PayId;
                                                                        //echo"<pre> Transfer Array ";print_r($transfer);die;
                                                                        $res = $api->payment->fetch($PayId)->transfer($transfer);
                                                                        //echo
                                                                        //print_r($res);//die();
                                                                        //$res = json_decode($res,true);
                                                                        //print_r($res);die();
                                                                        if ($res !== NULL) {
                                                                            $tdata['pay_id'] = trim($payId);
                                                                            $tdata['pay_status'] = $getPayD['status'];
                                                                            $tdata['order_id'] = trim($getPayD['order_id']);
                                                                            $tdata['xfer_to'] = trim($acc_id);
                                                                            //$tdata['xfer_id'] = trim($acc_id);
                                                                            $tdata['created_at'] = date('Y-m-d H:i:s');
                                                                            $tdata['aid'] = $aid;
                                                                            $this->db->insert('transferred_records', $tdata);
                                                                            echo "<pre>";
                                                                            print_r($tdata);
                                                                        }
                                                                    }
                                                                    //catch exception
                                                                    catch (Exception $e) {
                                                                        echo 'Message: ' . $e->getMessage(); //die;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    public function addIntoRealWebhook()
                                                    {
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $getNewWh = $this->db->where('payment_id !=', NULL)->where('order_id !=', NULL)->get('webhook_data')->result_array();
                                                        //echo $this->db->last_query();die;
                                                        foreach ($getNewWh as $key => $rarray) {
                                                            /*$this->pdb = $this->load->database("paypal",TRUE);
$anotherIns['data'] = $storedata['json_decode'];
$anotherIns['order_id'] = $storedata['order_id'];
$anotherIns['status'] = $storedata['status'];
$anotherIns['pay_id'] = $storedata['payment_id'];
$anotherIns['description'] = $storedata['description'];

$checkAlready = $this->pdb->where('status',$anotherIns['status'])->where('pay_id',$anotherIns['pay_id'])->get('razorpay_webhook')->row_array();
if($checkAlready == ''){
if(isset($anotherIns) && !empty($anotherIns))
{
$trans = $this->pdb->insert("razorpay_webhook", $anotherIns);
echo $this->pdb->affected_rows();
}
}*/
                                                            $this->adb = $this->load->database("ashrams", true);
                                                            $udata['status'] = 'CAPTURED';
                                                            $udata['razorpay_payment_id'] = $rarray['payment_id'];
                                                            $udata['amount_paid'] = $rarray['amount'];
                                                            $check1 = $this->adb->where('razorpay_order_id', $rarray['order_id'])->get('payments_razorpay')->row_array();
                                                            if (!empty($check1) && trim($check1['razorpay_payment_id']) == '' && $rarray['status'] == 'captured') {
                                                                $upquery = $this->adb->where('id', $check1['id'])->update('payments_razorpay', $udata);
                                                                $check2 = $this->adb->where('id', $check1['registration_id'])->get('registrations')->row_array();
                                                                if (!empty($check2) && trim($check2['status']) != 'CAPTURED') {
                                                                    $rdata['status'] = 'CAPTURED';
                                                                    //for registratin Table starts
                                                                    $upquery2 = $this->adb->where('id', $check1['registration_id'])->update('registrations', $rdata);
                                                                    echo "<br> " . $check1['email'] . ' ' . $check1['razorpay_payment_id'];
                                                                }
                                                            }
                                                        }
                                                    }


                                                    public function UpdateIntoRegistrations()
                                                    {
                                                        //echo"Hello";
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $getNewWh = $this->adb->where('razorpay_payment_id !=', NULL)->get('payments_razorpay')->result_array();

                                                        foreach ($getNewWh as $key => $rarray) {
                                                            if (trim($rarray['registration_id']) != '' && $rarray['status'] == 'captured') {
                                                                $check1 = $this->adb->where('id', trim($rarray['registration_id']))->get('registrations')->row_array();
                                                                //echo $this->adb->last_query();//die;
                                                                if (!empty($check1) && trim($check1['status']) != 'CAPTURED') {
                                                                    $rdata['status'] = 'CAPTURED';
                                                                    //for registratin Table starts
                                                                    $upquery2 = $this->adb->where('id', trim($check1['id']))->update('registrations', $rdata);
                                                                    echo $check1['id'] . "<br>";
                                                                }
                                                            }
                                                        }
                                                    }


                                                    public function fixWrongPids()
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);

                                                        $getWh = $this->db->where('payment_id !=', NULL)->where('order_id !=', NULL)->get('webhook_data')->result_array();

                                                        //case1

                                                        foreach ($getWh as $key => $rarray) {
                                                            $getPR = $this->adb->where('razorpay_order_id', trim($rarray['order_id']))->order_by('id', 'DESC')->get('payments_razorpay')->row_array();
                                                            if (trim($rarray['payment_id']) != trim($getPR['razorpay_payment_id'])) {
                                                                $checkCapt = $this->db->where('status', 'captured')->where('payment_id', trim($rarray['payment_id']))->get('webhook_data')->row_array();
                                                                if (!empty($checkCapt)) {
                                                                    $udata['status'] = $rarray['status'];
                                                                    $udata['razorpay_payment_id'] = $rarray['payment_id'];
                                                                    $updateRightPids = $this->adb->where('id', $getPR['id'])->update('payments_razorpay', $udata);
                                                                } else {
                                                                    $checkNCapt = $this->db->where('payment_id', trim($rarray['payment_id']))->order_by('id', 'DESC')->get('webhook_data')->row_array();
                                                                    $udata2['status'] = $checkNCapt['status'];
                                                                    $udata2['razorpay_payment_id'] = $checkNCapt['payment_id'];
                                                                    $updateRightPids = $this->adb->where('id', $getPR['id'])->update('payments_razorpay', $udata2);
                                                                    $rdata['status'] = 'Processing';
                                                                    //for registration Table starts
                                                                    $upquery2 = $this->adb->where('id', trim($getPR['id']))->update('registrations', $rdata);
                                                                    echo $getPR['registration_id'] . "<br>";
                                                                }
                                                            }
                                                        }

                                                        //case1 ends
                                                    }


                                                    function updateReceiptNoSoFarForCurrentFY($ashram_id)
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $this->adb->where('ashram_id', $ashram_id);
                                                        $regs =  $this->adb->where('receipt_no !=', '')->where('id >', '97021')->get('registrations')->result();
                                                        //echo $this->adb->last_query();
                                                        $count = count($regs);
                                                        //echo "count is".$count."<br>";
                                                        for ($i = 0; $i <= $count; $i++) {
                                                            //if(trim($regs[$i]->receipt_no) !== ''){
                                                            echo "<br>regid " . $regs[$i]->id;
                                                            $rn = $regs[$i]->receipt_no;
                                                            $rn = explode('/22-23/', $rn);
                                                            $lid = trim($rn[1]);
                                                            echo " lid " . $lid;
                                                            $j = $i + 1;
                                                            if (strlen($j) == '1') {
                                                                $j = '00' . $j;
                                                            }
                                                            if (strlen($j) == '2') {
                                                                $j = '0' . $j;
                                                            }
                                                            $updata['receipt_no'] = $rn[0] . '/22-23/' . $j;
                                                            echo "<br>Updata " . $updata['receipt_no'];
                                                            $this->adb->where('id', $regs[$i]->id)->update('registrations', $updata);
                                                        }
                                                        //}
                                                    }

                                                    function getAshrmsIds()
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        $query = "SELECT DISTINCT `ashram_id` FROM `registrations` WHERE `id`>'97021' ORDER BY `ashram_id` DESC";
                                                        $aids =  $this->adb->query($query)->result();
                                                        //print_r($aids);
                                                        foreach ($aids as $key => $value) {
                                                            if ($value->ashram_id !== '') {
                                                                $this->updateReceiptNoSoFarForCurrentFY($value->ashram_id);
                                                            }
                                                        }
                                                    }

                                                    function getOldDataByOrderId()
                                                    {
                                                        $this->db = $this->load->database("default", true);
                                                        $this->adb = $this->load->database("ashrams", true);
                                                        //echo $aid;
                                                        $this->adb->where('created_at >=', '1617215400');
                                                        $this->adb->where('created_at <=', '1640025000');
                                                        $result = $this->adb->get('payments_razorpay')->result();
                                                        foreach ($result as $k => $v) {
                                                            try {
                                                                $orderId = $v->razorpay_order_id;
                                                                $api = new RazorpayApi('rzp_live_36oDova5OdHkbP', '3NrcWcXUlwTc9zALkMIdQ6go');
                                                                $res = $api->order->fetch($orderId)->payments();
                                                                echo "<pre>";
                                                                print_r($res);
                                                                die;
                                                                $webhook_json_array = array();
                                                            }
                                                            //catch exception
                                                            catch (Exception $e) {
                                                                echo 'Message: ' . $e->getMessage(); //die;
                                                            }
                                                        }
                                                    }
                                                }
