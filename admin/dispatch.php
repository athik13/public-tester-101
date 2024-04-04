<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page

$number_of_pending_bookings = 0;
$dispatch_page_items = array();

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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-bookmark'></i> Dispatch Bookings"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "bookings"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "booking-dispatch"; //Set the appropriate menu item active




//Get number of rides in datatbase
$query = sprintf('SELECT COUNT(*) FROM %1$stbl_bookings 
INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
WHERE %1$stbl_bookings.status = 0 AND %1$stbl_routes.r_scope = 1', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_pending_bookings = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   




if(isset($_GET['page'])){
  $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
  }
  
  $pages = ceil($number_of_pending_bookings / ITEMS_PER_PAGE) ;
  if($page_number > $pages)$page_number = 1; 
  if($page_number < 0)$page_number = 1; 
  $offset = ($page_number - 1) * ITEMS_PER_PAGE;



$query = sprintf('SELECT *, %1$stbl_bookings.status AS booking_status,%1$stbl_users.country_dial_code AS user_country_dial_code,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.driver_id AS booking_driver_id, %1$stbl_driver_allocate.driver_id AS booking_driver_alloc,%1$stbl_driver_allocate.status AS booking_driver_alloc_status, %1$stbl_bookings.ride_id AS booking_ride,%1$stbl_drivers.firstname AS drvr_firstname, %1$stbl_drivers.lastname AS drvr_lastname  FROM %1$stbl_bookings 
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
LEFT JOIN %1$stbl_driver_allocate ON %1$stbl_driver_allocate.booking_id = %1$stbl_bookings.id AND (%1$stbl_driver_allocate.status = 0 OR %1$stbl_driver_allocate.status = 1)
INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
WHERE %1$stbl_routes.r_scope = 1 GROUP BY %1$stbl_bookings.id ORDER BY %1$stbl_bookings.date_created DESC LIMIT %2$d,%3$d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $dispatch_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   

/* echo mysqli_error($GLOBALS['DB']);

var_dump($dispatch_page_items);
exit; */

ob_start();
include "../../drop-files/templates/admin/dispatchtpl.php";

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