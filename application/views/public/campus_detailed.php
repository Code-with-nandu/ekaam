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
            echo "<pre>"; print_r( $ashram_name);  echo "<pre>"; die();
        ?>
        

    </div>
</div>



<?php 
    // Load footer view
    $this->load->view('template/campusFooter.php'); 
?>
