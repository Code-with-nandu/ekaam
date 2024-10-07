<?php
// Load necessary views for the header and navigation
$this->load->view('template/campusHeader.php');
$this->load->view('template/nav_page2.php');
?>

<br><br>

<!-- Main Part -->
<div class="container">
    <div class="posts">

        <?php
        // Extract organization ID and campus ID from ashram_id
        $orgid = substr($ashram_id, 0, 2);
        $id = substr($ashram_id, 2, 2);

        // Function to check if the request is allowed
        function allowed($value = '')
        {
            if (isset($_GET['test'])) {
                echo "<pre>";
                print_r($_SERVER['REQUEST_URI']);
                die();
            }

            if (strpos($_SERVER['REQUEST_URI'], 'public/campus/index/0111') === false) {
                if (strtolower($_SESSION['publicuser']['country']) != "india" || strtolower($_SESSION['publicuser']['country_last_year']) != "india") {
                    return "<center><br><br><h4 style='color:lightyellow;background-color:darkgrey;'>Currently, only Indian nationals are allowed to register online for other campus.</h4></center>";
                }
            } else {
                echo "<h1> JAIPUR INTERNATIONAL TESTING RULE EXCEPTION IS: ON!</h1>";
            }

            return "ok";
        }

        // Load the default database
        $this->db = $this->load->database("default", true);

        // Fetch ashram data based on organization ID and campus ID
        $asha = $this->db
            ->where("organisation_id", $orgid)
            ->where("id", $id)
            ->get("m_ashram")
            ->result_array();

        if (empty($asha)) {
            die("Campus Data Missing");
        }

        $ash = array_pop($asha);
        $ashram_name = $ash['name'];
        $ashramname = $ash['displayname'];
        $description = $ash['description'] . "  " . $ash['moreinfo'] . "  ";
        ?>

        <!-- Ribbon Part -->
        <style type="text/css">
            .github-ribbon {
                background-color: #8c8888;
                top: 8.5em;
                left: -3.7em;
                transform: rotate(-45deg);
                box-shadow: 0 0 0 1px #1d212e inset, 0 0 2px 1px #fff inset, 0 0 1em #888;
                color: rgba(255, 255, 255, 0.9);
                display: block;
                padding: .6em 3.5em;
                position: fixed;
                font: bold .82em sans-serif;
                text-align: center;
                text-decoration: none;
                z-index: 111;
            }

            @media (max-width: 768px) {
                .github-ribbon {
                    top: 7.5em;
                    left: -2.5em;
                    padding: .4em 2.5em;
                    font-size: 0.7em;
                }
            }
        </style>

        <a class="github-ribbon" href="<?= site_url("public/campus/index/" . $ashram_id) ?>" title="<?= $ashramname ?>"><?= $ashramname ?></a>

        <div class='col-xs-12'>
            <center>
                <h1>Campus at <?= $ash['displayname'] ?></h1>
                <img class='tn' src="<?= base_url() . "assets/image/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus">
                <h3><?= $ash['address'] ?></h3>
                <hr>
                <h3><?= $ash['email'] ?></h3>
            </center>
        </div>

        <!-- Display the top photo if available -->
        <?php if (trim($ash['top_photo']) != ''): ?>
            <center>
                <img class='tn' src="<?= base_url() . "assets/image/" . $ash['top_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus Top Banner">
            </center>
        <?php endif; ?>

        <!-- Querying Programs for Public User -->
        <?php
        // Load the 'ashrams' database
        $this->adb = $this->load->database("ashrams", TRUE);

        if (isset($_SESSION['publicuser'])) {
            // Query to fetch pending registrations
            $ra = $this->adb
                ->where("ashram_id", $ashram_id)
                ->where("visitor_id", $_SESSION['publicuser']["id"])
                ->where("`arrival` >= '" . date("Ymd") . "'")
                ->where("status", "donationpending")
                ->order_by("arrival", "asc")
                ->get("registrations")
                ->result_array();

            // Display pending donations if available
            if (!empty($ra)) {

                foreach ($ra as $k => $r) {
        ?>
                    <div class="row crow btn-warning">
                        <div class="col-xs-12">
                            <center>
                                <?php
                                $pa = $this->adb
                                    ->where("ashram_id", $ashram_id)
                                    ->where("id", $r['program_id'])
                                    ->order_by("program_start", "asc")
                                    ->get("programs")
                                    ->result_array();
                                if (!empty($pa)) {
                                    $p = array_pop($pa);
                                    echo "<h4>Donation Pending: {$p['program_name']}</h4>";

                                    // Handling arrival and departure dates
                                    if (intval($p['arrival']) < 100) {
                                        $p['arrival'] = date("d/m/Y", strtotime($p['program_start'] . " -1 day "));
                                    } else {
                                        $p['arrival'] = ddmmyyyy($p['arrival']);
                                    }

                                    if (intval($p['departure']) < 100) {
                                        $p['departure'] = date("d/m/Y", strtotime($p['program_end'] . " +1 day "));
                                    } else {
                                        $p['departure'] = ddmmyyyy($p['departure']);
                                    }

                                    echo "<p>{$p['arrival']} - {$p['departure']}</p>";

                                    // Check if the program is allowed for the user
                                    if ($_SESSION['natint'] == "national" && $p['nationals_allowed'] == "0") {
                                        $allowed = "<center><h1 style='color:orange;'> Sorry, Indian Nationals are not allowed for this program at the moment.</h1></center>";
                                    } else if ($_SESSION['natint'] != "national" && $p['internationals_allowed'] == "0") {
                                        $allowed = "<center><h1 style='color:orange;'> Sorry, Internationals are not allowed for this program at the moment.</h1></center>";
                                    } else {
                                        $allowed = "ok";
                                    }

                                    if ($allowed == "ok") {
                                        $pay = "campus/payrazor";
                                        if ($r['currency'] == "USD") {
                                            $pay = "payu/payusd";
                                        }
                                ?>
                                        <center>
                                            <h5><a href="<?= site_url("public/{$pay}/{$r['id']}/") ?>" class="btn btn-md btn-default mt">Go to Donation Page <?= $r['amount'] . " " . $r['currency'] ?>/-</a></h5>
                                        </center>
                                <?php
                                    } else {
                                        echo $allowed;
                                    }
                                }
                                ?>
                            </center>
                        </div>
                    </div>
        <?php
                }
            }
        }
        ?>

    </div>
</div>

<?php
// Load footer view
$this->load->view('template/campusFooter.php');
?>
