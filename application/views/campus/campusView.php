<?php $this->load->view('template/campusHeader.php') ;?>

    <!-- main Part -->
    <div class="container">
        <div class="posts">
            <?php foreach ($asha as $key => $ash) : ?>

                <div class="post">
                    <div class="thumbnil" loc='<?= $ash['organisation_id'] . "" . $ash['id'] ?> '><img class="tn" src="<?= base_url() . "assets/image/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> campus" style="Width:400px ; height:400px;">
                    </div>
                    <div class="post__content"> <!-- Content section of the post -->

                        <div class="post__inside"> <!-- Inner content of the post -->

                            <h5 class=""><?= $ash['displayname'] ?> Campus</h5> <!-- Heading with the display name of the ashram followed by "Campus" -->
                            <br><br>
                            <a href="<?= base_url() . "index.php/public/campus/index/01" .  $ash['id'] ?> ">
                                <button>Open Campus</button> </a> <!-- Link to open the campus with a button -->
                        </div>
                    </div>

                </div>


            <?php endforeach; ?>

        </div>
    </div>
    <?php $this->load->view('template/campusFooter.php') ;?>

   