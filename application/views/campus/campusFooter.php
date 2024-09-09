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