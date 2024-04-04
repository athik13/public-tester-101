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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-diamond'></i> Reward Points"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "rewardpoints"; //Set the appropriate menu item active



//get reward points data items from DB
$reward_points_data = [];

$query = sprintf('SELECT * FROM %1$stbl_reward_points WHERE id=1', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        $reward_points_data = mysqli_fetch_assoc($result);              
     }
    mysqli_free_result($result);
}



if(empty($_POST)){ //let's render the add-new-franchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/rewardpointstpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    
    ob_start();
    include('../../drop-files/templates/admin/rewardpointstpl.php'); 
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

if(empty($_POST['curtopoint'])){

    $_SESSION['action_error'][]    = "Please enter the amount of money customers must spend to earn 1 reward point";
}


if(empty($_POST['pointtocur'])){

    $_SESSION['action_error'][]    = "Please enter the amount of money customers will earn in redeeming 1 reward point";
}

if(empty($_POST['minredeempoint'])){

    $_SESSION['action_error'][]    = "Please enter the minimum redeemable number of points a rider must have";
}





$curtopoint = (float) $_POST['curtopoint'];

$pointtocur = (float) $_POST['pointtocur'];

$minredeempoint = (int) $_POST['minredeempoint'];


$status = (int) $_POST['reward-status'];



if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/rewardpointstpl.php'); 
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
$query = sprintf('UPDATE %stbl_reward_points SET cur_to_points_conv="%s",points_to_cur_conv="%s",`status`="%d",`min_points_redeemable`="%d" WHERE id=1', 
    DB_TBL_PREFIX, 
    $curtopoint,
    $pointtocur,
    $status,
    $minredeempoint
);



if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    //echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not update reward points info on database. Ensure database connection is working";

    ob_start();
    include('../../drop-files/templates/admin/rewardpointstpl.php'); 
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

    //reward point data
    $reward_points_data = [];

    $query = sprintf('SELECT * FROM %1$stbl_reward_points WHERE id=1', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            $reward_points_data = mysqli_fetch_assoc($result);              
        }
        mysqli_free_result($result);
    }





    $_SESSION['action_success'][] = "Reward points info was updated successfully.";
    ob_start();
    include('../../drop-files/templates/admin/rewardpointstpl.php'); 
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