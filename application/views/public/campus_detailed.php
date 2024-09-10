<?php
// Load header and navigation views
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
            // If test parameter is set, print server request URI and exit
            if (isset($_GET['test'])) {
                echo "<pre>";
                print_r($_SERVER['REQUEST_URI']);
                die();
            }

            // Check if the current URI is not related to Jaipur campus
            if (strpos($_SERVER['REQUEST_URI'], 'public/campus/index/0111') === false) {
                if (strtolower($_SESSION['publicuser']['country']) != "india" || strtolower($_SESSION['publicuser']['country_last_year']) != "india") {
                    return "<center><br><br><h4 style='color:lightyellow;background-color:darkgrey;'>Currently, only Indian nationals are allowed to register online for other campus.</h4></center>";
                }
            } else {
                echo "<h1> JAIPUR INTERNATIONAL TESTING RULE EXCEPTION IS: ON!</h1>";
            }

            return "ok";
        }

        // Load the database
        $this->db = $this->load->database("default", true);

        // Fetch ashram data based on organization ID and campus ID
        $asha = $this->db
            ->where("organisation_id", $orgid)
            ->where("id", $id)
            ->get("m_ashram")
            ->result_array();

        // If no ashram data is found, exit
        if (empty($asha)) {
            die("Campus Data Missing");
        }

        // Get the last element of the ashram data
        $ash = array_pop($asha);

        // Extract ashram details
        $ashram_name = $ash['name'];
        $ashramname = $ash['displayname'];
        $description = $ash['description'] . "  " . $ash['moreinfo'] . "  ";
        // echo "<pre>"; print_r( $ashram_name);  echo "<pre>"; die();
        ?>
        <!-- Ribbon Pert created -->
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

            /* Responsive adjustments for mobile */
            @media (max-width: 768px) {
                .github-ribbon {
                    top: 7.5em;
                    left: -2.5em;
                    padding: .4em 2.5em;
                    font-size: 0.7em;
                }

                .posts img {
                    max-width: 100%;
                    height: auto;
                }

                h1, h3 {
                    font-size: 1.2em;
                }
            }
        </style>

        <a class="github-ribbon" href="<?= site_url("public/campus/index/" . $ashram_id) ?>" title="<?= $ashramname ?>"><?= $ashramname ?></a>


        <div class='col-xs-12'>
            <center>
                <br>
                <h1>Campus at <?= $ash['displayname'] ?></h1>
                <br>
                <img class='tn' src="<?= base_url() . "assets/image/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus">
                <h3><?= $ash['address'] ?></h3>
                <hr>
                <h3><?= $ash['email'] ?></h3>
            </center>
        </div>
        <!-- responsive design mobile -->
        <div class='col-xs-12'>
            &nbsp;
        </div>

    </div>
</div>



<?php
// Load footer view
$this->load->view('template/campusFooter.php');
?>