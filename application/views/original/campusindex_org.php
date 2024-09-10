<?


$this->load->view('public/header');
?>

<!-- Bootstrap core CSS -->
<link href="<? echo base_url(); ?>css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap theme -->
<link href="<? echo base_url(); ?>css/bootstrap-theme.min.css" rel="stylesheet">
<script src="<? echo base_url(); ?>js/jquery.1.11.0.min.js"></script>
<script src="<? echo base_url(); ?>js/bootstrap.min.js"></script>


<meta name="description" content="Campus Selector Page">
<title>Select Campus</title>
<style type="text/css">
    .btn-lg {
        margin-top: 10px;
    }

    .odd {
        background-color: #7A5A4D;
        color: white;
    }

    .login {
        background-color: #E1F5FB;
        color: grey;
        text-shadow: none;
        padding: 20px 20px 0px 20px;
    }

    .tra {
        color: rgba(0, 0, 0, 1);
        font-size: 35px;
        margin-top: 10px;
    }

    .trab {
        background-color: rgba(254, 254, 254, 0.70);
        padding: 10px;
    }

    .error {
        color: red;
    }

    .success {
        color: green;
    }

    .contactus {
        color: lightgrey;
    }

    .home {
        float: right;
        vertical-align: top;
    }


    .btn-lg,
    .btn-sm {
        margin-top: 10px;
    }

    .h1 {
        font-size: 30px;
    }

    .odd {
        background-color: lightgrey;
    }

    .home {
        float: right;
        vertical-align: top;
    }

    .f,
    .m {
        text-align: center;
    }

    .seat {
        margin-top: 5px;
    }

    .noseats,
    {
    text-align: center;
    }

    .iodd {
        background-color: #f2fcff;
        padding: 10px;
    }

    .ieven {
        background-color: #fffce9;
        padding: 10px;
    }

    .cent td {
        padding: 10px;
    }

    .bxh {
        visibility: hidden;
    }

    * {
        font-family: Arial, sans;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
    }

    h1,
    h2 {
        margin: 1em 0 0 0;
        text-align: center;
    }

    h2 {
        margin: 0 0 1em 0;
    }

    #container {
        margin: 0 auto;
        width: 50%;
    }

    #accordion {
        margin: 15px 0 10px;
    }

    #accordion input {
        display: none;
    }

    #accordion label {
        background: #eee;
        border-radius: .25em;
        cursor: pointer;
        display: block;
        margin-bottom: .125em;
        padding: .25em 1em;
        z-index: 20;
    }

    #accordion label:hover {
        background: #ccc;
    }

    #accordion input:checked+label {
        background: #ccc;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
        color: white;
        margin-bottom: 0;
    }

    #accordion article {
        background: #f7f7f7;
        height: 0px;
        overflow: hidden;
        z-index: 10;
    }

    #accordion article p {
        padding: 1em;
    }

    #accordion input:checked article {}

    #accordion input:checked~article {
        border-bottom-left-radius: .25em;
        border-bottom-right-radius: .25em;
        height: auto;
        margin-bottom: .125em;
    }

    .ao {
        text-align: center;
        background-color: gold;
    }

    .donation {
        padding-bottom: 20px;
    }

    h1 {
        text-transform: capitalize;
        background-color: orange;
        padding: 15px;
        color: white;
    }

    #net,
    #hdfc,
    .adiv {
        display: none;
    }

    .thumbnail {
        min-height: 310px;
        /* 310 */
        text-align: center;
        background-color: lightgrey;
    }

    .thumbnail:hover {
        border: 1px solid darkgrey;
        background-color: white;
        color: white;
    }

    .thumbnail_new {
        text-align: center;
        background-color: lightgrey;
    }

    .thumbnail_new:hover {
        border: 1px solid darkgrey;
        background-color: white;
        color: white;
    }

    .tn {
        visibility: none;
    }
</style>


<script type="text/javascript">
    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
</script>





</head>

<body>


    <style>
        @media only screen and (max-width: 767px) {
            #menu1_id {
                display: none;
            }

            #menu2_id {
                display: block;
            }
        }

        @media only screen and (min-width: 767px) {
            #menu1_id {
                display: block;
            }

            #menu2_id {
                display: none;
            }

            #image_div_id {
                height: 320px;
            }
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <nav class="navbar navbar-default navbar-fixed-top topnav" role="navigation">

        <div class="container topnav" id="menu1_id">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                <a class="navbar-brand topnav" href="#"><img src='<? echo  base_url(); ?>logo.png' alt='Art of Living Logo' style='height:30px;' /></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">

                    <li>
                        <a class="test" href="#">Bangalore Ashram <span class="caret"></span></a>
                        <ul class="dropdown-menu" class="show_menu">
                            <li><a href="https://online.vvmvp.org/">Online Programs</a></li>
                            <li><a href="https://online.vvmvp.org/home/donate">Donate</a></li>
                        </ul>
                    </li>



                    <!--          <li> <a href="https://online.vvmvp.org/" >Online Programs</a> </li>-->
                    <?
                    /*
foreach ($buttons as $key => $bt)
{
$b = $bt['name'];
echo
"<li>
<a href='#{$b}' >{$b}</a>
</li> ";
}


if(isset($_SESSION['natint']))
{
if($_SESSION['natint']=="international")
echo "
<li>
<a href='".site_url("public/welcome/?natint=national")."' >Go to National</a>
</li> ";
else
echo "
<li>
<a href='".site_url("public/welcome/?natint=international")."' >Go to International</a>
</li>
";
}

<li> <a href="<? echo site_url("public/welcome/") ; ?>" >Home</a> </li>
<li> <a href="<? echo site_url("public/welcome/faqs") ; ?>" >FAQs</a> </li>
*/
                    ?>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>

        <div class="container topnav" id="menu2_id">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2"> Bangalore Ashram </button>
                <a class="navbar-brand topnav" href="#"><img src='<? echo  base_url(); ?>logo.png' alt='Art of Living Logo' style='height:30px;' /></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-2">
                <ul class="nav navbar-nav navbar-right">

                    <li><a href="https://online.vvmvp.org/">Online Programs</a></li>
                    <li><a href="https://online.vvmvp.org/home/donate">Donate</a></li>


                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>

        <!-- /.container -->
    </nav>
    <br style="clear:both;" />
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

    <div class="login">
        <div class="container">
            <!--        <div class="row">-->
            <!--            <div class="col-xs-12">-->
            <!--                <center><h2 style="color: maroon;">Choose A Campus</h2></center>-->
            <!--                 <div class="thumbnail" loc='../../'>-->
            <!--                  <br>-->
            <!--                    <img src="--><? //=base_url()
                                                    ?><!--public/images/2.jpg" alt="Bangalore, Karnataka, India">-->
            <!--                    <div class="caption">-->
            <!--                        <h3>The Art of Living International Center<br> Bangalore, Karnataka, India</h3>-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--            </div>-->
            <!--          </div>-->
            <!---->
            <!--        <div class="row">-->
            <!--            <div class="col-xs-12">-->
            <!--                <center>-->
            <!---->
            <!--                    <div class="thumbnail_new" >-->
            <!--                  <a href="https://online.vvmvp.org/home/donate">-->
            <!--                      <div class="caption"> <br/>-->
            <!--                        <h3>Bangalore Ashram Donations</h3>-->
            <!--                    </div>-->
            <!--                  </a>-->
            <!--                    </div>-->
            <!---->
            <!--            </div>-->
            <!--          </div>-->

            <div class="row">
                <h1>VVMVP Campuses</h1> <br><br>

                <?
                $this->db = $this->load->database("default", true);

                $asha = $this->db
                    ->where("active", "1")
                    ->get("m_ashram")
                    ->result_array();

                if (empty($asha)) die("<h1>Campus Data Missing</h1>");

                //echo "<pre>"; print_r($asha); echo "</pre>";
                foreach ($asha as $key => $ash) {
                    if ($ash['name'] != "Bangalore_Karnataka_India") {
                ?>
                        <div class="col-xs-12 col-sm-4 col-lg-3" id="image_div_id">
                            <div class="thumbnail" loc='<?= $ash['organisation_id'] . "" . $ash['id'] ?>'>
                                <img class='tn' src="<?= base_url() . "/ekamuploadedreferencefiles/" . $ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> Campus"> <? /*
<p><?=$ash['description']?></p> */ ?>
                                <div class="caption">
                                    <h4><?= $ash['displayname'] ?> Campus</h4>
                                </div>
                            </div>
                        </div>
                <?
                    }
                }

                ?>

            </div>




        </div>
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
                        <li> <a href="<? echo site_url("public/welcome/faqs"); ?>">FAQs</a> </li>
                    </ul>
                    <p class="copyright text-muted small">Copyright &copy; Art of Living
                        <?= date('Y') ?>
                        . All Rights Reserved</p>
                </div>
            </div>
        </div>
    </footer>

    <script type="text/javascript">
        $(function() {
            $(".thumbnail").click(function() {
                console.log("clicked on ... ", $(this).attr("loc"));
                var locx = $(this).attr("loc");
                if (locx != '../../') {
                    var win = window.open('<?= site_url("public/campus/index/") ?>' + $(this).attr("loc"), '_blank');
                    if (win) {
                        //Browser has allowed it to be opened
                        win.focus();
                    } else {
                        //Browser has blocked it
                        //alert('Please allow popups for this website');
                        window.location = '<?= site_url("public/campus/index/") ?>/' + $(this).attr("loc");
                    }
                }

            });
        });
    </script>



</body>

</html>