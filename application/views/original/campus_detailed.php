<?




$orgid = substr($ashram_id, 0, 2);
$id = substr($ashram_id, 2, 2);

//die();

function allowed($value = '')
{
    if (isset($_GET['test'])) {
        echo "<pre>";
        print_r($_SERVER['REQUEST_URI']);




        die();
    }

    if (strpos($_SERVER['REQUEST_URI'], 'public/campus/index/0111') === false)  // NOT JAIPur
    {
        if (strtolower($_SESSION['publicuser']['country']) != "india" || strtolower($_SESSION['publicuser']['country_last_year']) != "india") {
            return "<center><br><br><h4 style='color:lightyellow;background-color:darkgrey;'>Currently, only Indian nationals are allowed to register online for other campus.</h4></center>";
        }
    } else echo " <h1> JAIPUR INTERNATIONL TESTING RULE EXCEPTION IS:  ON!</h1>";
    return "ok";
}


$this->db = $this->load->database("default", true);

$asha = $this->db
    ->where("organisation_id", $orgid)
    ->where("id", $id)
    ->get("m_ashram")
    ->result_array();

if (empty($asha)) die("Campus Data Missing");

$ash = array_pop($asha);

//echo "<pre>"; print_r($_SESSION['publicuser']); die();

$ashram_name = $ash['name'];
$ashramname = $ash['displayname'];
$description = $ash['description'] . "  " . $ash['moreinfo'] . "  ";
//"Located on the banks of the tranquil Mahi River, spread over 14 acres of land, makes for the ideal retreat with its undulating hillocks and serene ambience.";


$this->load->view('public/header');
?>

<!-- Bootstrap core CSS -->
<link href="<? echo base_url(); ?>css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap theme -->
<link href="<? echo base_url(); ?>css/bootstrap-theme.min.css" rel="stylesheet">
<script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>
<script src="<? echo base_url(); ?>js/bootstrap.min.js"></script>


<meta name="description" content="Art of Living Campus at <?= $ashramname ?> ">
<title><?= $ashramname ?> Campus</title>
<style type="text/css">
    .brow {
        clear: both;
        color: white;
        background-color: #1aa8c6;
        padding: 20px;
        text-align: left;
        margin-top: 20px;
        border: 15px double white;
        border-radius: 20px;
    }

    .crow {
        clear: both;
        color: white;
        background-color: #c68b1a;
        ;
        padding: 20px;
        text-align: left;
        margin-top: 20px;
        border: 15px double white;
        border-radius: 20px;
    }

    .pgm {}
</style>


<script type="text/javascript">
    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
</script>





</head>

<body>
    <nav class="navbar navbar-default navbar-fixed-top topnav" role="navigation">
        <div class="container topnav">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                <a class="navbar-brand topnav" href="#"><img src='<? echo  base_url(); ?>logo.png' alt='Art of Living Logo' style='height:30px;' /></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <? /* <li> <a href="<? echo site_url("public/welcome/") ; ?>" >Home</a> </li> */ ?>
                    <li> <a href="<? echo site_url("public/campus/"); ?>">Campuses</a> </li>
                    <li id='donatemenu'> <a href="<? echo site_url("public/campus/donatenow/" . $ashram_id); ?>">Donate</a> </li>
                    <li id='contactmenu'> <a href="#contact">Contact</a> </li>
                    <li> <a href="<? echo site_url("public/welcome/faqs#{$ashram_name}"); ?>">FAQs</a> </li>
                    <li>
                        <?
                        if (isset($_SESSION['publicuser'])) {

                        ?>
                            <a href='<? echo site_url("public/campus/receipts"); ?>'>Receipts</a>
                    </li>
                    <li><a href='<? echo site_url("public/user/profile"); ?>'>Profile</a></li>
                    <li>
                        <a href='<? echo site_url("public/login/logout") . "?next=" . urlencode(base_url("index.php/" . uri_string())); ?>'>Logout</a>
                    <?
                        } else {
                    ?>
                        <a href='<? echo site_url("public/welcome/login/") . "?next=" . urlencode(base_url("index.php/" . uri_string())); ?>'>Login</a>
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
    <br><br>

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

    <a class="github-ribbon" href="<?= site_url("public/campus/index/" . $ashram_id) ?>" title="<?= $ashramname ?>"><?= $ashramname ?></a>

    <div class="login">
        <div class="container">
            <br style="clear:both;"><br>
            <div class="row" id='contact'>
                <div class='col-xs-12'>
                    <center>
                        <br>
                        <h1>Campus at <?= $ash['displayname'] ?></h1>
                        <br>
                        <? /* <img class='tn' src="<?=base_url()."/imagess/".$ash['name']?>.jpg" alt="<?=$ash['displayname']?> Campus">
//https://register.vvmvp.org/ekam/ekamuploadedreferencefiles/f1eff-gurudev-all-visit-5.jpg
*/ ?>
                        <img class='tn' src="<?= base_url() . "ekamuploadedreferencefiles/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus">
                        <h3><?= $ash['address'] ?></h3>
                        <hr>
                        <h3><?= $ash['email'] ?></h3>
                    </center>
                </div>


                <div class='col-xs-12'>
                    &nbsp;
                </div>
            </div><!-- /.row -->
            <br style="clear:both;">

            <?
            if (trim($ash['top_photo']) != '') { ?>
                <center>
                    <img class='tn' src="<?= base_url() . "ekamuploadedreferencefiles/" . $ash['top_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus Top Banner">
                </center>
                <? }




            $this->adb = $this->load->database("ashrams", true);

            if (isset($_SESSION['publicuser'])) {

                $vpa = $this->adb
                    ->where("ashram_id", $ashram_id)
                    ->like("participantreportview_uids_csv", "UID" . $_SESSION['publicuser']['id'] . "UID", "both")
                    ->order_by("program_start", "desc")
                    ->get("programs")
                    ->result_array();
                if (!empty($vpa)) {

                    //echo "<pre>"; print_r($vpa); die();
                    $t =  '<div class="row crow btn-success"><div class="col-xs-12   "><center> <h4>View Program Participant Report:</h4><table border=1 class="ttt">';
                    foreach ($vpa as $key => $vp) {
                        $t .= "<tr><td>Program #{$vp['id']}</td><td><a class='btn btn-default btn-lg' href='" . site_url("public/participantreport/program/{$vp['ashram_id']}/{$vp['id']}/") . "'>{$vp['program_name']}</a> </td><td>Program Start: {$vp['program_start']}</td></tr>";
                    }
                    $t .= "</table></center></div></div><style>.ttt td{ padding-top:1px; padding-bottom:5px; padding-left:10px; padding-right:10px;}</style>";
                    echo $t;
                }


                $ra = $this->adb
                    ->where("ashram_id", $ashram_id)
                    ->where("visitor_id", $_SESSION['publicuser']["id"])
                    ->where("`arrival` >= '" . date("Ymd") . "' ")
                    ->where("status", "donationpending")
                    ->order_by("arrival", "asc")
                    ->get("registrations")
                    ->result_array();
                 
                //echo "<pre>"; print_r($pa);  echo "</pre>";
                if (!empty($ra)) {

                    foreach ($ra as $k => $r) {
                ?>
                        <div class="row crow btn-warning">
                            <div class="col-xs-12   ">
                                <center>
                                    <?
                                    $pa = $this->adb
                                        ->where("ashram_id", $ashram_id)
                                        ->where("id", $r['program_id'])
                                        ->order_by("program_start", "asc")
                                        ->get("programs")
                                        ->result_array();
                                    if (!empty($pa)) {
                                        $p = array_pop($pa);
                                        echo "<p><h4>Donation Pending: {$p['program_name']}</h4></p>";

                                        //echo "<pre>"; print_r($p); die();
                                        if (intval($p['arrival']) < 100)
                                            $p['arrival'] = date("d/m/Y", strtotime($p['program_start'] . " -1 day "));
                                        else
                                            $p['arrival'] = ddmmyyyy($p['arrival']);


                                        if (intval($p['departure']) < 100)
                                            $p['departure'] = date("d/m/Y", strtotime($p['program_end'] . " +1 day "));
                                        else
                                            $p['departure'] = ddmmyyyy($p['departure']);


                                        echo "<p>{$p['arrival']} - {$p['departure']}</p>";

                                        if ($_SESSION['natint'] == "national" && $p['nationals_allowed'] == "0") // not allowed
                                        {
                                            $allowed = "<center><br><br><h1 style='color:orange; '> Sorry, Indian Nationals are not allowed for this program at the moment.</h1></center>";
                                        } else if ($_SESSION['natint'] != "national" && $p['internationals_allowed'] == "0") // not allowed
                                        {
                                            $allowed = "<center><br><br><h1 style='color:orange; '>  Sorry, Internationals are not allowed for this program at the moment.</h1></center>";
                                        } else $allowed = "ok";

                                        //die($allowed);

                                        if ($allowed == "ok") {
                                            $pay = "campus/payrazor";
                                            if ($r['currency'] == "USD") $pay = "payu/payusd";
                                    ?>
                                            <center>
                                                <h5><a href="<?= site_url("public/{$pay}/{$r['id']}/") ?>" class="btn btn-md btn-default mt pbtn<?= $p['id'] ?> pbtn">Go to Donation Page <?= $r['amount'] . " " . $r['currency'] ?>/- </a></h5>
                                                </h5>
                                            </center>
                                    <?
                                            // &nbsp; <a class='btn btn-warning btn-md mt pbtn<?=$p['id']? > pbtn' style='display:none;'     href='<?=$href? >'>Register for Someone Else</a>
                                        } else echo $allowed;
                                    }

                                    ?>
                                </center>
                            </div>
                        </div>
                <?
                    }
                }
            }

            $pa = $this->adb
                ->where("ashram_id", $ashram_id)
                ->where("public", "1")
                ->where("`program_start` >= '" . date("Ymd") . "' ")
                ->order_by("program_start", "asc")
                ->get("programs")->result_array();

            //echo "<pre>"; print_r($pa); die();

            if (empty($pa)) {
                ?>
                <div class="row brow btn-info">
                    <div class="col-xs-12   ">
                        <center>
                            <h1> No Programs found in Campus at<br> <?= $ashramname ?></h1>
                        </center>
                    </div>
                </div>
            <?

            } else
                foreach ($pa as $key => $p) {
                    //echo "<pre>"; print_r($p); die();
                    if (intval($p['arrival']) < 100)
                        $p['arrival'] = date("d/m/Y", strtotime($p['program_start'] . " -1 day "));
                    else
                        $p['arrival'] = ddmmyyyy($p['arrival']);


                    if (intval($p['departure']) < 100)
                        $p['departure'] = date("d/m/Y", strtotime($p['program_end'] . " +1 day "));
                    else
                        $p['departure'] = ddmmyyyy($p['departure']);



                    if (isset($_SESSION['publicuser'])) {
                        //$allowed = allowed();

                        #######    NAT INT ALLOWED

                        if ($_SESSION['natint'] == "national" && $p['nationals_allowed'] == "0") // not allowed
                        {
                            $allowed = "<center><br><br><h1 style='color:white; '> Sorry, Indian Nationals are not allowed for this program at the moment.</h1></center>";
                        } else if ($_SESSION['natint'] != "national" && $p['internationals_allowed'] == "0") // not allowed
                        {
                            $allowed = "<center><br><br><h1 style='color:white; '>  Sorry, Internationals are not allowed for this program at the moment.</h1></center>";
                        } else $allowed = "ok";
                    }


            ?>
                <div class="row brow btn-info">
                    <div class="col-xs-12   ">
                        <center>
                            <p style='font-size: 42px;'> <?= $p['program_name'] ?></p>
                            <h4>(Residential)</h4>
                            <h4>Campus : <?= $ashramname ?></h4>
                        </center>
                    </div>

                    <div class="col-xs-6 ">
                        <center>
                            <h3>Program Start Date <? echo ddmmyyyy($p['program_start']); ?></h3>
                        </center>
                    </div>
                    <div class="col-xs-6 ">
                        <center>
                            <h3>Program End&nbsp;Date <? echo ddmmyyyy($p['program_end']); ?></h3>
                        </center>
                    </div>

                    <br class='visible-xs' style="clear:both;" />

                    <div class="col-xs-6 ">
                        <center>
                            <h3>Arrival Date <?= $p['arrival'] ?></h3>
                        </center>
                    </div>
                    <div class="col-xs-6 ">
                        <center>
                            <h3>Departure Date <?= $p['departure'] ?></h3>
                        </center>
                    </div>

                    <br class='visible-xs' style="clear:both;" />


                    <? if (trim($p['teachers_names']) != "") {
                        echo '<div class="col-xs-12  col-sm-12 "><center>
<h2 > Teacher: ';
                        echo $p['teachers_names'];
                        echo '</h2></center>
</div>';
                    }
                    ?>
                    <div class="col-xs-12    ">
                        <center>

                            <?
                            if (trim(strip_tags($p['program_description']))  != "") {
                                echo "<h4><u>Description:</u>  ";
                                echo strip_tags($p['program_description']);
                                echo "</h4>";
                            }

                            $psa = $this->adb
                                ->select("requirements")
                                ->where("id", $p['program_subtype_id'])
                                ->get("program_subtype")->result_array();
                            if (!empty($psa)) {
                                $psr = array_pop($psa)['requirements'];
                                echo " <h4><u>Requirements:</u>" . strip_tags($psr) . "</h4>";

                                echo "<p style='color:yellow;'>By proceeding further, you agree that the<br> participant may be disqualified to attend/complete the program <br>if the program requirements are found to be not met. </p>";
                                if (isset($_SESSION['publicuser']) && $allowed == "ok") {
                                    echo "I agree : <input type='checkbox' style='height:70px;' class='form-control'  id='cb{$p['id']}' onclick='enabledisable({$p['id']})' > ";
                                }
                            }

                            ?>
                        </center>
                    </div>

                    <div class="col-xs-12  ">
                        <?
                        if (isset($_SESSION['publicuser'])) {

                            if ($allowed == "ok") {
                                //die("allowed");
                                $href = site_url("public/other/campus/?oid={$p['id']}&ashramid={$ashram_id}");

                                // site_url("public/campus/index/{$ashram_id}/{$p['id']}?oid={$p['id']}&ashram_id={$ashram_id}");

                        ?>
                                <center>
                                    <h5><a href="<?= site_url("public/campus/register/{$ashram_id}/{$p['id']}") ?>" class="btn btn-md btn-default mt pbtn<?= $p['id'] ?> pbtn" style='display:none;'>Register </a></h5>
                                    </h5>
                                </center>
                    <?
                                // &nbsp; <a class='btn btn-warning btn-md mt pbtn<?=$p['id']? > pbtn' style='display:none;'     href='<?=$href? >'>Register for Someone Else</a>
                            } else echo $allowed;
                        } else {
                            $href = site_url("public/welcome/login?next=" . urlencode(site_url("public/campus/index/{$ashram_id}")));
                            echo "<center><h5><a class='btn btn-default btn-lg' href='" . $href . "'>Login & Register</a></h5></center>";
                        }
                        echo "
</div>
</div>
";
                    } //if
                // <br style="clear:both;"><br>
                //<p><?=$description  ></p>
                    ?>
                    <br style="clear:both;"><br>
                    <div class="row" id='donate'>
                        <div class='col-xs-12'>
                            <center>
                                <h1>Donate to Campus at <br> <?= $ashramname ?> </h1>
                            </center>
                        </div>

                        <form method=post id='fid' action='<?= site_url("public/campus/donate") ?>'>
                            <div class='col-sm-2'>
                                &nbsp;
                            </div>
                            <div class='col-sm-4'>

                                <input type="number" placeholder="Amount " class='form-control' value="<? if (isset($_GET['amount'])) echo intval($_GET['amount']); ?>" name="amount">
                                <input type="hidden" value="<?= $ashram_id ?>" name="ashram_id">
                                <input type="hidden" value="<?= $this->uri->segment(3) ?>" name="ashram">
                            </div>
                            <div class='col-sm-4'>

                                <?
                                if (isset($_SESSION['publicuser'])) {
                                    echo "  <input class='form-control'  type=submit value='Donate Now' id='db'> ";
                                    if (isset($_GET['amount'])) { ?>
                                        <script type="text/javascript">
                                            $(function() {
                                                console.log("fid")
                                                $("#fid").submit();
                                            });
                                        </script>
                                <? }
                                } else
                                    echo "  <input class='form-control' type=submit id='db' value='Login & Donate '>  <hr><center>or</center> <br><a href='" . site_url("public/campus/donatenow/" . $ashram_id) . "' class='btn btn-lg btn-info' >Click here to Donate without login </a> ";
                                ?>
                            </div>
                            <div class='col-sm-2'>
                                &nbsp;
                            </div>
                        </form>

                        <div class='col-xs-12'>
                            &nbsp;


                        </div>
                    </div>


                    <?
                    if (trim($ash['bottom_photo']) != '') { ?>
                        <div class="row" id='donate' style='background-color: lightgrey;'>
                            <div class='col-xs-12'>
                                <br>
                                <center>
                                    <img class='tn' src="<?= base_url() . "ekamuploadedreferencefiles/" . $ash['bottom_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus Bottom Banner">
                                </center>
                                <br>
                            </div>
                        </div>
                    <? } ?>



                    </div><!-- /.container -->
                </div>

                <!-- Footer -->
                <footer>
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <ul class="list-inline">
                                    <li> <a href="<? echo site_url("public/welcome/"); ?>">Home</a> </li>
                                    <li class="footer-menu-divider">&sdot;</li>
                                    <li> <a href="<? echo site_url("public/welcome/faqs#{$ashram_name}"); ?>">FAQs</a> </li>
                                </ul>
                                <p class="copyright text-muted small">Copyright &copy; Art of Living
                                    <?= date('Y') ?>
                                    . All Rights Reserved</p>
                            </div>
                        </div>
                    </div>
                </footer>

                <script type="text/javascript">
                    function enabledisable(programid) {

                        console.log("enabledisable ", programid, $("#cb" + programid).is(':checked'), $(".pbtn" + programid));


                        if ($("#cb" + programid).is(':checked'))
                        //if (checkBox.checked == true)
                        {
                            $(".pbtn" + programid).show();
                        } else {
                            $(".pbtn" + programid).hide();
                        }


                    }
                </script>

                <style type="text/css">
                    .mt {
                        /* margin-top: 10px; */
                    }

                    #donate {

                        border: 15px double white;
                        border-radius: 20px;
                    }

                    #donate,
                    #donatemenu {
                        background-color: orange;
                        color: white;
                    }

                    #contact {

                        border: 15px double white;
                        border-radius: 20px;
                    }

                    #contact,
                    #contactmenu {
                        background-color: #d3d3d366;
                        color: black;
                    }

                    #db {
                        color: black;
                    }

                    .tn {
                        width: 50%;
                    }
                </style>


</body>

</html>
<?
function ddmmyyyy($dt)
{
    if (intval($dt) < 20000101)
        return $dt;
    //$retrieved = '20121226';
    $date = DateTime::createFromFormat('Ymd', $dt);
    return $date->format('d/m/Y');
}

?>
