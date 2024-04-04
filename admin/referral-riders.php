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

if($_SESSION['account_type'] /* != 2 && $_SESSION['account_type'] */ != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-recycle'></i> Manage Riders Referral"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "referrals"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "referral-riders"; //Set the appropriate menu item active



//get referral data items from DB
$referral_data = [];

$query = sprintf('SELECT * FROM %1$stbl_referral WHERE id=1', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        $referral_data = mysqli_fetch_assoc($result);              
     }
    mysqli_free_result($result);
}



if(empty($_POST)){ //let's render the add-new-franchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/referralriderstpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    
    ob_start();
    include('../../drop-files/templates/admin/referralriderstpl.php'); 
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
    



    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;    
    
    
    
    
}


/* var_dump($_POST);
exit; */

$beneficiary = !empty($_POST['ref-benef']) ? (int) $_POST['ref-benef'] : 0;
$discount = !empty($_POST['ref-discount']) ? (float) $_POST['ref-discount'] : 10;
$status = isset($_POST['ref-status']) && $_POST['ref-status'] == '1'? 1 : 0;
$desc =  !empty($_POST['ref-desc']) ? htmlspecialchars(mysqli_real_escape_string($GLOBALS['DB'], $_POST['ref-desc'])) :"Earn {$discount}% discount on your next ride when you invite a friend to register on our service using your referral code!" ;





if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/referralriderstpl.php'); 
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
    



    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;



}

//update referral record on database
$query = sprintf('UPDATE %stbl_referral SET beneficiary=%d,discount_value="%s",`status`=%d WHERE id = 1', 
    DB_TBL_PREFIX, 
    $beneficiary,
    $discount,
    $status
);



if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    //echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not update refferal info on database. Ensure database connection is working";

    ob_start();
    include('../../drop-files/templates/admin/referralriderstpl.php'); 
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
    



    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}

    //refresh referral data
    $referral_data = [];

    $query = sprintf('SELECT * FROM %1$stbl_referral WHERE id=1', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            $referral_data = mysqli_fetch_assoc($result);              
        }
        mysqli_free_result($result);
    }





    $_SESSION['action_success'][] = "Referral info was updated successfully.";
    ob_start();
    include('../../drop-files/templates/admin/referralriderstpl.php'); 
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
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;



?>