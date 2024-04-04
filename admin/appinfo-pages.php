<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");


$appinfo_page_items = array();

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 3){ ////if user is not an admin
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-clone'></i> Info Pages"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "app-info"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "app-pages"; //Set the appropriate menu item active





if(isset($_POST['updateinfo'])){

    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/appinfo-pages.php"); //Yes? then redirect 
        exit;
    }

    $id = (int) $_POST['appinfo-id'];

    $query = sprintf('UPDATE %stbl_appinfo_pages SET content = "%s", date_modified = "%s" WHERE id = %d',DB_TBL_PREFIX,mysqli_real_escape_string($GLOBALS['DB'], $_POST['appinfo-content']),gmdate('Y-m-d H:i:s', time()),$id);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        $_SESSION['action_success'][] = "App info page updated successfully.";
    }else{
        $_SESSION['action_error'][]    = "Error updating App info page";
    }  

    
    
}



$query = sprintf('SELECT * FROM %stbl_appinfo_pages WHERE `type` = 0 ORDER BY date_created DESC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $appinfo_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   

/* var_dump($franchise_page_items);
exit; */

ob_start();
include "../../drop-files/templates/admin/appinfopagestpl.php";

if(!empty($_SESSION['action_success'])){
    $msgs = '';
    foreach($_SESSION['action_success'] as $action_success){
        $msgs .= "<p style='text-align:left;'><i style='color:green;' class='fa fa-circle-o'></i> ".$action_success . "</p>";
    }

    $cache_prevent = RAND();
    echo"<script>
    setTimeout(function(){ 
            jQuery( function(){
            swal({
                title: '<h1>Success</h1>'".',
    text:"'.$msgs .'",'.
    "imageUrl: '../img/success_.gif?a=" . $cache_prevent . "',
    html:true,
            });
            });
            },500); 
            
            </script>";

        unset($_SESSION['action_success']);

}elseif(!empty($_SESSION['action_error'])){
        $msgs = '';
        foreach($_SESSION['action_error'] as $action_error){
            $msgs .= "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> ".$action_error . "</p>";
        }

        $cache_prevent = RAND();
        echo"<script>
    setTimeout(function(){ 
            jQuery( function(){
            swal({
                title: '<h1>Error</h1>'".',
    text:"'.$msgs .'",'.
    "imageUrl: '../img/info_.gif?a=" . $cache_prevent . "',
    html:true,
            });
            });
            },500); 
            
            </script>";
    
            unset($_SESSION['action_error']);
    
}


$GLOBALS['admin_template']['page_content'] = ob_get_clean();
include "../../drop-files/templates/admin/admin-interface.php";
exit;


























?>