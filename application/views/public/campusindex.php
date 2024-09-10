<?php $this->load->view('template/campusHeader.php'); ?>
<?php $this->load->view('template/nav_page1.php'); ?>
<br><br>
<script>
    $(document).ready(function() {
        $('a.test').on("click", function(e) {
            $(this).next('ul').toggle();
            e.stopPropagation();
            e.preventDefault();
        });
    });
</script>

<!-- main Part -->
<div class="container">
    <div class="posts">

        <?php
        // Load the default database connection
        $this->db = $this->load->database("default", true);

        // Fetch active ashrams from the database
        $asha = $this->db
            ->where("active", "1")
            ->get("m_ashram")
            ->result_array();

        // Check if data exists
        if (empty($asha)) {
            die("<h1>Campus Data Missing</h1>");
        }

        // Loop through each ashram and display its information
        foreach ($asha as $ash) :
            if ($ash['name'] != "Bangalore_Karnataka_India") :
        ?>

                <div class="post">
                    <div class="thumbnail" loc='<?= $ash['organisation_id'] . "" . $ash['id'] ?>'>
                        <img class="tn" src="<?= base_url() . "assets/image/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus" style="width:400px; height:400px;">
                    </div>
                    <div class="post__content"> <!-- Content section of the post -->

                        <div class="post__inside"> <!-- Inner content of the post -->
                            <h5 class=""><?= $ash['displayname'] ?> Campus</h5> <!-- Heading with the display name of the ashram followed by "Campus" -->
                            <br><br>
                            <a href="<?= base_url() . "camp/org/01" . $ash['id'] ?> ">
                                <button>Open Campus</button>
                            </a> <!-- Link to open the campus with a button -->
                        </div>
                    </div>

                </div>

        <?php
            endif;
        endforeach;
        ?>

    </div>
</div>

<?php $this->load->view('template/campusFooter.php'); ?>
