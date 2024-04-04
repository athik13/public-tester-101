<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page

$active_tab = 0;
$id = 0;
$user_page_items = [];



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



$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-users'></i> View Customer"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "customers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "customer-all"; //Set the appropriate menu item active


$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);


//get customer data

$query = sprintf('SELECT *,%1$stbl_users.user_id AS user_ids FROM %1$stbl_users
LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_users.user_id AND %1$stbl_account_codes.user_type = 0 AND %1$stbl_account_codes.context = 0
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_users.route_id
WHERE %1$stbl_users.user_id = "%2$d" AND %1$stbl_users.account_type = %3$d', DB_TBL_PREFIX, $id, 1);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $user_page_items = mysqli_fetch_assoc($result);
                
    }else{
        $_SESSION['action_error'][]    = "Invalid customer record.";
        header('location: all-customers.php');
        exit;
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
    header('location: all-customers.php');
    exit;
}






//get total amount earned this driver
/* $query = sprintf('SELECT %1$stbl_bookings.driver_id, SUM(%1$stbl_bookings.driver_commision / 100 * (%1$stbl_bookings.paid_amount - (%1$stbl_bookings.paid_amount * %1$stbl_bookings.cur_exchng_rate * %1$stbl_bookings.driver_commision / 100))) AS amount_currency FROM %1$stbl_bookings WHERE %1$stbl_bookings.franchise_id = %2$d AND %1$stbl_bookings.status = 3 GROUP BY %1$stbl_bookings.franchise_id',DB_TBL_PREFIX, $id);
if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);   
        $total_amount_earned_franchise = ($row['amount_currency'] * 100) / 100;    

         
     }
    mysqli_free_result($result);
} */


//echo mysqli_error($GLOBALS['DB']);



if(isset($_GET['tab'])){

    if($_GET['tab'] == 'ctransactions'){
        $active_tab = 0;
    }elseif($_GET['tab'] == 'cbookings'){
        $active_tab = 1;
    }elseif($_GET['tab'] == 'creviews'){
        $active_tab = 2;
    }

}



    
ob_start();
include('../../drop-files/templates/admin/viewcustomertpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;







?>