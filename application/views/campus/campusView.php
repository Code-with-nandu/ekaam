<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8"> <!-- The head section typically contains meta-information about the document. -->
    <title>The Art of Living</title>



    <!--META TAG-->
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

    <!--EXTERNAL CSS-->
    <link rel="stylesheet" href="<?php echo base_url('/assets/css/style.css') ?>">
    <!--GOOGLE FONTS-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400&display=swap" rel="stylesheet">

    <!--FONT AWESOME-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
</head>

<body>
    <!--HEADER-->
    <header>
        <div class="banner">
            <span class="section-left">
                <a href="emailto:">info@vvmvp.org</a>
                <a href="tel:">+(91)-80 67262626</a>
            </span>
            <span class="section-right">
                <a href="#" title="Facebook"><i class="fa fa-facebook"></i></a>
                <a href="#" title="Instagram"><i class="fa fa-instagram"></i></a>
                <a href="#" title="Twitter"><i class="fa fa-twitter"></i></a>
            </span>
        </div>

        <div class="logo parallelogram">
            <span class="skew-fix"><img src='<?php echo base_url('assets/image/logo.png') ?>' alt='Art of Living Logo' style='width:120px; height:60px;' /></span>
        </div>


        <div class="topnav" id="myTopnav">
            <a href="#" class="active"></a>
            <a href="#gallery"></a>
            <a href="#blog"></a>
            <div class="dropdown">
                <button class="dropbtn">Bangalore Ashram
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-content animate">
                    <a href="https://online.vvmvp.org/">Online Programs</a>
                    <a href="https://online.vvmvp.org/home/donate">Donate</a>

                </div>
            </div>
            <a href="#contact"></a>
            <a href="#about"></a>

            <a href="javascript:void(0);" style="font-size:15px;" class="icon" onclick="myFunction()">&#9776;</a>
        </div>
    </header>

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
    <!-- main part -->
    <h1>Thanks Gurudev , </h1>
    <footer>
        <div class="footer">
            <p class="copyright text-muted small">Copyright &copy; 2016 -
                <?= date('Y') ?>
                & Onwords Central ICT , Art of Living International Center, Bangalore. All rights reserved. Version 3.2.0</p>
        </div>
    </footer>

    <!-- partial -->
    <script>
        function myFunction() {
            var x = document.getElementById("myTopnav");
            if (x.className === "topnav") {
                x.className += " responsive";
            } else {
                x.className = "topnav";
            }
        }
    </script>

    <script type="text/javascript">
        $(function() {
            $(".thumbnail-button").click(function() {
                console.log("button clicked with loc: ", $(this).data("loc"));
                var locx = $(this).data("loc");
                if (locx !== '') {
                    var win = window.open('https://register.vvmvp.org/ekam/index.php/public/campus/index/' + locx, '_blank');
                    if (win) {
                        // Browser has allowed it to be opened
                        win.focus();
                    } else {
                        // Browser has blocked it
                        // alert('Please allow popups for this website');
                        window.location = 'https://register.vvmvp.org/ekam/index.php/public/campus/index//' + locx;
                    }
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            const body = document.body;
            // const content = document.querySelector(".content");
            const footer = document.querySelector(".footer");

            const adjustFooter = () => {
                const contentHeight = content.offsetHeight;
                const windowHeight = window.innerHeight;

                if (contentHeight < windowHeight) {
                    const footerHeight = footer.offsetHeight;
                    content.style.minHeight = `calc(100vh - ${footerHeight}px)`;
                }
            };

            // Call adjustFooter initially and on window resize
            adjustFooter();
            window.addEventListener("resize", adjustFooter);
        });
    </script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.4/lodash.min.js'></script>
    <script type="text/javascript">
        window.NREUM || (NREUM = {});
        NREUM.info = {
            "beacon": "bam.nr-data.net",
            "licenseKey": "NRJS-f2a6b0b9791906eca60",
            "applicationID": "1097981547",
            "transactionName": "NF1QZxBWXxdRW0AMCQ0Xc1AWXl4KH3tVCBYWSx1aDFNUHA==",
            "queueTime": 0,
            "applicationTime": 4,
            "atts": "GBpTEVhMTBk=",
            "errorBeacon": "bam.nr-data.net",
            "agent": ""
        }
    </script>
</body>

</html>

</body>

</html>