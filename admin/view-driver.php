<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page

$active_tab = 0;
$id = 0;
$driver_page_items = [];
$total_amount_earned_driver = 0;
$number_of_franchise_drivers = 0;
$number_of_completed_trips = 0;
$number_of_cancelled_trips = 0;
$number_of_rejected_trips = 0;
$number_of_referrals = 0;

$banks_details = getdefaultbanks();

/* $banks_details = array(
    "044"=>"Access Bank",
    "023"=>"Citibank",
    "063"=>"Diamond Bank",
    "050"=>"Ecobank",
    "040"=>"Equitorial Trust Bank",
    "011"=>"First Bank",
    "214"=>"FCMB",
    "070"=>"Fidelity Bank",
    "085"=>"Finbank",
    "058"=>"Guaranty Trust Bank",
    "030"=>"Heritage Bank",
    "082"=>"Keystone Bank",
    "014"=>"Mainstreet Bank",
    "076"=>"Skye Bank",
    "221"=>"Stanbic IBTC Bank",
    "032"=>"Union Bank of Nigeria",
    "033"=>"United Bank of Africa (UBA)",
    "215"=>"Unity Bank",
    "035"=>"Wema Bank",
    "057"=>"Zenith Bank",
    "xxx"=> "Other..."
); */

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 3 && $_SESSION['account_type'] != 2){ ////if user is not an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}



$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-drivers-license'></i> View Driver"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "drivers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "driver-all"; //Set the appropriate menu item active


$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);


//get driver data

$query = sprintf('SELECT *,%1$stbl_drivers.bank_name AS d_bank_name,%1$stbl_drivers.bank_acc_holder_name AS d_bank_acc_holder_name,%1$stbl_drivers.bank_acc_num AS d_bank_acc_num,%1$stbl_drivers.bank_code AS d_bank_code, %1$stbl_drivers.bank_swift_code AS d_bank_swift_code FROM %1$stbl_drivers
LEFT JOIN %1$stbl_franchise ON %1$stbl_franchise.id = %1$stbl_drivers.franchise_id
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_drivers.ride_id
LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_drivers.driver_id AND %1$stbl_account_codes.user_type = 1 AND %1$stbl_account_codes.context = 0
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_drivers.route_id
WHERE %1$stbl_drivers.driver_id = "%2$d"', DB_TBL_PREFIX, $id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $driver_page_items = mysqli_fetch_assoc($result);
        $number_of_cancelled_trips = !empty($driver_page_items['cancelled_rides']) ? $driver_page_items['cancelled_rides'] : 0;
        $number_of_completed_trips = !empty($driver_page_items['completed_rides']) ? $driver_page_items['completed_rides'] : 0 ;
        $number_of_rejected_trips = !empty($driver_page_items['rejected_rides']) ? $driver_page_items['rejected_rides'] : 0 ;

        $query = sprintf('SELECT COUNT(*) FROM %stbl_drivers WHERE reg_with_referal_code = "%s"', DB_TBL_PREFIX,$driver_page_items['referal_code']);
        if($result = mysqli_query($GLOBALS['DB'], $query)){
            if(mysqli_num_rows($result)){
                $row = mysqli_fetch_assoc($result);
                $number_of_referrals = $row['COUNT(*)'];
            }
        }

                
    }else{
        $_SESSION['action_error'][]    = "Invalid driver record.";
        header('location: all-drivers.php');
        exit;
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
    header('location: all-drivers.php');
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

    if($_GET['tab'] == 'dtransactions'){
        $active_tab = 0;
    }elseif($_GET['tab'] == 'dbookings'){
        $active_tab = 1;
    }elseif($_GET['tab'] == 'dwithdraw'){
        $active_tab = 2;
    }elseif($_GET['tab'] == 'dreviews'){
        $active_tab = 3;
    }elseif($_GET['tab'] == 'ddocuments'){
        $active_tab = 4;
    }

}



    
ob_start();
include('../../drop-files/templates/admin/viewdrivertpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;







?>