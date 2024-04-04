<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
$route_data = [];
$booking_data = [];
$id = 0;

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-bookmark'></i> View Booking"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "bookings"; //Set the appropriate menu item active



if(!empty($_GET['bkid'])) {
    $id = (int) $_GET['bkid'] ;
}else{
    $id = 0;    
}




/* //Get all route tariff
$query = sprintf('SELECT * FROM %1$stbl_routes', DB_TBL_PREFIX); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $route_data[] = $row;
        }
                         
    }    
} */


//Get all route tariff
$query = sprintf('SELECT *,%1$stbl_drivers.country_dial_code AS driver_country_dial_code,%1$stbl_users.country_dial_code AS user_country_dial_code,%1$stbl_drivers.driver_id AS driver_ids, %1$stbl_users.user_id AS user_ids,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.route_id AS b_route_id,%1$stbl_users.firstname AS user_firstname, %1$stbl_users.lastname AS user_lastname, %1$stbl_users.phone AS user_phone, %1$stbl_bookings.ride_id AS bookride_id, %1$stbl_drivers.photo_file AS driver_photo, %1$stbl_users.photo_file AS user_photo, %1$stbl_bookings.driver_commision AS drv_commision, %1$stbl_bookings.franchise_commision AS franch_commision FROM %1$stbl_bookings
INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_ratings_users ON %1$stbl_ratings_users.booking_id = %1$stbl_bookings.id
LEFT JOIN %1$stbl_ratings_drivers ON %1$stbl_ratings_drivers.booking_id = %1$stbl_bookings.id
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
WHERE %1$stbl_bookings.id = "%2$d"  ', DB_TBL_PREFIX,$id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        
            $booking_data = mysqli_fetch_assoc($result);
        
                         
    }    
}


/* var_dump($booking_data);
exit;
 */

/* //get all drivers

$query = sprintf('SELECT * FROM %1$stbl_drivers ORDER BY firstname ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   */ 






    
    ob_start();
    include('../../drop-files/templates/admin/viewbookingtpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;











?>