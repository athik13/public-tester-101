<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";

$GLOBALS['admin_template']['active_menu'] = "";
$GLOBALS['template']['page-heading'] = "Privacy Policy";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","privacy-policy" => "privacy.php");


include "../drop-files/templates/headertpl.php";
//include "../drop-files/templates/pageheadingtpl.php";

?>

<div class="container" style="margin-top:120px;">
    <div class="row">
        
            
        <div class="col-sm-8 ml-auto mr-auto">
            
                
        </div>




    </div>

</div>


<?php


include "../drop-files/templates/footertpl.php";

?>