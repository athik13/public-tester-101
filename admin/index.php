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


if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3 && $_SESSION['account_type'] != 4 && $_SESSION['account_type'] != 5){ ////if user is an admin or dispatcher
    
  header("location: ".SITE_URL."access-denied.php"); //Yes? then warn the user for trying to access an unauthorized page
  exit;

}


if($_SESSION['account_type'] == 4){
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/view-franchise.php?id={$_SESSION['uid']}"); //redirect franshise page
    exit;
}



$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-dashboard'></i> Dashboard"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "dashb"; //Set the appropriate menu item active

//get all intra-city routes
$inter_city_routes = [];

$query = sprintf('SELECT * FROM %1$stbl_routes WHERE r_scope = 0 ORDER BY r_title ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $inter_city_routes[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 




if(empty($_POST)){ //let's render the add-new-franchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/adminhometpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}



?>