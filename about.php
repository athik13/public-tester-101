<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";

$GLOBALS['admin_template']['active_menu'] = "";
$GLOBALS['template']['page-heading'] = "Terms and Conditions";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","About" => "about.php");


include "../drop-files/templates/headertpl.php";
//include "../drop-files/templates/pageheadingtpl.php";

?>
<div class="container-fluid" style="height:600px;background-image:url(img/about.jpg);background-size:cover;background-attachment:fixed;background-repeat:no-repeat;">
    
</div>
<div class="container">
    <div class="row">
        <br >
            
        <div class="col-sm-10">
            
        </div>



    </div>

</div>


<?php


include "../drop-files/templates/footertpl.php";

?>