<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-info-circle'></i> About "; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "about-cab"; //Set the appropriate menu item active
    
ob_start(); ?>

            
            <div class="row"> 
        
                <div class="col-sm-12">   
                    <div class="box box-primary">
                    
                    <div class="box-body">
                    
                    <div style="text-align:center;"><img src="../img/droptaxi-bg.png" /></div>

                    <hr />

                    <h3 style="text-align:center;">Droptaxi Web Administration Control Panel</h3>
                    <p style="text-align:center;">Design and Development: Onugha Michael Chike - Lead software developer</p>
                    <p style="text-align:center;">ProjectGICS</p>

                    <br />
                    <br />
                    



                    


                </div><!-- /.box-body -->
                </div>

            </div><!--/col-sm-12-->


    </div> <!--/row-->
            


            
        <?php
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";