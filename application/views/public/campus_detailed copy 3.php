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
    // Load the 'ashrams' database
    // $this->adb = $this->load->database("ashrams", true);
    $this->adb = $this->load->database('ashrams', TRUE);
        

    // Check if the public user session is set
    if (isset($_SESSION['publicuser'])) {

        // Query to fetch participant data related to the user's session ID
        $vpa = $this->adb
            ->where("ashram_id", $ashram_id)
            ->like("participantreportview_uids_csv", "UID" . $_SESSION['publicuser']['id'] . "UID", "both")
            ->order_by("program_start", "desc")
            ->get("programs")
            ->result_array();
            echo "<pre>"; print_r($vpa);  echo "<pre>"; die();
    }
?>


    </div>
</div>



<?php
// Load footer view
$this->load->view('template/campusFooter.php');
?>