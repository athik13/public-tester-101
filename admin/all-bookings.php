<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
define('ITEMS_PER_PAGE', 50); //define constant for number of items to display per page

$number_of_bookings = 0;
$bookings_page_items = array();
$drivers_data = [];
$franchise_data = [];
$search_result_price_sum_summary = '';

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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-bookmark'></i> All Instant Bookings"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "bookings"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "booking-all"; //Set the appropriate menu item active


$query_modifier = "> 0 AND ". DB_TBL_PREFIX . "tbl_bookings.scheduled = 0";

if(!empty($_GET['search-booking'])){

    if(isset($_GET['booking-type']) && $_GET['booking-type'] != NULL){
        if($_GET['booking-type'] == "0"){
            $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_routes.r_scope = 0";
            if(isset($_GET['booking-type-city']) && $_GET['booking-type-city'] != NULL){
                $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_routes.id = " . (int) $_GET['booking-type-city'];
            }
        }elseif($_GET['booking-type'] == "1"){
            $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_routes.r_scope = 1";
            if(isset($_GET['booking-type-state']) && $_GET['booking-type-state'] != NULL){
                $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_routes.id = " . (int) $_GET['booking-type-state'];
            }
        }
        
    }


    if(isset($_GET['bookingid']) && $_GET['bookingid'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.id = " . (int) $_GET['bookingid'];
    }

    if(isset($_GET['custphone']) && $_GET['custphone'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.user_phone = " .  mysqli_real_escape_string($GLOBALS['DB'], $_GET['custphone']);
    }


    if(isset($_GET['custname']) && $_GET['custname'] != NULL){
        $query_modifier .= " AND ( " . DB_TBL_PREFIX . "tbl_bookings.user_firstname LIKE '%" .  mysqli_real_escape_string($GLOBALS['DB'], $_GET['custname']) . "%' OR " . DB_TBL_PREFIX . "tbl_bookings.user_lastname LIKE '%" .  mysqli_real_escape_string($GLOBALS['DB'], $_GET['custname']) . "%' ) ";
    }


    if(!empty($_GET['booking-driverid'])){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.driver_id = " . (int) $_GET['booking-driverid'];
    }


    if(isset($_GET['bookstatus']) && $_GET['bookstatus'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.status = " . (int) $_GET['bookstatus'];
    }

    if(isset($_GET['bookspmethod']) && $_GET['bookspmethod'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.payment_type = " . (int) $_GET['bookspmethod'];
    }

    if(isset($_GET['franchise']) && $_GET['franchise'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.franchise_name = '" . urldecode($_GET['franchise']) . "'";
    }


    if(isset($_GET['bookingdate']) && $_GET['bookingdate'] != NULL){
        $end_date = date('Y-m-d', strtotime($_GET['bookingdate'] . " +1day"));
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_bookings.date_created BETWEEN '" . $_GET['bookingdate'] . "' AND '" . $end_date ."'" ;
    }





    /* $query = sprintf('SELECT SUM(%1$stbl_bookings.estimated_cost) AS price, %1$stbl_bookings.estimated_cost FROM %1$stbl_bookings 
    LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
    LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
    LEFT JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
    WHERE %1$stbl_bookings.id %2$s', DB_TBL_PREFIX,$query_modifier);
    
    
    if($result = mysqli_query($GLOBALS['DB'], $query)){

        if(mysqli_num_rows($result)){
            $row = mysqli_fetch_assoc($result);
            if(!empty($row['price'])){
                $search_result_price_sum_summary = "Sum of prices of search result amounts to ₦" . $row['price'];        
            }
            mysqli_free_result($result);
        }
    }    */
    


}




//get all drivers

/* $query = sprintf('SELECT * FROM %1$stbl_drivers ORDER BY firstname ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
} */


//get all franchises

$query = sprintf('SELECT * FROM %1$stbl_franchise ORDER BY franchise_name ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $franchise_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}  


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


//get all inter-state routes
$inter_state_routes = [];

$query = sprintf('SELECT * FROM %1$stbl_routes WHERE r_scope = 1 ORDER BY r_title ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $inter_state_routes[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 


//Get number of bookings in datatbase
$query = sprintf('SELECT COUNT(*) FROM %1$stbl_bookings
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id 
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
WHERE %1$stbl_bookings.id %2$s', DB_TBL_PREFIX,$query_modifier);

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_bookings = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   




if(isset($_GET['page'])){
  $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
  }
  
  $pages = ceil($number_of_bookings / ITEMS_PER_PAGE) ;
  if($page_number > $pages)$page_number = 1; 
  if($page_number < 0)$page_number = 1; 
  $offset = ($page_number - 1) * ITEMS_PER_PAGE;



$query = sprintf('SELECT *,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.route_id AS booking_route_id, %1$stbl_bookings.ride_id AS booking_ride,%1$stbl_drivers.firstname AS drvr_firstname, %1$stbl_drivers.lastname AS drvr_lastname  FROM %1$stbl_bookings 
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_bookings.driver_id
INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
WHERE %1$stbl_bookings.id %4$s ORDER BY %1$stbl_bookings.date_created DESC LIMIT %2$d,%3$d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE,$query_modifier);




if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $bookings_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   

/* var_dump($dispatch_page_items);
exit; */

ob_start();
include "../../drop-files/templates/admin/allbookingstpl.php";

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